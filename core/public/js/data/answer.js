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
		if (this.model.get('answered') === "1") {
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

AskView = Backbone.View.extend({
	el: '#modal-container',
	events: {
		'click .btn-ask': 'askQuestion'
	},
	template: _.template($('[data-template=select-question]').html()),
	initialize: function() {},
	render: function() {
		var openQuestions = _.filter(answerList.toJSON(), function(q) {return !q.answered;});
		var askData = {
			questions: openQuestions,
			targetUser: this.model.toJSON()
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
		$.ajax({
			url: '/api/v1/requests',
			method: 'post',
			data: {
				project_id: parseInt(Config.get('projectId')),
				recipient_id: parseInt(this.model.get('id')),
				type: 'answer',
				answer_name: answerName,
			},
			success: function(resp) {
				if (resp.result.state == "answered") {
					MessageDisplay.display(['Answer recieved!'], 'success');
				} else {
					MessageDisplay.display(['User did not have the answer'], 'danger');
				}
			},
			error: function(xhr) {
				var json = JSON.parse(xhr.responseText);
				MessageDisplay.displayIfError(json);
			}
		});
	}
})