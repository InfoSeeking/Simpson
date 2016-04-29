<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

// Authentication.
Route::get('auth/login', 'Auth\AuthController@getLogin');
Route::post('auth/login', 'Auth\AuthController@postLogin');
Route::get('auth/logout', 'Auth\AuthController@getLogout');
Route::get('auth/register', 'Auth\AuthController@getRegister');
Route::post('auth/register', 'Auth\AuthController@postRegister');
Route::post('auth/demoLogin', 'Auth\AuthController@demoLogin');

// Workspace pages.
Route::group(['middleware' => 'auth'], function() {
	// These pages do not make sense without a logged in user.
	Route::get('/', 'WorkspaceController@showInstructions');
	Route::get('workspace', 'WorkspaceController@viewPanel');
	Route::get('workspace/projects', 'WorkspaceController@showProjects');
	Route::get('workspace/projects/create', 'WorkspaceController@showProjectCreate');
	Route::get('workspace/projects/sharedWithMe', 'WorkspaceController@showShared');
	Route::post('workspace/projects/create', 'WorkspaceController@createProject');
	Route::get('workspace/user/settings', 'WorkspaceController@showUserSettings');
	Route::post('workspace/user/settings', 'WorkspaceController@updateUserSettings');
	Route::get('workspace/instructions', 'WorkspaceController@showInstructions');
	Route::get('workspace/end', 'WorkspaceController@showEnd');
});

Route::get('workspace/projects/{project_id}/bookmarks/{bookmark_id}',
	'WorkspaceController@viewBookmark');
Route::get('workspace/projects/{project_id}', 'WorkspaceController@viewProject');
Route::get('workspace/projects/{project_id}/bookmarks',
	'WorkspaceController@viewProjectBookmarks');
Route::get('workspace/projects/{project_id}/snippets', 'WorkspaceController@viewProjectSnippets');
Route::get('workspace/projects/{project_id}/chat', 'WorkspaceController@viewChat');
Route::get('workspace/projects/{project_id}/docs', 'WorkspaceController@viewDocs');
Route::get('workspace/projects/{project_id}/history', 'WorkspaceController@viewHistory');

// Viewing document requires write permissions until we can get read-only to work.
Route::get('workspace/projects/{project_id}/docs/{doc_id}', 'WorkspaceController@viewDoc');

Route::delete('workspace/projects/{project_id}', 'WorkspaceController@deleteProject');
Route::get('workspace/projects/{project_id}/settings', 'WorkspaceController@viewProjectSettings');

// API.
Route::group(['middleware' => 'api.auth'], function() {
	// These endpoints do not make sense without a logged in user.
	Route::get('api/v1/users/current', 'Api\UserController@getCurrent');
	Route::get('api/v1/users/logout', function(){
		Auth::logout();
	});
	Route::get('api/v1/projects', 'Api\ProjectController@index');
});

Route::group(['middleware' => 'api.optional.auth'], function(){
	// These routes may require some permissions, but not necessarily.
	// Users.
	Route::get('api/v1/users/{user_id}', 'Api\UserController@get');
	Route::post('api/v1/users', 'Api\UserController@create');
	Route::get('api/v1/users', 'Api\UserController@getMultiple');

	// Requests.
	Route::get('api/v1/requests', 'Api\RequestController@index');
	Route::get('api/v1/requests/{request_id}', 'Api\RequestController@get');
	Route::post('api/v1/requests', 'Api\RequestController@create');
	Route::put('api/v1/connection', 'Api\RequestController@updateConnection');
	Route::delete('api/v1/requests/{request_id}', 'Api\RequestController@delete');

	// Projects.
	Route::get('api/v1/projects/{project_id}', 'Api\ProjectController@get');
	Route::put('api/v1/projects/{project_id}', 'Api\ProjectController@update');
	Route::post('api/v1/projects', 'Api\ProjectController@create');
	Route::delete('api/v1/projects/{project_id}', 'Api\ProjectController@delete');
	Route::delete('api/v1/projects', 'Api\ProjectController@deleteMultiple');
	Route::post('api/v1/projects/{project_id}/share', 'Api\ProjectController@share');
	Route::put('api/v1/projects/{project_id}/share', 'Api\ProjectController@updateShare');
	Route::delete('api/v1/projects/{project_id}/share', 'Api\ProjectController@unshare');

	Route::post('api/v1/tick', 'Api\TickController@tick');

});