<?php

namespace App\Http\Controllers\Api;

use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Services\RequestService;
use App\Utilities\ApiResponse;

class RequestController extends Controller
{
    public function __construct(RequestService $requestService) {
        $this->requestService = $requestService;
    }
    /**
     * @api{post} /v1/requests Create
     * @apiDescription Creates a new request.
     * @apiPermission write
     * @apiGroup Request
     * @apiName CreateRequest
     * @apiParam {Integer} project_id
     * @apiParam {String} url
     * @apiParam {String} [notes] Related user written notes about this request.
     * @apiParam {String} title The contents of title in the page.
     * @apiParam {String[]} [tags] A list of initial tags.
     * @apiVersion 1.0.0
     */
    public function create(Request $req) {
        $requestStatus = $this->requestService->create($req->all());
        return ApiResponse::fromStatus($requestStatus);
    }

    /**
     * @api{get} /v1/requests Get Multiple
     * @apiDescription Gets a list of requests.
     * If the project_id is specified, returns all requests in a project (not just owned by user).
     * If project_id is omitted, then returns all user owned requests.
     * @apiPermission read
     * @apiGroup Request
     * @apiName GetRequests
     * @apiParam {Integer} [project_id]
     * @apiVersion 1.0.0
     */
    public function index(Request $req) {
        return ApiResponse::fromStatus($this->requestService->getMultiple($req->all()));
    }

    /**
     * @api{get} /v1/requests/:id Get
     * @apiDescription Gets a single request.
     * @apiPermission read
     * @apiGroup Request
     * @apiName GetRequest
     * @apiVersion 1.0.0
     */
    public function get(Request $req, $id) {
        return ApiResponse::fromStatus($this->requestService->get($id));
    }

    /**
     * @api{delete} /v1/requests/:id Delete
     * @apiDescription Deletes a single request.
     * @apiPermission write
     * @apiGroup Request
     * @apiName DeleteRequest
     * @apiVersion 1.0.0
     */
    public function delete($id) {
        $status = $this->requestService->delete($id);
        return ApiResponse::fromStatus($status);
    }

    /**
     * @api{put} /v1/requests/:id Update
     * @apiDescription Updates a request.
     * @apiPermission write
     * @apiGroup Request
     * @apiName UpdateRequest
     * @apiVersion 1.0.0
     */
    public function updateConnection(Request $req) {
        $requestStatus = $this->requestService->updateConnectionRequest($req->all());
        return ApiResponse::fromStatus($requestStatus);
    }
}
