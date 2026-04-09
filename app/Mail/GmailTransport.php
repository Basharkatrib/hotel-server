<?php

namespace App\Mail;

use Google\Auth\Credentials\UserRefreshCredentials;
use GuzzleHttp\Client;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;

class GmailTransport extends AbstractTransport
{
    protected function doSend(SentMessage $message): void
    {
        $credentials = new UserRefreshCredentials(
            'https://www.googleapis.com/auth/gmail.send',
            [
                'client_id'     => config('services.gmail.client_id'),
                'client_secret' => config('services.gmail.client_secret'),
                'refresh_token' => config('services.gmail.refresh_token'),
            ]
        );

        $token = $credentials->fetchAuthToken();
        $accessToken = $token['access_token'];

        $rawMessage = base64_encode($message->toString());
        $rawMessage = str_replace(['+', '/', '='], ['-', '_', ''], $rawMessage);

        $client = new Client();
        $client->post('https://gmail.googleapis.com/gmail/v1/users/me/messages/send', [
            'headers' => [
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type'  => 'application/json',
            ],
            'json' => ['raw' => $rawMessage],
        ]);
    }

    public function __toString(): string
    {
        return 'gmail';
    }
}