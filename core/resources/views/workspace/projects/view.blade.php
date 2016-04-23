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
<!-- <a href='/workspace/projects'><span class='fa fa-folder-open-o'></span> Projects</a>
<span class='fa fa-angle-right'></span>
<a href='/workspace/projects/{{ $project->id }}'>{{ $project->title }}</a> -->
@endsection('navigation')

@section('main-content')

<div class='row'>
<div class='col-md-6'>
	@include('helpers.showAllMessages')
	
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
</div>
<div class='col-md-6'>
	<p>Current score <b id='user-score'>{{ $userScore }}</b></p>

	<h4>Outgoing Connection Requests</h4>
	<div id='outgoing-request-list'></div>

	<h4>Incoming Connection Requests</h4>
	<div id='incoming-request-list'></div>

	<h4>Question List</h4>
	<div id='answer-list'></div>
	<p class='selected-answer'>Selecting question <span></span>. Select a user below to ask.</p>

	<h4>Users Connected to You</h4>
	<table class='table-condensed table-hover' style='width:100%' id='user-connection-list'></table>
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
	<p>Selecting <span class='name'><%= name %></span>.
	<% if (canRequestConnection(id)) %>
	<a href='#' class='request-connection' data-id=''>Request connection</a>
	<% else if (connectionExists(Config.get('userId'), id)) %>
	You are connected. <a href='#'class='request-intermediary-connection'>Request connection with a friend.</a>
</script>

<script type='text/template' data-template='request'>
<% if (type=='connection' && direction == 'incoming') { %>
Recieved a <%= type %> request from <%= initiator_name %>.

<% if (state == 'accepted') %>
You accepted.
<% else if (state == 'rejected') %>
You rejected.
<% else if (state == 'open') %>
<a class='connection-accept'>Accept</a> | <a class='connection-reject'>Reject</a>
<% } else if (type=='connection' && direction == 'outgoing') { %>
Sent a <%= type %> request to <%= recipient_name %> |
<% if (state == 'accepted') %>
They accepted.
<% else if (state == 'rejected') %>
They rejected.
<% if (state == 'open') %>
Awaiting response.
<% } %>
</script>

<script type='text/template' data-template='user_connection'>
	<td>You are connected to <span class='connected-user'><%= other_name %></span>.</td>
	<td><a class='btn-ask btn btn-default'>Ask Question</a></td>
</script>

<script type='text/template' data-template='answer'>
<span data-id='<%= id %>' title="<% if (answered) { %> Answered <% } else { %> Unanswered <% } %>" >
<%= name %>
</a>

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
	if (requestList.where({initiator_id: userId, recipient_id: to, type: 'connection'}).length > 0) {
		return false;
	}
	if (requestList.where({initiator_id: userId, recipient_id: to, type: 'connection'}).length > 0) {
		return false;
	}
	return true;
}

function realtimeDataHandler(param) {
	console.log(param);
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
			} else if (param.action == 'delete') {
				connectionList.remove(connection);
			}
		});
	} else if (param.dataType == 'answers') {
		_.each(param.data, function(answer) {
			if (answer.answered) {
				// Unselect it if it is selected.
				if (selectedAnswerId == answer.id) {
					setSelectedAnswer(null);
				}
				answerList.get(answer.id).set(answer);
			}
		});
	} else if (param.dataType == 'scores') {
		_.each(param.data, function(score) {
			if (score.user_id == Config.get('userId')) {
				$('#user-score').html(score.score);
			}
		})
	}
}

Realtime.init(realtimeDataHandler);

</script>
@endsection('main-content')