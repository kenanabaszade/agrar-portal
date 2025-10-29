<?php
/**
 * Тестовый скрипт для проверки API стажировочных программ
 * Запустите: php test_internship_programs_api.php
 */

// Настройки
$baseUrl = 'http://localhost:8000/api/v1';
$adminToken = 'your_admin_token_here'; // Замените на реальный токен админа

// Функция для выполнения HTTP запросов
function makeRequest($url, $method = 'GET', $data = null, $token = null) {
    $ch = curl_init();
    
    $headers = ['Content-Type: application/json'];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    
    if ($data && $method !== 'GET') {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'body' => json_decode($response, true)
    ];
}

echo "=== Тестирование API стажировочных программ ===\n\n";

// 1. Получение списка программ
echo "1. Получение списка программ...\n";
$response = makeRequest($baseUrl . '/internship-programs');
echo "Статус: " . $response['code'] . "\n";
echo "Ответ: " . json_encode($response['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// 2. Получение рекомендуемых программ
echo "2. Получение рекомендуемых программ...\n";
$response = makeRequest($baseUrl . '/internship-programs/featured');
echo "Статус: " . $response['code'] . "\n";
echo "Ответ: " . json_encode($response['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// 3. Получение категорий
echo "3. Получение категорий...\n";
$response = makeRequest($baseUrl . '/internship-programs/categories');
echo "Статус: " . $response['code'] . "\n";
echo "Ответ: " . json_encode($response['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// 4. Фильтрация по категории
echo "4. Фильтрация по категории 'Bitki Yetişdiriciliyi'...\n";
$response = makeRequest($baseUrl . '/internship-programs?category=Bitki Yetişdiriciliyi');
echo "Статус: " . $response['code'] . "\n";
echo "Ответ: " . json_encode($response['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// 5. Поиск программ
echo "5. Поиск программ по слову 'Bitki'...\n";
$response = makeRequest($baseUrl . '/internship-programs?search=Bitki');
echo "Статус: " . $response['code'] . "\n";
echo "Ответ: " . json_encode($response['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// 6. Создание новой программы (требует авторизации)
echo "6. Создание новой программы...\n";
$newProgram = [
    'title' => 'Test Staj Proqramı',
    'description' => 'Test proqramının təsviri',
    'image_url' => '/images/test_program.jpg',
    'is_featured' => false,
    'registration_status' => 'open',
    'category' => 'Test Kategoriya',
    'duration_weeks' => 8,
    'start_date' => '2025-07-01',
    'location' => 'Test Lokasiya',
    'current_enrollment' => 0,
    'max_capacity' => 15,
    'instructor_name' => 'Test Mütəxəssis',
    'instructor_title' => 'Test Mütəxəssis',
    'instructor_initials' => 'TM',
    'instructor_photo_url' => '/images/instructors/test.jpg',
    'instructor_description' => 'Test mütəxəssis təsviri',
    'instructor_rating' => 4.5,
    'details_link' => '/internship-programs/test',
    'cv_requirements' => 'Test tələblər',
    'modules' => [
        [
            'title' => 'Test Modul 1',
            'description' => 'Test modul təsviri',
            'order' => 1
        ],
        [
            'title' => 'Test Modul 2',
            'description' => 'Test modul təsviri 2',
            'order' => 2
        ]
    ],
    'requirements' => [
        [
            'requirement' => 'Test tələb 1',
            'order' => 1
        ],
        [
            'requirement' => 'Test tələb 2',
            'order' => 2
        ]
    ],
    'goals' => [
        [
            'goal' => 'Test məqsəd 1',
            'order' => 1
        ],
        [
            'goal' => 'Test məqsəd 2',
            'order' => 2
        ]
    ]
];

$response = makeRequest($baseUrl . '/internship-programs', 'POST', $newProgram, $adminToken);
echo "Статус: " . $response['code'] . "\n";
echo "Ответ: " . json_encode($response['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

// 7. Получение детальной информации о программе (если создание прошло успешно)
if ($response['code'] === 201 && isset($response['body']['program']['id'])) {
    $programId = $response['body']['program']['id'];
    
    echo "7. Получение детальной информации о программе ID: $programId...\n";
    $response = makeRequest($baseUrl . "/internship-programs/$programId");
    echo "Статус: " . $response['code'] . "\n";
    echo "Ответ: " . json_encode($response['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    
    // 8. Обновление программы
    echo "8. Обновление программы...\n";
    $updateData = [
        'title' => 'Updated Test Staj Proqramı',
        'description' => 'Updated test proqramının təsviri',
        'is_featured' => true
    ];
    
    $response = makeRequest($baseUrl . "/internship-programs/$programId", 'PUT', $updateData, $adminToken);
    echo "Статус: " . $response['code'] . "\n";
    echo "Ответ: " . json_encode($response['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    
    // 9. Удаление программы
    echo "9. Удаление программы...\n";
    $response = makeRequest($baseUrl . "/internship-programs/$programId", 'DELETE', null, $adminToken);
    echo "Статус: " . $response['code'] . "\n";
    echo "Ответ: " . json_encode($response['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
}

echo "=== Тестирование завершено ===\n";
?>
