var AnswerModel = Backbone.Model.extend({
	initialize: function() {
		this.on('error', this.onError, this);
	},
	onError: function(model, response) {
		MessageDisplay.displayIfError(response.responseJSON);
	}
});

var AnswerCollection = Backbone.Collection.extend({
	model: AnswerModel,
	url: '/api/v1/answers',
	initialize: function() {
		this.on('error', this.onError, this);
		this.comparator = 'position';
	},
	parse: function(json){
		return json.result;
	},
	onError: function(collection, response) {
		MessageDisplay.displayIfError(response.responseJSON);
	}
});

var AnswerListItemView = Backbone.View.extend({
	tagName: 'div',
	className: 'answer',
	events: {
		'click': 'onClick'
	},
	template: _.template($('[data-template=answer]').html()),
	attributes: function() {
		return {
			'data-id': this.model.id
		}
	},
	initialize: function (options) {
		this.model.on('remove', this.remove, this);
		this.model.on('change', this.render, this);
	},
	render: function() {
		var html = this.template(this.model.toJSON());
		this.$el.html(html).addClass('answer');
		if (this.model.get('isAnswered')) {
			this.$el.addClass('answered');
		} else {
			this.$el.addClass('unanswered');
		}
		return this;
	},
	onClick: function(e) {
		setSelectedAnswer(this.model);
	}
});


var AnswerListView = Backbone.View.extend({
	el: '#answer-list',
	initialize: function(options) {
		this.collection.on('add', this.add, this);
	},
	render: function() {
		this.$el.empty();
		var that = this;
		this.collection.forEach(function(model){
			that.add(model);
		});
	},
	add: function(model) {
		var item = new AnswerListItemView({model: model});
		item.render();
		this.$el.append(item.$el);
		var that = this;
		model.on('destroy', function() {
			item.remove();
		});
	}
});

var AskView = Backbone.View.extend({
	el: '#modal-container',
	events: {
		'click .btn-ask': 'askQuestion'
	},
	template: _.template($('[data-template=select-question]').html()),
	initialize: function(options) {
		if (options.hasOwnProperty('askAll') && options.askAll) {
			this.askAll = true;
		}
	},
	render: function() {
		var openQuestions = _.filter(answerList.toJSON(), function(q) {return !q.isAnswered;});
		var askData = {
			questions: openQuestions
		};
		var that = this;
		this.$el.html(this.template(askData));
		this.$el.find('#select-question-modal').modal();
		this.$el.find('#select-question-modal').on('hidden.bs.modal', function() {
			that.remove();
		});
	},
	remove: function() {
		this.$el.empty();
		this.undelegateEvents();
	},
	askQuestion: function(e) {
		e.preventDefault();
		// Prevent multiple submissions during fade-out with one-time lock.
		if (this.locked) return;
		resetTickTimer();
		this.locked = true;
		this.$el.find('#select-question-modal').modal('hide');
		var answerName = this.$el.find('option:selected').html();

		var data = {
			project_id: parseInt(Config.get('projectId')),
			ask_all: this.askAll,
			type: 'answer',
			answer_name: answerName,
		};

		if (!this.askAll) {
			data.recipient_id = parseInt(this.model.get('id'));
		} else {
			data.type = 'answer_all';
		}

		var that = this;

		$.ajax({
			url: '/api/v1/requests',
			method: 'post',
			data: data,
			success: function(resp) {
				if (resp.result.state == "answered") {
					MessageDisplay.display(['Answer recieved!'], 'success');
				} else {
					if (that.askAll) {
						MessageDisplay.display(['None of your connections had the answer'], 'danger');	
					} else {
						MessageDisplay.display(['User did not have the answer'], 'danger');
					}
				}
			},
			error: function(xhr) {
				var json = JSON.parse(xhr.responseText);
				MessageDisplay.displayIfError(json);
			}
		});
	}
});

var AskAllButtonView = Backbone.View.extend({
	el: '#ask-all-container',
	template: _.template($('[data-template=ask-all]').html()),
	events: {
		'click a': 'onClick'
	},
	initialize: function() {
		connectionList.on('update', this.render, this);
	},
	render: function () {
		var data = {
			cost: getCost('ask-all'),
			count: connectionCount(Config.get('userId'))
		};
		this.$el.html(this.template(data));
	},
	onClick: function(e) {
		e.preventDefault();
		new AskView({
			askAll: true
		}).render();
	}
});