var RequestModel = Backbone.Model.extend({
	initialize: function() {
		this.on('error', this.onError, this);

		if (this.get('type') == 'connection') {
			// Attempt to set user name if available.
			var initiator = userList.get(this.get('initiator_id'));
			this.set('initiator_name', initiator.get('name'));

			var recipient = userList.get(this.get('recipient_id'));
			this.set('recipient_name', recipient.get('name'));

			if (this.get('initiator_id') == Config.get('userId')) {
				this.set('direction', 'outgoing');
				this.set('timeLeft', 30);
			} else if (this.get('recipient_id') == Config.get('userId')) {
				this.set('direction', 'incoming');
				this.set('timeLeft', 30);
			} else if (this.get('intermediary_id') == Config.get('userId')) {
				this.set('direction', 'intermediary');
				this.set('timeLeft', 30);
			} else {
				this.set('direction', 'n/a');
			}

			this.on('change', this.castNums, this);
			this.castNums();
		}
	},
	castNums: function() {
		this.set('initiator_id', parseInt(this.get('initiator_id')));
		this.set('recipient_id', parseInt(this.get('recipient_id')));
	},
	onError: function(model, response) {
		MessageDisplay.displayIfError(response.responseJSON);
	}
});

var RequestCollection = Backbone.Collection.extend({
	model: RequestModel,
	url: '/api/v1/requests',
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

var RequestListItemView = Backbone.View.extend({
	tagName: 'tr',
	className: 'request',
	events: {
		'click .delete': 'onDelete',
		'click .connection-accept': 'onAccept',
		'click .connection-reject': 'onReject',
	},
	template: _.template($('[data-template=request]').html()),
	attributes: function() {
		return {
			'data-id': this.model.id
		}
	},
	initialize: function (options) {
		this.model.on('remove', this.remove, this);
		this.model.on('change', this.render, this);
		if (this.model.get('state') == 'open') {
			var that = this;
			window.setTimeout(function() {that.checkClock();}, 1000);
		}
	},
	checkClock: function() {
		var that = this;
		if (this.model.get('state') != 'open') return;
		var timeLeft = this.model.get('timeLeft');
		timeLeft--;
		timeLeft = Math.max(timeLeft, 0);
		if (timeLeft <= 0 && this.model.get('direction') == 'incoming') {
			// Auto reject on timeout for the recipient of the request.
			this.onReject();
		} else {
			window.setTimeout(function() {that.checkClock();}, 1000);
		}
		this.model.set('timeLeft', timeLeft);
		this.render();
	},
	onDelete: function(e) {
		console.log('delete');
		e.preventDefault();
		var modalEl = $('#delete-request-modal');
		modalEl.find('[name=incoming_request_id]').val(this.model.get('id'));
		modalEl.modal('show');
	},
	onAccept: function(e) {
		e.preventDefault();
		this.sendConnectionResponse('accepted');
	},
	onReject: function(e) {
		if (e) e.preventDefault();
		this.sendConnectionResponse('rejected');
	},
	sendConnectionResponse: function(state) {
		resetTickTimer();
		$.ajax({
			url: '/api/v1/connection',
			method: 'put',
			dataType: 'json',
			data: {
				request_id: this.model.get('id'),
				state: state
			},
			success: function(resp) {},
			error: function(xhr) {
				var json = JSON.parse(xhr.responseText);
				MessageDisplay.displayIfError(json);
			}
		});
	},
	render: function() {
		var data = {
			request: this.model.toJSON(),
			acceptCost: getCost('accept', this.model.get('id')),
			rejectCost: getCost('reject', this.model.get('id'))
		};
		var html = this.template(data);
		this.$el.html(html).addClass(this.model.get('direction') + '-request');
		return this;
	},
});


var IncomingRequestListView = Backbone.View.extend({
	el: '#incoming-request-list',
	initialize: function(options) {
		this.collection.on('add', this.add, this);
		this.collection.on('change', this.refreshAllUsers, this);
	},
	render: function() {
		this.$el.empty();
		var that = this;
		this.collection.forEach(function(model){
			that.add(model);
		});
	},
	add: function(model) {
		// Filter by only incoming requests.
		if (model.get('recipient_id') != Config.get('userId') 
			&& model.get('intermediary_id') != Config.get('userId')) return;
		if (model.get('type') != 'connection') return;
		var item = new RequestListItemView({model: model});
		item.render();
		this.$el.append(item.$el);
		var that = this;
		model.on('destroy', function() {
			item.remove();
		});
		this.$el.parent().scrollTop(this.$el.prop('scrollHeight'));
	},
});

var OutgoingRequestListView = IncomingRequestListView.extend({
	el: '#outgoing-request-list',
	add: function(model) {
		// Filter by outgoing requests.
		if (model.get('initiator_id') != Config.get('userId')) return;
		if (model.get('type') != 'connection') return;
		var item = new RequestListItemView({model: model});
		item.render();
		this.$el.append(item.$el);
		var that = this;
		model.on('destroy', function() {
			item.remove();
		});
		this.$el.parent().scrollTop(this.$el.prop('scrollHeight'));
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