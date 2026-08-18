<?php

namespace App\Http\Controllers\Internal\AdminIt;

use App\Http\Controllers\Controller;
use App\Models\HariLibur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HariLiburController extends Controller
{
    public function index(Request $request)
    {
        $tahun = $request->input('tahun', now()->year);
        $search = $request->input('search');

        $query = HariLibur::query()->whereYear('tanggal', $tahun);

        if ($search) {
            $query->where('keterangan', 'like', "%{$search}%");
        }

        $hariLiburs = $query->orderBy('tanggal')->paginate(15)->withQueryString();
        // SQLite uses strftime, MySQL uses YEAR
        $driver = DB::connection()->getDriverName();
        $yearExpr = $driver === 'sqlite' ? "strftime('%Y', tanggal)" : 'YEAR(tanggal)';
        $tahunList = HariLibur::selectRaw("{$yearExpr} as tahun")
            ->distinct()
            ->orderByDesc('tahun')
            ->pluck('tahun');

        return view('internal.adminit.hari-libur.index', compact('hariLiburs', 'tahun', 'tahunList', 'search'));
    }

    public function create()
    {
        return view('internal.adminit.hari-libur.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tanggal' => 'required|date|unique:hari_libur,tanggal',
            'keterangan' => 'required|string|max:150',
        ]);

        HariLibur::create($data);

        return redirect()->route('internal.adminit.hari-libur.index')->with('success', 'Hari libur berhasil ditambahkan.');
    }

    public function edit(HariLibur $hariLibur)
    {
        return view('internal.adminit.hari-libur.edit', compact('hariLibur'));
    }

    public function update(Request $request, HariLibur $hariLibur)
    {
        $data = $request->validate([
            'tanggal' => 'required|date|unique:hari_libur,tanggal,' . $hariLibur->id,
            'keterangan' => 'required|string|max:150',
        ]);

        $hariLibur->update($data);

        return redirect()->route('internal.adminit.hari-libur.index')->with('success', 'Hari libur berhasil diperbarui.');
    }

    public function destroy(HariLibur $hariLibur)
    {
        $hariLibur->delete();
        return redirect()->route('internal.adminit.hari-libur.index')->with('success', 'Hari libur berhasil dihapus.');
    }

    public function bulkStore(Request $request)
    {
        $data = $request->validate([
            'tanggal_list' => 'required|string',
            'keterangan' => 'required|string|max:150',
        ]);

        $lines = array_filter(array_map('trim', explode("\n", $data['tanggal_list'])), fn($l) => $l !== '');
        if (empty($lines)) {
            return redirect()->back()->with('error', 'Minimal satu tanggal harus diisi.')->withInput();
        }

        $created = 0;
        $duplicates = [];
        foreach ($lines as $line) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $line)) {
                continue;
            }
            $exists = HariLibur::where('tanggal', $line)->exists();
            if ($exists) {
                $duplicates[] = $line;
                continue;
            }
            HariLibur::create(['tanggal' => $line, 'keterangan' => $data['keterangan']]);
            $created++;
        }

        $msg = $created > 0 ? "{$created} hari libur berhasil ditambahkan." : 'Tidak ada tanggal baru yang ditambahkan.';
        if ($duplicates) {
            $msg .= ' (' . count($duplicates) . ' tanggal sudah ada dan dilewati: ' . implode(', ', $duplicates) . '.)';
        }

        return redirect()->route('internal.adminit.hari-libur.index')->with('success', $msg);
    }
}
