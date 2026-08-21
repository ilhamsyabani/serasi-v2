<?php

namespace App\Http\Controllers\Internal\AdminIt;

use App\Http\Controllers\Controller;
use App\Models\ConfigSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ConfigSettingController extends Controller
{
    public function index()
    {
        return redirect()->route('internal.adminit.dashboard');
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'key' => 'required|string',
            'value' => 'required|string',
        ]);

        ConfigSetting::set($validated['key'], $validated['value'] === 'true' ? true : false);
        Cache::forget('config:' . $validated['key']);

        return back()->with('success', 'Pengaturan berhasil disimpan.');
    }
}
