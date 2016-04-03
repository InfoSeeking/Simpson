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
		this.collection.on('remove', this.remove, this);
	},
	render: function() {
		var that = this;
		this.$el.empty();
		// Show all request links, remove if they are already connected.
		$('#all-user-list .request-connection').show();
		$('#all-user-list li[data-id=' + Config.get('userId') + '] .request-connection').hide();
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

		var otherId = model.get('initiator_id');
		if (otherId == Config.get('userId')) otherId = model.get('recipient_id');
		$('#all-user-list li[data-id=' + otherId + '] .request-connection').hide();

		var item = new ConnectionListItemView({model: model});
		item.render();
		this.$el.append(item.$el);
		var that = this;
		model.on('destroy', function() {
			item.remove();
		});
	},
	remove: function(model) {
		var otherId = model.get('initiator_id');
		if (otherId == Config.get('userId')) otherId = model.get('recipient_id');
		$('#all-user-list li[data-id=' + otherId + '] .request-connection').show();		
	}
});

var ConnectionGraphView = Backbone.View.extend({
	el: '#connection-graph',
	initialize: function(options) {
		var that = this;
		this.collection.on('add', this.add, this);
		this.collection.on('remove', this.remove, this);
		this.nodes = new vis.DataSet();
		userList.each(function(user) {
			var userNode = {
				id: user.get('id'),
				label: user.get('name')
			};
			if (user.get('id') == Config.get('userId')) {
				userNode.color = {
					background: '#0E0',
					highlight: '#0F0',
				};
			}
			that.nodes.add(userNode);
		});

		// Create an array with edges
		this.edges = new vis.DataSet();

		// create a network
		var container = $('#graph-display')[0];

		// provide the data in the vis format
		var data = {
		    nodes: this.nodes,
		    edges: this.edges
		};

		var options = {
			height: '400px',
			nodes: {
				shape: 'dot',
				font: {
					size: 22
				}
			}
		};

		// initialize your network!
		var network = new vis.Network(container, data, options);
		network.on('selectNode', function(e) {
			var node = that.nodes.get(e.nodes[0]);
			var user = userList.get(node.id);
			$('#selected-user').find('.name').html(user.get('name'));
			$('#selected-user').find('.request-connection').attr('data-id', node.id);
			var hideRequest = false;
			// If we're selecting someone we're already connected to, 
			// or if we're selecting ourselves, do not show request connection button.
			if (Config.get('userId') == node.id) {
				hideRequest = true;
			}
			if(that.collection.where({initiator_id: Config.get('userId'), recipient_id: node.id}).length > 0) {
				hideRequest = true;
			}
			if(that.collection.where({initiator_id: node.id, recipient_id: Config.get('userId')}).length > 0) {
				hideRequest = true;
			}

			if (hideRequest) {
				$('#selected-user .request-connection').hide();
			} else {
				$('#selected-user .request-connection').show();
			}

			$('#selected-user').show();
		});
		network.on('deselectNode', function(e) {
			$('#selected-user').hide();
		})
	},
	render: function() {
		var that = this;
		this.collection.forEach(function(model){
			that.add(model);
		});
	},
	add: function(model) {
		// Add connection to graph.
		var connection = this.edges.add({from: model.get('initiator_id'), to: model.get('recipient_id')});

		var that = this;
		model.on('destroy', function() {
			that.edges.remove(connection[0]);
		});
	},
	remove: function(model) {

	}
});