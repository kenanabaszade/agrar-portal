<?php
/**
 * Тестовый скрипт для проверки API фотографий профиля
 * Запустите: php test_profile_photo_api.php
 */

// Настройки
$baseUrl = 'http://localhost:8000/api/v1';
$testToken = 'your_test_token_here'; // Замените на реальный токен

// Функция для выполнения HTTP запросов
function makeRequest($url, $method = 'GET', $data = null, $token = null, $isFile = false) {
    $ch = curl_init();
    
    $headers = [];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    
    if (!$isFile) {
        $headers[] = 'Content-Type: application/json';
    }
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    
    if ($data && $method !== 'GET') {
        if ($isFile) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        } else {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'body' => json_decode($response, true)
    ];
}

echo "=== Тестирование API фотографий профиля ===\n\n";

// 1. Получение текущего профиля
echo "1. Получение текущего профиля...\n";
$response = makeRequest($baseUrl . '/profile', 'GET', null, $testToken);
echo "Статус: " . $response['code'] . "\n";
echo "Ответ: " . json_encode($response['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// 2. Создание тестового изображения (1x1 пиксель PNG)
echo "2. Создание тестового изображения...\n";
$testImageData = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPhfDwAChwGA60e6kgAAAABJRU5ErkJggg==');
$testImagePath = 'test_image.png';
file_put_contents($testImagePath, $testImageData);
echo "Тестовое изображение создано: $testImagePath\n\n";

// 3. Загрузка фотографии профиля
echo "3. Загрузка фотографии профиля...\n";
$ch = curl_init();
$postData = [
    'profile_photo' => new CURLFile($testImagePath, 'image/png', 'test_image.png')
];

curl_setopt_array($ch, [
    CURLOPT_URL => $baseUrl . '/profile/upload-photo',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $postData,
    CURLOPT_HTTPHEADER => [
        'Authorization: Bearer ' . $testToken
    ],
    CURLOPT_SSL_VERIFYPEER => false,
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Статус: $httpCode\n";
echo "Ответ: " . json_encode(json_decode($response, true), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// 4. Получение обновленного профиля
echo "4. Получение обновленного профиля...\n";
$response = makeRequest($baseUrl . '/profile', 'GET', null, $testToken);
echo "Статус: " . $response['code'] . "\n";
echo "Ответ: " . json_encode($response['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// 5. Удаление фотографии профиля
echo "5. Удаление фотографии профиля...\n";
$response = makeRequest($baseUrl . '/profile/delete-photo', 'DELETE', null, $testToken);
echo "Статус: " . $response['code'] . "\n";
echo "Ответ: " . json_encode($response['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// 6. Получение профиля после удаления
echo "6. Получение профиля после удаления...\n";
$response = makeRequest($baseUrl . '/profile', 'GET', null, $testToken);
echo "Статус: " . $response['code'] . "\n";
echo "Ответ: " . json_encode($response['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// Очистка
unlink($testImagePath);
echo "Тестовое изображение удалено.\n";
echo "=== Тестирование завершено ===\n";
?>
