<?php

namespace App\Controllers;

use App\Models\Donation;
use App\Services\PaymentService;

class DonationController
{
    protected $donationModel;
    protected $paymentService;

    public function __construct()
    {
        $this->donationModel = new Donation();
        $this->paymentService = new PaymentService();
    }

    public function showDonationPage()
    {
        // Render the donation page view
        include_once '../src/Views/donate.php';
    }

    public function processDonation($data)
    {
        // Validate donation data
        if ($this->validateDonationData($data)) {
            // Process payment
            $paymentResult = $this->paymentService->processPayment($data['amount'], $data['paymentMethod']);

            if ($paymentResult['success']) {
                // Save donation details to the database
                $this->donationModel->create($data);
                return ['success' => true, 'message' => 'Thank you for your donation!'];
            } else {
                return ['success' => false, 'message' => 'Payment failed. Please try again.'];
            }
        }

        return ['success' => false, 'message' => 'Invalid donation data.'];
    }

    protected function validateDonationData($data)
    {
        // Basic validation for donation data
        return isset($data['amount']) && is_numeric($data['amount']) && $data['amount'] > 0;
    }
}