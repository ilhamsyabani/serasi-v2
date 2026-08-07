<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class WhatsappSender
{
    private string $apiUrl;
    private string $token;
    private string $secretKey;
    private int $timeout;

    public function __construct()
    {
        $this->apiUrl    = config('services.wa_gateway.url');
        $this->token     = config('services.wa_gateway.token');
        $this->secretKey = config('services.wa_gateway.secret_key');
        $this->timeout   = config('services.wa_gateway.timeout', 30);
    }

    public function send(string $noWa, string $pesan): bool
    {
        if (empty($this->apiUrl) || empty($this->token)) {
            Log::warning('WhatsappSender: WA_GATEWAY_URL atau WA_GATEWAY_TOKEN belum dikonfigurasi.');
            return false;
        }

        $noWa = $this->normalizePhone($noWa);

        try {
            // Match format API production: x-www-form-urlencoded, Authorization: token+secret
            $data = [
                'phone'   => $noWa,
                'message' => $pesan,
            ];

            $ch = curl_init($this->apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/x-www-form-urlencoded',
                'Authorization: ' . $this->token . $this->secretKey,
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (curl_errno($ch)) {
                $error = curl_error($ch);
                Log::error('WhatsappSender: cURL Error', [
                    'no_wa'  => $noWa,
                    'error'  => $error,
                    'api_url' => $this->apiUrl,
                ]);
                curl_close($ch);
                return false;
            }

            curl_close($ch);

            if ($httpCode != 200) {
                Log::error('WhatsappSender: Gagal kirim', [
                    'no_wa'    => $noWa,
                    'http_code' => $httpCode,
                    'response'  => $response,
                ]);
                return false;
            }

            Log::info("WhatsappSender: Pesan berhasil dikirim ke $noWa", [
                'response' => $response,
            ]);
            return true;
        } catch (\Throwable $e) {
            Log::error('WhatsappSender: Exception', [
                'no_wa' => $noWa,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function sendWithRetry(string $noWa, string $pesan, int $maxRetries = 3): bool
    {
        for ($i = 1; $i <= $maxRetries; $i++) {
            if ($this->send($noWa, $pesan)) {
                return true;
            }
            if ($i < $maxRetries) {
                usleep(500000 * $i);
            }
        }
        return false;
    }

    private function normalizePhone(string $noWa): string
    {
        $noWa = preg_replace('/[^0-9+]/', '', $noWa);
        if (str_starts_with($noWa, '0')) {
            $noWa = '62' . substr($noWa, 1);
        }
        if (!str_starts_with($noWa, '62')) {
            $noWa = '62' . $noWa;
        }
        // Tanpa @c.us — sesuai format API production
        return $noWa;
    }
}
