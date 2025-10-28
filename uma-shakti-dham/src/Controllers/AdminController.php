<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Moderator;

class AdminController
{
    public function index()
    {
        // Display the admin dashboard
    }

    public function listUsers()
    {
        // Retrieve and display a list of users
        $users = User::all();
        require_once '../src/Views/admin/users.php';
    }

    public function createUser()
    {
        // Handle user creation logic
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user = new User();
            $user->name = $_POST['name'];
            $user->email = $_POST['email'];
            $user->password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $user->save();
            header('Location: /admin/users');
        }
        require_once '../src/Views/admin/create_user.php';
    }

    public function editUser($id)
    {
        // Handle user editing logic
        $user = User::find($id);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user->name = $_POST['name'];
            $user->email = $_POST['email'];
            $user->save();
            header('Location: /admin/users');
        }
        require_once '../src/Views/admin/edit_user.php';
    }

    public function deleteUser($id)
    {
        // Handle user deletion logic
        $user = User::find($id);
        $user->delete();
        header('Location: /admin/users');
    }

    public function listModerators()
    {
        // Retrieve and display a list of moderators
        $moderators = Moderator::all();
        require_once '../src/Views/admin/moderators.php';
    }

    public function createModerator()
    {
        // Handle moderator creation logic
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $moderator = new Moderator();
            $moderator->name = $_POST['name'];
            $moderator->email = $_POST['email'];
            $moderator->password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $moderator->save();
            header('Location: /admin/moderators');
        }
        require_once '../src/Views/admin/create_moderator.php';
    }

    public function editModerator($id)
    {
        // Handle moderator editing logic
        $moderator = Moderator::find($id);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $moderator->name = $_POST['name'];
            $moderator->email = $_POST['email'];
            $moderator->save();
            header('Location: /admin/moderators');
        }
        require_once '../src/Views/admin/edit_moderator.php';
    }

    public function deleteModerator($id)
    {
        // Handle moderator deletion logic
        $moderator = Moderator::find($id);
        $moderator->delete();
        header('Location: /admin/moderators');
    }
}