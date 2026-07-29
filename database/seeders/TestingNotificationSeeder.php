<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class TestingNotificationSeeder extends Seeder
{
    public function run(): void
    {
        $noWa = '08996372950';
        $noWaNormalized = preg_replace('/[^0-9+]/', '', $noWa);
        if (str_starts_with($noWaNormalized, '0')) {
            $noWaNormalized = '62' . substr($noWaNormalized, 1);
        }

        $apiUrl  = config('services.wa_gateway.url');
        $token   = config('services.wa_gateway.token');
        $secret  = config('services.wa_gateway.secret_key');

        $pesan = "🔐 *Test dari Serasi*\n\nSeeder test berhasil!";

        echo "=== Testing WA Notification ===\n";
        echo "Nomor   : $noWa\n";
        echo "Normalized: $noWaNormalized\n";
        echo "URL     : $apiUrl\n";

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => $token,
                    'Secret-Key'  => $secret,
                    'Content-Type'=> 'application/json',
                ])
                ->post($apiUrl, [
                    'phone'   => $noWaNormalized,
                    'message' => $pesan,
                ]);

            $body = $response->json();

            if ($response->successful() && ($body['status'] ?? false)) {
                echo "Status  : BERHASIL\n";
                echo "Message : " . ($body['message'] ?? '-') . "\n";
                echo "ID      : " . ($body['data']['messages'][0]['id'] ?? '-') . "\n";
                echo "Quota   : " . ($body['data']['quota'] ?? '-') . "\n";
            } else {
                echo "Status  : GAGAL\n";
                echo "Message : " . ($body['message'] ?? $response->body()) . "\n";
            }
        } catch (\Throwable $e) {
            echo "ERROR   : " . $e->getMessage() . "\n";
        }
    }
}
