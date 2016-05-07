@extends('workspace.layouts.single-project')

@section('navigation')
<a href='/workspace/instructions'>Instructions</a>
<a href='/workspace/projects/{{$project->id}}'>Study</a>
@endsection('navigation')

@section('main-content')
<div class='col-md-6'>
<h1>Welcome to SIMPSON</h1>
<p>In this study you will have sixty minutes to collect answers from other users. Everyone is initially randomly assigned a fixed number of answers. To obtain an answer, you must first connect with a user, then request an answer from them. Each answer you obtain increases your information capital (IC). Your session completes if you obtain all of the answers.
</p>

<p>Each action has an associated cost. In addition to finding all of the answers, the goal of the task includes getting a high network capital (NC). The costs are summarized below, but are also presented during the study.
</p>

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

<p>You can return to these instructions at any time by clicking the <i>INSTRUCTIONS</i> link at the top of the page.</p>

<a class='btn btn-primary' href='/workspace/projects/{{$project->id}}'>Continue to Study &raquo;</a>
</div>
@endsection