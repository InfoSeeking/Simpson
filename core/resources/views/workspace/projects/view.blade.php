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
	<div id='selected-user'>
		<p>Currently selecting <span class='name'></span>. <a href='#' class='request-connection' data-id=''>Request connection</a>
	</div>
	
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
	<h4>Outgoing Connection Requests</h4>
	<ul id='outgoing-request-list'></ul>

	<h4>Incoming Connection Requests</h4>
	<ul id='incoming-request-list'></ul>

	<h4>Question List</h4>
	<ul id='answer-list'></ul>
	<p class='selected-answer'>Selecting question <span></span>. Select a user below to ask.</p>

	<h4>Users Connected to You</h4>
	<ul id='user-connection-list'>
	</ul>
</div>

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
You are connected to <span class='connected-user 
<% if (selectedAnswerId) { %>
answer-selected
<% } %>'
><%= other_name %></span>.
</script>

<script type='text/template' data-template='answer'>
<span data-id='<%= id %>' title=<% if (answered) { %> Answered <% } else { %> Unanswered <% } %> >
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
	if (requestList.where({initiator_id: userId, recipient_id: to, type: 'connection'}).length > 0) {
		return false;
	}
	if (requestList.where({initiator_id: userId, recipient_id: to, type: 'connection'}).length > 0) {
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

function setSelectedAnswer(answerModel) {
	var selectedAnswerEl = $('.selected-answer');
	
	if (!answerModel) {
		selectedAnswerId = null;
		selectedAnswerEl.hide();
		$('.answer').removeClass('selected');
		$('.connected-user').removeClass('answer-selected');
	} else {
		selectedAnswerId = answerModel.get('id');
		selectedAnswerEl.find('span').html(answerModel.get('name'));
		selectedAnswerEl.show();
		$('.answer').removeClass('selected');
		$('.answer span[data-id=' + answerModel.get('id') + ']').parent().addClass('selected');
		$('.connected-user').addClass('answer-selected');
	}
}

function handleUserClick(userModel) {
	if (!selectedAnswerId) return;
	$.ajax({
		url: '/api/v1/requests',
		method: 'post',
		dataType: 'json',
		data: {
			project_id: parseInt(Config.get('projectId')),
			recipient_id: parseInt(userModel.get('id')),
			answer_name: answerList.get(selectedAnswerId).get('name'),
			type: 'answer'
		},
		success: function(resp) {
			if (resp.result.state == "answered") {
				MessageDisplay.display(['Answer recieved!'], 'success');
			} else {
				MessageDisplay.display(['User did not have the answer'], 'danger');
			}
		},
		error: function(xhr) {
			var json = JSON.parse(xhr.responseText);
			MessageDisplay.displayIfError(json);
		}
	});
}

function realtimeDataHandler(param) {
	console.log(param);
	if(param.dataType == 'requests') {
		_.each(param.data, function(request) {
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
	}
	refreshAllUsers();
}

Realtime.init(realtimeDataHandler);

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