<?php

namespace App\Controllers;

use App\Models\Family;
use App\Models\User;

class FamilyController
{
    public function index($userId)
    {
        $families = Family::where('user_id', $userId)->get();
        require_once '../src/Views/members/family.php';
    }

    public function create($userId)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $familyData = [
                'user_id' => $userId,
                'name' => $_POST['name'],
                'relation' => $_POST['relation'],
                'dob' => $_POST['dob'],
                'gender' => $_POST['gender'],
            ];
            Family::create($familyData);
            header("Location: /dashboard.php");
        }
        require_once '../src/Views/members/family.php';
    }

    public function edit($familyId)
    {
        $family = Family::find($familyId);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $family->name = $_POST['name'];
            $family->relation = $_POST['relation'];
            $family->dob = $_POST['dob'];
            $family->gender = $_POST['gender'];
            $family->save();
            header("Location: /dashboard.php");
        }
        require_once '../src/Views/members/family.php';
    }

    public function delete($familyId)
    {
        $family = Family::find($familyId);
        if ($family) {
            $family->delete();
        }
        header("Location: /dashboard.php");
    }
}