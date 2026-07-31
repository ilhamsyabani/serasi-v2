<?php

namespace App\Http\Controllers\Internal\Kabalai;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePermohonanRequest;
use App\Mail\AkunBaruMail;
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
use Illuminate\Support\Facades\Mail;

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

        $noReg = 'PBF/DENAH/' . date('Y') . '/' . str_pad(Permohonan::count() + 1, 5, '0', STR_PAD_LEFT);

        try {
            Mail::to($pbf->email)->send(new AkunBaruMail($username, $password, $pbf->nama_pbf));
            $statusEmail = Notifikasi::STATUS_TERKIRIM;
        } catch (\Throwable) {
            $statusEmail = Notifikasi::STATUS_GAGAL;
        }
        Notifikasi::create([
            'permohonan_id' => null,
            'tujuan_tipe'   => Notifikasi::TUJUAN_PEMOHON,
            'tujuan_id'     => $pbf->id,
            'channel'       => Notifikasi::CHANNEL_EMAIL,
            'template_kode' => 'AKUN_BARU',
            'status_kirim'  => $statusEmail,
        ]);

        // WA kredensial via template
        $permTemp = new Permohonan();
        $permTemp->pbf = $pbf;
        $permTemp->no_registrasi = $noReg;
        $permTemp->nama_pbf_snapshot = $pbf->nama_pbf;
        app(NotifikasiService::class)->kirim(
            $permTemp,
            Notifikasi::TUJUAN_PEMOHON,
            $pbf->id,
            Notifikasi::CHANNEL_WHATSAPP,
            'AKUN_BARU',
            null,
            [
                '{{username}}' => $username,
                '{{password}}' => $password,
                '{{app_url}}'  => config('app.url'),
            ]
        );

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

        return redirect()->route('internal.kabalai.permohonan.index')->with('success', 'Permohonan berhasil dibuat.');
    }

    public function show(Permohonan $permohonan)
    {
        abort_unless($permohonan->kepala_balai_id === Auth::id(), 403);
        return view('internal.kabalai.permohonan.show', compact('permohonan'));
    }

    public function edit(Permohonan $permohonan)
    {
        abort_unless($permohonan->kepala_balai_id === Auth::id(), 403);
        return view('internal.kabalai.permohonan.edit', compact('permohonan'));
    }

    public function update(Request $request, Permohonan $permohonan)
    {
        abort_unless($permohonan->kepala_balai_id === Auth::id(), 403);

        $data = $request->validate([
            'nama_pbf_snapshot' => 'required|string|max:200',
            'email_snapshot' => 'required|email|max:150',
            'no_wa_snapshot' => 'required|string|max:20',
        ]);

        $permohonan->update($data);

        return redirect()->route('internal.kabalai.permohonan.index')->with('success', 'Permohonan berhasil diperbarui.');
    }
}
