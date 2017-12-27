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

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {

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
