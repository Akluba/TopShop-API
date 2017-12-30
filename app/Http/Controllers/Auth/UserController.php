<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use App\User;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $users = \App\User::all();

        $response = [
            'message' => "List of active users",
            'data'    => $users
        ];

        return response()->json($response, 201);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $authenticated_user = $request->user();
        if ($authenticated_user['profile']  !== 'admin') {
            $message = 'You do not have permission to add users.';
            return response()->json(['message' => $message], 401);
        }

        $request->validate([
            'name' => 'required',
            'email' => 'required',
            'active' => 'required',
            'profile' => 'required'
        ]);

        $email = $request->input('email');

        $password = substr($email, 0, strpos($email, '@')) .'#321';

        $user = new User;
        $user->name = $request->input('name');
        $user->email = $email;
        $user->password = Hash::make($password);
        $user->active = $request->input('active');
        $user->profile = $request->input('profile');

        $user->save();

        $response = [
            'message' => "User: {$user->name}, has been created.",
            'data'    => $user
        ];

        return response()->json($response, 201);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $authenticated_user = $request->user();
        $user = \App\User::find($id);

        $component = $request->input('component');

        if ($authenticated_user['profile']  === 'admin' && $component === 'manage') {
            $request->validate([
                'name' => 'required',
                'email' => 'required',
                'active' => 'required',
                'profile' => 'required'
            ]);

            $user->active = $request->input('active');
            $user->profile = $request->input('profile');
        } else {
            $request->validate([
                'name' => 'required',
                'email' => 'required'
            ]);
        }

        if ($authenticated_user['profile']  !== 'admin' && $authenticated_user['id'] !== $user['id']) {
            return response()->json(['message' => 'You do not have permission to make changes to this user.'], 401);
        }

        $password_group = $request->input('pwGroup');
        if (!empty($password_group['newPW'])) {
            if (!Hash::check($password_group['password'], $user['password'])) {
                $message = "The current password you entered did not match your current password.";
                return response()->json(['message' => $message], 401);
            }

            $user->password = Hash::make($password_group['newPW']);
        }

        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->save();

        $response = [
            'message' => "User: {$user->name}, has been updated.",
            'data'    => $user
        ];

        return response()->json($response, 201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {

    }

}
