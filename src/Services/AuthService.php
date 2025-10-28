<?php

namespace App\Services;

use App\Models\User;

class AuthService
{
    public function register(array $data)
    {
        // Validate and create a new user
        $user = new User();
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->password = password_hash($data['password'], PASSWORD_BCRYPT);
        $user->save();

        return $user;
    }

    public function login(array $data)
    {
        // Find user by email
        $user = User::where('email', $data['email'])->first();

        if ($user && password_verify($data['password'], $user->password)) {
            // Start user session
            $_SESSION['user_id'] = $user->id;
            return true;
        }

        return false;
    }

    public function logout()
    {
        // Destroy user session
        session_destroy();
    }

    public function getUserById($id)
    {
        return User::find($id);
    }

    public function updateUser($id, array $data)
    {
        $user = User::find($id);
        if ($user) {
            $user->name = $data['name'] ?? $user->name;
            $user->email = $data['email'] ?? $user->email;
            if (isset($data['password'])) {
                $user->password = password_hash($data['password'], PASSWORD_BCRYPT);
            }
            $user->save();
        }

        return $user;
    }
}