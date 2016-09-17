var QuestionModel = Backbone.Model.extend({
    initialize: function() {
        this.on('error', this.onError, this);
    },
    onError: function(model, response) {
        MessageDisplay.displayIfError(response.responseJSON);
    }
});

var QuestionCollection = Backbone.Collection.extend({
    model: QuestionModel,
    url: '/api/v1/questions',
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

var QuestionListItemView = Backbone.View.extend({
    tagName: 'tr',
    className: 'question',
    events: {
        'click': 'onClick'
    },
    template: _.template($('[data-template=question]').html()),
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
        var json = this.model.toJSON();
        // Get answers for this question.
        json.answers = answerList.where({question_id: this.model.get('id')});
        var html = this.template(json);
        this.$el.html(html).addClass('question');
        return this;
    },
    onClick: function(e) {
        //setSelectedAnswer(this.model);
    }
});


var QuestionListView = Backbone.View.extend({
    el: '#question-list',
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
        var item = new QuestionListItemView({model: model});
        item.render();
        this.$el.append(item.$el);
        var that = this;
        model.on('destroy', function() {
            item.remove();
        });
    }
});