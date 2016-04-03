<?php

namespace App\Services;

use Auth;
use Validator;

use App\Models\Connection;
use App\Models\Membership;
use App\Models\User;
use App\Services\MembershipService;
use App\Services\RealtimeService;
use App\Utilities\Status;
use App\Utilities\StatusCodes;

class ConnectionService {
	public function __construct(
		MembershipService $memberService,
		RealtimeService $realtimeService
		) {
		$this->memberService = $memberService;
		$this->realtimeService = $realtimeService;
		$this->user = Auth::user();
	}

	public function getMultiple($args){
		$validator = Validator::make($args, [
			'project_id' => 'required|exists:projects,id'
			]);
		if ($validator->fails()) {
			return Status::fromValidator($validator);
		}
		
		$memberStatus = $this->memberService->checkPermission($args['project_id'], 'r', $this->user);
		if (!$memberStatus->isOK()) return Status::fromStatus($memberStatus);
		$connections = Connection::where('project_id', $args['project_id']);
		return Status::fromResult($connections->get());
	}
	
	// if initiator_id is not passed, current user is assumed.
	public function create($args) {
		$validator = Validator::make($args, [
			'initiator_id' => 'sometimes|integer|exists:users,id',
			'recipient_id' => 'required|integer|exists:users,id',
			'intermediary_id' => 'sometimes|integer|exists:users,id',
			'project_id' => 'required|integer|exists:projects,id'
		]);
		if ($validator->fails()) return Status::fromValidator($validator);

		$idA = null;
		$idB = $args['recipient_id'];
		if (array_key_exists('initiator_id', $args)) {
			$idA = $args['initiator_id'];
		} else {
			$idA = $this->user->id;
		}
		
		// Ensure a connection does not already exist.
		$query = Connection::whereIn('initiator_id', [$idA, $idB])
			->whereIn('recipient_id', [$idA, $idB])
			->where('project_id', $args['project_id']);

		if (array_key_exists('intermediary_id', $args)) {
			$query = $query->where('intermediary_id', $args['intermediary_id']);
		}

		if (!is_null($query->first())) {
			return Status::fromError('Connection already exists');
		}

		$connection = new Connection($args);
		$connection->save();

		$this->realtimeService
			->withModel($connection)
			->onProject($args['project_id'])
			->emit('create');

		return Status::fromResult($connection);
		
	}
}