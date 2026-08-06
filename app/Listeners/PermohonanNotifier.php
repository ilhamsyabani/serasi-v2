<?php

namespace App\Listeners;

use App\Events\PermohonanStatusChanged;

/**
 * Listener otomatis yang dipicu setiap kali status permohonan berubah.
 *
 * CATATAN: Semua notifikasi (WA/Email) saat ini ditangani langsung oleh controller
 * (EvaluasiController, RevisiController, DistribusiController, DisposisiController, dll)
 * saat aksi spesifik dilakukan. Listener ini dipertahankan struktur event-handle-nya
 * agar bisa dipakai di masa depan untuk aksi non-notifikasi (mis. audit, logging, webhook)
 * yang perlu triggered setiap kali status berubah, TANPA duplikasi notifikasi.
 *
 * Untuk menambahkan notifikasi baru, TARUH DI CONTROLLER — bukan di sini,
 * agar tidak terjadi duplikasi dengan notifikasi yang sudah dikirim controller.
 */
class PermohonanNotifier
{
    public function handle(PermohonanStatusChanged $event): void
    {
        // Logika non-notifikasi bisa ditambahkan di sini di masa depan.
        // Contoh: audit trail, webhook, update cache, dll.
    }
}
