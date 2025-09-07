#!/bin/bash

echo "🔧 Setting up Google Meet Integration with Personal Account"
echo "=========================================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "\n${BLUE}This script will help you set up Google Meet integration using your personal Google account.${NC}"
echo -e "${YELLOW}No users need to log in - all meetings will be created from your account!${NC}"

echo -e "\n${BLUE}Step 1: Enter your personal Google account email${NC}"
read -p "Your Google email (e.g., yourname@gmail.com): " PERSONAL_EMAIL

if [ -z "$PERSONAL_EMAIL" ]; then
    echo -e "${RED}❌ Email is required!${NC}"
    exit 1
fi

echo -e "\n${BLUE}Step 2: Update .env file${NC}"
if [ -f .env ]; then
    # Check if GOOGLE_PERSONAL_EMAIL already exists
    if grep -q "GOOGLE_PERSONAL_EMAIL" .env; then
        # Update existing entry
        sed -i.bak "s/GOOGLE_PERSONAL_EMAIL=.*/GOOGLE_PERSONAL_EMAIL=$PERSONAL_EMAIL/" .env
        echo -e "${GREEN}✅ Updated GOOGLE_PERSONAL_EMAIL in .env${NC}"
    else
        # Add new entry
        echo "GOOGLE_PERSONAL_EMAIL=$PERSONAL_EMAIL" >> .env
        echo -e "${GREEN}✅ Added GOOGLE_PERSONAL_EMAIL to .env${NC}"
    fi
else
    echo -e "${RED}❌ .env file not found!${NC}"
    exit 1
fi

echo -e "\n${BLUE}Step 3: Verify service account setup${NC}"
if [ -f storage/app/google-credentials.json ]; then
    echo -e "${GREEN}✅ Service account credentials found${NC}"
    
    # Extract service account email from credentials
    SERVICE_ACCOUNT_EMAIL=$(grep -o '"client_email": "[^"]*"' storage/app/google-credentials.json | cut -d'"' -f4)
    echo -e "${BLUE}Service Account Email: $SERVICE_ACCOUNT_EMAIL${NC}"
    
    # Update .env with service account email if not set
    if ! grep -q "GOOGLE_SERVICE_ACCOUNT_EMAIL" .env; then
        echo "GOOGLE_SERVICE_ACCOUNT_EMAIL=$SERVICE_ACCOUNT_EMAIL" >> .env
        echo -e "${GREEN}✅ Added GOOGLE_SERVICE_ACCOUNT_EMAIL to .env${NC}"
    fi
else
    echo -e "${RED}❌ Service account credentials not found!${NC}"
    echo -e "${YELLOW}Please run the service account setup first:${NC}"
    echo "1. Go to Google Cloud Console"
    echo "2. Create a service account"
    echo "3. Download the JSON credentials"
    echo "4. Save as storage/app/google-credentials.json"
    exit 1
fi

echo -e "\n${BLUE}Step 4: Set up Domain-Wide Delegation (Required for Personal Accounts)${NC}"
echo -e "${YELLOW}To create real Google Meet conferences with a personal account, you need:${NC}"
echo ""
echo "1. Go to Google Cloud Console → IAM & Admin → Service Accounts"
echo "2. Find your service account: $SERVICE_ACCOUNT_EMAIL"
echo "3. Click 'Edit' (pencil icon)"
echo "4. Check 'Enable Google Workspace Domain-wide Delegation'"
echo "5. Add these OAuth scopes:"
echo "   - https://www.googleapis.com/auth/calendar"
echo "   - https://www.googleapis.com/auth/calendar.events"
echo "6. Save the changes"
echo ""
echo -e "${YELLOW}Note: This requires Google Workspace (G Suite) or special permissions for personal accounts.${NC}"

echo -e "\n${BLUE}Step 5: Test the integration${NC}"
echo -e "${YELLOW}Run this command to test:${NC}"
echo "curl -X POST http://localhost:8000/api/v1/meetings \\"
echo "  -H \"Content-Type: application/json\" \\"
echo "  -H \"Authorization: Bearer YOUR_TOKEN\" \\"
echo "  -d '{"
echo "    \"title\": \"Test Meeting\","
echo "    \"description\": \"Testing Google Meet integration\","
echo "    \"start_time\": \"2025-09-15 10:00:00\","
echo "    \"end_time\": \"2025-09-15 12:00:00\","
echo "    \"timezone\": \"UTC\","
echo "    \"max_attendees\": 50,"
echo "    \"training_id\": 1,"
echo "    \"is_recurring\": false"
echo "  }'"

echo -e "\n${GREEN}🎉 Setup Complete!${NC}"
echo -e "${BLUE}Your Google Meet integration is now configured to use your personal account: $PERSONAL_EMAIL${NC}"
echo -e "${YELLOW}Users don't need to log in - all meetings will be created from your account!${NC}"

echo -e "\n${BLUE}Next Steps:${NC}"
echo "1. Complete the Domain-Wide Delegation setup in Google Cloud Console"
echo "2. Test the meeting creation endpoint"
echo "3. Check your Google Calendar for created meetings with Google Meet links"
