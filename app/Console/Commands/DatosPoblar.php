<?php

namespace App\Console\Commands;

use App\Models\Auditoria;
use App\Models\Persona;
use App\Models\User;
use App\Services\GeneradorDatosPrueba;
use App\Services\HabilitacionUsuarios;
use Illuminate\Console\Command;


class DatosPoblar extends Command
{
    protected $signature = 'datos:poblar
        {--personas=500 : cantidad de agentes (personas con legajo); no todos tienen usuario}
        {--empleados=200 : usuarios con rol empleado}
        {--rrhh=10 : usuarios con rol RRHH}
        {--funcionarios=10 : usuarios con rol funcionario}
        {--licencias=6 : PDF de licencias aprobadas que quedan pendientes de asignar}
        {--documentos=10 : documentos por persona (maximo 10)}
        {--password= : clave unica para todos los usuarios (si no, cada uno recibe la suya)}
        {--archivo= : donde guardar el txt con las claves y el token (si no, no se guarda)}
        {--sin-archivos : no genera los archivos de los documentos}
        {--sin-auditoria : no anota la carga en la auditoria ni en el historico (la auditoria queda vacia)}
        {--agregar : permite cargar aunque ya haya personas}
        {--si : no pregunta}';

    protected $description = 'Carga agentes ficticios de Santiago del Estero (1 admin TIC, 1 admin RRHH, RRHH, funcionarios y empleados) con legajo, familia, estudios, cursos, idiomas y documentos';


    public function handle(): int
    {
        // con --sin-auditoria todo el comando corre con la auditoria y el historico en pausa
        if ($this->option('sin-auditoria')) {
            return Auditoria::sinRegistrar(fn () => $this->cargar());
        }

        return $this->cargar();
    }


    private function cargar(): int
    {
        $personas = max(2, (int) $this->option('personas'));
        $empleados = max(0, (int) $this->option('empleados'));
        $rrhh = max(0, (int) $this->option('rrhh'));
        $funcionarios = max(0, (int) $this->option('funcionarios'));
        $documentos = min(10, max(0, (int) $this->option('documentos')));
        $licencias = max(0, (int) $this->option('licencias'));
        $clave = (string) $this->option('password');

        if (Persona::withTrashed()->exists() && ! $this->option('agregar')) {
            $this->error('Ya hay personas cargadas. Si querés sumar más, usá --agregar. Para empezar de cero: borrar_todo.sh');

            return self::FAILURE;
        }

        $conUsuario = 2 + $rrhh + $funcionarios + $empleados;

        $this->warn('Se van a crear datos FICTICIOS (no son personas reales).');
        $this->line("Agentes: {$personas} | con usuario: " . min($conUsuario, $personas) . " (1 admin TIC, 1 admin RRHH, {$rrhh} RRHH, {$funcionarios} funcionarios, {$empleados} empleados)");
        $this->line('Documentos por persona: ' . ($this->option('sin-archivos') ? 0 : $documentos) . ' | PDF de licencias pendientes: ' . ($this->option('sin-archivos') ? 0 : $licencias));
        $this->line('Auditoría de la carga: ' . ($this->option('sin-auditoria') ? 'NO (la auditoría y el historial quedan vacíos)' : 'sí (queda anotada y se guarda la versión base en el historial)'));

        if (! $this->option('si') && ! $this->confirm('Seguir?', true)) {
            return self::SUCCESS;
        }

        $inicio = microtime(true);
        $barra = $this->output->createProgressBar($personas);
        $barra->start();
        $anterior = 0;

        $generador = new GeneradorDatosPrueba(
            personas: $personas,
            documentos: $documentos,
            password: $clave,
            conArchivos: ! $this->option('sin-archivos'),
            empleados: $empleados,
            rrhh: $rrhh,
            funcionarios: $funcionarios,
            pendientesLicencia: $licencias,
        );

        $resumen = $generador->ejecutar(function ($hechas) use ($barra, &$anterior) {
            $barra->advance($hechas - $anterior);
            $anterior = $hechas;
        });

        $barra->finish();
        $this->newLine(2);

        // el histórico guarda lo cargado (si la auditoria esta en pausa no se copia) y se aplica la regla de baja
        if (Auditoria::$activo) {
            $this->call('historico:sincronizar', ['--copiar-actual' => true]);
        }

        Persona::has('usuario')->with('usuario')->chunkById(200, function ($lote) {
            foreach ($lote as $persona) {
                HabilitacionUsuarios::sincronizar($persona);
            }
        });

        Auditoria::registrar(
            evento: 'importacion',
            descripcion: "Carga de datos de prueba: {$resumen['personas']} personas, {$resumen['documentos']} documentos",
            extra: array_map('strval', $resumen),
        );

        $segundos = round(microtime(true) - $inicio, 1);

        $this->table(['Dato', 'Cantidad'], collect($resumen)->except('bytes')->map(fn ($n, $k) => [$k, $n])->values()->all());
        $this->info('Documentos guardados (cifrados): ' . round($resumen['bytes'] / 1048576, 1) . " MB. Tiempo: {$segundos} s.");

        $cuentas = $this->cuentasConEstado($generador->cuentas());

        if ($this->option('archivo')) {
            $this->guardarClaves((string) $this->option('archivo'), $cuentas, $generador->clienteLicencias(), $resumen, ! $this->option('sin-auditoria'));
            $this->info('Claves de TODOS los usuarios y token de la API guardados en: ' . $this->option('archivo'));
        }

        $this->newLine();
        $this->warn('CUENTAS PRINCIPALES:');
        $this->table(
            ['Rol', 'Sector', 'Correo', 'Clave'],
            collect($cuentas)->filter(fn ($c) => $c['rol'] !== 'empleado')->take(24)->map(fn ($c) => [$c['rol'], $c['sector'] ?? '-', $c['correo'], $c['clave']])->all()
        );
        $this->line('Los empleados (' . collect($cuentas)->where('rol', 'empleado')->count() . ') y sus claves están en el archivo de claves.');
        $this->line('Las personas dadas de baja tienen el usuario deshabilitado (es la regla del sistema).');

        return self::SUCCESS;
    }


    // despues de aplicar la regla de baja, anota si cada usuario quedo habilitado
    private function cuentasConEstado(array $cuentas): array
    {
        $habilitados = User::whereIn('email', array_column($cuentas, 'correo'))->pluck('habilitado', 'email');

        return array_map(fn ($c) => $c + ['habilitado' => (bool) ($habilitados[$c['correo']] ?? true)], $cuentas);
    }


    private function guardarClaves(string $ruta, array $cuentas, array $cliente, array $resumen, bool $conAuditoria = true): void
    {
        $linea = str_repeat('=', 78);
        $texto = [
            $linea,
            ' CUENTAS DE PRUEBA - TRIBUNAL DE CUENTAS (DATOS FICTICIOS)',
            ' Generado: ' . now()->format('d/m/Y H:i') . ' | Agentes: ' . $resumen['personas'] . ' | Usuarios: ' . count($cuentas),
            ' Auditoría de la carga: ' . ($conAuditoria ? 'registrada' : 'NO registrada (la auditoría y el historial empiezan vacíos)'),
            ' GUARDALO EN UN LUGAR SEGURO Y NO LO SUBAS AL REPOSITORIO: tiene las claves en texto.',
            $linea,
            '',
        ];

        $grupos = [
            'administrador' => 'ADMINISTRADORES (uno del sector TIC y otro del sector RRHH)',
            'rrhh' => 'RRHH',
            'funcionario' => 'FUNCIONARIOS',
            'empleado' => 'EMPLEADOS',
        ];

        foreach ($grupos as $rol => $titulo) {
            $lista = array_values(array_filter($cuentas, fn ($c) => $c['rol'] === $rol));

            $texto[] = "--- {$titulo} (" . count($lista) . ') ---';
            $texto[] = sprintf('%-34s %-16s %-6s %-9s %s', 'CORREO', 'CLAVE', 'SECTOR', 'ACCESO', 'NOMBRE');

            foreach ($lista as $c) {
                $texto[] = sprintf('%-34s %-16s %-6s %-9s %s', $c['correo'], $c['clave'], $c['sector'] ?? '-', $c['habilitado'] ? 'si' : 'BLOQUEADO', $c['nombre']);
            }

            $texto[] = '';
        }

        if ($cliente) {
            $texto[] = '--- API DEL SISTEMA DE LICENCIAS ---';
            $texto[] = 'Cliente: ' . $cliente['nombre'];
            $texto[] = 'Token:   ' . $cliente['token'];
            $texto[] = 'Permisos: ' . ($cliente['permisos'] ?? 'todos');
            $texto[] = 'Uso:     POST /api/licencias/documentos  (multipart: archivo=PDF, id_solicitud, nro_resolucion)';
            $texto[] = '         Header  Authorization: Bearer <token>';
            $texto[] = '';
        }

        $texto[] = 'ACCESO "BLOQUEADO" = el agente está dado de baja: el sistema le deshabilita el usuario (regla del sistema).';

        file_put_contents($ruta, implode(PHP_EOL, $texto) . PHP_EOL);
        @chmod($ruta, 0600);
    }
}
