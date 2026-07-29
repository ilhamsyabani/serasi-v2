<div style="font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="text-align: center; margin-bottom: 24px;">
        <div style="display: inline-block; background: #166534; color: white; padding: 10px 16px; border-radius: 8px; font-weight: bold; font-size: 18px; margin-bottom: 4px;">BBPOM</div>
        <p style="color: #6b7280; font-size: 12px; margin: 0;">Pengesahan Denah PBF</p>
    </div>

    <p style="color: #374151; line-height: 1.6;">Yth. <strong>{{ $namaPbf }}</strong>,</p>
    <p style="color: #374151; line-height: 1.6;">Akun Portal Pelaku Usaha Anda telah dibuat. Silakan login menggunakan kredensial berikut:</p>

    <div style="margin: 24px 0; padding: 20px; background: #f3f4f6; border-radius: 8px; border-left: 4px solid #166534;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 6px 0; color: #6b7280; font-size: 14px; width: 100px;">Username</td>
                <td style="padding: 6px 0; font-weight: bold; color: #111827; font-family: monospace; font-size: 14px;">{{ $username }}</td>
            </tr>
            <tr>
                <td style="padding: 6px 0; color: #6b7280; font-size: 14px;">Password</td>
                <td style="padding: 6px 0; font-weight: bold; color: #111827; font-family: monospace; font-size: 14px;">{{ $password }}</td>
            </tr>
        </table>
    </div>

    <p style="color: #374151; line-height: 1.6;"><strong>Petunjuk Login:</strong></p>
    <ol style="color: #374151; line-height: 1.8; padding-left: 20px;">
        <li>Buka portal di <strong>{{ config('app.url') }}</strong></li>
        <li>Login menggunakan <strong>email</strong> atau <strong>nomor WhatsApp</strong> Anda sebagai username.</li>
        <li>Masukkan password di atas.</li>
        <li>Pada login pertama, Anda akan diminta memasukkan kode OTP yang dikirim via WhatsApp.</li>
    </ol>

    <p style="color: #dc2626; font-weight: bold; font-size: 14px; margin-top: 16px;">Jangan berikan kredensial ini kepada siapapun.</p>

    <div style="margin-top: 24px; padding: 16px; background: #f3f4f6; border-radius: 8px;">
        <p style="color: #6b7280; font-size: 12px; margin: 0;">
            Email ini dikirim secara otomatis oleh Sistem Pengesahan Denah PBF — BBPOM.<br>
            Mohon jangan membalas email ini.
        </p>
    </div>
</div>
