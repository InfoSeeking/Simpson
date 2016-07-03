<?php

namespace App\Http\Controllers\Api;

use Auth;
use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Services\ScoreService;
use App\Utilities\ApiResponse;

class ScoreController extends Controller
{
    public function __construct(ScoreService $scoreService) {
        $this->user = Auth::user();
        $this->scoreService = $scoreService;
    }

    public function getPlace($projectId) {
        return ApiResponse::fromResult($this->scoreService->getPlace($projectId));
    }
}
