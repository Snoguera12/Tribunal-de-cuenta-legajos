@php
    // texto seguro: enums con etiqueta, fechas dd/mm/aaaa, vacíos como guion
    $t = function ($v) {
        if ($v instanceof \Filament\Support\Contracts\HasLabel) { return $v->getLabel() ?? '-'; }
        if ($v instanceof \UnitEnum) { return $v->name; }
        if ($v instanceof \DateTimeInterface) { return $v->format('d/m/Y'); }
        if (is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}/', $v)) { return \Illuminate\Support\Carbon::parse($v)->format('d/m/Y'); }
        return filled($v) ? $v : '-';
    };
    $p = $legajo->persona;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1a1a1a; }
    h1 { font-size: 15px; margin: 0; color: #0b1a2c; }
    h2 { font-size: 11px; margin: 14px 0 4px; padding: 3px 6px; background: #0b1a2c; color: #fff; }
    .sub { color: #555; margin-bottom: 8px; border-bottom: 2px solid #b8934a; padding-bottom: 6px; }
    table { width: 100%; border-collapse: collapse; }
    td, th { border: 1px solid #ccc; padding: 3px 5px; text-align: left; vertical-align: top; }
    th { background: #eef1f5; width: 28%; }
    table.lista th { width: auto; }
    .pie { margin-top: 16px; color: #777; font-size: 8px; }
</style>
</head>
<body>
    <h1>Tribunal de Cuentas de Santiago del Estero</h1>
    <div class="sub">Ficha de legajo Nº {{ $legajo->num_legajo }} · {{ $p?->apellido }}, {{ $p?->nombre }}</div>

    <h2>Legajo</h2>
    <table>
        <tr><th>Número</th><td>{{ $legajo->num_legajo }}</td><th>Estado</th><td>{{ $t($legajo->estado) }}</td></tr>
        <tr><th>Fecha de ingreso</th><td>{{ $t($legajo->fecha_de_ingreso) }}</td><th>Tipo de contrato</th><td>{{ $t($legajo->tipo_contrato) }}</td></tr>
        <tr><th>Cargo</th><td>{{ $t($legajo->cargo?->nombre) }}</td><th>Área</th><td>{{ $t($legajo->area?->nombre) }}</td></tr>
        <tr><th>Categoría</th><td colspan="3">{{ $t($legajo->categoria?->nombre) }}</td></tr>
    </table>

    <h2>Datos personales</h2>
    <table>
        <tr><th>Apellido y nombre</th><td>{{ $p?->apellido }}, {{ $p?->nombre }}</td><th>DNI</th><td>{{ $t($p?->dni) }}</td></tr>
        <tr><th>CUIL</th><td>{{ $t($p?->cuil) }}</td><th>Correo</th><td>{{ $t($p?->email) }}</td></tr>
        <tr><th>Género</th><td>{{ $t($p?->genero) }}</td><th>Estado civil</th><td>{{ $t($p?->estado_civil) }}</td></tr>
        <tr><th>Nacimiento</th><td>{{ $t($p?->fecha_de_nacimiento) }}</td><th>Teléfono</th><td>{{ $t($p?->telefono) }}</td></tr>
        <tr><th>Domicilio</th><td colspan="3">{{ $t($p?->domicilio) }}</td></tr>
        <tr><th>Tel. de emergencia</th><td colspan="3">{{ $t($p?->telefono_emergencia) }}</td></tr>
    </table>

    @if ($p?->estudios->isNotEmpty())
        <h2>Estudios</h2>
        <table class="lista">
            <tr><th>Nivel</th><th>Institución</th><th>Título</th><th>Finalización</th></tr>
            @foreach ($p->estudios as $e)
                <tr>
                    <td>{{ $t($e->nivel_estudio) }}</td>
                    <td>{{ $t($e->institucion_mostrada) }}</td>
                    <td>{{ $e->estudioTitulos->map(fn ($et) => $et->nombre_mostrado . ($et->titulo_id ? '' : ' (pendiente de aprobación)'))->implode(', ') ?: '-' }}</td>
                    <td>{{ $t($e->fecha_fin) }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    @if ($p?->cursos->isNotEmpty())
        <h2>Cursos</h2>
        <table class="lista">
            <tr><th>Curso</th><th>Duración</th><th>Fecha</th></tr>
            @foreach ($p->cursos as $c)
                <tr><td>{{ $t($c->nombre) }}</td><td>{{ $t($c->duracion) }}</td><td>{{ $t($c->fecha) }}</td></tr>
            @endforeach
        </table>
    @endif

    @if ($p?->antecedentesLaborales->isNotEmpty())
        <h2>Antecedentes laborales</h2>
        <table class="lista">
            <tr><th>Empleador</th><th>Cargo</th><th>Desde</th><th>Hasta</th></tr>
            @foreach ($p->antecedentesLaborales as $a)
                <tr><td>{{ $t($a->empleador) }}</td><td>{{ $t($a->cargo) }}</td><td>{{ $t($a->fecha_inicio) }}</td><td>{{ $t($a->fecha_fin) }}</td></tr>
            @endforeach
        </table>
    @endif

    @if ($p?->familiares->isNotEmpty())
        <h2>Familiares</h2>
        <table class="lista">
            <tr><th>Nombre</th><th>Parentesco</th><th>DNI</th><th>Nacimiento</th></tr>
            @foreach ($p->familiares as $f)
                <tr><td>{{ $f->apellido }}, {{ $f->nombre }}</td><td>{{ $t($f->parentesco) }}</td><td>{{ $t($f->dni) }}</td><td>{{ $t($f->fecha_de_nacimiento) }}</td></tr>
            @endforeach
        </table>
    @endif
    
    @if ($p?->idiomas->isNotEmpty())
        <h2>Idiomas</h2>
        <table class="lista">
            <tr><th>Idioma</th><th>Nivel</th></tr>
            @foreach ($p->idiomas as $i)
                <tr>
                    <td>{{ $i->nombre_mostrado }}{{ $i->idioma_id ? '' : ' (pendiente de aprobación)' }}</td>
                    <td>{{ $t($i->nivel) }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    @if ($legajo->historial->isNotEmpty())
        <h2>Historial de bajas</h2>
        <table class="lista">
            <tr><th>Fecha de baja</th><th>Motivo</th></tr>
            @foreach ($legajo->historial as $h)
                <tr><td>{{ $t($h->fecha_baja) }}</td><td>{{ $t($h->motivo) }}</td></tr>
            @endforeach
        </table>
    @endif

    <div class="pie">Generado el {{ now()->format('d/m/Y H:i') }} por {{ auth()->user()?->name }}. Documento de uso interno.</div>
</body>
</html>
