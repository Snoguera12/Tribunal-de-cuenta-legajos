<?php

namespace App\Models;

use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Sanctum\PersonalAccessToken;

class Token extends Model
{
    use HasApiTokens;

    protected $fillable = [
        'nombre',
        'is_active',
        'is_created',
    ];
    protected $casts = ['expires_at' => 'datetime'];
    public function restablecer(): void
    {
        $this->update([
            'is_active' => false,
            'is_created' => false,
        ]);
    }

    public static function purgarVencidos(): int
    {
        return DB::transaction(function (): int {
            $vencidos = PersonalAccessToken::query()
                ->where('tokenable_type', (new static)->getMorphClass())
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', now())
                ->get(['id', 'tokenable_id']);

            if ($vencidos->isEmpty()) {
                return 0;
            }

            PersonalAccessToken::whereIn('id', $vencidos->pluck('id'))->delete();

            static::query()
                ->whereIn('id', $vencidos->pluck('tokenable_id')->unique())
                ->doesntHave('tokens')
                ->get()
                ->each->restablecer();

            return $vencidos->count();
        });
    }
}
