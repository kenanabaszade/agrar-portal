<?php

/**
 * FAQ API Test Script
 * 
 * This script demonstrates how to use the FAQ API endpoints
 * Make sure to replace the base URL and token with your actual values
 */

$baseUrl = 'http://localhost:8000/api/v1';
$token = 'YOUR_AUTH_TOKEN_HERE'; // Replace with actual admin token

$headers = [
    'Content-Type: application/json',
    'Accept: application/json',
    'Authorization: Bearer ' . $token
];

echo "=== FAQ API Test Script ===\n\n";

// Test 1: Get all FAQs
echo "1. Getting all FAQs...\n";
$response = makeRequest('GET', $baseUrl . '/faqs', $headers);
echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n\n";

// Test 2: Create a new FAQ
echo "2. Creating a new FAQ...\n";
$faqData = [
    'question' => 'Sistemdə necə qeydiyyatdan keçmək olar?',
    'answer' => 'Sistemdə qeydiyyatdan keçmək üçün ana səhifədə "Qeydiyyat" düyməsini basın və bütün tələb olunan məlumatları doldurun.',
    'category' => 'Qeydiyyat',
    'is_active' => true
];
$response = makeRequest('POST', $baseUrl . '/faqs', $headers, $faqData);
echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n\n";

// Test 3: Get FAQ categories
echo "3. Getting FAQ categories...\n";
$response = makeRequest('GET', $baseUrl . '/faqs/categories', $headers);
echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n\n";

// Test 4: Get FAQ statistics
echo "4. Getting FAQ statistics...\n";
$response = makeRequest('GET', $baseUrl . '/faqs/stats', $headers);
echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n\n";

// Test 5: Search FAQs
echo "5. Searching FAQs...\n";
$response = makeRequest('GET', $baseUrl . '/faqs?search=qeydiyyat', $headers);
echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n\n";

// Test 6: Filter by category
echo "6. Filtering FAQs by category...\n";
$response = makeRequest('GET', $baseUrl . '/faqs?category=Qeydiyyat', $headers);
echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n\n";

// Test 7: Mark FAQ as helpful (if FAQ exists)
if (isset($response['data']) && count($response['data']) > 0) {
    $faqId = $response['data'][0]['id'];
    echo "7. Marking FAQ as helpful...\n";
    $response = makeRequest('POST', $baseUrl . '/faqs/' . $faqId . '/helpful', $headers);
    echo "Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n\n";
}

echo "=== Test completed ===\n";

function makeRequest($method, $url, $headers, $data = null) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    
    if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'status_code' => $httpCode,
        'data' => json_decode($response, true)
    ];
}
