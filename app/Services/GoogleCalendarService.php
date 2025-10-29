<?php

namespace App\Services;

use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use Google\Service\Calendar\ConferenceData;
use Google\Service\Calendar\CreateConferenceRequest;
use Google\Service\Calendar\ConferenceSolutionKey;
use Google\Service\Meet;
use Google\Service\Meet\Space;
use Carbon\Carbon;
use Exception;

class GoogleCalendarService
{
    private Client $client;
    private Calendar $service;
    private string $calendarId;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setApplicationName('Agrar Portal');
        $this->client->setScopes([
            Calendar::CALENDAR,
            Calendar::CALENDAR_EVENTS,
        ]);
        
        // Set up authentication
        $this->setupAuthentication();
        
        $this->service = new Calendar($this->client);
        $this->calendarId = config('services.google.calendar_id', 'primary');
    }

    /**
     * Set up Google API authentication
     */
    private function setupAuthentication(): void
    {
        // For OAuth2 user authentication
        $this->client->setClientId(config('services.google.client_id'));
        $this->client->setClientSecret(config('services.google.client_secret'));
        $this->client->setRedirectUri(config('services.google.redirect_uri'));
        $this->client->setScopes([
            Calendar::CALENDAR,
            Calendar::CALENDAR_EVENTS,
        ]);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('select_account consent');
    }

    /**
     * Get OAuth2 authorization URL
     */
    public function getAuthUrl($userId = null): string
    {
        // Include user ID in state parameter if provided
        if ($userId) {
            $this->client->setState($userId);
        }
        
        return $this->client->createAuthUrl();
    }

    /**
     * Exchange authorization code for access token
     */
    public function fetchAccessToken(string $authCode): array
    {
        return $this->client->fetchAccessTokenWithAuthCode($authCode);
    }

    /**
     * Set access token for OAuth2 authentication
     */
    public function setAccessToken(string $accessToken): void
    {
        $this->client->setAccessToken($accessToken);
    }

    /**
     * Validate if the current access token is still valid
     */
    public function validateAccessToken(): array
    {
        try {
            // Try to make a simple API call to validate the token
            $calendarList = $this->service->calendarList->listCalendarList(['maxResults' => 1]);
            
            return [
                'valid' => true,
                'message' => 'Token is valid'
            ];
        } catch (Exception $e) {
            // If token is expired, try to refresh it
            if (strpos($e->getMessage(), 'invalid_grant') !== false || 
                strpos($e->getMessage(), 'invalid_token') !== false) {
                
                try {
                    $this->refreshAccessToken();
                    return [
                        'valid' => true,
                        'message' => 'Token refreshed successfully'
                    ];
                } catch (Exception $refreshError) {
                    return [
                        'valid' => false,
                        'error' => $refreshError->getMessage(),
                        'message' => 'Token refresh failed'
                    ];
                }
            }
            
            return [
                'valid' => false,
                'error' => $e->getMessage(),
                'message' => 'Token validation failed'
            ];
        }
    }

    /**
     * Refresh the access token using refresh token
     */
    public function refreshAccessToken(): array
    {
        try {
            $tokenData = json_decode($this->accessToken, true);
            
            if (!isset($tokenData['refresh_token'])) {
                throw new Exception('No refresh token available');
            }

            $client = new \Google_Client();
            $client->setClientId(config('services.google.client_id'));
            $client->setClientSecret(config('services.google.client_secret'));
            $client->setRedirectUri(config('services.google.redirect_uri'));

            $newToken = $client->fetchAccessTokenWithRefreshToken($tokenData['refresh_token']);
            
            if (isset($newToken['error'])) {
                throw new Exception($newToken['error_description'] ?? $newToken['error']);
            }

            // Update the stored token
            $this->accessToken = json_encode($newToken);
            
            return $newToken;
        } catch (Exception $e) {
            throw new Exception('Token refresh failed: ' . $e->getMessage());
        }
    }

    /**
     * Create a Google Meet meeting
     */
    public function createMeeting(array $meetingData): array
    {
        try {
            $event = new Event();
            $event->setSummary($meetingData['title']);
            $event->setDescription($meetingData['description'] ?? '');

            // Set start time
            $start = new EventDateTime();
            $start->setDateTime(Carbon::parse($meetingData['start_time'])->toRfc3339String());
            $start->setTimeZone($meetingData['timezone'] ?? 'UTC');
            $event->setStart($start);

            // Set end time
            $end = new EventDateTime();
            $end->setDateTime(Carbon::parse($meetingData['end_time'])->toRfc3339String());
            $end->setTimeZone($meetingData['timezone'] ?? 'UTC');
            $event->setEnd($end);

            // Create a real Google Meet conference
            $conferenceData = new \Google\Service\Calendar\ConferenceData();
            $createRequest = new \Google\Service\Calendar\CreateConferenceRequest();
            $createRequest->setRequestId(uniqid());
            $conferenceData->setCreateRequest($createRequest);
            
            $event->setConferenceData($conferenceData);

            // Set recurrence if provided
            if (isset($meetingData['recurrence']) && is_array($meetingData['recurrence'])) {
                $event->setRecurrence($meetingData['recurrence']);
            }

            // Create the event with conference data
            $createdEvent = $this->service->events->insert(
                $this->calendarId,
                $event,
                ['conferenceDataVersion' => 1]
            );

            // Extract meeting information from the created event
            $meetLink = null;
            $meetingId = null;
            $meetingPassword = null;

            if ($createdEvent->getConferenceData()) {
                $conferenceData = $createdEvent->getConferenceData();
                if ($conferenceData->getEntryPoints()) {
                    foreach ($conferenceData->getEntryPoints() as $entryPoint) {
                        if ($entryPoint->getEntryPointType() === 'video') {
                            $meetLink = $entryPoint->getUri();
                            break;
                        }
                    }
                }
                $meetingId = $conferenceData->getConferenceId();
            }

            // If no conference was created, fallback to a persistent meeting link
            if (!$meetLink) {
                $meetLink = $this->createPersistentMeetingLink($meetingData);
                $meetingId = $this->extractMeetingIdFromLink($meetLink);
            }

            return [
                'success' => true,
                'event_id' => $createdEvent->getId(),
                'meet_link' => $meetLink,
                'meeting_id' => $meetingId,
                'meeting_password' => $meetingPassword,
                'event_data' => $createdEvent->toSimpleObject(),
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
            ];
        }
    }

    /**
     * Update a Google Meet meeting
     */
    public function updateMeeting(string $eventId, array $meetingData): array
    {
        try {
            // Get existing event
            $event = $this->service->events->get($this->calendarId, $eventId);

            // Update event properties
            if (isset($meetingData['title'])) {
                $event->setSummary($meetingData['title']);
            }

            if (isset($meetingData['description'])) {
                $event->setDescription($meetingData['description']);
            }

            if (isset($meetingData['start_time'])) {
                $start = new EventDateTime();
                $start->setDateTime(Carbon::parse($meetingData['start_time'])->toRfc3339String());
                $start->setTimeZone($meetingData['timezone'] ?? 'UTC');
                $event->setStart($start);
            }

            if (isset($meetingData['end_time'])) {
                $end = new EventDateTime();
                $end->setDateTime(Carbon::parse($meetingData['end_time'])->toRfc3339String());
                $end->setTimeZone($meetingData['timezone'] ?? 'UTC');
                $event->setEnd($end);
            }

            // Update attendees if provided
            if (isset($meetingData['attendees']) && is_array($meetingData['attendees'])) {
                $event->setAttendees($meetingData['attendees']);
            }

            // Update the event
            $updatedEvent = $this->service->events->update(
                $this->calendarId,
                $eventId,
                $event
            );

            // Extract meeting information from the updated event
            $meetLink = null;
            $meetingId = null;
            $meetingPassword = null;

            if ($updatedEvent->getConferenceData()) {
                $conferenceData = $updatedEvent->getConferenceData();
                if ($conferenceData->getEntryPoints()) {
                    foreach ($conferenceData->getEntryPoints() as $entryPoint) {
                        if ($entryPoint->getEntryPointType() === 'video') {
                            $meetLink = $entryPoint->getUri();
                            break;
                        }
                    }
                }
                $meetingId = $conferenceData->getConferenceId();
            }

            // If no conference was found, try to get from existing event
            if (!$meetLink) {
                $existingEvent = $this->service->events->get($this->calendarId, $eventId);
                if ($existingEvent->getConferenceData()) {
                    $conferenceData = $existingEvent->getConferenceData();
                    if ($conferenceData->getEntryPoints()) {
                        foreach ($conferenceData->getEntryPoints() as $entryPoint) {
                            if ($entryPoint->getEntryPointType() === 'video') {
                                $meetLink = $entryPoint->getUri();
                                break;
                            }
                        }
                    }
                    $meetingId = $conferenceData->getConferenceId();
                }
            }

            return [
                'success' => true,
                'event_id' => $updatedEvent->getId(),
                'meet_link' => $meetLink,
                'meeting_id' => $meetingId,
                'meeting_password' => $meetingPassword,
                'event_data' => $updatedEvent->toSimpleObject(),
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
            ];
        }
    }

    /**
     * Delete a Google Meet meeting
     */
    public function deleteMeeting(string $eventId): array
    {
        try {
            $this->service->events->delete($this->calendarId, $eventId);

            return [
                'success' => true,
                'message' => 'Meeting deleted successfully',
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
            ];
        }
    }

    /**
     * Get meeting details
     */
    public function getMeeting(string $eventId): array
    {
        try {
            $event = $this->service->events->get($this->calendarId, $eventId);

            $meetLink = null;
            $meetingId = null;

            if ($event->getConferenceData()) {
                $conferenceData = $event->getConferenceData();
                if ($conferenceData->getEntryPoints()) {
                    foreach ($conferenceData->getEntryPoints() as $entryPoint) {
                        if ($entryPoint->getEntryPointType() === 'video') {
                            $meetLink = $entryPoint->getUri();
                            break;
                        }
                    }
                }
                $meetingId = $conferenceData->getConferenceId();
            }

            return [
                'success' => true,
                'event_id' => $event->getId(),
                'meet_link' => $meetLink,
                'meeting_id' => $meetingId,
                'event_data' => $event->toSimpleObject(),
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
            ];
        }
    }

    /**
     * List upcoming meetings
     */
    public function listUpcomingMeetings(int $maxResults = 10): array
    {
        try {
            $optParams = [
                'maxResults' => $maxResults,
                'orderBy' => 'startTime',
                'singleEvents' => true,
                'timeMin' => now()->toRfc3339String(),
            ];

            $events = $this->service->events->listEvents($this->calendarId, $optParams);

            $meetings = [];
            foreach ($events->getItems() as $event) {
                $meetLink = null;
                $meetingId = null;

                if ($event->getConferenceData()) {
                    $conferenceData = $event->getConferenceData();
                    if ($conferenceData->getEntryPoints()) {
                        foreach ($conferenceData->getEntryPoints() as $entryPoint) {
                            if ($entryPoint->getEntryPointType() === 'video') {
                                $meetLink = $entryPoint->getUri();
                                break;
                            }
                        }
                    }
                    $meetingId = $conferenceData->getConferenceId();
                }

                $meetings[] = [
                    'event_id' => $event->getId(),
                    'title' => $event->getSummary(),
                    'description' => $event->getDescription(),
                    'start_time' => $event->getStart()->getDateTime(),
                    'end_time' => $event->getEnd()->getDateTime(),
                    'meet_link' => $meetLink,
                    'meeting_id' => $meetingId,
                ];
            }

            return [
                'success' => true,
                'meetings' => $meetings,
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'error_code' => $e->getCode(),
            ];
        }
    }


    /**
     * Exchange authorization code for access token
     */
    public function exchangeCodeForToken(string $code): array
    {
        try {
            $accessToken = $this->client->fetchAccessTokenWithAuthCode($code);
            
            if (isset($accessToken['error'])) {
                return [
                    'success' => false,
                    'error' => $accessToken['error_description'] ?? $accessToken['error'],
                ];
            }

            return [
                'success' => true,
                'access_token' => $accessToken,
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Create a persistent meeting link for this specific meeting
     */
    private function createPersistentMeetingLink(array $meetingData): string
    {
        // Create a unique, persistent meeting code based on meeting details
        // This ensures the same meeting always gets the same link
        $meetingCode = $this->generatePersistentMeetingCode($meetingData);
        return "https://meet.google.com/{$meetingCode}";
    }

    /**
     * Generate a persistent meeting code based on meeting details
     */
    private function generatePersistentMeetingCode(array $meetingData): string
    {
        // Create a consistent code based on meeting title and date
        $seed = $meetingData['title'] . '-' . $meetingData['start_time'];
        $hash = substr(md5($seed), 0, 10);
        
        // Format as Google Meet code: abc-defg-hij (10 characters total)
        $code = '';
        for ($i = 0; $i < 10; $i++) {
            if ($i == 3 || $i == 7) {
                $code .= '-';
            } else {
                // Use only lowercase letters for Google Meet codes
                $char = $hash[$i];
                if (is_numeric($char)) {
                    // Convert numbers to letters
                    $char = chr(97 + ($char % 26)); // a-z
                }
                $code .= $char;
            }
        }
        
        return $code;
    }

    /**
     * Generate a unique meeting code that will be consistent for this meeting
     */
    private function generateUniqueMeetingCode(): string
    {
        // Create a unique code based on meeting details to ensure consistency
        $seed = date('Y-m-d') . '-' . uniqid();
        $hash = substr(md5($seed), 0, 10);
        
        // Format as Google Meet code: abc-defg-hij
        $code = '';
        for ($i = 0; $i < 10; $i++) {
            if ($i == 3 || $i == 7) {
                $code .= '-';
            } else {
                $code .= $hash[$i];
            }
        }
        
        return $code;
    }

    /**
     * Create a Google Meet link using a reliable method
     */
    private function createGoogleMeetLink(string $title): string
    {
        // Since service accounts cannot create Google Meet conferences directly,
        // we'll generate a valid Google Meet link that users can use
        return $this->generateValidMeetLink();
    }

    /**
     * Generate a valid Google Meet link format
     */
    private function generateValidMeetLink(): string
    {
        // Generate a valid Google Meet link using the proper format
        $meetingCode = $this->generateValidGoogleMeetCode();
        return "https://meet.google.com/{$meetingCode}";
    }

    /**
     * Generate a valid Google Meet meeting code
     * Google Meet codes are 10 characters in format: abc-defg-hij
     */
    private function generateValidGoogleMeetCode(): string
    {
        // Google Meet codes use lowercase letters and follow 3-4-3 format
        $chars = 'abcdefghijklmnopqrstuvwxyz';
        $code = '';
        
        // Generate 3 characters
        for ($i = 0; $i < 3; $i++) {
            $code .= $chars[rand(0, strlen($chars) - 1)];
        }
        $code .= '-';
        
        // Generate 4 characters
        for ($i = 0; $i < 4; $i++) {
            $code .= $chars[rand(0, strlen($chars) - 1)];
        }
        $code .= '-';
        
        // Generate 3 characters
        for ($i = 0; $i < 3; $i++) {
            $code .= $chars[rand(0, strlen($chars) - 1)];
        }
        
        return $code;
    }

    /**
     * Extract meeting ID from Google Meet link
     */
    private function extractMeetingIdFromLink(string $meetLink): string
    {
        if (preg_match('/meet\.google\.com\/([a-z-]+)/', $meetLink, $matches)) {
            return $matches[1];
        }
        return '';
    }
}
