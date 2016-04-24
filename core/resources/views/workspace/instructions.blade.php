@extends('workspace.layouts.single-project')

@section('navigation')
<a href='/workspace/instructions'>Instructions</a>
<a href='/workspace/projects/{{$project->id}}'>Study</a>
@endsection('navigation')

@section('main-content')
<div class='col-md-6'>
<h1>Welcome to SIMPSON</h1>
<p>In this study you will have sixty minutes to collect answers from other users. Everyone is initially randomly assigned a fixed number of answers. To obtain an answer, you must first connect with a user, then request an answer from them. Each answer you obtain increases your information score (IC). Your session completes if you obtain all of the answers.
</p>

<p>Each action has an associated cost. In addition to finding all of the answers, the goal of the task includes getting a high network score (NC). The costs are summarized below, but are also presented during the study.
</p>

<h4>Action Costs/Benefits</h4>
<table class='table table-condensed table-striped'>
<tr><td>Requesting a connection with someone new</td><td class='cost cost-n'>-L*</td></tr>
<tr><td>Requesting a connection with a friend-of-a-friend</td><td class='cost cost-n'>-(L-2)*</td></tr>
<tr><td>Accepting a connection from someone new</td><td class='cost cost-p'>2</td></tr>
<tr><td>Accepting a connection from a friend-of-a-friend</td><td class='cost cost-p'>5</td></tr>
<tr><td>Refusing a connection from someone new</td><td class='cost cost-z'>0</td></tr>
<tr><td>Refusing a connection from a friend-of-a-friend</td><td class='cost cost-n'>-2</td></tr>
<tr><td>Asking for an answer from someone</td><td class='cost cost-n'>-5</td></tr>
<tr><td>Doing nothing for a five second period (i.e. lurking)</td><td class='cost cost-p'>1</td></tr>
</table>
<p>
	<small>*L is equal to the number of connections that person has.</small>
</p>

<p>You can return to these instructions at any time by clicking the <i>INSTRUCTIONS</i> link at the top of the page.</p>

<a class='btn btn-primary' href='/workspace/projects/{{$project->id}}'>Continue to Study &raquo;</a>
</div>
@endsection