<?php

namespace App\Services\Api;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Firebase\Messaging\MulticastSendReport;

class FirebaseService
{
    protected $messaging;

    public function __construct()
    {

    }
    private function getMessaging()
    {
        if ($this->messaging)
            return $this->messaging;
        // if (!class_exists(Factory::class)) {
        //     throw new \RuntimeException('Kreait not installed/loaded. Run composer install and restart workers.');
        // }
        $path = storage_path('firebase_credentials.json');
        \Log::info('firebase json', [$path]);
        if (!file_exists($path)) {
            throw new \RuntimeException("Firebase credentials not found at: {$path}");
        }
        $factory = (new Factory)->withServiceAccount($path);
        $this->messaging = $factory->createMessaging();
        return $this->messaging;
    }

    /**
     * Envía push a 1 o N tokens (multicast).
     * @param string|array $fcmToken
     * @return MulticastSendReport|string|null
     */
    public function sendPushNotification($fcmToken, string $title, string $body, array $data = [])
    {

        $messaging = $this->getMessaging();
        \Log::info('sendPushNotification', ['title' => $title, 'body' => $body, 'data' => $data, 'tokens' => is_array($fcmToken) ? count($fcmToken) : 1]);
        $notification = Notification::create($title, $body);
        $base = CloudMessage::new()->withNotification($notification)->withData($data);

        if (is_array($fcmToken)) {
            // foreach ($fcmToken as $key => $_fcmToken) {
            //    try {
            //      $messaging->send(CloudMessage::withTarget('token', (string) $_fcmToken)->withNotification($notification)->withData($data));
            //    } catch (\Throwable $th) {
            //     //throw $th;
            //     \Log::error('Throwable',[$th]);
            //    }
            // }
            $tokens = array_values(array_filter(array_unique($fcmToken)));
            if (empty($tokens)) {
                \Log::warning('sendPushNotification: empty tokens array');
                return null;
            }
            $report = $messaging->sendMulticast($base, $tokens);
            \Log::info('FCM multicast report', [
                'successes' => $report->successes()->count(),
                'failures' => $report->failures()->count(),
                'report_failures' => $report->failures(),
            ]);
            return $report;
        }

        return $messaging->send(CloudMessage::withTarget('token', (string) $fcmToken)->withNotification($notification)->withData($data));

    }
}
