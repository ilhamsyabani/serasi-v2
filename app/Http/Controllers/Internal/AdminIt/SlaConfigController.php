<?php

namespace App\Http\Controllers\Internal\AdminIt;

use App\Http\Controllers\Controller;
use App\Models\SlaConfig;
use Illuminate\Http\Request;

class SlaConfigController extends Controller
{
    public function index()
    {
        $configs = SlaConfig::orderBy('kode_tahap')->get();
        return view('internal.adminit.sla-config.index', compact('configs'));
    }

    public function edit(SlaConfig $slaConfig)
    {
        return view('internal.adminit.sla-config.edit', compact('slaConfig'));
    }

    public function update(Request $request, SlaConfig $slaConfig)
    {
        $data = $request->validate([
            'durasi' => 'nullable|integer|min:0|max:999',
            'satuan' => 'required|in:hari_kerja,hari_kalender',
            'is_active' => 'boolean',
        ]);

        $slaConfig->update($data);

        return redirect()->route('internal.adminit.sla-config.index')->with('success', 'Konfigurasi SLA berhasil diperbarui.');
    }
}
