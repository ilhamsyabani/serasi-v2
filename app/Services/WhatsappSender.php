<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappSender
{
    private string $apiUrl;
    private string $token;
    private string $secretKey;
    private int $timeout;
    private bool $skipSslVerify;
    private string $authPrefix;
    private string $phoneSuffix;

    public function __construct()
    {
        $this->apiUrl        = config('services.wa_gateway.url');
        $this->token         = config('services.wa_gateway.token');
        $this->secretKey     = config('services.wa_gateway.secret_key');
        $this->timeout       = config('services.wa_gateway.timeout', 30);
        $this->skipSslVerify = config('services.wa_gateway.skip_ssl_verify', false);
        $this->authPrefix    = config('services.wa_gateway.auth_prefix', 'Bearer ');
        $this->phoneSuffix   = config('services.wa_gateway.phone_suffix', '@c.us');
    }

    public function send(string $noWa, string $pesan): bool
    {
        if (empty($this->apiUrl) || empty($this->token)) {
            Log::warning('WhatsappSender: WA_GATEWAY_URL atau WA_GATEWAY_TOKEN belum dikonfigurasi.');
            return false;
        }

        $noWa = $this->normalizePhone($noWa);

        try {
            $http = Http::timeout($this->timeout)
                ->withHeaders([
                    'Authorization' => $this->authPrefix . $this->token,
                    'Secret-Key'   => $this->secretKey,
                    'Content-Type' => 'application/json',
                ]);

            if ($this->skipSslVerify) {
                $http = $http->withoutVerifying();
            }

            $response = $http->post($this->apiUrl, [
                'phone'   => $noWa,
                'message' => $pesan,
            ]);

            if ($response->successful()) {
                Log::info("WhatsappSender: Pesan berhasil dikirim ke $noWa", [
                    'response' => $response->json(),
                ]);
                return true;
            }

            Log::error("WhatsappSender: Gagal kirim ke $noWa", [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return false;
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('WhatsappSender: Connection Exception - WA gateway tidak bisa dijangkau', [
                'no_wa'   => $noWa,
                'error'   => $e->getMessage(),
                'api_url' => $this->apiUrl,
                'hint'    => 'Cek apakah server hosting bisa mengakses ' . $this->apiUrl,
            ]);
            return false;
        } catch (\Throwable $e) {
            Log::error('WhatsappSender: Exception', [
                'no_wa' => $noWa,
                'error' => $e->getMessage(),
                'class' => get_class($e),
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
        return $noWa . $this->phoneSuffix;
    }
}
