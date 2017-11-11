<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\User;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = [
            'name' => 'Anthony Kluba',
            'email' => 'ak@gmail.com',
            'password' => Hash::make('password'),
            'active' => 1,
            'profile' => 'admin'
        ];

        User::create($user);
    }
}
