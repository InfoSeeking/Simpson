<?php

namespace App\Services;

use Auth;
use Validator;

use App\Models\Connection;
use App\Models\Request;
use App\Models\Membership;
use App\Models\User;
use App\Services\AnswerService;
use App\Services\MembershipService;
use App\Services\ConnectionService;
use App\Services\RealtimeService;
use App\Utilities\Status;
use App\Utilities\StatusCodes;

class RequestService {
	public function __construct(
		MembershipService $memberService,
		RealtimeService $realtimeService,
		ConnectionService $connectionService
		) {
		$this->memberService = $memberService;
		$this->realtimeService = $realtimeService;
		$this->connectionService = $connectionService;
		$this->user = Auth::user();
	}
	
	/**
	 * Retrieves a single request.
	 *
	 * @param int $id The request id.
	 * @return Status The resulting request.
	 */
	public function get($id) {
		$request = Request::find($id);
		if (is_null($request)) {
			return Status::fromError('Request not found', StatusCodes::NOT_FOUND);
		}

		$memberStatus = $this->memberService->checkPermission($request->project_id, 'r', $this->user);
		if (!$memberStatus->isOK()) return Status::fromStatus($memberStatus);

		return Status::fromResult($request);
	}

	/**
	 * Retrieves a list of requests.
	 *
	 * If a project is specified, returns all requests from the project.
	 * Otherwise returns only requests created by this user.
	 * @param Array $args Can filter by project.
	 * @param Boolean $countOnly If true, returns an integer.
	 * @return Status Collection of requests.
	 */
	public function getMultiple($args, $countOnly=false) {
		$validator = Validator::make($args, [
			'project_id' => 'sometimes|exists:projects,id'
			]);
		if ($validator->fails()) {
			return Status::fromValidator($validator);
		}

		if (array_key_exists('project_id', $args)) {
			$memberStatus = $this->memberService->checkPermission($args['project_id'], 'r', $this->user);
			if (!$memberStatus->isOK()) return Status::fromStatus($memberStatus);
			$requests = Request::where('project_id', $args['project_id']);
			if ($countOnly) return Status::fromResult($requests->count());
			return Status::fromResult($requests->get());
		}

		// Return all user created requests.
		if (!$this->user) return Status::fromError('Log in to see requests or specify a project_id');
		$requests = Request::where('user_id', $this->user->id);
		if ($countOnly) return Status::fromResult($requests->count());
		return Status::fromResult($requests->get());
	}

	/**
	 * Creates a request. Updates scores as needed.
	 * 
	 * @param Array $args Must contain url and project.
	 * @return Status The newly created request.
	 */
	public function create($args) {
		$validator = Validator::make($args, [
			'type' => 'required|string',
			'status' => 'sometimes|string',
			'recipient_id' => 'required|exists:users,id',
			'intermediary_id' => 'sometimes|exists:users,id',
			'project_id' => 'required|exists:projects,id',
			'answer_id' => 'sometimes|exists:answers,id',
			]);

		if ($validator->fails()) {
			return Status::fromValidator($validator);
		}
		$memberStatus = $this->memberService->checkPermission($args['project_id'], 'w', $this->user);
		if (!$memberStatus->isOK()) return $memberStatus;

		$request = new Request($args);
		$request->initiator_id = $this->user->id;
		$request->project_id = $args['project_id'];
		$request->state = 'open';
		$request->save();

		// TODO: handle scoring.

		if ($request->type == 'answer') {
			// This gets an immediate response.
			$wasAnswered = $this->answerService->handle($request);
			$request->state = $wasAnswered ? 'answered' : 'not_answered';
			$request->save();
		}

		$this->realtimeService
			->withModel($request)
			->onProject($request->project_id)
			->emit('create');

		return Status::fromResult($request);
	}

	/**
	 * Deletes a request
	 * 
	 * @param int $id The request ID.
	 * @return Status
	 */
	public function delete($id) {
		$request = Request::find($id);
		if (is_null($request)) {
			return Status::fromError('Request not found', StatusCodes::NOT_FOUND);
		}
		
		$memberStatus = $this->memberService->checkPermission($request->project_id, 'w', $this->user);
		if (!$memberStatus->isOK()) return $memberStatus;
		
		$this->realtimeService
			->withModel($request)
			->onProject($request->project_id)
			->emit('delete');
		$request->delete();
		return Status::OK();
	}

    /**
     * Updates a connection request and scores as needed.
     *
     * @param Array $args
     */
    public function updateConnectionRequest($args) {
    	$validator = Validator::make($args, [
			'request_id' => 'required|exists:requests,id',
			'state' => 'required|string'
			]);
		if ($validator->fails()) {
			return Status::fromValidator($validator);
		}

		$request = Request::find($args['request_id']);
		if ($this->user->id != $request->recipient_id)
			return Status::fromError('Only recipient may reply');

		if ($request->type != 'connection')
			return Status::fromError('This is not a connection request');

		if ($request->state != 'open')
			return Status::fromError('Cannot update closed request');

		if ($args['state'] == 'accepted') {
			$connectionStatus = $this->connectionService->create([
				'initiator_id' => $request->initiator_id,
				'recipient_id' => $request->recipient_id,
				// 'intermediary_id' => $request->intermediary_id, Will this spoil the validator?
				'project_id' => $request->project_id
				]);
			if (!$connectionStatus->isOK()) return $connectionStatus;
		} else if ($args['state'] == 'rejected') {
			// Update request
		} else {
			return Status::fromError('Status not recognized');
		}

		$request->state = $args['state'];
		$request->save();

		$this->realtimeService
			->withModel($request)
			->onProject($request->project_id)
			->emit('update');
		return Status::OK();
    }

}