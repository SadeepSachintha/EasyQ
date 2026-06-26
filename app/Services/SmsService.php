<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    /**
     * Send an SMS message
     */
    public static function send($phone, $message)
    {
        if (empty($phone)) {
            return false;
        }

        $driver = config('services.sms.driver', 'log');

        if ($driver === 'twilio') {
            return self::sendTwilio($phone, $message);
        }

        // Default to Log driver
        return self::sendLog($phone, $message);
    }

    /**
     * Send using Twilio REST API
     */
    protected static function sendTwilio($phone, $message)
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $from = config('services.twilio.from');

        if (empty($sid) || empty($token) || empty($from)) {
            Log::warning('Twilio configuration is missing. Falling back to Log driver.');

            return self::sendLog($phone, $message);
        }

        try {
            $response = Http::asForm()
                ->withBasicAuth($sid, $token)
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                    'To' => $phone,
                    'From' => $from,
                    'Body' => $message,
                ]);

            if ($response->successful()) {
                Log::info("Twilio SMS sent successfully to {$phone}");

                return true;
            }

            Log::error("Twilio SMS failed to {$phone}: ".$response->body());

            return false;
        } catch (\Exception $e) {
            Log::error('Twilio SMS error: '.$e->getMessage());

            return false;
        }
    }

    /**
     * Log SMS to file for testing
     */
    protected static function sendLog($phone, $message)
    {
        $logPath = storage_path('logs/sms.log');
        $dir = dirname($logPath);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] TO: {$phone} | MESSAGE: {$message}\n";

        file_put_contents($logPath, $logEntry, FILE_APPEND);
        Log::info("Mock SMS logged to sms.log for {$phone}");

        return true;
    }
}
