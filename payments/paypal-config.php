<?php
/**
 * PayPal REST API v2 Configuration
 * =================================
 * File: payments/paypal-config.php
 * 
 * SETUP INSTRUCTIONS:
 * 1. Go to https://developer.paypal.com
 * 2. Create a Business account (if you don't have one)
 * 3. Create an App in the Developer Dashboard
 * 4. Copy the Client ID and Secret for both Sandbox and Live
 * 5. Update the constants below
 * 6. For Live: change PAYPAL_MODE to 'live'
 * 
 * Required PHP Extensions: curl, json
 */

// ==== CONFIGURATION ====
define('PAYPAL_MODE', 'sandbox'); // 'sandbox' or 'live'

// Sandbox Credentials (for testing)
define('PAYPAL_SANDBOX_CLIENT_ID', 'Ae0kA...YOUR_SANDBOX_CLIENT_ID...');
define('PAYPAL_SANDBOX_CLIENT_SECRET', 'EJjkA...YOUR_SANDBOX_SECRET...');

// Live Credentials (for production)
define('PAYPAL_LIVE_CLIENT_ID', 'YOUR_LIVE_CLIENT_ID_HERE');
define('PAYPAL_LIVE_CLIENT_SECRET', 'YOUR_LIVE_SECRET_HERE');

// API Endpoints
if (PAYPAL_MODE === 'sandbox') {
    define('PAYPAL_API_BASE', 'https://api-m.sandbox.paypal.com');
    define('PAYPAL_CLIENT_ID', PAYPAL_SANDBOX_CLIENT_ID);
    define('PAYPAL_CLIENT_SECRET', PAYPAL_SANDBOX_CLIENT_SECRET);
} else {
    define('PAYPAL_API_BASE', 'https://api-m.paypal.com');
    define('PAYPAL_CLIENT_ID', PAYPAL_LIVE_CLIENT_ID);
    define('PAYPAL_CLIENT_SECRET', PAYPAL_LIVE_CLIENT_SECRET);
}

define('PAYPAL_CURRENCY', 'USD');
define('PAYPAL_BRAND_NAME', 'ShopVibe');

// ==== FUNCTIONS ====

/**
 * Get PayPal OAuth Access Token
 * @return string|false Access token or false on error
 */
function getPayPalAccessToken() {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => PAYPAL_API_BASE . '/v1/oauth2/token',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERPWD => PAYPAL_CLIENT_ID . ':' . PAYPAL_CLIENT_SECRET,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => 'grant_type=client_credentials',
        CURLOPT_HTTPHEADER => ['Accept: application/json', 'Accept-Language: en_US'],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        error_log('PayPal cURL Error: ' . curl_error($ch));
        curl_close($ch);
        return false;
    }
    curl_close($ch);

    if ($httpCode !== 200) {
        error_log('PayPal Auth HTTP ' . $httpCode . ': ' . $response);
        return false;
    }

    $data = json_decode($response, true);
    return $data['access_token'] ?? false;
}

/**
 * Create a PayPal Order (v2/checkout/orders)
 * 
 * @param float  $amount    Order total amount
 * @param string $orderNum  Internal order number for reference
 * @param string $returnUrl URL to redirect after successful payment
 * @param string $cancelUrl URL to redirect if user cancels
 * @return array|false PayPal order data or false
 */
function createPayPalOrder($amount, $orderNum, $returnUrl, $cancelUrl) {
    $accessToken = getPayPalAccessToken();
    if (!$accessToken) return false;

    $payload = [
        'intent' => 'CAPTURE',
        'purchase_units' => [[
            'reference_id' => $orderNum,
            'description'  => 'ShopVibe Order #' . $orderNum,
            'amount' => [
                'currency_code' => PAYPAL_CURRENCY,
                'value' => number_format($amount, 2, '.', '')
            ]
        ]],
        'application_context' => [
            'brand_name'          => PAYPAL_BRAND_NAME,
            'landing_page'        => 'BILLING',
            'shipping_preference' => 'NO_SHIPPING',
            'user_action'         => 'PAY_NOW',
            'return_url'          => $returnUrl,
            'cancel_url'          => $cancelUrl
        ]
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => PAYPAL_API_BASE . '/v2/checkout/orders',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken,
            'Prefer: return=representation'
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        error_log('PayPal Order cURL Error: ' . curl_error($ch));
        curl_close($ch);
        return false;
    }
    curl_close($ch);

    if ($httpCode !== 201) {
        error_log('PayPal Order HTTP ' . $httpCode . ': ' . $response);
        return false;
    }

    return json_decode($response, true);
}

/**
 * Capture PayPal Payment (after user approves)
 * 
 * @param string $orderId PayPal order ID (token)
 * @return array|false Capture result or false
 */
function capturePayPalOrder($orderId) {
    $accessToken = getPayPalAccessToken();
    if (!$accessToken) return false;

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => PAYPAL_API_BASE . '/v2/checkout/orders/' . urlencode($orderId) . '/capture',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => '{}',
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken,
            'Prefer: return=representation'
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        error_log('PayPal Capture cURL Error: ' . curl_error($ch));
        curl_close($ch);
        return false;
    }
    curl_close($ch);

    if ($httpCode !== 201) {
        error_log('PayPal Capture HTTP ' . $httpCode . ': ' . $response);
        return false;
    }

    return json_decode($response, true);
}

/**
 * Get PayPal Order Details
 * @param string $orderId PayPal order ID
 * @return array|false
 */
function getPayPalOrderDetails($orderId) {
    $accessToken = getPayPalAccessToken();
    if (!$accessToken) return false;

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => PAYPAL_API_BASE . '/v2/checkout/orders/' . urlencode($orderId),
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
