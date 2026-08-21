<div style="font-family: sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
    <div style="text-align: center; margin-bottom: 24px;">
        <div style="display: inline-block; background: #166534; color: white; padding: 10px 16px; border-radius: 8px; font-weight: bold; font-size: 18px; margin-bottom: 4px;">BBPOM</div>
        <p style="color: #6b7280; font-size: 12px; margin: 0;">Pengesahan Denah PBF</p>
    </div>

    <p style="color: #374151; line-height: 1.6;">Yth. <strong>{{ $namaPbf }}</strong>,</p>
    <p style="color: #374151; line-height: 1.6;">Akun Portal Pelaku Usaha Anda telah dibuat.</p>

    <div style="margin: 20px 0; padding: 20px; background: #f3f4f6; border-radius: 8px; border-left: 4px solid #166534;">
        <p style="color: #6b7280; font-size: 12px; font-weight: bold; margin: 0 0 10px 0;">DATA PEMOHON</p>
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 16px;">
            <tr>
                <td style="padding: 4px 0; color: #6b7280; font-size: 13px; width: 110px;">No. Registrasi</td>
                <td style="padding: 4px 0; font-weight: bold; color: #111827; font-size: 13px;">{{ $noRegistrasi }}</td>
            </tr>
            <tr>
                <td style="padding: 4px 0; color: #6b7280; font-size: 13px;">NIB</td>
                <td style="padding: 4px 0; font-weight: bold; color: #111827; font-size: 13px;">{{ $nib }}</td>
            </tr>
            <tr>
                <td style="padding: 4px 0; color: #6b7280; font-size: 13px;">Alamat</td>
                <td style="padding: 4px 0; color: #111827; font-size: 13px;">{{ $alamat }}</td>
            </tr>
        </table>

        <div style="border-top: 1px dashed #d1d5db; padding-top: 12px;">
            <p style="color: #6b7280; font-size: 12px; font-weight: bold; margin: 0 0 8px 0;">DATA AKUN LOGIN</p>
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 4px 0; color: #6b7280; font-size: 13px; width: 110px;">Username</td>
                    <td style="padding: 4px 0; font-weight: bold; color: #111827; font-family: monospace; font-size: 13px;">{{ $username }}</td>
                </tr>
                <tr>
                    <td style="padding: 4px 0; color: #6b7280; font-size: 13px;">Password</td>
                    <td style="padding: 4px 0; font-weight: bold; color: #111827; font-family: monospace; font-size: 13px;">{{ $password }}</td>
                </tr>
            </table>
        </div>
    </div>

    <p style="color: #374151; line-height: 1.6;">Silakan login di: <strong>{{ config('app.url') }}</strong></p>
    <p style="color: #374151; line-height: 1.6;">Pada login pertama, kode OTP akan dikirim via WhatsApp.</p>

    <p style="color: #dc2626; font-weight: bold; font-size: 14px; margin-top: 16px;">Jangan berikan kredensial ini kepada siapapun.</p>

    <div style="margin-top: 24px; padding: 16px; background: #f3f4f6; border-radius: 8px;">
        <p style="color: #6b7280; font-size: 12px; margin: 0;">
            Email ini dikirim secara otomatis oleh Sistem Pengesahan Denah PBF — BBPOM.<br>
            Mohon jangan membalas email ini.
        </p>
    </div>
</div>
