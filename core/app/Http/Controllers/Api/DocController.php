<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Services\DocService;
use App\Utilities\Status;
use App\Utilities\ApiResponse;

class DocController extends Controller
{
    public function __construct(DocService $docService) {
        $this->docService = $docService;
    }

    /**
     * @api{post} /v1/docs Create
     * @apiDescription Creates a new document.
     * @apiPermission write
     * @apiGroup Doc
     * @apiName CreateDocument
     * @apiParam {Integer} project_id
     * @apiParam {String} title
     * @apiVersion 1.0.0
     */
    public function create(Request $req) {
        $docStatus = $this->docService->create($req->all());
        return ApiResponse::fromStatus($docStatus);
    }

    /**
     * @api{get} /v1/docs/{doc-id}/text Get Text
     * @apiDescription Gets the plain text of a document.
     * @apiPermission read
     * @apiGroup Doc
     * @apiName GetText
     * @apiParam {Boolean} [as_html=false] If true, returns formatted HTML instead of plain text.
     * @apiVersion 1.0.0
     * @apiExample {curl} Example Usage
     * curl "http://localhost:8000/api/v1/docs/2/text?auth_email=coagmento_demo@demo.demo&auth_password=demo&project_id=300&as_html=1"
     * @apiSuccessExample {json} Success Response
     *   {
     *    "status": "ok",
     *    "errors": {
     *      "input": [],
     *      "general": []
     *    },
     *    "result": {
     *      "text": {
     *        "html": "<!DOCTYPE HTML><html><body>This is a test of getting text.</body></html>"
     *      }
     *    }
     *  }
     */
    public function getText(Request $req, $id) {
        $args = array_merge(['id' => $id], $req->all());
        return ApiResponse::fromStatus($this->docService->getText($args));
    }

    /* @api{get} /v1/docs Get Multiple
     * @apiDescription Gets a list of docs.
     * If the project_id is specified, returns all docs in a project (not just owned by user).
     * If project_id is omitted, then returns all user owned docs.
     * @apiPermission read
     * @apiGroup Doc
     * @apiName GetDocs
     * @apiParam {Integer} [project_id]
     * @apiVersion 1.0.0
     */
    public function getMultiple(Request $req) {
        return ApiResponse::fromStatus($this->docService->getMultiple($req->all()));
    }

    /**
     * @api{delete} /v1/docs/:id Delete
     * @apiDescription Deletes a single doc.
     * @apiPermission write
     * @apiGroup Doc
     * @apiName DeleteDoc
     * @apiVersion 1.0.0
     */
    public function delete($id) {
        $status = $this->docService->delete($id);
        return ApiResponse::fromStatus($status);
    }
}
