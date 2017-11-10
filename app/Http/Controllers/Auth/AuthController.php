<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Auth\AuthProxy;
use App\Http\Requests\LoginRequest;

class AuthController extends Controller
{
	private $authProxy;

	public function __construct(AuthProxy $authProxy)
	{
		$this->authProxy = $authProxy;
	}

	public function login(LoginRequest $request)
	{
		$email = $request->get('email');
		$password = $request->get('password');

		$response = $this->authProxy->attemptLogin($email, $password);
		return response()->json($response, 201);
	}

	public function refresh(Request $request)
	{
		return $this->response($this->authProxy->attemptRefresh());
	}

	public function logout()
	{
		$this->authProxy->logout();

		$response = ['message' => 'User has been logged out'];
		return response()->json($response, 200);
	}

}