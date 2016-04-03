var ConnectionModel = Backbone.Model.extend({
	initialize: function() {
		this.on('error', this.onError, this);
		// Attempt to set user name if available.
		var initiator = userList.get(this.get('initiator_id'));
		this.set('initiator_name', initiator.get('name'));

		var recipient = userList.get(this.get('recipient_id'));
		this.set('recipient_name', recipient.get('name'));
	},
	onError: function(model, response) {
		MessageDisplay.displayIfError(response.responseJSON);
	}
});

var ConnectionCollection = Backbone.Collection.extend({
	model: RequestModel,
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

var ConnectionItemView = Backbone.View.extend({
	tagName: 'div',
	className: 'request',
	events: {
		'click .delete': 'onDelete',
	},
	template: _.template($('[data-template=request]').html()),
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
		var modalEl = $('#delete-request-modal');
		modalEl.find('[name=incoming_request_id]').val(this.model.get('id'));
		modalEl.modal('show');
	},
	render: function() {
		var html = this.template(this.model.toJSON());
		this.$el.html(html).addClass(this.model.get('direction') + '-request');
		return this;
	},
});

// Only shows connections with current user.
var ConnectionListView = Backbone.View.extend({
	el: '#incoming-request-list',
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
		// Filter only user connections.
		if (model.get('initiator_id') != Config.get('userId') &&
			model.get('recipient_id') != Config.get('userId')) {
			return;
		}
		var item = new RequestListItemView({model: model});
		item.render();
		this.$el.append(item.$el);
		var that = this;
		model.on('destroy', function() {
			item.remove();
		});
	}
});

var ConnectionGraphView = Backbone.View.extend({
	el: '#incoming-request-list',
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
		var item = new RequestListItemView({model: model});
		item.render();
		this.$el.append(item.$el);
		var that = this;
		model.on('destroy', function() {
			item.remove();
		});
	}
});

// This function will initialize event handlers for the request forms.
// The collection is updated when a request is created/updated/deleted.
// This way, the collection could be a RequestCollection or any other 
// collection. (e.g. a feed list containing multiple types of objects).
//
function initializeRequestFormEventHandlers(collection){
	if (!collection) throw 'Collection not passed';
	$('#create-request').on('submit', onCreateSubmit);
	$('#delete-request').on('submit', onDeleteSubmit);

	function onDeleteSubmit(e) {
		e.preventDefault();
		$('#delete-request-modal').modal('hide');
		var incoming_request_id = $(this).find('[name=incoming_request_id]').val();
		var incoming_request = collection.get(incoming_request_id);
		if (!incoming_request) {
			MessageDisplay.display(['Could not delete incoming request'], 'danger');
			return;
		}
		incoming_request.destroy();
	}
}