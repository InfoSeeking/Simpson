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
        var openQuestions = _.filter(questionList.toJSON(), function(q) {
            var pendingAnswers = answerList.where({question_id: q['id'], answered: 0});
            return pendingAnswers.length > 0;
        });
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
        var questionId = this.$el.find('option:selected').attr('value');

        var data = {
            project_id: parseInt(Config.get('projectId')),
            ask_all: this.askAll,
            type: 'answer',
            question_id: questionId,
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
                        MessageDisplay.display(['None of your connections had an answer'], 'danger');  
                    } else {
                        MessageDisplay.display([that.model.get('name') + ' did not have an answer'], 'danger');
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