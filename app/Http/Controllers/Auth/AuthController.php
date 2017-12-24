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

		$proxyResponse = $this->authProxy->attemptLogin($email, $password);

		$response = [
			'access_token' => $proxyResponse['access_token'],
			'expires_in'   => $proxyResponse['expires_in']
		];

		return response()->json($response, 201)->cookie('refresh_token',$proxyResponse['refresh_token'],14400,null,null,false,true);
	}

	public function currentUser(Request $request)
	{
		$user = $request->user();

		$current_user = [
			'id' => $user['id'],
			'name' => $user['name'],
			'email' => $user['email'],
			'profile' => $user['profile']
		];

		$response = [
			'message'     => 'Current User Information',
			'currentUser' => $current_user
		];

		return response()->json($current_user, 200);
	}

	public function refresh(Request $request)
	{
		$refreshToken = $request->cookie('refresh_token');
		$response = $this->authProxy->attemptRefresh($refreshToken);

		return response()->json($response, 200);
	}

	public function logout()
	{
		$this->authProxy->logout();

		$response = ['message' => 'User has been logged out'];
		return response()->json($response, 200);
	}

}