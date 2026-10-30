<?php

namespace Tests\Unit;

use App\Enums\TokenDuracion;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TokenDuracionTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_las_duraciones_acordadas_son_las_unicas(): void
    {
        $this->assertSame(
            ['media_hora', 'una_hora', 'seis_horas', 'fin_jornada', 'un_dia'],
            array_column(TokenDuracion::cases(), 'value'),
        );
    }

    public function test_media_hora_una_hora_seis_horas_y_un_dia(): void
    {
        Carbon::setTestNow('2026-10-30 10:00:00');

        $this->assertEquals('2026-10-30 10:30:00', TokenDuracion::MediaHora->expiraEn()->toDateTimeString());
        $this->assertEquals('2026-10-30 11:00:00', TokenDuracion::UnaHora->expiraEn()->toDateTimeString());
        $this->assertEquals('2026-10-30 16:00:00', TokenDuracion::SeisHoras->expiraEn()->toDateTimeString());
        $this->assertEquals('2026-10-31 10:00:00', TokenDuracion::UnDia->expiraEn()->toDateTimeString());
    }

    public function test_fin_de_jornada_vence_hoy_si_todavia_no_llego_la_hora(): void
    {
        config()->set('sistema.fin_jornada', '18:00');
        Carbon::setTestNow('2026-10-30 10:00:00');

        $this->assertEquals('2026-10-30 18:00:00', TokenDuracion::FinJornada->expiraEn()->toDateTimeString());
    }

    public function test_fin_de_jornada_vence_mañana_si_ya_paso_la_hora(): void
    {
        config()->set('sistema.fin_jornada', '18:00');
        Carbon::setTestNow('2026-10-30 19:00:00');

        $this->assertEquals('2026-10-31 18:00:00', TokenDuracion::FinJornada->expiraEn()->toDateTimeString());
    }

    public function test_la_hora_de_fin_de_jornada_se_puede_configurar(): void
    {
        config()->set('sistema.fin_jornada', '16:30');
        Carbon::setTestNow('2026-10-30 10:00:00');

        $this->assertEquals('2026-10-30 16:30:00', TokenDuracion::FinJornada->expiraEn()->toDateTimeString());
    }
}
