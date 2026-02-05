<?php

namespace App\Services;

use Carbon\Carbon;

class LocalizationService
{
    public function getLocalContext(): string
    {
        $location = env('AGENT_LOCATION', 'Sydney, Australia');
        $timezone = env('AGENT_TIMEZONE', 'Australia/Sydney');
        
        // Set PHP timezone temporarily to get correct time
        $now = Carbon::now($timezone);
        
        $context = "🌍 **LOCATION CONTEXT**\n";
        $context .= "- **Current Location:** $location\n";
        $context .= "- **Local Time:** " . $now->format('l, F j, Y g:i A') . " ({$now->timezoneName})\n";
        $context .= "- **Currency:** AUD (Australian Dollar)\n";
        $context .= "- **Language:** English (Australian/British spelling preferred).\n";
        $context .= "- **Legal Framework:** Adhere to Australian Privacy Principles (APP).\n";
        $context .= "- **Cultural Nuance:** Be direct but polite. Use local terminology where appropriate (e.g., 'mate' is acceptable in casual persona, but keep professional in formal tasks).\n";

        return $context;
    }
}
