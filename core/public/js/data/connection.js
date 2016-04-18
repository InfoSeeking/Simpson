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
			this.set('other_id', recipient.get('id'));
		} else if (this.get('recipient_id') == Config.get('userId')) {
			this.set('other_name', initiator.get('name'));
			this.set('other_id', initiator.get('id'));
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
		'click .connected-user': 'onUserClick'
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
	onUserClick: function(){
		// Forward it to main script.
		handleUserClick(userList.get(this.model.get('other_id')));
	}
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
	},
});

var ConnectionGraphView = Backbone.View.extend({
	el: '#connection-graph',
	selectedUserView: null,
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
					size: 20
				}
			}
		};

		// initialize your network!
		var that = this;
		var network = new vis.Network(container, data, options);
		network.on('selectNode', function(e) {
			var node = that.nodes.get(e.nodes[0]);
			var user = userList.get(node.id);
			if (that.selectedUserView != null) {
				that.selectedUserView.remove();
			}
			that.selectedUserView = new CurrentSelectedView({model: user});
			that.selectedUserView.render();
		});
		network.on('deselectNode', function(e) {
			that.selectedUserView.remove();
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

var CurrentSelectedView = Backbone.View.extend({
	el: "#selected-user",
	template: _.template($('[data-template=selected-user]').html()),
	events: {
		'click .request-connection': 'requestConnection',
		'click .request-intermediary-connection': 'requestIntermediaryConnection'
	},
	initialize: function(args) {
		var that = this;
		connectionList.on('update', function() {
			that.render();
		});
		requestList.on('update', function() {
			that.render();
		});
	},
	render: function() {
		this.$el.empty().html(this.template(this.model.toJSON()));
	},
	remove: function() {
		this.$el.empty();
		this.undelegateEvents();
	},
	requestConnection: function(e) {
		e.preventDefault();
		var recipientId = this.model.get('id');
		$.ajax({
			url: '/api/v1/requests',
			method: 'post',
			dataType: 'json',
			data: {
				project_id: parseInt(Config.get('projectId')),
				recipient_id: parseInt(recipientId),
				type: 'connection'
			},
			success: function(resp) {},
			error: function(xhr) {
				var json = JSON.parse(xhr.responseText);
				MessageDisplay.displayIfError(json);
			}
		});
	},
	requestIntermediaryConnection: function(e) {
		e.preventDefault();
		// Show modal window with current connections.
		new IntermediarySelectView({
			model: this.model
		}).render();
	}
});

var IntermediarySelectView = Backbone.View.extend({
	el: "#select-intermediary-container",
	template : _.template(
		$('[data-template=select-intermediary]').html()),
	initialize: function(args) {},
	remove: function() {
		this.$el.empty();
		this.undelegateEvents();
	},
	render: function() {
		var loggedInUser = userList.get(Config.get('userId'));
		var that = this;
		// Get connections with this user.
		var friends = [];
		connectionList.each(function(cxn){
			var otherId = -1;
			if (cxn.get('recipient_id') == that.model.get('id')) {
				otherId = cxn.get('initiator_id'); 
			} else if (cxn.get('initiator_id') == that.model.get('id')) {
				otherId = cxn.get('recipient_id');
			}
			if (otherId == -1) return;
			if (otherId == loggedInUser.get('id')) return;
			friends.push(userList.get(otherId));
		})
		var templateData = {
			'user': this.model.toJSON,
			'friends': friends
		};
		this.$el.empty();
		this.$el.html(this.template(templateData));
		this.$el.find('#select-intermediary-modal').modal();
		this.$el.find('#select-intermediary-modal').on('hidden.bs.modal', function() {
			console.log('here');
			that.remove();
		});
	}
});