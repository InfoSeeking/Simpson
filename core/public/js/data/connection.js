var ConnectionModel = Backbone.Model.extend({
	initialize: function() {
		this.on('error', this.onError, this);
		// Attempt to set user name if available.
		var initiator = userList.get(this.get('initiator_id'));
		this.set('initiator_name', initiator.get('name'));

		var recipient = userList.get(this.get('recipient_id'));
		this.set('recipient_name', recipient.get('name'));

		if (this.get('initiator_id') == Config.get('userId')) {
			this.set('other_name', recipient.get('name'));
		} else if (this.get('recipient_id') == Config.get('userId')) {
			this.set('other_name', initiator.get('name'));
		}
	},
	onError: function(model, response) {
		MessageDisplay.displayIfError(response.responseJSON);
	}
});

var ConnectionCollection = Backbone.Collection.extend({
	model: ConnectionModel,
	url: '/api/v1/connections',
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

var ConnectionListItemView = Backbone.View.extend({
	tagName: 'div',
	className: 'connection',
	events: {
		'click .delete': 'onDelete',
	},
	template: _.template($('[data-template=user_connection]').html()),
	attributes: function() {
		return {
			'data-id': this.model.id
		}
	},
	initialize: function (options) {
		this.layout = options.layout;
		this.model.on('remove', this.remove, this);
		this.model.on('change', this.render, this);
	},
	onDelete: function(e) {
		e.preventDefault();
		var modalEl = $('#delete-connection-modal');
		modalEl.find('[name=connection_id]').val(this.model.get('id'));
		modalEl.modal('show');
	},
	render: function() {
		var html = this.template(this.model.toJSON());
		this.$el.html(html).addClass('user-connection');
		return this;
	},
});

// Only shows connections with current user.
var ConnectionListView = Backbone.View.extend({
	el: '#user-connection-list',
	initialize: function(options) {
		this.collection.on('add', this.add, this);
	},
	render: function() {
		var that = this;
		this.$el.empty();
		this.collection.forEach(function(model){
			that.add(model);
		});
	},
	add: function(model) {
		console.log('adding', model);
		// Filter only user connections.
		if (model.get('initiator_id') != Config.get('userId') &&
			model.get('recipient_id') != Config.get('userId')) {
			console.log('ignoring');
			return;
		}
		var item = new ConnectionListItemView({model: model});
		item.render();
		this.$el.append(item.$el);
		var that = this;
		model.on('destroy', function() {
			item.remove();
		});
	}
});

var ConnectionGraphView = Backbone.View.extend({
	el: '#connection-graph',
	initialize: function(options) {
		this.collection.on('add', this.add, this);
	},
	render: function() {
		this.$el.empty();
		this.collection.forEach(function(model){
			that.add(model);
		});
	},
	add: function(model) {
		var item = new ConnectionListItemView({model: model});
		item.render();
		this.$el.append(item.$el);
		var that = this;
		model.on('destroy', function() {
			item.remove();
		});
	}
});