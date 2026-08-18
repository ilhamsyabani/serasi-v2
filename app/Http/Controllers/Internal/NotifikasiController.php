<?php

namespace App\Http\Controllers\Internal;

use App\Http\Controllers\Controller;
use App\Models\Notifikasi;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Halaman daftar notifikasi untuk user internal BBPOM (Staff, Ketua Tim, Kepala Balai).
 */
class NotifikasiController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        $query = Notifikasi::query()
            ->where('tujuan_tipe', '!=', Notifikasi::TUJUAN_PEMOHON)
            ->where('tujuan_id', $user->id)
            ->with('permohonan')
            ->latest();

        if ($request->boolean('belum_dibaca')) {
            $query->whereNull('dibaca_at');
        }

        if ($request->get('channel')) {
            $query->where('channel', $request->get('channel'));
        }

        $notifikasis = $query->paginate(20)->withQueryString();

        return view('internal.notifikasi.index', compact('notifikasis'));
    }

    /** Count notifikasi unread untuk user saat ini (AJAX). */
    public function count(): JsonResponse
    {
        $count = Notifikasi::query()
            ->where('tujuan_tipe', '!=', Notifikasi::TUJUAN_PEMOHON)
            ->where('tujuan_id', Auth::id())
            ->whereNull('dibaca_at')
            ->count();

        return response()->json(['count' => $count]);
    }

    /** Ambil daftar notifikasi terbaru untuk dropdown (AJAX). */
    public function dropdown(): JsonResponse
    {
        $user = Auth::user();

        $notifikasis = Notifikasi::query()
            ->where('tujuan_tipe', '!=', Notifikasi::TUJUAN_PEMOHON)
            ->where('tujuan_id', $user->id)
            ->with('permohonan:id,no_registrasi')
            ->latest()
            ->take(10)
            ->get();

        $items = $notifikasis->map(fn($n) => [
            'id' => $n->id,
            'label' => $n->label,
            'icon' => $n->icon,
            'channel' => $n->channel,
            'channel_badge_class' => $n->channel_badge_class,
            'is_unread' => $n->isUnread(),
            'dibaca_at' => $n->dibaca_at?->toISOString(),
            'created_at' => $n->created_at->diffForHumans(),
            'permohonan_id' => $n->permohonan_id,
            'no_registrasi' => $n->permohonan?->no_registrasi,
            'route' => $n->permohonan_id
                ? route('internal.permohonan.show', $n->permohonan_id)
                : route('internal.notifikasi.index'),
        ]);

        $unreadCount = $notifikasis->where('is_unread', true)->count();

        return response()->json([
            'items' => $items,
            'unread_count' => $unreadCount,
        ]);
    }

    /** Tandai satu notifikasi sebagai dibaca (AJAX). */
    public function markAsRead(Notifikasi $notifikasi): JsonResponse
    {
        abort_unless(
            $notifikasi->tujuan_tipe !== Notifikasi::TUJUAN_PEMOHON
            && (int) $notifikasi->tujuan_id === (int) Auth::id(),
            403
        );

        $notifikasi->markAsRead();

        return response()->json(['success' => true]);
    }

    /** Tandai SEMUA notifikasi user sebagai dibaca (AJAX). */
    public function markAllAsRead(): JsonResponse
    {
        Notifikasi::query()
            ->where('tujuan_tipe', '!=', Notifikasi::TUJUAN_PEMOHON)
            ->where('tujuan_id', Auth::id())
            ->whereNull('dibaca_at')
            ->update(['dibaca_at' => now()]);

        return response()->json(['success' => true]);
    }
}
