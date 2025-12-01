<?php

namespace App\Controllers;

use App\Models\Donation;
use App\Services\PaymentService;

class DonationController
{
    protected $donationModel;
    protected $paymentService;

    /**
     * Allow optional dependency injection for easier testing/bootstrap wiring.
     */
    public function __construct(Donation $donationModel = null, PaymentService $paymentService = null)
    {
        $this->donationModel = $donationModel ?? new Donation();
        $this->paymentService = $paymentService ?? new PaymentService();
    }

    public function showDonationPage()
    {
        // Use the shared view renderer so layouts and data are consistent
        if (function_exists('render_view')) {
            render_view('src/Views/donate.php');
            return;
        }

        // Fallback to include when helper is not present
        include_once __DIR__ . '/../../src/Views/donate.php';
    }

    public function processDonation($data = null)
    {
        // Allow calling code (router) to simply dispatch here; controller will parse input if needed
        if ($data === null) {
            $raw = file_get_contents('php://input');
            $json = $raw ? json_decode($raw, true) : null;
            $data = $_POST ?: $json ?: [];
        }

        // Validate donation data
        if ($this->validateDonationData($data)) {
            // Process payment (PaymentService defines processDonation)
            try {
                $transactionId = $this->paymentService->processDonation($data['amount'], $data['donor'] ?? []);
            } catch (\Throwable $e) {
                return ['success' => false, 'message' => $e->getMessage()];
            }

            if (!empty($transactionId)) {
                // Save donation details to the database (Donation model defines createDonation)
                $saved = $this->donationModel->createDonation($data);
                if ($saved) {
                    return ['success' => true, 'message' => 'Thank you for your donation!', 'transaction_id' => $transactionId];
                }

                return ['success' => false, 'message' => 'Donation saved failed.'];
            }

            return ['success' => false, 'message' => 'Payment failed. Please try again.'];
        }

        return ['success' => false, 'message' => 'Invalid donation data.'];
    }

    protected function validateDonationData($data)
    {
        // Basic validation for donation data
        return isset($data['amount']) && is_numeric($data['amount']) && $data['amount'] > 0;
    }
}