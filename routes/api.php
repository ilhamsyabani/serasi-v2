<?php

use App\Http\Controllers\Api\CheckWhatsappController;
use Illuminate\Support\Facades\Route;

Route::get('/check-whatsapp', CheckWhatsappController::class)->name('api.check-whatsapp');
