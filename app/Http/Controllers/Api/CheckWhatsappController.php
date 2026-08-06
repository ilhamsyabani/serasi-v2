<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pbf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CheckWhatsappController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $no = $request->get('no', '');

        if (strlen($no) < 8) {
            return response()->json(['warning' => '']);
        }

        $pbf = Pbf::where('no_whatsapp', $no)->first();

        if ($pbf) {
            return response()->json([
                'warning' => "Nomor WhatsApp ini sudah terdaftar untuk {$pbf->nama_pbf} (NIB: {$pbf->nib}).",
            ]);
        }

        return response()->json(['warning' => '']);
    }
}
