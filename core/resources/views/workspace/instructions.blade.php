@extends('workspace.layouts.single-project')

@section('navigation')
<a href='/workspace/instructions'>Instructions</a>
@if ($project->active)
<a href='/workspace/projects/{{$project->id}}'>Study</a>
@endif
@endsection('navigation')

@section('main-content')
<div class='col-md-6'>
<h1>Welcome to SIMPSON</h1>


<b>Welcome!</b><p>In this game, you are looking to find answers for certain questions. 
Each of you have 20 questions to answer and you all start off with about 4 randomly assigned answers. The row of numbers that you will see on the next page represent the 20 questions and the ones highlighted in green represent the answers you have in real time. </p><p>You will find these answers by picking people to connect with and then ask them if they have an answer (you can only ask for one at a time). To pick someone, click on their representation in the real-time network visual window (on the left hand side) and you will be prompted to connect with them. The person receiving such a request must then say "yes" within a certain amount of time (otherwise, the response defaults to a "no").</p><p>Making connections, rejecting connections, and asking questions all have costs and benefits to them and these are outlined for you in the table below and also as you progress through the game.</p><p>The goal of this exercise is to collect ALL answers to the 20 questions, while maximizing your total score (also depicted in real-time on the next page). You have 30 minutes to complete this exercise.</p>

<!-- 
<p>In this study you will have sixty minutes to collect answers from other users. Everyone is initially randomly assigned a fixed number of answers. To obtain an answer, you must first connect with a user, then request an answer from them. Each answer you obtain increases your information capital (IC). Your session completes if you obtain all of the answers.
</p>

<p>Each action has an associated cost. In addition to finding all of the answers, the goal of the task includes getting a high network capital (NC). The costs are summarized below, but are also presented during the study.
</p>
-->

<h4>Action Costs/Benefits</h4>
<table class='table table-condensed table-striped'>
<thead>
<tr><th>Action</th><th>Your score change</th><th>Their score change</th></tr>
</thead>

<tr><td>Requesting a connection with someone new</td><td class='cost cost-n'>-L  &nbsp;*</td><td>0</td></tr>
<tr><td>Requesting a connection with a friend-of-a-friend</td><td class='cost cost-n'>-L<span style='color: #4DB339'>+2</span> &nbsp;*</td><td>0</td></tr>
<tr><td>Accepting a connection from someone new</td><td class='cost cost-p'>2</td><td class='cost cost-p'>10</td></tr>
<tr><td>Accepting a connection from a friend-of-a-friend</td><td class='cost cost-p'>5</td><td class='cost cost-p'>10</td></tr>
<tr><td>Refusing a connection from someone new</td><td class='cost cost-n'>-1</td><td class='cost cost-n'>-10</td></tr>
<tr><td>Refusing a connection from a friend-of-a-friend</td><td class='cost cost-n'>-2</td><td class='cost cost-n'>-10</td></tr>
<tr><td>Asking for an answer from someone</td><td class='cost cost-n'>-5</td><td class='cost cost-z'>0</td></tr>
<tr><td>Asking for an answer from all friends</td><td class='cost cost-n'>-5N<span style='color: #4DB339'>+5</span>&nbsp;**</td><td class='cost cost-z'>0</td></tr>
<tr><td>Doing nothing for a twenty second period (i.e. lurking)</td><td class='cost cost-p'>1</td><td>not applicable</td></tr>
</table>
<p>
	<small>*L is the number of connections that person has.</small>
	<small>**N is the number of connections you have.</small>
</p>

@if (!empty($project->description))
<h2>Study Instructions</h2>
<p> {!! $project->description !!} </p>
@endif

@if ($project->active && $project->state == 'started')
<a class='btn btn-primary' href='/workspace/projects/{{$project->id}}'>Continue to Study &raquo;</a>
@else
<p>The study is not currently active. This page will automatically refresh every 60 seconds until the study becomes active.</p>
<script>
window.setTimeout(function(){
    window.location.reload();
}, 60000);
</script>

<p>You can return to these instructions at any time by clicking the <i>INSTRUCTIONS</i> link at the top of the page.</p>

@endif
</div>
@endsection