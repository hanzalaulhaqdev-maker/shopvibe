<?php
/**
 * Payoneer Checkout API Configuration
 * ====================================
 * File: payments/payoneer-config.php
 * 
 * SETUP INSTRUCTIONS:
 * 1. Sign up at https://www.payoneer.com/solutions/accept-payments/
 * 2. Complete merchant verification (business docs, bank details)
 * 3. Get Program ID and API credentials from Payoneer Merchant Dashboard
 * 4. Configure webhook URL: https://yoursite.com/payments/payoneer-webhook.php
 * 5. Update constants below
 * 
 * Payoneer uses OAuth 2.0 + Hosted Checkout (redirect flow).
 * 
 * Required PHP Extensions: curl, json
 */

// ==== CONFIGURATION ====
define('PAYONEER_ENV', 'sandbox'); // 'sandbox' or 'production'

if (PAYONEER_ENV === 'sandbox') {
    define('PAYONEER_API_BASE', 'https://api.sandbox.payoneer.com/v4');
    define('PAYONEER_PROGRAM_ID', 'YOUR_SANDBOX_PROGRAM_ID');
    define('PAYONEER_CLIENT_ID', 'YOUR_SANDBOX_CLIENT_ID');
    define('PAYONEER_CLIENT_SECRET', 'YOUR_SANDBOX_CLIENT_SECRET');
} else {
    define('PAYONEER_API_BASE', 'https://api.payoneer.com/v4');
    define('PAYONEER_PROGRAM_ID', 'YOUR_LIVE_PROGRAM_ID');
    define('PAYONEER_CLIENT_ID', 'YOUR_LIVE_CLIENT_ID');
    define('PAYONEER_CLIENT_SECRET', 'YOUR_LIVE_CLIENT_SECRET');
}

define('PAYONEER_CURRENCY', 'USD');
define('PAYONEER_BRAND_NAME', 'ShopVibe');

// ==== FUNCTIONS ====

/**
 * Get Payoneer OAuth Access Token
 * @return string|false
 */
function getPayoneerAccessToken() {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => PAYONEER_API_BASE . '/auth/oauth/token',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'grant_type'    => 'client_credentials',
            'client_id'     => PAYONEER_CLIENT_ID,
            'client_secret' => PAYONEER_CLIENT_SECRET
        ]),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded'
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        error_log('Payoneer cURL Error: ' . curl_error($ch));
        curl_close($ch);
        return false;
    }
    curl_close($ch);

    if ($httpCode !== 200) {
        error_log('Payoneer Auth HTTP ' . $httpCode . ': ' . $response);
        return false;
    }

    $data = json_decode($response, true);
    return $data['access_token'] ?? false;
}

/**
 * Create Payoneer Payment Session (Hosted Checkout)
 * 
 * @param float  $amount       Total amount
 * @param string $orderNum      Internal order reference
 * @param string $customerEmail Customer email
 * @param string $returnUrl      Success redirect URL
 * @param string $cancelUrl      Cancel/failure redirect URL
 * @return array|false { redirect_url, payment_id, status }
 */
function createPayoneerPayment($amount, $orderNum, $customerEmail, $returnUrl, $cancelUrl) {
    $accessToken = getPayoneerAccessToken();
    if (!$accessToken) return false;

    $payload = [
        'integration' => 'CHECKOUT',
        'payment' => [
            'amount'   => number_format($amount, 2, '.', ''),
            'currency' => PAYONEER_CURRENCY,
            'reference'=> $orderNum,
            'statement_description' => PAYONEER_BRAND_NAME . ' Order ' . $orderNum
        ],
        'customer' => [
            'email' => $customerEmail
        ],
        'redirect' => [
            'success_url' => $returnUrl,
            'failure_url' => $cancelUrl,
            'cancel_url'  => $cancelUrl
        ],
        'notification' => [
            'type' => 'WEBHOOK'  // We'll also listen for webhooks
        ]
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => PAYONEER_API_BASE . '/programs/' . PAYONEER_PROGRAM_ID . '/payments',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        error_log('Payoneer Payment cURL Error: ' . curl_error($ch));
        curl_close($ch);
        return false;
    }
    curl_close($ch);

    if ($httpCode !== 200 && $httpCode !== 201) {
        error_log('Payoneer Payment HTTP ' . $httpCode . ': ' . $response);
        return false;
    }

    return json_decode($response, true);
}

/**
 * Get Payoneer Payment Status
 * @param string $paymentId Payoneer payment ID
 * @return array|false
 */
function getPayoneerPaymentStatus($paymentId) {
    $accessToken = getPayoneerAccessToken();
    if (!$accessToken) return false;

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => PAYONEER_API_BASE . '/programs/' . PAYONEER_PROGRAM_ID . '/payments/' . urlencode($paymentId),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $accessToken
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}
