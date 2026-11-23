<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\FirebaseException;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

class FirebaseService
{
    public function __construct(protected Messaging $messaging)
    {
    }

    /**
     * Mengirim notifikasi ke satu perangkat (FCM Token)
     */
    public function sendNotification(string $deviceToken, string $title, string $body, array $data = []): array
    {
        try {
            // 1. Membuat objek notifikasi standar
            $notification = Notification::create($title, $body);

            // 2. Konfigurasi spesifik Android (Channel ID & Click Action)
            $config = AndroidConfig::fromArray([
                'notification' => [
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'channel_id' => 'high_importance_channel', // Selaras dengan AndroidManifest di Flutter
                ],
            ]);

            // 3. Menyusun payload CloudMessage FCM v1
            $message = CloudMessage::new()
                ->withToken($deviceToken)
                ->withNotification($notification)
                ->withAndroidConfig($config)
                ->withData(array_map('strval', $data)); // Memastikan semua nilai bertipe string

            // 4. Proses pengiriman ke FCM
            $response = $this->messaging->send($message);

            return [
                'success' => true,
                'message_id' => $response['name'] ?? $response,
            ];

        } catch (MessagingException $e) {
            Log::error('Message Error: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Gagal mengirim pesan : '.$e->getMessage(),
            ];
        } catch (FirebaseException $e) {
            Log::error('Firebase Core Error: '.$e->getMessage());

            return [
                'success' => false,
                'error' => 'Gangguan layanan Firebase: '.$e->getMessage(),
            ];
        }
    }

    public function sendToTopic(string $topicName, string $title, string $body, array $data = []): array
    {
        try {
            $notification = Notification::create($title, $body);

            $config = AndroidConfig::fromArray([
                'notification' => [
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'channel_id' => 'high_importance_channel',
                ],
            ]);

            // Menyusun payload CloudMessage FCM v1 dengan target Topic
            $message = CloudMessage::new()
                ->withTopic($topicName)
                ->withNotification($notification)
                ->withAndroidConfig($config)
                ->withData(array_map('strval', $data));

            $response = $this->messaging->send($message);

            return [
                'success' => true,
                'message_id' => $response['name'] ?? $response,
            ];
        } catch (\Exception $e) {
            Log::error('Firebase Topic Error for '.$topicName.': '.$e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function sendMulticast(array $deviceTokens, string $title, string $body, array $data = []): array
    {
        if (empty($deviceTokens)) {
            return ['success' => false, 'message' => 'Tidak ada token tujuan.'];
        }

        try {
            $notification = Notification::create($title, $body);
            $config = AndroidConfig::fromArray([
                'notification' => [
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    'channel_id' => 'high_importance_channel',
                ],
            ]);

            $message = CloudMessage::new()
                ->withNotification($notification)
                ->withAndroidConfig($config)
                ->withData(array_map('strval', $data));

            $report = $this->messaging->sendMulticast($message, $deviceTokens);

            return [
                'success' => true,
                'success_count' => $report->successes()->count(),
                'failure_count' => $report->failures()->count(),
            ];
        } catch (\Exception $e) {
            Log::error('Multicast Firebase Error: '.$e->getMessage());

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
