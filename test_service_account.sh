#!/bin/bash

echo "🧪 Testing Google Service Account Setup"
echo "======================================"

echo ""
echo "📋 Checking setup..."

# Check if credentials file exists
if [ -f "storage/app/google-credentials.json" ]; then
    echo "✅ Google credentials file found"
    echo "   File: storage/app/google-credentials.json"
else
    echo "❌ Google credentials file not found"
    echo "   Please download your service account JSON key and place it at:"
    echo "   storage/app/google-credentials.json"
    echo ""
    echo "   Run: ./setup_service_account.sh for instructions"
    exit 1
fi

# Check if service account email is configured
if grep -q "GOOGLE_SERVICE_ACCOUNT_EMAIL" .env; then
    echo "✅ Service account email configured"
    SERVICE_EMAIL=$(grep "GOOGLE_SERVICE_ACCOUNT_EMAIL" .env | cut -d'=' -f2)
    echo "   Email: $SERVICE_EMAIL"
else
    echo "❌ Service account email not configured"
    echo "   Please add GOOGLE_SERVICE_ACCOUNT_EMAIL to your .env file"
    echo "   Format: agrar-portal-meetings@YOUR-PROJECT-ID.iam.gserviceaccount.com"
    exit 1
fi

echo ""
echo "📋 Testing Google Calendar Service..."

# Test the service
php artisan tinker --execute="
try {
    use App\Services\GoogleCalendarService;
    \$service = new GoogleCalendarService();
    echo '✅ Google Calendar Service initialized successfully';
} catch (Exception \$e) {
    echo '❌ Error: ' . \$e->getMessage();
    exit(1);
}
"

echo ""
echo "📋 Testing meeting creation..."

# Test meeting creation
php artisan tinker --execute="
try {
    use App\Services\GoogleCalendarService;
    \$service = new GoogleCalendarService();
    
    \$meetingData = [
        'title' => 'Test Meeting - ' . date('Y-m-d H:i:s'),
        'description' => 'Testing service account setup',
        'start_time' => date('Y-m-d H:i:s', strtotime('+1 hour')),
        'end_time' => date('Y-m-d H:i:s', strtotime('+2 hours')),
        'timezone' => 'UTC'
    ];
    
    \$result = \$service->createMeeting(\$meetingData);
    
    if (\$result['success']) {
        echo '✅ Meeting created successfully!';
        echo '   Event ID: ' . \$result['event_id'];
        echo '   Meet Link: ' . (\$result['meet_link'] ?? 'No meet link');
    } else {
        echo '❌ Failed to create meeting: ' . \$result['error'];
        exit(1);
    }
} catch (Exception \$e) {
    echo '❌ Error: ' . \$e->getMessage();
    exit(1);
}
"

echo ""
echo "🎉 Service account setup is working correctly!"
echo ""
echo "📋 Next steps:"
echo "1. Test the API endpoint: POST /api/v1/meetings"
echo "2. Create meetings from your application"
echo "3. Users can register and join meetings"
echo ""
echo "🎯 Ready to use Google Meet integration!"
