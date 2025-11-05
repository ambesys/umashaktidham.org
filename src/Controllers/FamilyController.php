<?php

namespace App\Controllers;

use App\Models\Family;
use App\Models\User;
use App\Models\FamilyMember;
use App\Services\PhoneService;

class FamilyController
{
    public function index($userId)
    {
        $fm = new FamilyMember($GLOBALS['pdo'] ?? null);
        $families = $fm->listByUserId($userId);
        require_once '../src/Views/members/family.php';
    }

    public function create($userId)
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // normalize and map POST to family member fields
            $phone = PhoneService::normalizeToE164($_POST['phone'] ?? null);
            $family = new FamilyMember($GLOBALS['pdo'] ?? null);
            $data = [
                'user_id' => $userId,
                'first_name' => $_POST['first_name'] ?? $_POST['name'] ?? null,
                'last_name' => $_POST['last_name'] ?? null,
                'birth_year' => $_POST['birth_year'] ?? null,
                'gender' => $_POST['gender'] ?? null,
                'email' => $_POST['email'] ?? null,
                'phone_e164' => $phone,
                'relationship' => $_POST['relation'] ?? $_POST['relationship'] ?? 'other',
                'relationship_other' => $_POST['relationship_other'] ?? null,
                'occupation' => $_POST['occupation'] ?? null,
                'business_info' => $_POST['business_info'] ?? null,
            ];
            $family->create($data);
            header("Location: /dashboard.php");
        }
        require_once '../src/Views/members/family.php';
    }

    public function edit($familyId)
    {
        $fm = new FamilyMember($GLOBALS['pdo'] ?? null);
        $family = $fm->findById($familyId);
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $phone = PhoneService::normalizeToE164($_POST['phone'] ?? null);
            $data = [
                'first_name' => $_POST['first_name'] ?? $_POST['name'] ?? null,
                'last_name' => $_POST['last_name'] ?? null,
                'birth_year' => $_POST['birth_year'] ?? null,
                'gender' => $_POST['gender'] ?? null,
                'email' => $_POST['email'] ?? null,
                'phone_e164' => $phone,
                'relationship' => $_POST['relation'] ?? $_POST['relationship'] ?? null,
                'relationship_other' => $_POST['relationship_other'] ?? null,
                'occupation' => $_POST['occupation'] ?? null,
                'business_info' => $_POST['business_info'] ?? null,
            ];
            $fm->update($familyId, $data);
            header("Location: /dashboard.php");
        }
        require_once '../src/Views/members/family.php';
    }

    public function delete($familyId)
    {
        $fm = new FamilyMember($GLOBALS['pdo'] ?? null);
        $fm->delete($familyId);
        header("Location: /dashboard.php");
    }
}