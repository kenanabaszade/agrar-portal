<?php
/**
 * Простой тест загрузки изображения
 */

// Настройки
$baseUrl = 'http://localhost:8000/api/v1';
$adminToken = 'your_admin_token_here'; // Замените на реальный токен

// Создание тестового изображения
echo "=== Простой тест загрузки изображения ===\n\n";

echo "1. Создание тестового изображения...\n";
$testImageData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
$testImagePath = 'test_simple.png';
file_put_contents($testImagePath, $testImageData);
echo "Тестовое изображение создано: $testImagePath\n\n";

// Тест загрузки
echo "2. Тестирование загрузки изображения...\n";
$ch = curl_init();

$postData = [
    'title' => 'Test Image Upload',
    'description' => 'Testing image upload functionality',
    'image' => new CURLFile($testImagePath, 'image/png', 'test_simple.png'),
    'is_featured' => 'false',
    'registration_status' => 'open',
    'category' => 'Test',
    'duration_weeks' => '4',
    'start_date' => '2025-12-01',
    'location' => 'Test Location',
    'current_enrollment' => '0',
    'max_capacity' => '10',
    'instructor_name' => 'Test Instructor',
    'instructor_title' => 'Test Title',
    'instructor_initials' => 'TI',
    'instructor_description' => 'Test description',
    'instructor_rating' => '4.0'
];

curl_setopt_array($ch, [
    CURLOPT_URL => $baseUrl . '/internship-programs',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $postData,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $adminToken
    ],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_VERBOSE => true
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "Статус: $httpCode\n";
if ($error) {
    echo "Ошибка cURL: $error\n";
}
echo "Ответ: " . json_encode(json_decode($response, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Очистка
unlink($testImagePath);
echo "Тестовое изображение удалено.\n";
echo "=== Тест завершен ===\n";
?>
