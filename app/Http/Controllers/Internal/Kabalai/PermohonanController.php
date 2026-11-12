<?php

namespace App\Http\Controllers\Internal\Kabalai;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePermohonanRequest;
use App\Models\DokumenPermohonan;
use App\Models\Notifikasi;
use App\Models\Permohonan;
use App\Models\Pbf;
use App\Services\NotifikasiService;
use App\Services\OtpService;
use App\Services\StatusTransitionService;
use App\Traits\ValidatesFileContent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class PermohonanController extends Controller
{
    use ValidatesFileContent;
    public function index(Request $request)
    {
        $sort   = $request->get('sort', 'tanggal_pengajuan');
        $dir    = $request->get('dir', 'desc');
        $status = $request->get('status', '');
        $tanggalDari   = $request->get('tanggal_dari', '');
        $tanggalSampai = $request->get('tanggal_sampai', '');
        $search = $request->get('search');
       


        $allowedSorts = ['status_saat_ini', 'tanggal_pengajuan'];

        $query = Permohonan::select('permohonan.*','pbf.nib as nib')
            ->with(['statusLog', 'disposisi.ketuaTim', 'pbf','distribusiAktif.staff'])
            // Termasuk pengajuan ulang mandiri pemohon (kepala_balai_id NULL).
            ->untukKepalaBalai(Auth::id())
            -> leftJoin('pbf', 'permohonan.pbf_id', '=', 'pbf.id');

        if ($status !== '' && $status !== null) {
            $query->where('status_saat_ini', $status);
        }

        if ($tanggalDari) {
            $query->whereDate('tanggal_pengajuan', '>=', $tanggalDari);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama_pbf_snapshot', 'like', "%{$search}%")
                ->orWhere('no_registrasi', 'like', "%{$search}%")
                ->orWhere('nib_snapshot', 'like', "%{$search}%");
            });
        }

        if ($tanggalSampai) {
            $query->whereDate('tanggal_pengajuan', '<=', $tanggalSampai);
        }

        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $dir === 'asc' ? 'asc' : 'desc');
        }

        $permohonans = $query->latest()->paginate(10);

        return view('internal.kabalai.permohonan.index', compact(
            'permohonans', 'sort', 'dir', 'status', 'tanggalDari', 'tanggalSampai', 'search'
        ));
    }

    public function create()
    {
        return view('internal.kabalai.permohonan.create');
    }

    public function store(StorePermohonanRequest $request)
    {
        $data = $request->validated();

        $password = OtpService::generatePassword();

        $pbfByWa = Pbf::where('no_whatsapp', $data['no_whatsapp'])->first();
        if ($pbfByWa && $pbfByWa->nib !== $data['nib']) {
            return redirect()->back()
                ->withInput($request->except('_token', 'no_whatsapp'))
                ->with('warning', 'Nomor WhatsApp ini sudah terdaftar untuk NIB lain (' . $pbfByWa->nib . ' — ' . $pbfByWa->nama_pbf . ').');
        }

        // NIB yang sudah terdaftar = PBF lama mengajukan lagi: pakai akunnya yang ada.
        // Membuat baris baru akan menabrak unique `pbf.nib`, dan kredensialnya tidak
        // boleh di-reset diam-diam.
        $pbf = Pbf::where('nib', $data['nib'])->first();
        $akunBaru = $pbf === null;

        if ($akunBaru) {
            $pbf = Pbf::create([
                'nib' => $data['nib'],
                'nama_pbf' => $data['nama_pbf'],
                'alamat' => $data['alamat'] ?? null,
                'email' => $data['email'],
                'no_whatsapp' => $data['no_whatsapp'],
                'password_hash' => \Illuminate\Support\Facades\Hash::make($password),
                'otp_terverifikasi' => false,
            ]);
        }

        $username = $pbf->email;

        try {
            $noReg = Permohonan::generateNoRegistrasi();

            $permohonan = Permohonan::create([
                'no_registrasi' => $noReg,
                'pbf_id' => $pbf->id,
                'nama_pbf_snapshot' => $data['nama_pbf'],
                'nib_snapshot' => $data['nib'],
                'email_snapshot' => $data['email'],
                'no_wa_snapshot' => $data['no_whatsapp'],
                'status_saat_ini' => Permohonan::STATUS_PENGAJUAN,
                'tanggal_pengajuan' => now(),
                'kepala_balai_id' => Auth::id(),
                'dibuat_oleh_tipe' => Permohonan::DIBUAT_OLEH_KEPALA_BALAI,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            if ($this->isDuplicateKeyError($e)) {
                \Illuminate\Support\Facades\Log::critical('Duplikat no_registrasi: ' . ($noReg ?? 'unknown'), [
                    'nib' => $data['nib'],
                    'nama_pbf' => $data['nama_pbf'],
                    'kepala_balai_id' => Auth::id(),
                ]);
                return redirect()->back()
                    ->withInput($request->except('_token'))
                    ->with('error', "Gagal menyimpan: No. Registrasi '{$noReg}' sudah terdaftar. Harap coba beberapa saat lagi atau hubungi Administrator IT.");
            }
            throw $e;
        }

        // Simpan tiap dokumen yang diunggah. Kunci array = jenis_dokumen (dari
        // DokumenPermohonan::JENIS), sehingga field & jenis tidak pernah bergeser.
        foreach (array_keys(DokumenPermohonan::JENIS) as $jenis) {
            if (! $request->hasFile($jenis)) {
                continue;
            }

            $file = $request->file($jenis);
            $this->assertAllowedFileMime($file);
            DokumenPermohonan::create([
                'permohonan_id' => $permohonan->id,
                'jenis_dokumen' => $jenis,
                'nama_file_asli' => $file->getClientOriginalName(),
                'path_file' => app(\App\Services\BerkasService::class)->simpan($file, 'dokumen_permohonan'),
                'ukuran_file_kb' => (int) ceil($file->getSize() / 1024),
                'mime_type' => $file->getMimeType(),
                'uploaded_by_user_id' => Auth::id(),
                'uploaded_at' => now(),
            ]);
        }

        app(StatusTransitionService::class)->transisi($permohonan, Permohonan::STATUS_PENGAJUAN, 'Pengajuan baru', Auth::user(), 'internal');

        $notif = app(NotifikasiService::class);
        if ($akunBaru) {
            // Kirim info akun baru via WA + email (sekali saja)
            $notif->kirimAkunBaru($permohonan, $username, $password);
        } else {
            // Akun sudah ada: cukup kabari bahwa pengajuan diterima, tanpa kredensial.
            $notif->kirim($permohonan, \App\Models\Notifikasi::TUJUAN_PEMOHON, $pbf->id, \App\Models\Notifikasi::CHANNEL_WHATSAPP, 'PENGAJUAN_BARU');
            $notif->kirim($permohonan, \App\Models\Notifikasi::TUJUAN_PEMOHON, $pbf->id, \App\Models\Notifikasi::CHANNEL_EMAIL, 'PENGAJUAN_BARU');
        }

        return redirect()->route('internal.kabalai.permohonan.index')->with('success', 'Permohonan berhasil dibuat.');
    }

    public function show(Permohonan $permohonan)
    {
        // Sama dengan halaman detail lintas-role: Kepala Balai melihat seluruh permohonan
        // balai, termasuk pengajuan ulang mandiri pemohon (kepala_balai_id NULL).
        Gate::authorize('view', $permohonan);
        $permohonan->load('pbf');
        return view('internal.kabalai.permohonan.show', compact('permohonan'));
    }

    public function edit(Permohonan $permohonan)
    {
        // Policy: hanya pemilik, dan hanya selama belum berstatus akhir.
        Gate::authorize('update', $permohonan);
        $permohonan->load('dokumen', 'pbf');
        return view('internal.kabalai.permohonan.edit', compact('permohonan'));
    }

    public function update(Request $request, Permohonan $permohonan)
    {
        Gate::authorize('update', $permohonan);

        $data = $request->validate([
            'nama_pbf_snapshot' => 'required|string|max:200',
            'email_snapshot' => 'required|email|max:150',
            'no_wa_snapshot' => 'required|string|max:20',
            'alamat' => 'nullable|string|max:500',
        ]);

        $permohonan->update([
            'nama_pbf_snapshot' => $data['nama_pbf_snapshot'],
            'email_snapshot' => $data['email_snapshot'],
            'no_wa_snapshot' => $data['no_wa_snapshot'],
        ]);

        // Update alamat on PBF record
        if (isset($data['alamat']) && $permohonan->pbf) {
            $permohonan->pbf->update(['alamat' => $data['alamat']]);
        }

        // Handle document re-uploads (same as store but updates existing)
        foreach (array_keys(DokumenPermohonan::JENIS) as $jenis) {
            if (!$request->hasFile($jenis)) {
                continue;
            }
            $file = $request->file($jenis);
            $this->assertAllowedFileMime($file);

            $existing = $permohonan->dokumen()->where('jenis_dokumen', $jenis)->first();
            if ($existing) {
                app(\App\Services\BerkasService::class)->hapus($existing->path_file);
                $existing->update([
                    'nama_file_asli' => $file->getClientOriginalName(),
                    'path_file' => app(\App\Services\BerkasService::class)->simpan($file, 'dokumen_permohonan'),
                    'ukuran_file_kb' => (int) ceil($file->getSize() / 1024),
                    'mime_type' => $file->getMimeType(),
                    'versi' => ($existing->versi ?? 0) + 1,
                    'uploaded_by_user_id' => Auth::id(),
                    'uploaded_at' => now(),
                ]);
            } else {
                DokumenPermohonan::create([
                    'permohonan_id' => $permohonan->id,
                    'jenis_dokumen' => $jenis,
                    'nama_file_asli' => $file->getClientOriginalName(),
                    'path_file' => app(\App\Services\BerkasService::class)->simpan($file, 'dokumen_permohonan'),
                    'ukuran_file_kb' => (int) ceil($file->getSize() / 1024),
                    'mime_type' => $file->getMimeType(),
                    'uploaded_by_user_id' => Auth::id(),
                    'uploaded_at' => now(),
                ]);
            }
        }

        \App\Models\AuditTrail::create([
            'user_id' => Auth::id(),
            'user_type' => \App\Models\AuditTrail::USER_TYPE_INTERNAL,
            'aksi' => 'permohonan_update',
            'modul' => 'M-03',
            'permohonan_id' => $permohonan->id,
            'detail' => ['kolom' => array_keys($permohonan->getChanges())],
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('internal.kabalai.permohonan.show', $permohonan)->with('success', 'Permohonan berhasil diperbarui.');
    }

    public function destroy(Permohonan $permohonan)
    {
        abort_unless($permohonan->kepala_balai_id === Auth::id(), 403);
        abort_unless($permohonan->status_saat_ini === Permohonan::STATUS_PENGAJUAN, 403);

        // Soft delete: permohonan hilang dari seluruh daftar, tetapi dokumen, riwayat
        // status, notifikasi, dan audit trail-nya TIDAK dihapus — jejak tetap utuh.
        \DB::transaction(function () use ($permohonan) {
            $permohonan->delete();

            \App\Models\AuditTrail::create([
                'user_id' => Auth::id(),
                'user_type' => \App\Models\AuditTrail::USER_TYPE_INTERNAL,
                'aksi' => 'permohonan_hapus',
                'modul' => 'M-03',
                'permohonan_id' => $permohonan->id,
                'detail' => [
                    'no_registrasi' => $permohonan->no_registrasi,
                    'nama_pbf' => $permohonan->nama_pbf_snapshot,
                    'status_saat_dihapus' => $permohonan->status_saat_ini,
                ],
                'ip_address' => request()->ip(),
            ]);
        });

        return redirect()->route('internal.kabalai.permohonan.index')->with('success', 'Permohonan berhasil dihapus.');
    }

    private function isDuplicateKeyError(\Illuminate\Database\QueryException $e): bool
    {
        $code = $e->getCode();
        return $code === '23000' || $code === '23505';
    }
}
