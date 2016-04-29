var UserModel = Backbone.Model.extend({
	initialize: function() {
		this.on('error', this.onError, this);
		this.on('change', this.castNums, this);
		this.castNums();
	},
	castNums: function() {
		this.set('id', parseInt(this.get('id')));
	},
	onError: function(model, response) {
		MessageDisplay.displayIfError(response.responseJSON);
	}
});

var UserCollection = Backbone.Collection.extend({
	model: UserModel,
	url: '/api/v1/users',
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