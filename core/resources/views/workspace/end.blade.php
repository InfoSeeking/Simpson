@extends('workspace.layouts.single-project')

@section('navigation')

@endsection('navigation')

@section('main-content')
<div class='col-md-6'>

@if ($project)
	<h1>Don't leave yet!</h1>

	@if ($project->description)
		<p>{!! $project->description !!}</p>
	@endif

	@if ($project->state == 'started')
		<a class='btn btn-primary' href='/workspace/projects/{{$project->id}}'>Continue to Next Part of Study &raquo;</a>
	@else
		<p>You can take a short break before the next part of the study begins.</p>
	@endif

@else
<h1>End of Study</h1>
<p>There are no further tasks. Thank you for your participation!</p>
@endif

</div>
@endsection