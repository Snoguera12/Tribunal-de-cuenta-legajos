<?php

namespace App\Services;

use App\Models\Area;
use App\Models\AntecedenteLaboral;
use App\Models\Cargo;
use App\Models\Categoria;
use App\Models\Curso;
use App\Models\Documento;
use App\Models\Estudio;
use App\Models\Familiar;
use App\Models\Historialbaja;
use App\Models\Idioma;
use App\Models\Legajo;
use App\Models\Persona;
use App\Models\Titulo;
use App\Models\User;
use Database\Seeders\CatalogosSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;


/*
 * crea personas ficticias pero verosimiles (nombres, DNI, CUIL valido, familia, estudios,
 * cursos, idiomas, trabajos anteriores, legajo y documentos). NO usa datos de personas reales.
 * Es repetible: con la misma semilla genera lo mismo.
 */
class GeneradorDatosPrueba
{

    private const NOMBRES_F = ['María', 'Ana', 'Laura', 'Silvia', 'Marta', 'Graciela', 'Claudia', 'Patricia', 'Verónica', 'Carolina', 'Daniela', 'Gabriela',
        'Florencia', 'Lucía', 'Valeria', 'Romina', 'Natalia', 'Soledad', 'Mariana', 'Cecilia', 'Sandra', 'Andrea', 'Paola', 'Alejandra', 'Karina',
        'Mónica', 'Noemí', 'Liliana', 'Sofía', 'Camila', 'Agustina', 'Micaela', 'Belén', 'Julieta', 'Rocío', 'Yamila', 'Ayelén', 'Estela', 'Norma', 'Rosa'];

    private const NOMBRES_M = ['Juan', 'Carlos', 'José', 'Luis', 'Miguel', 'Jorge', 'Roberto', 'Ricardo', 'Daniel', 'Fernando', 'Pablo', 'Diego', 'Martín',
        'Sebastián', 'Gustavo', 'Marcelo', 'Alejandro', 'Hernán', 'Facundo', 'Matías', 'Nicolás', 'Lucas', 'Emiliano', 'Ramón', 'Héctor', 'Raúl',
        'Oscar', 'Julio', 'Mario', 'Rubén', 'Walter', 'Claudio', 'Ariel', 'Cristian', 'Maximiliano', 'Franco', 'Ezequiel', 'Gonzalo', 'Federico', 'Tomás'];

    private const APELLIDOS = ['González', 'Rodríguez', 'Gómez', 'Fernández', 'López', 'Díaz', 'Martínez', 'Pérez', 'García', 'Sánchez', 'Romero', 'Sosa',
        'Álvarez', 'Torres', 'Ruiz', 'Ramírez', 'Flores', 'Benítez', 'Acosta', 'Medina', 'Herrera', 'Suárez', 'Aguirre', 'Giménez', 'Gutiérrez', 'Pereyra',
        'Rojas', 'Molina', 'Castro', 'Ortiz', 'Silva', 'Núñez', 'Luna', 'Juárez', 'Cabrera', 'Ríos', 'Ferreyra', 'Domínguez', 'Moreno', 'Peralta', 'Vega',
        'Carrizo', 'Ledesma', 'Coronel', 'Paz', 'Montenegro', 'Santillán', 'Lescano', 'Arias', 'Bustos', 'Corvalán', 'Figueroa', 'Gerez', 'Ibarra', 'Jiménez',
        'Leguizamón', 'Maldonado', 'Navarro', 'Olivera', 'Palacios', 'Quiroga', 'Rearte', 'Salvatierra', 'Toledo', 'Unzaga', 'Villarreal', 'Zurita', 'Barraza',
        'Brandán', 'Cejas', 'Dorado', 'Escobar', 'Farías', 'Galván', 'Heredia', 'Iturre', 'Lugones', 'Mansilla', 'Nieva', 'Orellana', 'Padilla', 'Rueda', 'Sayago'];

    private const LOCALIDADES = [
        ['Santiago del Estero', 'Capital', 45], ['La Banda', 'La Banda', 30], ['Termas de Río Hondo', 'Río Hondo', 6],
        ['Frías', 'Frías', 4], ['Añatuya', 'Añatuya', 4], ['Clodomira', 'Clodomira', 3], ['Fernández', 'Fernández', 3],
        ['Quimilí', 'Quimilí', 3], ['Loreto', 'Loreto', 2],
    ];

    private const CALLES = ['Av. Belgrano', 'Av. Libertad', 'Av. Roca', 'Av. Moreno', 'Av. Rivadavia', 'Av. Alsina', 'Calle Independencia', 'Calle 9 de Julio',
        'Calle Buenos Aires', 'Calle Mitre', 'Calle Tucumán', 'Calle Salta', 'Calle San Martín', 'Calle Sarmiento', 'Calle Urquiza', 'Calle Catamarca',
        'Calle Córdoba', 'Calle Santa Fe', 'Calle Pellegrini', 'Calle Lavalle', 'Calle Perón', 'Calle Yrigoyen', 'Calle Avellaneda', 'Calle Chacabuco'];

    private const BARRIOS = ['Centro', 'Autonomía', 'Mosconi', 'Villa Griselda', 'Ejército Argentino', 'Borges', 'Aeropuerto', 'Jardín', 'Belgrano', 'Santa Rosa',
        'San Martín', 'Huaico Hondo', 'Almirante Brown', '8 de Abril', 'Villa Unión', 'Los Lagos', 'Rivadavia', 'Cabildo', 'Matadero', 'Palermo'];

    // cargos que tienen sentido en cada area
    private const CARGOS_POR_AREA = [
        'Presidencia' => ['Presidente', 'Vocal', 'Administrativo'],
        'Secretaría General' => ['Secretario General', 'Administrativo', 'Auxiliar Administrativo'],
        'Secretaría Contable' => ['Secretario Contable', 'Contador Fiscal', 'Administrativo'],
        'Auditoría de Cuentas' => ['Director de Auditoría', 'Auditor Contable', 'Auditor Fiscal', 'Contador Fiscal'],
        'Fiscalización de Obras Públicas' => ['Auditor Fiscal', 'Contador Fiscal', 'Auxiliar Administrativo'],
        'Asesoría Legal' => ['Abogado Dictaminante', 'Procurador', 'Administrativo'],
        'Recursos Humanos' => ['Jefe de Recursos Humanos', 'Liquidador de Haberes', 'Administrativo', 'Auxiliar Administrativo'],
        'Sistemas e Informática' => ['Analista de Sistemas', 'Soporte Técnico'],
        'Mesa de Entradas' => ['Jefe de Mesa de Entradas', 'Administrativo', 'Auxiliar Administrativo'],
        'Tesorería y Contaduría' => ['Tesorero', 'Contador Fiscal', 'Administrativo'],
        'Archivo General' => ['Archivista', 'Auxiliar Administrativo'],
        'Capacitación y Estudios' => ['Capacitador', 'Administrativo'],
        'Prensa y Ceremonial' => ['Periodista', 'Ceremonial'],
        'Servicios Generales' => ['Chofer', 'Ordenanza', 'Mantenimiento'],
        'Sumarios y Responsabilidades' => ['Instructor Sumariante', 'Abogado Dictaminante', 'Administrativo'],
    ];

    private const TITULOS_UNIV = [
        ['Abogado', 'Abogada'], ['Contador Público Nacional', 'Contadora Pública Nacional'], ['Licenciado en Administración', 'Licenciada en Administración'],
        ['Licenciado en Economía', 'Licenciada en Economía'], ['Ingeniero en Sistemas de Información', 'Ingeniera en Sistemas de Información'],
        ['Licenciado en Sistemas', 'Licenciada en Sistemas'], ['Licenciado en Comunicación Social', 'Licenciada en Comunicación Social'],
        ['Licenciado en Ciencias Políticas', 'Licenciada en Ciencias Políticas'], ['Arquitecto', 'Arquitecta'], ['Licenciado en Trabajo Social', 'Licenciada en Trabajo Social'],
    ];

    private const TITULOS_TERC = [
        ['Técnico Superior en Administración Pública', 'Técnica Superior en Administración Pública'], ['Técnico Superior en Programación', 'Técnica Superior en Programación'],
        ['Técnico Superior en Gestión Contable', 'Técnica Superior en Gestión Contable'], ['Técnico Superior en Seguridad e Higiene', 'Técnica Superior en Seguridad e Higiene'],
        ['Profesor de Educación Superior', 'Profesora de Educación Superior'], ['Técnico Superior en Archivología', 'Técnica Superior en Archivología'],
    ];

    private const TITULOS_SEC = ['Bachiller en Economía y Gestión de las Organizaciones', 'Bachiller en Ciencias Sociales', 'Técnico en Informática',
        'Perito Mercantil', 'Bachiller en Humanidades', 'Técnico en Electromecánica', 'Bachiller en Ciencias Naturales'];

    private const CURSOS = ['Gestión documental y archivo', 'Excel avanzado', 'Ley de Procedimiento Administrativo', 'Ética en la función pública', 'Primeros auxilios',
        'Contrataciones públicas', 'Atención al público', 'Redacción administrativa', 'Control de gestión', 'Contabilidad gubernamental', 'Seguridad informática',
        'Liquidación de haberes', 'Auditoría en el sector público', 'Oratoria y ceremonial', 'Derecho administrativo', 'Gestión de la calidad ISO 9001'];

    private const INSTITUCIONES_CURSO = ['IPAP Santiago del Estero', 'Universidad Nacional de Santiago del Estero', 'INAP', 'UTN Facultad Regional Santiago del Estero',
        'Cámara de Comercio de Santiago del Estero', 'Colegio de Abogados de Santiago del Estero', 'Consejo Profesional de Ciencias Económicas', 'Fundación Capacitar'];

    private const UNIVERSIDADES = ['Universidad Nacional de Santiago del Estero', 'Universidad Católica de Santiago del Estero', 'UTN Facultad Regional Santiago del Estero',
        'Universidad Nacional de Tucumán', 'Universidad Nacional de Córdoba', 'Universidad Siglo 21'];

    private const IDIOMAS = ['Inglés' => 50, 'Portugués' => 15, 'Quechua' => 14, 'Francés' => 9, 'Italiano' => 8, 'Alemán' => 4];

    private const EMPLEADORES = [
        ['Banco Santiago del Estero', 'Casa Central'], ['Municipalidad de La Banda', 'Secretaría de Hacienda'], ['Municipalidad de Santiago del Estero', 'Dirección de Rentas'],
        ['Poder Judicial de Santiago del Estero', 'Juzgado Civil y Comercial'], ['Obras Sanitarias de Santiago del Estero', 'Oficina Comercial'],
        ['Estudio Contable Rojas y Asociados', 'Oficina central'], ['Supermercados del Norte S.A.', 'Sucursal Centro'], ['Clínica del Parque', 'Administración'],
        ['Ministerio de Educación de Santiago del Estero', 'Dirección de Personal'], ['Distribuidora Santiagueña S.R.L.', 'Administración'],
        ['Universidad Nacional de Santiago del Estero', 'Facultad de Ciencias Exactas'], ['Cooperativa Eléctrica de La Banda', 'Atención al cliente'],
    ];

    private const CARGOS_ANTERIORES = ['Administrativo', 'Cajero', 'Auxiliar contable', 'Empleado de mesa de entradas', 'Recepcionista', 'Analista junior', 'Ayudante de oficina',
        'Auxiliar de liquidaciones', 'Asistente administrativo', 'Cobrador', 'Secretario/a', 'Técnico de soporte'];

    private const MOTIVOS_EGRESO = ['Renuncia por mejor oferta laboral', 'Fin de contrato', 'Reducción de personal', 'Traslado de ciudad', 'Cambio de rubro', 'Concurso en la administración pública'];

    private array $dniUsados = [];
    private array $emailsUsados = [];
    private array $ids = [];
    private ?string $hashClave = null;
    private ?int $idAdmin = null;
    private array $resumen = ['personas' => 0, 'usuarios' => 0, 'legajos' => 0, 'bajas' => 0, 'familiares' => 0, 'estudios' => 0,
        'titulos' => 0, 'cursos' => 0, 'idiomas' => 0, 'antecedentes' => 0, 'documentos' => 0, 'bytes' => 0];
    private array $cuentas = [];


    public function __construct(
        private int $personas = 1000,
        private int $documentos = 10,
        private string $password = '',
        private bool $conArchivos = true,
        private int $semilla = 2026,
        private string $dominio = 'prueba.tribunal.test',
    ) {
    }


    public function cuentas(): array
    {
        return $this->cuentas;
    }


    public function ejecutar(?callable $progreso = null): array
    {
        mt_srand($this->semilla);

        $this->hashClave = Hash::make($this->password);

        (new CatalogosSeeder())->run();
        $this->cargarCatalogos();

        $this->dniUsados = array_flip(Persona::withTrashed()->pluck('dni')->all());
        $this->emailsUsados = array_flip(User::withTrashed()->pluck('email')->map(fn ($e) => mb_strtolower($e))->all());

        $roles = $this->repartirRoles();

        // sin eventos: no se llena la auditoria ni se mandan mails (el histórico se copia al final)
        Model::withoutEvents(function () use ($roles, $progreso) {

            foreach (array_chunk($roles, 50, true) as $tanda) {

                DB::transaction(function () use ($tanda) {
                    foreach ($tanda as $indice => $rol) {
                        $this->crearPersonaCompleta($indice, $rol);
                    }
                });

                if ($progreso) {
                    $progreso($this->resumen['personas'], count($roles));
                }
            }
        });

        return $this->resumen;
    }


    // 1 administrador, 5 RRHH, 5 funcionarios y el resto empleados
    private function repartirRoles(): array
    {
        $roles = [];

        // si ya se cargo una vez (--agregar), las cuentas fijas ya existen: solo se suman empleados
        $yaHayCuentasBase = User::where('email', "admin@{$this->dominio}")->exists();

        for ($i = 0; $i < $this->personas; $i++) {
            $roles[$i] = $yaHayCuentasBase ? 'empleado'
                : ($i === 0 ? 'administrador' : ($i <= 5 ? 'rrhh' : ($i <= 10 ? 'funcionario' : 'empleado')));
        }

        return $roles;
    }


    private function cargarCatalogos(): void
    {
        $this->ids = [
            'areas' => Area::pluck('id', 'nombre')->all(),
            'cargos' => Cargo::pluck('id', 'nombre')->all(),
            'categorias' => Categoria::pluck('id', 'nombre')->all(),
        ];
    }


    // ---------------------------------------------------------- una persona con todo

    private function crearPersonaCompleta(int $indice, string $rol): void
    {
        $genero = mt_rand(1, 100) <= 49 ? 'F' : 'M';
        $edad = match ($rol) {
            'administrador', 'funcionario' => mt_rand(42, 62),
            'rrhh' => mt_rand(32, 56),
            default => mt_rand(21, 64),
        };

        $nombre = $this->elegir($genero === 'F' ? self::NOMBRES_F : self::NOMBRES_M);

        if (mt_rand(1, 100) <= 40) {
            $nombre .= ' ' . $this->elegir($genero === 'F' ? self::NOMBRES_F : self::NOMBRES_M);
        }

        $apellido = $this->elegir(self::APELLIDOS);
        $nacimiento = now()->subYears($edad)->subDays(mt_rand(0, 364))->startOfDay();
        $dni = $this->dniNuevo($nacimiento->year);
        [$localidad] = $this->localidad();
        $estadoCivil = $edad < 26 ? 0 : $this->ponderado([0 => 30, 1 => 55, 2 => 10, 3 => 5]);

        $persona = Persona::create([
            'nombre' => $nombre,
            'apellido' => $apellido,
            'dni' => $dni,
            'cuil' => $this->cuil($dni, $genero),
            'email' => $this->correoPersonal($nombre, $apellido),
            'genero' => $genero === 'F' ? 0 : 1,
            'estado_civil' => $estadoCivil,
            'fecha_de_nacimiento' => $nacimiento->toDateString(),
            'domicilio' => $this->domicilio($localidad),
            'telefono' => $this->telefono(),
            'telefono_emergencia' => $this->telefono(),
        ]);

        $this->resumen['personas']++;

        $usuario = $this->crearUsuario($persona, $rol, $indice);

        $legajo = $this->crearLegajos($persona, $rol, $edad, $nacimiento, $indice);

        $estudios = $this->crearEstudios($persona, $genero, $edad, $rol);
        $this->crearFamilia($persona, $genero, $edad, $estadoCivil);
        $this->crearCursos($persona);
        $this->crearIdiomas($persona);
        $this->crearAntecedentes($persona, $legajo->fecha_de_ingreso, $edad);

        if ($this->conArchivos && $this->documentos > 0) {
            $this->crearDocumentos($persona, $legajo, $estudios, $estadoCivil, $edad);
        }
    }


    private function crearUsuario(Persona $persona, string $rol, int $indice): User
    {
        $correo = match (true) {
            $rol === 'administrador' => "admin@{$this->dominio}",
            $rol === 'rrhh' => "rrhh{$indice}@{$this->dominio}",
            $rol === 'funcionario' => 'funcionario' . ($indice - 5) . "@{$this->dominio}",
            default => $this->correoUnico($persona->nombre, $persona->apellido),
        };

        $this->emailsUsados[mb_strtolower($correo)] = true;

        $usuario = User::create([
            'name' => $persona->nombre . ' ' . $persona->apellido,
            'email' => $correo,
            'password' => $this->hashClave,
            'rol' => $rol,
            'persona_id' => $persona->id,
            'habilitado' => true,
            'debe_cambiar_clave' => false,
        ]);

        if ($rol === 'administrador') {
            $this->idAdmin = $usuario->id;
        }

        if ($rol !== 'empleado' || count($this->cuentas) < 3) {
            $this->cuentas[] = ['rol' => $rol, 'correo' => $correo, 'nombre' => $usuario->name];
        }

        $this->resumen['usuarios']++;

        return $usuario;
    }


    // ---------------------------------------------------------- legajo y baja

    private function crearLegajos(Persona $persona, string $rol, int $edad, $nacimiento, int $indice): Legajo
    {
        $area = match ($rol) {
            'administrador' => 'Presidencia',
            'rrhh' => 'Recursos Humanos',
            'funcionario' => $this->elegir(['Secretaría General', 'Secretaría Contable', 'Auditoría de Cuentas', 'Asesoría Legal', 'Presidencia']),
            default => $this->ponderadoTexto(['Auditoría de Cuentas' => 16, 'Fiscalización de Obras Públicas' => 8, 'Asesoría Legal' => 8, 'Mesa de Entradas' => 10,
                'Tesorería y Contaduría' => 8, 'Secretaría Contable' => 8, 'Sistemas e Informática' => 7, 'Archivo General' => 6, 'Servicios Generales' => 8,
                'Capacitación y Estudios' => 4, 'Prensa y Ceremonial' => 3, 'Sumarios y Responsabilidades' => 5, 'Secretaría General' => 5, 'Recursos Humanos' => 2, 'Presidencia' => 2]),
        };

        $cargos = self::CARGOS_POR_AREA[$area];
        $cargo = match ($rol) {
            'administrador' => 'Presidente',
            'rrhh' => $indice === 1 ? 'Jefe de Recursos Humanos' : $this->elegir(['Liquidador de Haberes', 'Administrativo']),
            'funcionario' => $this->elegir(['Vocal', 'Secretario General', 'Secretario Contable', 'Director de Auditoría']),
            default => $this->elegir($cargos),
        };

        $categoria = in_array($rol, ['administrador', 'funcionario'], true) ? 'PS'
            : (in_array($cargo, ['Chofer', 'Ordenanza', 'Mantenimiento'], true) ? 'MAE'
            : (in_array($cargo, ['Analista de Sistemas', 'Soporte Técnico', 'Archivista', 'Periodista', 'Ceremonial'], true) ? 'TEC'
            : (str_contains($cargo, 'Auditor') || str_contains($cargo, 'Contador') || str_contains($cargo, 'Abogado') || $cargo === 'Procurador' ? 'PROF' : 'ADM')));

        $contrato = in_array($rol, ['administrador', 'funcionario'], true) ? 0 : $this->ponderado([2 => 68, 1 => 32]);

        $minIngreso = $nacimiento->copy()->addYears(19);
        $maxIngreso = now()->subDays(20);
        $ingreso = $minIngreso->gt($maxIngreso) ? $maxIngreso : $minIngreso->copy()->addDays(mt_rand(0, max(1, $minIngreso->diffInDays($maxIngreso))));

        $enBaja = $rol === 'empleado' && mt_rand(1, 100) <= 8;
        $conReingreso = $rol === 'empleado' && ! $enBaja && mt_rand(1, 100) <= 3 && $ingreso->diffInDays(now()) > 900;

        $numero = 1000 + $persona->id;

        if ($conReingreso) {
            // trabajo antes, se fue y volvio: el primer legajo quedo de baja
            $primeraBaja = $ingreso->copy()->addDays(mt_rand(300, 700));
            $this->nuevoLegajo($persona, $numero + 50000, false, $ingreso, $contrato, $categoria, $cargo, $area, $this->motivoBaja($edad, $contrato), $primeraBaja);
            $ingreso = $primeraBaja->copy()->addDays(mt_rand(60, 400));
            $numero += 90000;
        }

        $legajo = $this->nuevoLegajo($persona, $numero, ! $enBaja, $ingreso, $contrato, $categoria, $cargo, $area,
            $enBaja ? $this->motivoBaja($edad, $contrato) : null,
            $enBaja ? $ingreso->copy()->addDays(mt_rand(120, max(121, (int) $ingreso->diffInDays(now()) - 5))) : null);

        return $legajo;
    }


    private function nuevoLegajo(Persona $persona, int $numero, bool $alta, $ingreso, int $contrato, string $categoria, string $cargo, string $area, ?int $motivo, $fechaBaja): Legajo
    {
        $legajo = Legajo::create([
            'num_legajo' => $numero,
            'estado' => $alta,
            'fecha_de_ingreso' => $ingreso->toDateTimeString(),
            'tipo_contrato' => $contrato,
            'persona_id' => $persona->id,
            'categoria_id' => $this->ids['categorias'][$categoria],
            'cargo_id' => $this->ids['cargos'][$cargo],
            'area_id' => $this->ids['areas'][$area],
        ]);

        $this->resumen['legajos']++;

        if (! $alta) {
            Historialbaja::create([
                'legajo_id' => $legajo->id,
                'user_id' => $this->idAdmin ?? 1,
                'motivo' => $motivo,
                'fecha_baja' => $fechaBaja->toDateTimeString(),
            ]);

            $this->resumen['bajas']++;
        }

        return $legajo;
    }


    private function motivoBaja(int $edad, int $contrato): int
    {
        $pesos = [0 => 30, 1 => 4, 6 => 12, 5 => 3, 4 => 1];   // renuncia, despido, traslado, incapacidad, fallecimiento

        if ($contrato === 1) {
            $pesos[2] = 40;   // vencimiento de contrato
        }

        if ($edad >= 58) {
            $pesos[3] = 50;   // jubilacion
        }

        return $this->ponderado($pesos);
    }


    // ---------------------------------------------------------- estudios y titulos

    private function crearEstudios(Persona $persona, string $genero, int $edad, string $rol): array
    {
        $maximo = match ($rol) {
            'administrador', 'funcionario' => $this->ponderado([7 => 70, 9 => 20, 8 => 10]),
            'rrhh' => $this->ponderado([7 => 55, 6 => 30, 4 => 15]),
            default => $this->ponderado([4 => 35, 6 => 22, 7 => 30, 3 => 5, 5 => 4, 9 => 3, 2 => 1]),
        };

        $niveles = [2 => 'Escuela Primaria N° ' . mt_rand(1, 950) . ' ' . $this->elegir(['Domingo F. Sarmiento', 'Manuel Belgrano', 'Bernardino Rivadavia', 'Juana Manso', 'Provincia de Santiago del Estero', 'Mariano Moreno'])];
        $fechaPrimaria = $this->anioFin($edad, 13);
        $estudios = [];

        $crear = function (int $nivel, string $institucion, $fin, array $titulos = []) use ($persona, &$estudios) {
            $estudio = Estudio::create(['institucion' => $institucion, 'nivel_estudio' => $nivel, 'fecha_fin' => $fin?->toDateString(), 'persona_id' => $persona->id]);
            $this->resumen['estudios']++;

            foreach ($titulos as $titulo) {
                Titulo::create(['nombre' => mb_substr($titulo, 0, 100), 'estudio_id' => $estudio->id]);
                $this->resumen['titulos']++;
            }

            $estudios[] = ['nivel' => $nivel, 'titulos' => $titulos, 'institucion' => $institucion];
        };

        $crear(2, $niveles[2], $fechaPrimaria);

        if ($maximo >= 4 || $maximo === 3) {
            $secundarioCompleto = $maximo >= 4;
            $titulo = $secundarioCompleto ? [$this->elegir(self::TITULOS_SEC)] : [];
            $crear($secundarioCompleto ? 4 : 3, $this->elegir(['Colegio Nacional Absalón Rojas', 'Escuela Normal Superior Manuel Belgrano', 'Colegio Nacional N° ' . mt_rand(1, 30), 'Escuela de Comercio Dr. Dalmacio Vélez Sarsfield', 'Escuela Técnica N° ' . mt_rand(1, 20) . ' Ing. Santiago Maradona', 'Colegio Don Bosco']),
                $secundarioCompleto ? $this->anioFin($edad, 18) : null, $titulo);
        }

        if ($maximo === 5 || $maximo === 6) {
            $crear($maximo, $this->elegir(['Instituto de Formación Docente Nº ' . mt_rand(1, 12), 'Instituto Superior de Ciencias Económicas', 'IES Santiago del Estero', 'Instituto Superior de Informática Santiagueño']),
                $maximo === 6 ? $this->anioFin($edad, 22) : null, $maximo === 6 ? [$this->variante(self::TITULOS_TERC, $genero)] : []);
        }

        if ($maximo === 7 || $maximo === 9) {

            $titulo = $this->variante(self::TITULOS_UNIV, $genero);
            $crear(7, $this->elegir(self::UNIVERSIDADES), $this->anioFin($edad, 24), [$titulo]);

            if ($maximo === 9) {
                $crear(9, $this->elegir(self::UNIVERSIDADES), $this->anioFin($edad, 30), ['Magíster en ' . $this->elegir(['Administración Pública', 'Auditoría y Control', 'Gestión de Recursos Humanos', 'Derecho Administrativo', 'Finanzas Públicas'])]);
            }
        }

        if ($maximo === 8) {
            $crear(7, $this->elegir(self::UNIVERSIDADES), $this->anioFin($edad, 24), [$this->variante(self::TITULOS_UNIV, $genero)]);
            $crear(8, $this->elegir(self::UNIVERSIDADES), $this->anioFin($edad, 34), [($genero === 'F' ? 'Doctora' : 'Doctor') . ' en ' . $this->elegir(['Derecho', 'Ciencias Económicas', 'Administración', 'Ciencias Sociales'])]);
        }

        return $estudios;
    }


    // ---------------------------------------------------------- familia

    private function crearFamilia(Persona $persona, string $genero, int $edad, int $estadoCivil): void
    {
        $apellido = $persona->apellido;

        // conyuge
        if ($estadoCivil === 1 || ($estadoCivil === 3 && mt_rand(0, 1))) {

            $generoConyuge = mt_rand(1, 100) <= 95 ? ($genero === 'F' ? 'M' : 'F') : $genero;
            $this->familiar($persona, $this->elegir($generoConyuge === 'F' ? self::NOMBRES_F : self::NOMBRES_M), $this->elegir(self::APELLIDOS), 0,
                max(19, $edad + mt_rand(-7, 7)), $estadoCivil === 3 ? 1 : 0);
        }

        // hijos
        if ($edad >= 24) {
            $cantidad = $this->ponderado([0 => 28, 1 => 26, 2 => 28, 3 => 12, 4 => 6]);
            $maxEdadHijo = min(35, $edad - 20);

            for ($i = 0; $i < $cantidad && $maxEdadHijo > 0; $i++) {
                $esF = mt_rand(0, 1) === 1;
                $this->familiar($persona, $this->elegir($esF ? self::NOMBRES_F : self::NOMBRES_M), $apellido, 2, mt_rand(0, $maxEdadHijo), 0);
            }
        }

        // padres (a veces fallecidos)
        foreach ([0, 1] as $n) {
            if (mt_rand(1, 100) <= 85) {
                $edadPadre = $edad + mt_rand(24, 36);
                $vive = $edadPadre > 78 ? mt_rand(0, 100) < 40 : mt_rand(0, 100) < 85;
                $esF = $n === 0;
                $this->familiar($persona, $this->elegir($esF ? self::NOMBRES_F : self::NOMBRES_M), $esF ? $this->elegir(self::APELLIDOS) : $apellido, 1, $edadPadre, $vive ? 0 : 1);
            }
        }

        // sobrinos y suegros, poco frecuentes
        if (mt_rand(1, 100) <= 8) {
            $this->familiar($persona, $this->elegir(mt_rand(0, 1) ? self::NOMBRES_F : self::NOMBRES_M), $apellido, 3, mt_rand(1, 18), 0);
        }

        if ($estadoCivil === 1 && mt_rand(1, 100) <= 15) {
            $this->familiar($persona, $this->elegir(self::NOMBRES_M), $this->elegir(self::APELLIDOS), 4, $edad + mt_rand(22, 32), mt_rand(0, 100) < 70 ? 0 : 1);
        }
    }


    private function familiar(Persona $persona, string $nombre, string $apellido, int $parentesco, int $edad, int $vive): void
    {
        Familiar::create([
            'nombre' => $nombre,
            'apellido' => $apellido,
            'dni' => $this->dniNuevo(now()->subYears($edad)->year),
            'fecha_de_nacimiento' => now()->subYears($edad)->subDays(mt_rand(0, 364))->toDateString(),
            'parentesco' => $parentesco,
            'vive' => $vive,
            'persona_id' => $persona->id,
        ]);

        $this->resumen['familiares']++;
    }


    // ---------------------------------------------------------- cursos, idiomas, trabajos anteriores

    private function crearCursos(Persona $persona): void
    {
        $cantidad = $this->ponderado([0 => 12, 1 => 20, 2 => 24, 3 => 20, 4 => 14, 5 => 10]);
        $elegidos = (array) array_rand(array_flip(self::CURSOS), max(1, $cantidad));

        foreach (array_slice($elegidos, 0, $cantidad) as $nombre) {
            Curso::create([
                'persona_id' => $persona->id,
                'nombre' => $nombre,
                'institucion' => $this->elegir(self::INSTITUCIONES_CURSO),
                'duracion' => $this->elegir(['20 horas', '30 horas', '40 horas', '60 horas', '80 horas', '120 horas']),
                'fecha' => now()->subDays(mt_rand(60, 2800))->toDateString(),
                'tiene_certificado' => mt_rand(1, 100) <= 82,
            ]);

            $this->resumen['cursos']++;
        }
    }


    private function crearIdiomas(Persona $persona): void
    {
        $cantidad = $this->ponderado([0 => 40, 1 => 40, 2 => 16, 3 => 4]);
        $usados = [];

        for ($i = 0; $i < $cantidad; $i++) {

            $idioma = $this->ponderadoTexto(self::IDIOMAS);

            if (isset($usados[$idioma])) {
                continue;
            }

            $usados[$idioma] = true;

            // el quechua en Santiago del Estero muchas veces es lengua materna
            $nivel = $idioma === 'Quechua' ? $this->ponderado([3 => 50, 2 => 25, 0 => 25]) : $this->ponderado([0 => 45, 1 => 30, 2 => 22, 3 => 3]);

            Idioma::create(['persona_id' => $persona->id, 'idioma' => $idioma, 'nivel' => $nivel]);
            $this->resumen['idiomas']++;
        }
    }


    private function crearAntecedentes(Persona $persona, $ingresoActual, int $edad): void
    {
        $cantidad = $edad < 25 ? $this->ponderado([0 => 60, 1 => 40]) : $this->ponderado([0 => 15, 1 => 30, 2 => 30, 3 => 18, 4 => 7]);
        $fin = \Illuminate\Support\Carbon::parse($ingresoActual)->subDays(mt_rand(20, 200));

        for ($i = 0; $i < $cantidad; $i++) {

            $duracion = mt_rand(300, 1800);
            $inicio = $fin->copy()->subDays($duracion);

            if ($inicio->year < now()->subYears($edad - 18)->year) {
                break;
            }

            [$empleador, $lugar] = $this->elegir(self::EMPLEADORES);

            AntecedenteLaboral::create([
                'persona_id' => $persona->id,
                'empleador' => $empleador,
                'lugar_de_trabajo' => $lugar,
                'cargo' => $this->elegir(self::CARGOS_ANTERIORES),
                'fecha_inicio' => $inicio->toDateString(),
                'fecha_fin' => $fin->toDateString(),
                'motivo_egreso' => $this->elegir(self::MOTIVOS_EGRESO),
            ]);

            $this->resumen['antecedentes']++;
            $fin = $inicio->copy()->subDays(mt_rand(10, 120));
        }
    }


    // ---------------------------------------------------------- documentos (archivos reales, cifrados)

    private function crearDocumentos(Persona $persona, Legajo $legajo, array $estudios, int $estadoCivil, int $edad): void
    {
        $nombreCompleto = $persona->nombre . ' ' . $persona->apellido;
        $dni = number_format($persona->dni, 0, ',', '.');
        $mayor = collect($estudios)->sortByDesc('nivel')->first();
        $tituloTexto = $mayor['titulos'][0] ?? 'Nivel ' . (['', '', 'Primario', '', 'Secundario'][$mayor['nivel']] ?? 'alcanzado');
        $anio = now()->subDays(mt_rand(30, 1500))->year;
        $ingreso = \Illuminate\Support\Carbon::parse($legajo->fecha_de_ingreso);

        $lista = [
            [10, 'jpg', 'Foto de perfil', fn () => $this->imagenPerfil($persona)],
            [0, 'jpg', 'DNI - frente y dorso', fn () => $this->imagenDni($persona)],
            [4, 'pdf', 'Acta de nacimiento', fn () => MiniPdf::hoja('ACTA DE NACIMIENTO', [
                ['Registro Civil y Capacidad de las Personas - Santiago del Estero', 11, true], ['', 10],
                ["Acta N° " . mt_rand(100, 9999) . " - Tomo " . mt_rand(1, 40) . " - Folio " . mt_rand(1, 300), 10],
                ["Se hace constar que {$nombreCompleto}, DNI {$dni},", 11], ['nació el ' . \Illuminate\Support\Carbon::parse($persona->fecha_de_nacimiento)->format('d/m/Y') . ' en la provincia de Santiago del Estero.', 11],
            ])],
            [11, 'pdf', 'Curriculum vitae', fn () => MiniPdf::hoja('CURRICULUM VITAE', array_merge([
                [$nombreCompleto, 14, true], ["DNI {$dni} - CUIL {$persona->cuil}", 10], [$persona->domicilio, 10], ["Tel: {$persona->telefono} - {$persona->email}", 10], ['', 10],
                ['Formación', 12, true], [$tituloTexto . ' - ' . ($mayor['institucion'] ?? ''), 10], ['', 10], ['Experiencia', 12, true]],
                $this->lineasExperiencia($persona)))],
            [1, 'pdf', 'Título: ' . $tituloTexto, fn () => MiniPdf::hoja('TÍTULO / CERTIFICADO DE ESTUDIOS', [
                [$mayor['institucion'] ?? 'Institución educativa', 12, true], ['', 10],
                ["Se certifica que {$nombreCompleto}, DNI {$dni}, ha cumplido con los requisitos para obtener el título de:", 11],
                ['', 10], [$tituloTexto, 14, true], ['', 10], ['Registro N° ' . mt_rand(1000, 99999) . '/' . mt_rand(1990, 2025), 10],
            ])],
            [2, 'pdf', 'Certificado de curso de capacitación', fn () => MiniPdf::hoja('CERTIFICADO DE CAPACITACIÓN', [
                [$this->elegir(self::INSTITUCIONES_CURSO), 12, true], ['', 10],
                ["Se certifica que {$nombreCompleto}, DNI {$dni}, aprobó el curso:", 11], ['', 10], [$this->elegir(self::CURSOS), 14, true],
                ['Carga horaria: ' . $this->elegir(['20', '40', '60', '80']) . ' horas - Año ' . $anio, 10],
            ])],
            [9, 'pdf', "Resolución de designación N° " . mt_rand(10, 899) . "/" . $ingreso->year, fn () => MiniPdf::hoja('TRIBUNAL DE CUENTAS - RESOLUCIÓN', [
                ['Santiago del Estero, ' . $ingreso->format('d/m/Y'), 10], ['', 10],
                ["VISTO: la designación de {$nombreCompleto}, DNI {$dni}, Legajo N° {$legajo->num_legajo}.", 11],
                ['Y CONSIDERANDO que cumple con los requisitos del cargo;', 11], ['', 10],
                ['EL TRIBUNAL RESUELVE: Designar al agente mencionado, a partir del ' . $ingreso->format('d/m/Y') . '.', 11, true],
            ])],
            [3, 'pdf', 'Licencia anual ordinaria ' . $anio, fn () => MiniPdf::hoja('SOLICITUD DE LICENCIA', [
                ["Agente: {$nombreCompleto} - Legajo N° {$legajo->num_legajo}", 11, true], ['', 10],
                ['Tipo: ' . $this->elegir(['Licencia anual ordinaria', 'Licencia por examen', 'Licencia por razones particulares', 'Licencia por enfermedad']), 11],
                ['Desde: ' . now()->subDays(mt_rand(10, 400))->format('d/m/Y') . ' - Cantidad de días: ' . mt_rand(3, 21), 11],
            ])],
            $estadoCivil === 1
                ? [7, 'pdf', 'Certificado de matrimonio', fn () => MiniPdf::hoja('ACTA DE MATRIMONIO', [
                    ['Registro Civil - Santiago del Estero', 12, true], ['', 10], ["Contrayente: {$nombreCompleto}, DNI {$dni}", 11],
                    ['Fecha de celebración: ' . now()->subYears(mt_rand(1, max(2, $edad - 22)))->format('d/m/Y'), 11],
                ])]
                : [5, 'pdf', 'Certificado de escolaridad', fn () => MiniPdf::hoja('CERTIFICADO DE ESCOLARIDAD', [
                    ["Se certifica que el/la alumno/a vinculado/a al agente {$nombreCompleto} cursa regularmente el año " . $anio . '.', 11],
                ])],
            [12, 'pdf', 'Declaración jurada de bienes y cargos', fn () => MiniPdf::hoja('DECLARACIÓN JURADA', [
                ["{$nombreCompleto}, DNI {$dni}, declara bajo juramento:", 11], ['', 10],
                ['- No desempeñar otro cargo público incompatible.', 10], ['- Los datos consignados en su legajo son verídicos.', 10],
                ['Fecha: ' . now()->subDays(mt_rand(10, 700))->format('d/m/Y'), 10],
            ])],
        ];

        $columnaNombre = Schema::hasColumn('documentos', 'nombre_original');

        foreach (array_slice($lista, 0, $this->documentos) as [$tipo, $formato, $descripcion, $generar]) {

            $contenido = $generar();
            $ruta = 'documentos/' . ArchivoSeguro::nombreDesdeContenido($contenido) . '.' . $formato;
            $cifrado = ArchivoSeguro::encriptar($contenido);

            Storage::disk('local')->put($ruta, $cifrado);

            $datos = ['ruta' => $ruta, 'descripcion' => $descripcion, 'tipodoc' => $tipo, 'legajo_id' => $legajo->id];

            if ($columnaNombre) {
                $datos['nombre_original'] = Str::slug($descripcion, '_') . '.' . $formato;
            }

            Documento::create($datos);

            $this->resumen['documentos']++;
            $this->resumen['bytes'] += strlen($cifrado);
        }
    }


    private function lineasExperiencia(Persona $persona): array
    {
        $lineas = [];

        foreach (AntecedenteLaboral::where('persona_id', $persona->id)->limit(4)->get() as $a) {
            $lineas[] = [$a->empleador . ' - ' . $a->cargo . ' (' . \Illuminate\Support\Carbon::parse($a->fecha_inicio)->year . '-' . \Illuminate\Support\Carbon::parse($a->fecha_fin)->year . ')', 10];
        }

        return $lineas ?: [['Sin experiencia previa registrada', 10]];
    }


    // imagen tipo DNI hecha con GD (letras sin acentos porque la fuente interna de GD no los dibuja)
    private function imagenDni(Persona $persona): string
    {
        $im = imagecreatetruecolor(640, 400);
        $fondo = imagecolorallocate($im, mt_rand(200, 225), mt_rand(225, 240), mt_rand(225, 245));
        $azul = imagecolorallocate($im, 40, 80, 130);
        $negro = imagecolorallocate($im, 20, 20, 20);
        $gris = imagecolorallocate($im, 120, 120, 120);
        $blanco = imagecolorallocate($im, 255, 255, 255);

        imagefilledrectangle($im, 0, 0, 640, 400, $fondo);
        imagefilledrectangle($im, 0, 0, 640, 52, $azul);
        imagestring($im, 5, 20, 18, 'REPUBLICA ARGENTINA - DOCUMENTO NACIONAL DE IDENTIDAD', $blanco);
        imagefilledrectangle($im, 24, 80, 190, 280, imagecolorallocate($im, 170, 180, 195));
        imagefilledellipse($im, 107, 150, 70, 70, $gris);
        imagefilledellipse($im, 107, 250, 130, 100, $gris);

        $filas = [
            ['Apellido', Str::ascii($persona->apellido)], ['Nombre', Str::ascii($persona->nombre)],
            ['DNI', number_format($persona->dni, 0, ',', '.')], ['Sexo', $persona->genero === 0 || ($persona->genero?->value ?? null) === 0 ? 'F' : 'M'],
            ['Nacimiento', \Illuminate\Support\Carbon::parse($persona->fecha_de_nacimiento)->format('d/m/Y')], ['CUIL', (string) $persona->cuil],
        ];

        $y = 92;

        foreach ($filas as [$etiqueta, $valor]) {
            imagestring($im, 3, 215, $y, strtoupper($etiqueta), $gris);
            imagestring($im, 5, 215, $y + 14, $valor, $negro);
            $y += 40;
        }

        imagestring($im, 3, 24, 330, 'Domicilio: ' . substr(Str::ascii($persona->domicilio), 0, 70), $negro);
        imagestring($im, 2, 24, 372, 'DOCUMENTO DE PRUEBA - DATOS FICTICIOS', $gris);

        return $this->jpeg($im, 72);
    }


    private function imagenPerfil(Persona $persona): string
    {
        $im = imagecreatetruecolor(400, 500);

        for ($y = 0; $y < 500; $y += 5) {
            imagefilledrectangle($im, 0, $y, 400, $y + 5, imagecolorallocate($im, 120 + intdiv($y, 6), 140 + intdiv($y, 8), 170 + intdiv($y, 12)));
        }

        $piel = imagecolorallocate($im, mt_rand(190, 235), mt_rand(150, 190), mt_rand(120, 160));
        imagefilledellipse($im, 200, 420, 340, 260, imagecolorallocate($im, mt_rand(40, 90), mt_rand(50, 100), mt_rand(60, 120)));
        imagefilledellipse($im, 200, 200, 190, 230, $piel);
        imagefilledellipse($im, 200, 130, 200, 110, imagecolorallocate($im, mt_rand(20, 90), mt_rand(15, 70), mt_rand(10, 50)));
        imagestring($im, 3, 10, 480, 'FOTO DE PRUEBA - FICTICIA - ' . substr(Str::ascii($persona->apellido), 0, 20), imagecolorallocate($im, 255, 255, 255));

        return $this->jpeg($im, 70);
    }


    private function jpeg($im, int $calidad): string
    {
        ob_start();
        imagejpeg($im, null, $calidad);

        return ob_get_clean();
    }


    // ---------------------------------------------------------- datos sueltos

    private function elegir(array $lista)
    {
        return $lista[mt_rand(0, count($lista) - 1)];
    }


    private function variante(array $lista, string $genero): string
    {
        return $this->elegir($lista)[$genero === 'F' ? 1 : 0];
    }


    // [valor => peso]
    private function ponderado(array $pesos): int
    {
        return (int) $this->ponderadoTexto($pesos);
    }


    private function ponderadoTexto(array $pesos)
    {
        $tirada = mt_rand(1, array_sum($pesos));

        foreach ($pesos as $valor => $peso) {
            $tirada -= $peso;

            if ($tirada <= 0) {
                return $valor;
            }
        }

        return array_key_first($pesos);
    }


    private function anioFin(int $edad, int $edadAlTerminar)
    {
        return now()->subYears(max(0, $edad - $edadAlTerminar))->subDays(mt_rand(0, 300));
    }


    private function localidad(): array
    {
        $pesos = [];

        foreach (self::LOCALIDADES as $i => $l) {
            $pesos[$i] = $l[2];
        }

        return self::LOCALIDADES[$this->ponderado($pesos)];
    }


    private function domicilio(string $localidad): string
    {
        $barrio = in_array($localidad, ['Santiago del Estero', 'La Banda'], true) ? 'B° ' . $this->elegir(self::BARRIOS) . ', ' : '';

        return $this->elegir(self::CALLES) . ' ' . mt_rand(10, 3900) . ', ' . $barrio . $localidad . ', Santiago del Estero';
    }


    private function telefono(): string
    {
        return '+54 9 385 4' . mt_rand(100, 999) . '-' . mt_rand(1000, 9999);
    }


    // los DNI van creciendo con el año de nacimiento (1960 ~ 13 millones, 2000 ~ 42 millones)
    private const DNI_POR_ANIO = [1950 => 6_000_000, 1955 => 9_500_000, 1960 => 13_500_000, 1965 => 17_000_000, 1970 => 21_500_000,
        1975 => 24_500_000, 1980 => 28_000_000, 1985 => 31_500_000, 1990 => 35_000_000, 1995 => 38_500_000, 2000 => 42_000_000,
        2005 => 46_000_000, 2010 => 50_000_000, 2015 => 54_000_000, 2020 => 58_000_000, 2026 => 60_000_000];


    private function dniNuevo(int $anioNacimiento): int
    {
        $anios = array_keys(self::DNI_POR_ANIO);
        $anio = min(max($anioNacimiento, $anios[0]), end($anios));

        // interpolacion lineal entre los dos puntos mas cercanos
        for ($i = 0; $i < count($anios) - 1; $i++) {
            if ($anio >= $anios[$i] && $anio <= $anios[$i + 1]) {
                $a = $anios[$i];
                $b = $anios[$i + 1];
                $centro = self::DNI_POR_ANIO[$a] + (self::DNI_POR_ANIO[$b] - self::DNI_POR_ANIO[$a]) * ($anio - $a) / ($b - $a);
                break;
            }
        }

        do {
            $dni = (int) ($centro + mt_rand(-900_000, 900_000));
        } while (isset($this->dniUsados[$dni]));

        return $this->dniUsados[$dni] = $dni;
    }


    // cuil con el digito verificador real (algoritmo de ARCA/AFIP)
    public static function cuil(int $dni, string $genero): int
    {
        $prefijo = $genero === 'F' ? 27 : 20;
        $base = $prefijo . str_pad((string) $dni, 8, '0', STR_PAD_LEFT);
        $pesos = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
        $suma = 0;

        foreach (str_split($base) as $i => $digito) {
            $suma += (int) $digito * $pesos[$i];
        }

        $resto = $suma % 11;

        if ($resto === 1) {
            $base = '23' . str_pad((string) $dni, 8, '0', STR_PAD_LEFT);
            $verificador = $genero === 'F' ? 4 : 9;
        } else {
            $verificador = $resto === 0 ? 0 : 11 - $resto;
        }

        return (int) ($base . $verificador);
    }


    private function slug(string $texto): string
    {
        return preg_replace('/[^a-z0-9]+/', '', strtolower(Str::ascii(explode(' ', $texto)[0])));
    }


    private function correoPersonal(string $nombre, string $apellido): string
    {
        return mb_substr($this->slug($nombre) . '.' . $this->slug($apellido) . mt_rand(10, 99) . '@correo.prueba', 0, 50);
    }


    // nombre.apellido@dominio, con un numero si ya existe
    private function correoUnico(string $nombre, string $apellido): string
    {
        $base = $this->slug($nombre) . '.' . $this->slug($apellido);
        $correo = "{$base}@{$this->dominio}";
        $n = 1;

        while (isset($this->emailsUsados[$correo])) {
            $n++;
            $correo = "{$base}{$n}@{$this->dominio}";
        }

        return $correo;
    }
}
