<?php

namespace App\Services;

use Auth;
use App\Models\User;
use App\Models\Log;

class LogService {
	public function __construct() {
		$this->clear();
	}

	private function clear() {
		$user = Auth::user();
		$this->userId = $user ? $user->id : null;
		$this->key = "";
		$this->value = "";
		$this->projectId = null;
		$this->connectionId = null;
		$this->requestId = null;
		$this->answerId = null;
	}

	public function withUserId($userId) {
		$this->userId = $userId;
		return $this;
	}

	public function withKey($key) {
		$this->key = $key;
		return $this;
	}

	public function withValue($value) {
		$this->value = $value;
		return $this;
	}

	public function withRequestId($requestId) {
		$this->requestId = $requestId;
		return $this;
	}

	public function withConnectionId($connectionId) {
		$this->connectionId = $connectionId;
		return $this;
	}

	public function withProjectId($projectId) {
		$this->projectId = $projectId;
		return $this;
	}

	public function withAnswerId($answerId) {
		$this->answerId = $answerId;
		return $this;
	}

	public function save() {
		Log::create([
			'user_id' => $this->userId,
			'key' => $this->key,
			'value' => $this->value,
			'project_id' => $this->projectId,
			'connection_id' => $this->connectionId,
			'request_id' => $this->requestId,
			'answer_id' => $this->answerId
		]);
		$this->clear();
	}
}