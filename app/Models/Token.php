<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\PersonalAccessToken;

class Token extends Model
{
    use HasApiTokens;

    protected $fillable = [
        'nombre',
        'is_active',
        'is_created',
        'enviados',
    ];
    protected $casts = ['expires_at' => 'datetime', 'enviados' => 'array'];
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
