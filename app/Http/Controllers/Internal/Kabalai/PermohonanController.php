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
            ->where('kepala_balai_id', Auth::id())
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

        $pbfByNib = Pbf::where('nib', $data['nib'])->first();

        if ($pbfByNib && $pbfByNib->no_whatsapp !== $data['no_whatsapp']) {
            return redirect()->back()
                ->withInput($request->except('_token', 'no_whatsapp'))
                ->with('warning', 'NIB sudah terdaftar dengan nomor WhatsApp lain (' . $pbfByNib->no_whatsapp . '). Silakan gunakan nomor yang sama atau hubungi Administrator IT.');
        }

        $pbfByWa = Pbf::where('no_whatsapp', $data['no_whatsapp'])->first();
        if ($pbfByWa && $pbfByWa->nib !== $data['nib']) {
            return redirect()->back()
                ->withInput($request->except('_token', 'no_whatsapp'))
                ->with('warning', 'Nomor WhatsApp ini sudah terdaftar untuk NIB lain (' . $pbfByWa->nib . ' — ' . $pbfByWa->nama_pbf . ').');
        }

        $pbf = Pbf::updateOrCreate(
            ['nib' => $data['nib']],
            [
                'nama_pbf' => $data['nama_pbf'],
                'alamat' => $data['alamat'] ?? null,
                'email' => $data['email'],
                'no_whatsapp' => $data['no_whatsapp'],
                'password_hash' => \Illuminate\Support\Facades\Hash::make($password),
                'otp_terverifikasi' => false,
            ]
        );

        $username = $data['email'];

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
                'path_file' => $file->store('dokumen_permohonan', 'public'),
                'ukuran_file_kb' => (int) ceil($file->getSize() / 1024),
                'mime_type' => $file->getMimeType(),
                'uploaded_by_user_id' => Auth::id(),
                'uploaded_at' => now(),
            ]);
        }

        app(StatusTransitionService::class)->transisi($permohonan, Permohonan::STATUS_PENGAJUAN, 'Pengajuan baru', Auth::user(), 'internal');

        $notif = app(NotifikasiService::class);
        // Kirim kredensial akun baru via WA + email
        $notif->kirimAkunBaru($permohonan, $username, $password);
        // Kirim notifikasi pengajuan baru via WA saja
        $notif->kirimBatch($permohonan, [
            [Notifikasi::TUJUAN_PEMOHON, $pbf->id, Notifikasi::CHANNEL_WHATSAPP],
        ], 'PENGAJUAN_BARU');

        return redirect()->route('internal.kabalai.permohonan.index')->with('success', 'Permohonan berhasil dibuat.');
    }

    public function show(Permohonan $permohonan)
    {
        abort_unless($permohonan->kepala_balai_id === Auth::id(), 403);
        $permohonan->load('pbf');
        return view('internal.kabalai.permohonan.show', compact('permohonan'));
    }

    public function edit(Permohonan $permohonan)
    {
        abort_unless($permohonan->kepala_balai_id === Auth::id(), 403);
        $permohonan->load('dokumen', 'pbf');
        return view('internal.kabalai.permohonan.edit', compact('permohonan'));
    }

    public function update(Request $request, Permohonan $permohonan)
    {
        abort_unless($permohonan->kepala_balai_id === Auth::id(), 403);

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
                \Illuminate\Support\Facades\Storage::disk('public')->delete($existing->path_file);
                $existing->update([
                    'nama_file_asli' => $file->getClientOriginalName(),
                    'path_file' => $file->store('dokumen_permohonan', 'public'),
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
                    'path_file' => $file->store('dokumen_permohonan', 'public'),
                    'ukuran_file_kb' => (int) ceil($file->getSize() / 1024),
                    'mime_type' => $file->getMimeType(),
                    'uploaded_by_user_id' => Auth::id(),
                    'uploaded_at' => now(),
                ]);
            }
        }

        return redirect()->route('internal.kabalai.permohonan.show', $permohonan)->with('success', 'Permohonan berhasil diperbarui.');
    }

    public function destroy(Permohonan $permohonan)
    {
        abort_unless($permohonan->kepala_balai_id === Auth::id(), 403);
        abort_unless($permohonan->status_saat_ini === Permohonan::STATUS_PENGAJUAN, 403);

        \DB::transaction(function () use ($permohonan) {
            // Hapus semua relasi terkait
            $permohonan->dokumen()->delete();
            $permohonan->disposisi()->delete();
            $permohonan->distribusi()->delete();
            $permohonan->evaluasi()->delete();
            $permohonan->revisi()->delete();
            $permohonan->suratPengesahan()->delete();
            $permohonan->statusLog()->delete();
            $permohonan->notifikasi()->delete();
            $permohonan->reassignmentLog()->delete();
            \DB::table('audit_trail')->where('permohonan_id', $permohonan->id)->delete();

            $permohonan->delete();
        });

        return redirect()->route('internal.kabalai.permohonan.index')->with('success', 'Permohonan berhasil dihapus.');
    }

    private function isDuplicateKeyError(\Illuminate\Database\QueryException $e): bool
    {
        $code = $e->getCode();
        return $code === '23000' || $code === '23505';
    }
}
