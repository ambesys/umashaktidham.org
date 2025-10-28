<?php

namespace App\Services;

use App\Models\Donation;

class PaymentService
{
    private $apiUrl;
    private $apiKey;

    public function __construct()
    {
        $this->apiUrl = getenv('PAYMENT_API_URL');
        $this->apiKey = getenv('PAYMENT_API_KEY');
    }

    public function processDonation($amount, $donorDetails)
    {
        // Validate the donation amount
        if ($amount <= 0) {
            throw new \InvalidArgumentException("Donation amount must be greater than zero.");
        }

        // Prepare the payment data
        $paymentData = [
            'amount' => $amount,
            'currency' => 'USD',
            'donor' => $donorDetails,
        ];

        // Call the payment gateway API
        $response = $this->makeApiRequest($paymentData);

        // Handle the response
        if ($response['status'] === 'success') {
            // Save donation details to the database
            $this->saveDonation($amount, $donorDetails);
            return $response['transaction_id'];
        } else {
            throw new \Exception("Payment processing failed: " . $response['message']);
        }
    }

    private function makeApiRequest($data)
    {
        // Simulate an API request to the payment gateway
        // In a real application, you would use cURL or a library like Guzzle
        return [
            'status' => 'success',
            'transaction_id' => uniqid('txn_'),
            'message' => 'Payment processed successfully.',
        ];
    }

    private function saveDonation($amount, $donorDetails)
    {
        $donation = new Donation();
        $donation->amount = $amount;
        $donation->donor_name = $donorDetails['name'];
        $donation->donor_email = $donorDetails['email'];
        $donation->save();
    }
}