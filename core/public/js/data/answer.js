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
		if (this.model.get('answered')) {
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