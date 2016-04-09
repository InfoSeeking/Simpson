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
<a href='/workspace/projects'><span class='fa fa-folder-open-o'></span> Projects</a>
<span class='fa fa-angle-right'></span>
<a href='/workspace/projects/{{ $project->id }}'>{{ $project->title }}</a>
@endsection('navigation')

@section('main-content')

<div class='row'>
<div class='col-md-6'>
	@include('helpers.showAllMessages')
	
	<div id='graph-display'></div>
	<div id='selected-user'>
		<p>Currently selecting <span class='name'></span>. <a href='#' class='request-connection' data-id=''>Request connection</a>
	</div>
	
	<h4>All users</h4>
	<ul id='all-user-list'>
	@foreach ($sharedUsers as $sharedUser)
	<li data-id={{$sharedUser->user->id}}>
	{{$sharedUser->user->name}}
	<a href='#' class='request-connection' style='display:none' data-id='{{ $sharedUser->user->id }}'>Request connection</a></li>
	@endforeach
	</ul>
</div>
<div class='col-md-6'>
	<p>Welcome to your study project.</p>
	<h4>Question List</h4>
	<ul id='answer-list'></ul>

	<h4>Outgoing Connection Requests</h4>
	<ul id='outgoing-request-list'></ul>

	<h4>Incoming Connection Requests</h4>
	<ul id='incoming-request-list'></ul>

	<h4>Users Connected to You</h4>
	<ul id='user-connection-list'>
	</ul>
</div>

<script type='text/template' data-template='request'>
<% if (direction == 'incoming') { %>
Recieved a <%= type %> request from <%= initiator_name %>.

<% if (state == 'accepted') %>
You accepted.
<% else if (state == 'rejected') %>
You rejected.
<% else if (state == 'open') %>
<a class='connection-accept'>Accept</a> | <a class='connection-reject'>Reject</a>
<% } else if (direction == 'outgoing') { %>
Sent a <%= type %> request to <%= recipient_name %> |
<% if (state == 'accepted') %>
They accepted.
<% else if (state == 'rejected') %>
They rejected.
<% } %>
</script>

<script type='text/template' data-template='user_connection'>
You are connected to <%= other_name %>.
</script>

<script type='text/template' data-template='answer'>
<span title=<% if (answered) { %> Answered <% } else { %> Unanswered <% } %> >
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

var incomingRequestList = new RequestCollection();
var incomingRequestListView = new IncomingRequestListView({
	collection: incomingRequestList
});

var outgoingRequestList = new RequestCollection();
var outgoingRequestListView = new OutgoingRequestListView({
	collection: outgoingRequestList
});

var userList = new UserCollection();
// Add all project users to user collection.
@foreach ($sharedUsers as $sharedUser)
userList.add(new UserModel(
	{!! $sharedUser->user->toJson() !!}
));
@endforeach

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
	if(connectionList.where({initiator_id: a, recipient_id: b}).length > 0) {
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
	if (outgoingRequestList.where({initiator_id: userId, recipient_id: to, type: 'connection'}).length > 0) {
		return false;
	}
	if (incomingRequestList.where({initiator_id: to, recipient_id: userId, type: 'connection'}).length > 0) {
		return false;
	}
	return true;
}

function refreshAllUsers() {
	var userId = Config.get('userId');
	userList.each(function(user) {
		var otherId = user.get('id');
		if (canRequestConnection(otherId)) {
			$('#all-user-list li[data-id=' + otherId + '] .request-connection').show();
		} else {
			$('#all-user-list li[data-id=' + otherId + '] .request-connection').hide();	
		}
		
	})
}

function realtimeDataHandler(param) {
	console.log(param);
	if(param.dataType == 'requests') {
		_.each(param.data, function(request) {
			var requestList = null;
			// Check if incoming or outgoing or N/A.
			if (request.initiator_id == Config.get('userId')) {
				requestList = outgoingRequestList;
			} else if (request.recipient_id == Config.get('userId')) {
				requestList = incomingRequestList;
			} else {
				return;
			}
			if (param.action == 'create') {
				console.log('adding', request);
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
	} else if (param.dataType == 'answer') {
		_.each(param.data, function(answer) {
			// Only add answers we have.
			if (answer.user_id != Config.get('userId')) return;
			answerList.add(answer);
		});
	}
	refreshAllUsers();
}

Realtime.init(realtimeDataHandler);

var rawRequests = {!! $requests->toJSON() !!};
_.each(rawRequests, function(request) {
	if (request.initiator_id == Config.get('userId')) {
		outgoingRequestList.add(request);
	} else if (request.recipient_id == Config.get('userId')) {
		incomingRequestList.add(request);
	}
});


$('.request-connection').on('click', onConnectionClick);

function onConnectionClick(e){
	e.preventDefault();
	var recipientId = $(this).attr('data-id');
	$(this).hide();
	$.ajax({
		url: '/api/v1/requests',
		method: 'post',
		dataType: 'json',
		data: {
			project_id: parseInt(Config.get('projectId')),
			recipient_id: parseInt(recipientId),
			type: 'connection'
		},
		success: function(resp) {
			//MessageDisplay.display(['Connection request sent'], 'success');

		},
		error: function(xhr) {
			var json = JSON.parse(xhr.responseText);
			MessageDisplay.displayIfError(json);
		}
	});
}

refreshAllUsers();

</script>
@endsection('main-content')