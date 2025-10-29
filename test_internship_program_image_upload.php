<?php
/**
 * Тестовый скрипт для проверки загрузки изображений в стажировочных программах
 * Запустите: php test_internship_program_image_upload.php
 */

// Настройки
$baseUrl = 'http://localhost:8000/api/v1';
$adminToken = 'your_admin_token_here'; // Замените на реальный токен админа

// Создание тестового изображения (1x1 пиксель PNG)
echo "=== Тестирование загрузки изображений в стажировочных программах ===\n\n";

echo "1. Создание тестового изображения...\n";
$testImageData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
$testImagePath = 'test_program_image.png';
file_put_contents($testImagePath, $testImageData);
echo "Тестовое изображение создано: $testImagePath\n\n";

// 2. Создание программы с изображением
echo "2. Создание программы с изображением...\n";
$ch = curl_init();

$postData = [
    'trainer_id' => '1',
    'title' => 'Test Program with Image',
    'description' => 'Test program description with image upload',
    'image' => new CURLFile($testImagePath, 'image/png', 'test_program_image.png'),
    'is_featured' => 'false',
    'registration_status' => 'open',
    'category' => 'Test Category',
    'duration_weeks' => '8',
    'start_date' => '2025-12-01',
    'location' => 'Test Location',
    'current_enrollment' => '0',
    'max_capacity' => '15',
    'instructor_title' => 'Test Instructor',
    'instructor_description' => 'Test instructor description',
    'instructor_rating' => '4.5'
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
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Статус: $httpCode\n";
echo "Ответ: " . json_encode(json_decode($response, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// 3. Обновление программы с новым изображением
if ($httpCode === 201) {
    $responseData = json_decode($response, true);
    $programId = $responseData['program']['id'];
    
    echo "3. Обновление программы с новым изображением (ID: $programId)...\n";
    
    // Создаем другое тестовое изображение
    $testImageData2 = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
    $testImagePath2 = 'test_program_image_2.png';
    file_put_contents($testImagePath2, $testImageData2);
    
    $ch = curl_init();
    
    $updateData = [
        'title' => 'Updated Test Program with New Image',
        'description' => 'Updated test program description',
        'image' => new CURLFile($testImagePath2, 'image/png', 'test_program_image_2.png'),
        'is_featured' => 'true'
    ];
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $baseUrl . "/internship-programs/$programId",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'PUT',
        CURLOPT_POSTFIELDS => $updateData,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $adminToken
        ],
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Статус: $httpCode\n";
    echo "Ответ: " . json_encode(json_decode($response, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    
    // 4. Получение обновленной программы
    echo "4. Получение обновленной программы...\n";
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $baseUrl . "/internship-programs/$programId",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $adminToken
        ],
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Статус: $httpCode\n";
    echo "Ответ: " . json_encode(json_decode($response, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    
    // 5. Удаление программы
    echo "5. Удаление программы...\n";
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $baseUrl . "/internship-programs/$programId",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => 'DELETE',
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $adminToken
        ],
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "Статус: $httpCode\n";
    echo "Ответ: " . json_encode(json_decode($response, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    
    // Очистка
    unlink($testImagePath2);
}

// Очистка
unlink($testImagePath);
echo "Тестовые изображения удалены.\n";
echo "=== Тестирование завершено ===\n";
?>
