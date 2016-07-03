@extends('workspace.layouts.single-project')
@inject('memberService', 'App\Services\MembershipService')

@section('page')
page-view
@endsection

@section('context')
@if ($memberService->can($project->id, 'w', $user))
<div class='context'></div>
@endif
@endsection('context')

@section('navigation')
<a href='/workspace/instructions'>Instructions</a>
<a href='/workspace/projects/{{$project->id}}'>Study</a>
@endsection('navigation')

@section('main-content')

<div class='row'>
<div class='col-md-6'>
	@include('helpers.showAllMessages')
	<p>Select a user below to request connections and ask questions.</p>
	<div id='graph-display'></div>
	<div id='selected-user'></div>
	
	<h4 style='display: none'>All users</h4>
	<ul style='display: none' id='all-user-list'>
	@foreach ($sharedUsers as $sharedUser)
	<li data-id={{$sharedUser->user->id}}>
	{{$sharedUser->user->name}}
	<a href='#' class='request-connection' style='display:none' data-id='{{ $sharedUser->user->id }}'>Request connection</a></li>
	@endforeach
	</ul>

	<h4>Outgoing Connection Requests</h4>
	<div class='scrollpane'>
		<table class='table table-condensed table-hover' id='outgoing-request-list'></table>
	</div>
</div>
<div class='col-md-6'>
	<p>You have <b id='user-score'>{{ $userScore }}</b> Network Capital (NC) Points, <b id='answer-score'>0</b> Answers collected, and are linked to  <b id='link-score'>0</b> other people</p>
	<p>Your total score is <b id='total-score'>0</b>. Your rank is <b id='place'>{{ $place }}</b> of {{ count($sharedUsers) }}.</p>

	<p>Time left: <b id='time-left'>{{ floor($timeLeft / 60) }}:{{ $timeLeft % 60}}</b></p>

	<h4>Question List</h4>
	<div id='answer-list'></div>
	<p class='selected-answer'>Selecting question <span></span>. Select a user below to ask.</p>

	

	<h4>Users Connected to You</h4>
	<table class='table table-condensed table-hover' id='user-connection-list'></table>
	<div id='ask-all-container' style='text-align: center; padding-bottom: 20px;'></div>

	<h4>Incoming Connection Requests</h4>
	<div class='scrollpane'>
		<table class='table table-condensed table-hover' id='incoming-request-list'></table>
	</div>

	
</div>

<div id='select-intermediary-container'></div>
<div id='modal-container'></div>

<!-- Select intermediary view -->
<script type='text/template' data-template='select-intermediary'>
<div class='row modal fade' tabindex='-1' id='select-intermediary-modal'>
	<div class='modal-dialog'>
		<div class='modal-content'>
			<div class='modal-header'>Select Person to Request Connection</div>
			<div class='modal-body'>
				<div class='form-group'>
					<% if (friends.length == 0) { %>
					This user has no other available direct connections to request.
					<% } else { %>
					<select class='form-control' name='friends'>
						<% for (var i = 0; i < friends.length; i++) { %>
						<option value=<%= friends[i].get('id') %>><%= friends[i].get('name') %></option>
						<% } %>
					</select>
					<% } %>
					<p>The cost of this request is <span class='cost'>0</span></p>
				</div>
				<button class='cancel btn btn-danger' data-dismiss='modal'>Close</button>
				<div class='pull-right'>
					<% if (friends.length > 0) { %>
					<button type='submit' class='btn-request-connection btn btn-primary'>Request Connection</button>
					<% } %>
				</div>
			</div>
		</div>
	</div>
</div>
</script>

<!-- Select question view -->
<script type='text/template' data-template='select-question'>
<div class='row modal fade' tabindex='-1' id='select-question-modal'>
	<div class='modal-dialog'>
		<div class='modal-content'>
			<div class='modal-header'>Select Question to Ask</div>
			<div class='modal-body'>
				<div class='form-group'>
					<% if (questions.length == 0) { %>
					You have no questions left to answer.
					<% } else { %>
					<select class='form-control' name='questions'>
						<% for (var i = 0; i < questions.length; i++) { %>
						<option value=<%= questions[i].id %>><%= questions[i].name %></option>
						<% } %>
					</select>
					<% } %>
				</div>
				<button class='cancel btn btn-danger' data-dismiss='modal'>Close</button>
				<div class='pull-right'>
					<button type='submit' class='btn-ask btn btn-primary'>Ask</button>
				</div>
			</div>
		</div>
	</div>
</div>
</script>

<script type='text/template' data-template='selected-user'>
	<p>Selecting <span class='name'><%= user.name %></span>.
	<% if (canRequestConnection(user.id)) %>
	<a href='#' class='btn btn-default request-connection' data-id=''>Request connection <span class='cost cost-<%= cost.sign %>'>(<%= cost.cost %>)</span></a>
	<% else if (connectionExists(Config.get('userId'), user.id)) %>
	You are connected. <a href='#' class='btn btn-default request-intermediary-connection'>Request connection with a friend</a> <a href='#' class='btn btn-default ask-question'>Ask Question <span class='cost cost-<%= askcost.sign %>'>(<%= askcost.cost %>)</a>
</script>

<script type='text/template' data-template='request'>
<% if (request.type=='connection' && request.direction == 'incoming') { %>
<td>
Request to connect from <%= request.initiator_name %>.
</td>

<td>
<% if (request.state == 'accepted') %>
You accepted.
<% else if (request.state == 'rejected') %>
You rejected.
<% else if (request.state == 'open') %>
<a class='connection-accept btn btn-default'>Accept <span class='cost cost-<%= acceptCost.sign %>'>(<%= acceptCost.cost %>)</span></a>
&nbsp;
<a class='connection-reject btn btn-default'>Reject <span class='cost cost-<%= rejectCost.sign %>'>(<%= rejectCost.cost %>)</span></a>
</td>
<td>
Time left: <span class='time-left'><%= request.timeLeft %></span>
</td>

<% } else if (request.type=='connection' && request.direction == 'outgoing') { %>
<td>
Sent a <%= request.type %> request to <%= request.recipient_name %>
</td>

<td>
<% if (request.state == 'accepted') %>
They accepted.
<% else if (request.state == 'rejected') %>
They rejected.
<% if (request.state == 'open') %>
Time left for them: <span class='time-left'><%= request.timeLeft %></span>
<% } else if (request.type=='connection' && request.direction == 'intermediary') { %>
<td>
Request was made from <%= request.initiator_name %> to <%= request.recipient_name %> through you.
</td>

<td>
<% if (request.state == 'accepted') %>
They accepted.
<% else if (request.state == 'rejected') %>
They rejected.
<% if (request.state == 'open') %>
Time left for them: <span class='time-left'><%= request.timeLeft %></span>
<% } %>
</td>

</script>

<script type='text/template' data-template='user_connection'>
	<td>You are connected to <span class='connected-user'><%= connection.other_name %></span>.</td>
	<td><a class='btn-ask btn btn-default'>Ask Question <span class='cost cost-<%= cost.sign %>'>(<%= cost.cost %>)</span></a></td>
	<td><a class='btn-intermediary-connection btn btn-default'>Request Connection with Friend</a></td>
</script>

<script type='text/template' data-template='answer'>
<span data-id='<%= id %>' title="<% if (answered) { %> Answered <% } else { %> Unanswered <% } %>" >
<%= name %>
</a>
</script>

<script type='text/template' data-template='ask-all'>
<% if (count > 2) %>
<a href='#' class='ask-all-btn btn btn-default'>Ask all connected users a Question <span class='cost cost-<%= cost.sign %>'>(<%= cost.cost %>)</span></a>
</script>

<script src='/js/realtime.js'></script>
<script src='/js/data/layouts.js'></script>
<script src='/js/data/feed.js'></script>
<script src='/js/data/user.js'></script>
<script src='/js/data/request.js'></script>
<script src='/js/data/connection.js'></script>
<script src='/js/data/answer.js'></script>
<script src='/js/vendor/moment.js'></script>
<script>
Config.setAll({
	permission: '{{ $permission }}',
	projectId: {{ $project->id }},
	userId: {{ is_null($user) ? 'null' : $user->id }},
	realtimeEnabled: {{ env('REALTIME_SERVER') == null ? 'false' : 'true'}},
	realtimeServer: '{{ env('REALTIME_SERVER') }}'
});

var selectedAnswerId = null;
var userList = new UserCollection();
// Add all project users to user collection.
@foreach ($sharedUsers as $sharedUser)
userList.add(new UserModel(
	{!! $sharedUser->user->toJson() !!}
));
@endforeach


var requestList = new RequestCollection({!! $requests->toJSON() !!});
var incomingRequestListView = new IncomingRequestListView({collection: requestList});
var outgoingRequestListView = new OutgoingRequestListView({collection: requestList});

incomingRequestListView.render();
outgoingRequestListView.render();

var connectionList = new ConnectionCollection({!! $connections->toJSON() !!});
var connectionListView = new ConnectionListView({ collection: connectionList });
connectionListView.render();

var connectionGraphView = new ConnectionGraphView({ collection: connectionList });
connectionGraphView.render();

// These are only my own answers.
var answerList = new AnswerCollection({!! $answers->toJSON() !!});
var answerListView = new AnswerListView({ collection: answerList });
answerListView.render();

answerList.on('change', updateAnswerScore);
updateAnswerScore();

// connectionList change event doesn't seem to get triggered,
// so I put a manual call in the realtime handler.
connectionList.on('change', updateLinkScore);
updateLinkScore();

function updateAnswerScore() {
	var total = answerList.where({isAnswered: true}).length;
	$('#answer-score').html(total);
	updateTotalScore();
}

function updateLinkScore() {
	var total = connectionCount(Config.get('userId'));
	$('#link-score').html(total);
	updateTotalScore();
}

function updateTotalScore() {
	var total = parseInt($('#answer-score').html()) +
		parseInt($('#link-score').html()) +
		parseInt($('#user-score').html());
	$('#total-score').html(total);
}

function connectionCount(a) {
	var temp = connectionList.where({initiator_id: a}).length;
	return temp + connectionList.where({recipient_id: a}).length;
}

function getCost(type, recipient_id, intermediary_id) {
	var cost = 0;
	if (type == 'answer') {
		cost = -5;
	} else if (type == 'connection') {
		cost = -1 * connectionCount(recipient_id) - 2;
		if (intermediary_id) cost += 4;
	} else if (type == 'accept') {
		cost = 2;
		if (intermediary_id) cost = 5;
	} else if (type == 'reject') {
		cost = -1;
		if (intermediary_id) cost = -2;
	} else if (type == 'ask-all') {
		cost = -5 * connectionCount(Config.get('userId')) + 5;
	}
	return {
		cost: cost,
		sign: (cost > 0) ? 'p' : (cost < 0 ? 'n' : 'z')
	}
}

function connectionExists(a, b) {
	if(connectionList.where({initiator_id: a, recipient_id: b}).length > 0) {
		return true;
	}
	if(connectionList.where({initiator_id: b, recipient_id: a}).length > 0) {
		return true;
	}
	return false;
}

function canRequestConnection(to) {
	var canRequest = true;
	var userId = Config.get('userId');
	// Check if self.
	if (userId == to) return false;
	// Check if connection exists.
	if (connectionExists(userId, to)) return false;
	// Check if request already in progress from either side.
	if (requestList.where({initiator_id: userId, recipient_id: to, type: 'connection', state: 'open'}).length > 0) {
		return false;
	}
	if (requestList.where({initiator_id: userId, recipient_id: to, type: 'connection', state: 'open'}).length > 0) {
		return false;
	}
	return true;
}

function checkAllAnswered() {
	if (answerList.where({'isAnswered': false}).length == 0) {
		window.setTimeout(function() {
			window.location = '/workspace/end';
		}, 1000);
	}
}

function realtimeDataHandler(param) {
	if(param.dataType == 'requests') {
		_.each(param.data, function(request) {
			if (param.action == 'create') {
				requestList.add(request);
			} else if (param.action == 'delete') {
				requestList.remove(request);
			} else if (param.action == 'update') {
				requestList.get(request.id).set(request);
			}
		});
	} else if (param.dataType == 'connections') {
		_.each(param.data, function(connection) {
			if (param.action == 'create') {
				connectionList.add(connection);
				updateLinkScore();
			} else if (param.action == 'delete') {
				connectionList.remove(connection);
			}
		});
	} else if (param.dataType == 'answers') {
		_.each(param.data, function(answer) {
			if (answer.answered) {
				answerList.get(answer.id).set(answer);
				checkAllAnswered();
			}
		});
	} else if (param.dataType == 'scores') {
		_.each(param.data, function(score) {
			if (score.user_id == Config.get('userId')) {
				$('#user-score').html(score.score);
				updateTotalScore();
			}
		})
	}
}

Realtime.init(realtimeDataHandler);

var resetTickTimer = (function(){
	var tickTimer = window.setTimeout(tick, 20000);
	var admissable = true;
	function tick() {
		console.log('tick');
		if (admissable) {
			$.ajax({
				url: '/api/v1/tick',
				method: 'post',
				data: {
					project_id: Config.get('projectId')
				}
			});
		} else {
			admissable = true;
		}
		tickTimer = window.setTimeout(tick, 20000);
	}

	function resetTickTimer() {
		console.log('reset');
		admissable = false;
	}

	return resetTickTimer;
}());

(function() {
	new AskAllButtonView().render();
	var countdownEl = $("#time-left")
	var timeLeft = {{ $timeLeft }};
	function countdown() {
		timeLeft--;
		if (timeLeft < 0) timeLeft = 0;
		if (timeLeft == 0) window.location = "/workspace/end";
		countdownEl.html(Math.floor(timeLeft / 60) + ":" + (timeLeft % 60));
	}
	window.setInterval(countdown, 1000);

	function updatePlace() {
		$.ajax({
			'url': '/api/v1/scores/place/{{ $project->id }}',
			'success': function(res) {
				$("#place").html(res.result);
			}
		});
	}
	window.setInterval(updatePlace, 10000);
}());



</script>
@endsection('main-content')