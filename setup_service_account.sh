#!/bin/bash

echo "🔧 Google Service Account Setup Helper"
echo "======================================"

echo ""
echo "📋 Follow these steps to set up your service account:"
echo ""

echo "1️⃣  Go to Google Cloud Console:"
echo "   https://console.cloud.google.com/"
echo ""

echo "2️⃣  Create a new project (or select existing):"
echo "   - Project name: 'Agrar Portal'"
echo "   - Note down your Project ID"
echo ""

echo "3️⃣  Enable Google Calendar API:"
echo "   - Go to: APIs & Services → Library"
echo "   - Search: 'Google Calendar API'"
echo "   - Click 'Enable'"
echo ""

echo "4️⃣  Create Service Account:"
echo "   - Go to: APIs & Services → Credentials"
echo "   - Click: 'Create Credentials' → 'Service Account'"
echo "   - Name: 'agrar-portal-meetings'"
echo "   - Description: 'Service account for Agrar Portal meetings'"
echo "   - Click 'Create and Continue'"
echo "   - Skip 'Grant access' (click 'Continue')"
echo "   - Click 'Done'"
echo ""

echo "5️⃣  Generate Service Account Key:"
echo "   - Find your service account in Credentials page"
echo "   - Click on the service account email"
echo "   - Go to 'Keys' tab"
echo "   - Click 'Add Key' → 'Create new key'"
echo "   - Choose 'JSON' format"
echo "   - Click 'Create'"
echo "   - The JSON file will download automatically"
echo ""

echo "6️⃣  Configure Your Application:"
echo "   - Rename downloaded file to: google-credentials.json"
echo "   - Move it to: storage/app/google-credentials.json"
echo ""

echo "7️⃣  Update your .env file:"
echo "   Add this line to your .env file:"
echo "   GOOGLE_SERVICE_ACCOUNT_EMAIL=agrar-portal-meetings@YOUR-PROJECT-ID.iam.gserviceaccount.com"
echo ""

echo "8️⃣  Test the setup:"
echo "   Run: ./test_service_account_setup.sh"
echo ""

echo "🎯 Ready to start? Follow the steps above!"
echo "📚 For detailed instructions, see: GOOGLE_SERVICE_ACCOUNT_SETUP.md"
