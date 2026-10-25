<?php

namespace App\Jobs;

use App\Models\Token;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;


class EliminarTokenVencido implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $borrados = Token::purgarVencidos();
        Log::info('EliminarTokenVencido', ['borrados' => $borrados]);
    }
}