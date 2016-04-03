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

@include('helpers.showAllMessages')

<p>Welcome to your study project.</p>

<h4>Questions</h4>
<h5>You have Answered</h5>
<h5>You need to Answer</h5>
<h4>Available Users</h4>
<small>To be replaced with a graph interface</small>
<ul>
@foreach ($sharedUsers as $sharedUser)
<li>{{$sharedUser->user->name}} <a href='#' class='request-connection' data-id='{{ $sharedUser->user->id }}'>Request connection</a></li>
@endforeach
</ul>

<h4>Outgoing Connection Requests</h4>
<ul id='outgoing-request-list'></ul>

<h4>Incoming Connection Requests</h4>
<ul id='incoming-request-list'></ul>

<h4>Users Connected to You</h4>
<ul id='user-connected-list'>
</ul>

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

<script src='/js/realtime.js'></script>
<script src='/js/data/layouts.js'></script>
<script src='/js/data/feed.js'></script>
<script src='/js/data/user.js'></script>
<script src='/js/data/request.js'></script>
<script src='/js/data/connection.js'></script>
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

var connectionList = new ConnectionCollection();

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
	}
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
	$.ajax({
		url: '/api/v1/requests',
		method: 'post',
		dataType: 'json',
		data: {
			project_id: Config.get('projectId'),
			recipient_id: recipientId,
			type: 'connection'
		},
		success: function(resp) {
			MessageDisplay.display(['Connection request sent'], 'success');
		},
		error: function(xhr) {
			var json = JSON.parse(xhr.responseText);
			MessageDisplay.displayIfError(json);
		}
	});
}

</script>
@endsection('main-content')