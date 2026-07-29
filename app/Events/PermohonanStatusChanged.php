<?php

namespace App\Events;

use App\Models\Permohonan;
use App\Models\StatusLog;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PermohonanStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Permohonan $permohonan,
        public ?string $statusLama,
        public string $statusBaru,
        public ?string $catatan,
        public ?User $actor,
        public ?string $aktorTipe,
        public StatusLog $statusLog,
    ) {}
}
