<?php

/**
 * Test script for Online Training with Google Meet Integration
 * 
 * This script demonstrates how to create an online training with Google Meet integration
 * and email notifications, similar to the webinar functionality.
 */

require_once 'vendor/autoload.php';

echo "🌾 Aqrar Portal - Online Training with Google Meet Integration Test\n";
echo "================================================================\n\n";

// Example API request for creating an online training with Google Meet
$apiEndpoint = 'http://localhost:8000/api/v1/trainings';
$bearerToken = 'your_bearer_token_here'; // Replace with actual token

$trainingData = [
    'title' => 'Kənd Təsərrüfatında Müasir Texnologiyalar',
    'description' => 'Bu təlimdə kənd təsərrüfatında istifadə olunan müasir texnologiyalar haqqında məlumat veriləcək.',
    'category' => 'Kənd Təsərrüfatı',
    'trainer_id' => 1, // Replace with actual trainer ID
    'start_date' => '2024-12-01',
    'end_date' => '2024-12-01',
    'start_time' => '10:00',
    'end_time' => '12:00',
    'timezone' => 'Asia/Baku',
    'type' => 'online',
    'difficulty' => 'intermediate',
    'has_certificate' => true,
    'status' => 'published',
    
    // Google Meet integration fields
    'google_meet_enabled' => true,
    'meeting_start_time' => '2024-12-01 10:00:00',
    'meeting_end_time' => '2024-12-01 12:00:00',
    
    // Attendees for email notifications
    'attendees' => [
        [
            'email' => 'user1@example.com',
            'name' => 'İstifadəçi 1'
        ],
        [
            'email' => 'user2@example.com',
            'name' => 'İstifadəçi 2'
        ],
        [
            'email' => 'user3@example.com',
            'name' => 'İstifadəçi 3'
        ]
    ]
];

echo "📋 Training Data:\n";
echo "Title: " . $trainingData['title'] . "\n";
echo "Type: " . $trainingData['type'] . "\n";
echo "Google Meet Enabled: " . ($trainingData['google_meet_enabled'] ? 'Yes' : 'No') . "\n";
echo "Meeting Time: " . $trainingData['meeting_start_time'] . " - " . $trainingData['meeting_end_time'] . "\n";
echo "Attendees: " . count($trainingData['attendees']) . " users\n\n";

echo "🔧 Expected Functionality:\n";
echo "1. ✅ Training will be created in the database\n";
echo "2. ✅ Google Meet meeting will be created via Google Calendar API\n";
echo "3. ✅ Google Meet link will be stored in the training record\n";
echo "4. ✅ Email notifications will be sent to all attendees\n";
echo "5. ✅ Email will include Google Meet link for direct access\n";
echo "6. ✅ Email will include registration link for the training\n\n";

echo "📧 Email Notification Features:\n";
echo "- Subject: 'Yeni Online Təlim: [Training Title]'\n";
echo "- Professional HTML template with Aqrar Portal branding\n";
echo "- Training details (title, description, trainer, time)\n";
echo "- Google Meet link for direct access\n";
echo "- Registration button for training signup\n";
echo "- Important information about the online format\n";
echo "- Tips for optimal participation\n\n";

echo "🎯 API Request Example:\n";
echo "POST " . $apiEndpoint . "\n";
echo "Authorization: Bearer " . $bearerToken . "\n";
echo "Content-Type: application/json\n\n";

echo "Request Body:\n";
echo json_encode($trainingData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";

echo "📱 Response Expected:\n";
echo "{\n";
echo "  \"message\": \"Training created successfully\",\n";
echo "  \"training\": { ... training data ... },\n";
echo "  \"google_meet_link\": \"https://meet.google.com/abc-defg-hij\",\n";
echo "  \"notifications_sent\": 3\n";
echo "}\n\n";

echo "🔍 Key Differences from Webinar System:\n";
echo "1. Uses Training model instead of Meeting model\n";
echo "2. Integrates with existing training system\n";
echo "3. Supports both online and offline training types\n";
echo "4. Maintains training-specific features (modules, lessons, certificates)\n";
echo "5. Uses TrainingCreatedNotification email class\n";
echo "6. Email template specifically designed for training context\n\n";

echo "✅ Implementation Complete!\n";
echo "The online training system now has the same Google Meet integration\n";
echo "and email notification functionality as the webinar system.\n\n";

echo "🚀 Next Steps:\n";
echo "1. Test the API endpoint with actual data\n";
echo "2. Verify Google Calendar integration works\n";
echo "3. Check email delivery and formatting\n";
echo "4. Test with different training types and scenarios\n\n";

echo "📞 Support:\n";
echo "For any issues or questions, check the logs and ensure:\n";
echo "- Google Calendar API credentials are configured\n";
echo "- Email settings are properly configured\n";
echo "- User has valid Google access token\n";
echo "- Training data is valid and complete\n\n";

echo "🎉 Happy Training! 🌾\n";


