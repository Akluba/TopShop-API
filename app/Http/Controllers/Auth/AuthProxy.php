<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Hash;
use App\Exceptions\InvalidCredentialsException;
use App\User;
use Cookie;

class AuthProxy
{
	const REFRESH_TOKEN = 'refresh_token';
	private $apiConsumer;
	private $auth;
	private $cookie;
	private $db;
	private $request;

	public function __construct(Application $app)
	{
		$this->apiConsumer = $app->make('apiconsumer');
		$this->auth = $app->make('auth');
		$this->cookie = $app->make('cookie');
		$this->db = $app->make('db');
		$this->request = $app->make('request');
	}

	public function attemptLogin($email, $password)
	{
		$user = \App\User::where('email', $email)
			->where('active', 1)
			->get()
			->first();

		if (!is_null($user) && Hash::check($password, $user['password'])) {
			return $this->proxy('password', [
				'username' => $email,
				'password' => $password
			]);
		}

		throw new InvalidCredentialsException('Invalid login credentials');
	}

	public function attemptRefresh($refreshToken)
	{
		return $this->proxy('refresh_token', [
			'refresh_token' => $refreshToken
		]);
	}

	public function proxy($grantType, array $data = [])
	{
		$data = array_merge($data, [
			'client_id'     => env('PASSWORD_CLIENT_ID'),
			'client_secret' => env('PASSWORD_CLIENT_SECRET'),
			'grant_type'    => $grantType
		]);

		$response = $this->apiConsumer->post('/oauth/token', $data);

		if (!$response->isSuccessful()) {
			throw new InvalidCredentialsException('Error: Please contact TopShop for assistance.');
		}

		$data = json_decode($response->getContent());

		return [
			'access_token'  => $data->access_token,
			'refresh_token' => $data->refresh_token,
			'expires_in'    => $data->expires_in
		];
	}

	public function logout()
	{
		$accessToken = $this->auth->user()->token();

		$refreshToken = $this->db
			->table('oauth_refresh_tokens')
			->where('access_token_id', $accessToken->id)
			->update([
				'revoked' => true
			]);

		$accessToken->revoke();

		$this->cookie->queue($this->cookie->forget(self::REFRESH_TOKEN));
	}

}