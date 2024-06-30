<?php

namespace Yusef\Channels;

use Google\Client as GoogleClient;
use GuzzleHttp\Client;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;

/**
 * Class FirebaseChannel
 * @package Yusef\Channels
 */
class FirebaseChannel
{
    /**
     * const Google Client Scope
     */
    const MESSAGIN_SCOPE = 'https://www.googleapis.com/auth/firebase.messaging';

    /**
     * @var GoogleClient
     */
    private $googleClient;

    /**
     * @var mixed
     */
    protected $projectId;

    /**
     *
     */
    public function __construct()
    {
        $this->googleClient = new GoogleClient();
        $this->googleClient->useApplicationDefaultCredentials();
        $this->googleClient->addScope(self::MESSAGIN_SCOPE);

        $serviceAccount = json_decode(file_get_contents($this->getServiceAccountPath()), true);
        $this->projectId = $serviceAccount['project_id'];
    }

    /**
     * @param mixed $notifiable
     * @param Notification $notification
     */
    public function send($notifiable, Notification $notification)
    {
        /** @var FcmMessage $message */
        $message = $notification->toFcm($notifiable);

        if (is_null($message->getTo())) {
            if (!$to = $notifiable->routeNotificationFor('fcm')) {
                return;
            }

            $message->to($to);
        }

        $url = "https://fcm.googleapis.com/v1/projects/{$this->projectId}/messages:send";

        $accessToken = $this->googleClient->fetchAccessTokenWithAssertion()['access_token'];

        Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
            'Content-Type' => 'application/json',
        ])->post($url, $message->formatData());
    }

    /**
     * @return string
     */
    private function getServiceAccountPath()
    {
        return config('services.fcm.service_account_path');
    }
}
