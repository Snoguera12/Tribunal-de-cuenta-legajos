<?php

namespace Database\Seeders;

use App\Models\AntecedenteLaboral;
use App\Models\Area;
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
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

// crea datos de prueba: 206 personas (10 funcionarios, 5 rrhh, 1 admin, 190 empleados)
// los datos son inventados pero con formato real (dni, cuil, etc)
class PersonalSantiago206Seeder extends Seeder
{
    private string $passwordDemo = 'Legajo2026!';
    private string $dominio = '@tribunalsgo.gob.ar';

    // para que no se repitan
    private array $usedEmails = [];
    private array $usedDni = [];



    // numerito que va sumando para variar los datos
    private int $seed = 0;   

    // listas de datos de santiago del estero
    private array $calles = [
        'Av. Belgrano', 'Av. Roca', 'Av. Alsina', 'Av. Sarmiento', 'Pellegrini',
        '24 de Setiembre', 'Independencia', 'Rivadavia', 'Mitre', 'Moreno',
        'San Martín', 'Buenos Aires', 'Salta', 'Tucumán', 'Catamarca',
        'Libertad', 'Urquiza', '25 de Mayo', 'Avellaneda', 'Entre Ríos',
        'Corrientes', 'Chacabuco', 'Tacuarí', 'Pringles', 'Rondeau',
        'Colón', 'Alberdi', 'Roque Sáenz Peña', 'Absalón Rojas', 'Jujuy',
    ];
    private array $barriosCapitalLaBanda = [
        'Autonomista', 'Yapeyú', 'Egipto', 'Sarmiento', 'Villa Zeballos',
        'Nuevo Horizonte', 'Centenario', '9 de Julio', 'Independencia',
        'Borges', 'Máximo Ramos', 'Villa Griselda', 'El Zanjón',
        'Juan Felipe Ibarra', 'Almirante Brown', 'Nueva Banda',
    ];
    // codigo de telefono de cada localidad
    private array $codigoAreaPorLocalidad = [
        'Santiago del Estero (Capital)' => '0385', 'La Banda' => '0385',
        'Clodomira' => '0385', 'Beltrán' => '0385', 'Forres' => '0385',
        'Loreto' => '0385', 'Colonia Dora' => '0385', 'Fernández' => '0385',
        'Pinto' => '0385', 'Selva' => '0385', 'Nueva Esperanza' => '0385',
        'San Pedro de Guasayán' => '0385', 'Brea Pozo' => '0385', 'Villa Robles' => '0385',
        'Termas de Río Hondo' => '03858', 'Icaño' => '03858',
        'Añatuya' => '03844', 'Tintina' => '03844', 'Los Juríes' => '03844',
        'Quimilí' => '03843', 'Bandera' => '03843', 'Campo Gallo' => '03843',
        'Monte Quemado' => '03841', 'Pampa de los Guanacos' => '03841', 'Sachayoj' => '03841',
        'Suncho Corral' => '03841', 'Frías' => '03858', 'Sumampa' => '03854',
        'Villa Ojo de Agua' => '03854', 'Villa Atamisqui' => '03854',
    ];

    public function run(): void
    {
        $areaIds = Area::pluck('id', 'nombre');
        $cargoIds = Cargo::pluck('id', 'nombre');
        $categoriaIds = Categoria::pluck('id', 'nombre');

        if ($areaIds->isEmpty() || $cargoIds->isEmpty() || $categoriaIds->isEmpty()) {
            $this->command?->error('Corré primero AreaSeeder, CargoSeeder y CategoriaSeeder (o el DatabaseSeeder completo).');
            return;
        }

        $numLegajo = 2000;

        // 1) los funcionarios (10)
        $funcionarios = [
            ['María Cristina', 'Robledo', 'F', '1966-04-12', 'Santiago del Estero (Capital)', 'Presidente/a', 'Secretaria General', 'CAT 1', '2009-03-02'],
            ['Jorge Osvaldo', 'Salto', 'M', '1963-08-25', 'La Banda', 'Fiscal Jefe/a', 'Fiscal General 1', 'CAT 1', '2010-05-11'],
            ['Ana María', 'Vallejos', 'F', '1968-01-30', 'Santiago del Estero (Capital)', 'Fiscal Jefe/a', 'Fiscal General 2', 'CAT 1', '2011-02-14'],
            ['Carlos Alberto', 'Bazán', 'M', '1965-11-05', 'Termas de Río Hondo', 'Consejero/a de Cuentas', 'Auditoria', 'CAT 1', '2012-06-01'],
            ['Liliana Esther', 'Argañaraz', 'F', '1970-07-19', 'Añatuya', 'Consejero/a de Cuentas', 'Rendición de cuentas', 'CAT 2', '2013-09-16'],
            ['Ricardo Hugo', 'Contreras', 'M', '1962-03-08', 'Santiago del Estero (Capital)', 'Interventor', 'Mesa de entradas', 'CAT 1', '2008-11-03'],
            ['Marta Alicia', 'Elías', 'F', '1971-09-22', 'La Banda', 'Vocero', 'Secretaria General', 'CAT 2', '2014-04-07'],
            ['Rubén Darío', 'Carabajal', 'M', '1967-12-14', 'Frías', 'Coordinador Ejecutivo', 'Comision de estudios 1', 'CAT 2', '2013-01-20'],
            ['Beatriz Alejandra', 'Guzmán', 'F', '1969-05-27', 'Quimilí', 'Relator Mayor Coordinador', 'Comision de estudios 2', 'CAT 2', '2015-08-10'],
            ['Miguel Ángel', 'Palavecino', 'M', '1964-10-02', 'Santiago del Estero (Capital)', 'Servicio Jurídico del Estado', 'Municipios', 'CAT 2', '2011-07-05'],
        ];

        foreach ($funcionarios as $f) {
            $this->crearPersonal(
                nombre: $f[0], apellido: $f[1], sexo: $f[2], fechaNac: $f[3], localidad: $f[4],
                cargo: $f[5], area: $f[6], categoria: $f[7], fechaIngreso: $f[8],
                rol: 'funcionario', tipoContrato: 0, numLegajo: $numLegajo++,
                estadoCivil: 1, areaIds: $areaIds, cargoIds: $cargoIds, categoriaIds: $categoriaIds,
            );
        }

        // 2) rrhh (5)
        $rrhh = [
            ['Silvia Beatriz', 'Paz', 'F', '1980-06-23', 'Santiago del Estero (Capital)', 'Oficial Principal de Despacho', 'CAT 4', '2015-03-01'],
            ['Marcelo Fabián', 'Ávila', 'M', '1983-01-09', 'La Banda', 'Administrativo', 'CAT 6', '2016-02-10'],
            ['Gabriela Noelia', 'Cardozo', 'F', '1990-09-30', 'Termas de Río Hondo', 'Coordinador Ejecutivo', 'CAT 5', '2017-05-15'],
            ['Diego Sebastián', 'Ferreyra', 'M', '1985-04-18', 'Santiago del Estero (Capital)', 'Auditoria', 'CAT 7', '2018-08-20'],
            ['Paola Vanesa', 'Miranda', 'F', '1992-11-11', 'La Banda', 'Operador', 'CAT 8', '2019-10-01'],
        ];

        $rrhhResponsableEmail = null;
        foreach ($rrhh as $indexRrhh => $r) {
            $personaRrhh = $this->crearPersonal(
                nombre: $r[0], apellido: $r[1], sexo: $r[2], fechaNac: $r[3], localidad: $r[4],
                cargo: $r[5], area: 'Personal', categoria: $r[6], fechaIngreso: $r[7],
                rol: 'rrhh', tipoContrato: 2, numLegajo: $numLegajo++,
                estadoCivil: 1, areaIds: $areaIds, cargoIds: $cargoIds, categoriaIds: $categoriaIds,
            );

            // este rrhh queda como responsable de las bajas
            if ($indexRrhh === 0) {
                $rrhhResponsableEmail = $personaRrhh->email;
            }
        }

        // 3) el administrador (1)
        $this->crearPersonal(
            nombre: 'Federico Andrés', apellido: 'Toledo', sexo: 'M', fechaNac: '1988-02-17',
            localidad: 'Santiago del Estero (Capital)', cargo: 'Informático', area: 'Computos',
            categoria: 'CAT 3', fechaIngreso: '2016-06-01', rol: 'administrador', tipoContrato: 2,
            numLegajo: $numLegajo++, estadoCivil: 0, areaIds: $areaIds, cargoIds: $cargoIds,
            categoriaIds: $categoriaIds,
        );

        // 4) los empleados (190), se generan con datos random de las listas
        $this->generarEmpleados(190, $numLegajo, $areaIds, $cargoIds, $categoriaIds, $rrhhResponsableEmail);
    }

    // funcion que genera todos los empleados
    private function generarEmpleados(
        int $cantidad,
        int $numLegajoInicial,
        $areaIds,
        $cargoIds,
        $categoriaIds,
        ?string $rrhhResponsableEmail
    ): void {
        $nombresF = [
            'María José', 'Ana Laura', 'Claudia Fernanda', 'Verónica Soledad', 'Patricia Alejandra',
            'Mónica Beatriz', 'Lorena Vanesa', 'Natalia Soledad', 'Carla Estefanía', 'Yolanda del Valle',
            'Rosa Mabel', 'Estela Marina', 'Marisa Alejandra', 'Cynthia Paola', 'Sandra Noemí',
            'Viviana Karina', 'Griselda Raquel', 'Alejandra Fabiana', 'Norma Beatriz', 'Miriam Elizabeth',
            'Daniela Soledad', 'Romina Belén', 'Florencia Anahí', 'Julieta Milagros', 'Antonella Sofía',
            'Marina Elizabeth', 'Karina Vanesa', 'Susana Edith', 'Adriana Marisol', 'Elsa Noemí',
            'Camila Abril', 'Micaela Ayelén', 'Brenda Nahiara', 'Agustina Belén', 'Valeria Andrea',
            'Cecilia Inés', 'Silvana Raquel', 'Graciela Ester', 'Nora Susana', 'Teresa Isabel',
        ];
        $nombresM = [
            'Diego Alejandro', 'Héctor Ramón', 'Jorge Luis', 'Ricardo Daniel', 'Sergio Damián',
            'Andrés Fabián', 'Pablo Ezequiel', 'Emanuel Nicolás', 'Walter Ariel', 'Julio César',
            'Fabián Osvaldo', 'Nicolás Ezequiel', 'Ramón Alberto', 'Gustavo Adrián', 'Martín Ezequiel',
            'Lucas Gabriel', 'Cristian Javier', 'Maximiliano David', 'Rodrigo Emanuel', 'Federico Nahuel',
            'Alejandro Rafael', 'Oscar Alfredo', 'Roberto Carlos', 'Guillermo Ariel', 'Leandro Iván',
            'Matías Ezequiel', 'Ezequiel Damián', 'Facundo Nicolás', 'Braian Alexis', 'Ivo Tomás',
            'Sebastián Andrés', 'Hugo Rolando', 'Néstor Fabián', 'Mauricio Adrián', 'Adrián Gustavo',
            'Marcos Antonio', 'Raúl Ernesto', 'Enzo Gastón', 'Bruno Agustín', 'Franco Nahuel',
        ];
        $apellidos = [
            'Ledesma', 'Juárez', 'Paz', 'Ávila', 'Cardozo', 'Herrera', 'Gómez', 'Ibáñez', 'Barrionuevo',
            'Coronel', 'Suárez', 'Acuña', 'Díaz', 'Nazur', 'Salomón', 'Taboada', 'Iturre', 'Gerez',
            'Farías', 'Corbalán', 'Romano', 'Frías', 'Ibarra', 'Sayago', 'Alderete', 'Argañaraz',
            'Brandán', 'Carabajal', 'Contreras', 'Cuéllar', 'Elías', 'Ferreyra', 'Gauna', 'Godoy',
            'Guzmán', 'Jiménez', 'Leiva', 'López', 'Luna', 'Maldonado', 'Medina', 'Miranda', 'Moreno',
            'Navarro', 'Olivera', 'Ortiz', 'Palavecino', 'Pereyra', 'Quiroga', 'Ramírez', 'Ríos',
            'Robledo', 'Rojas', 'Romero', 'Sánchez', 'Sosa', 'Toledo', 'Torres', 'Vega', 'Villalba',
            'Zurita', 'Ávalos', 'Bazán', 'Britos', 'Carrizo', 'Ceballos', 'Chávez', 'Delgado', 'Escobar',
            'Figueroa', 'Flores', 'García', 'Heredia', 'Jerez', 'Lescano', 'Maidana', 'Molina', 'Moyano',
            'Nieva', 'Ojeda', 'Oviedo', 'Pérez', 'Ponce', 'Ramos', 'Reyes', 'Rodríguez', 'Ruiz', 'Salazar',
            'Silva', 'Talavera', 'Tévez', 'Vera', 'Vidal', 'Yapura', 'Zalazar',
        ];
        $localidades = array_keys($this->codigoAreaPorLocalidad);

        // cargos que no son de jefes
        $cargosEmpleado = [
            ['Administrativo', ['CAT 6', 'CAT 7', 'CAT 8', 'CAT 9']],
            ['Auditoria', ['CAT 5', 'CAT 6', 'CAT 7']],
            ['Informático', ['CAT 5', 'CAT 6', 'CAT 7']],
            ['Oficial Mayor de Estudios', ['CAT 4', 'CAT 5']],
            ['Oficial Principal de Despacho', ['CAT 4', 'CAT 5']],
            ['Operador', ['CAT 6', 'CAT 7', 'CAT 8']],
            ['Ordenanza', ['CAT 10', 'CAT 11', 'CAT 12']],
            ['Sección de Fiscalización', ['CAT 7', 'CAT 8', 'CAT 9']],
        ];
        $areasEmpleado = array_values(array_diff($areaIds->keys()->toArray(), ['Personal']));

        $motivosBaja = ['Renuncia', 'Despido', 'VencimientoContrato', 'Jubilacion', 'Fallecimiento', 'Incapacidad', 'Traslado'];

        $numLegajo = $numLegajoInicial;
        $bajaCount = 0;

        for ($i = 0; $i < $cantidad; $i++) {
            $esMujer = $i % 2 === 0;
            $nombre = $esMujer
                ? $nombresF[($i * 7) % count($nombresF)]
                : $nombresM[($i * 7) % count($nombresM)];
            $apellido = $apellidos[($i * 13 + 5) % count($apellidos)];
            $localidad = $localidades[$i % count($localidades)];

            $edad = 22 + ($i % 43); // 22 a 64 años
            $anioNac = (int) date('Y') - $edad;
            $mesNac = ($i % 12) + 1;
            $diaNac = ($i % 28) + 1;
            $fechaNac = sprintf('%04d-%02d-%02d', $anioNac, $mesNac, $diaNac);

            $estadoCivil = $i % 4; // 0 Soltero, 1 Casado, 2 Divorciado, 3 Viudo

            [$cargoNombre, $categoriasPosibles] = $cargosEmpleado[$i % count($cargosEmpleado)];
            $categoriaNombre = $categoriasPosibles[$i % count($categoriasPosibles)];
            $areaNombre = $areasEmpleado[$i % count($areasEmpleado)];

            $tipoContrato = ($i % 6 === 0) ? 1 : 2; // mayoría Permanente, algunos Locación
            $anioIngreso = min(2026, $anioNac + 22 + ($i % 6)); // ingresó siendo adulto
            $anioIngreso = max(2008, $anioIngreso);
            $fechaIngreso = sprintf('%04d-%02d-%02d', $anioIngreso, (($i % 12) + 1), (($i % 27) + 1));

            $esBaja = ($i % 8 === 3); // ~12% del personal está de baja
            $motivoBaja = null;
            $fechaBaja = null;
            if ($esBaja) {
                $motivoBaja = $motivosBaja[$bajaCount % count($motivosBaja)];
                $bajaCount++;
                $fechaBaja = sprintf('2025-%02d-%02d', (($i % 12) + 1), (($i % 27) + 1));
            }

            $this->crearPersonal(
                nombre: $nombre,
                apellido: $apellido,
                sexo: $esMujer ? 'F' : 'M',
                fechaNac: $fechaNac,
                localidad: $localidad,
                cargo: $cargoNombre,
                area: $areaNombre,
                categoria: $categoriaNombre,
                fechaIngreso: $fechaIngreso,
                rol: 'empleado',
                tipoContrato: $tipoContrato,
                numLegajo: $numLegajo++,
                estadoCivil: $estadoCivil,
                areaIds: $areaIds,
                cargoIds: $cargoIds,
                categoriaIds: $categoriaIds,
                estadoAlta: !$esBaja,
                motivoBaja: $motivoBaja,
                fechaBaja: $fechaBaja,
                rrhhResponsableEmail: $rrhhResponsableEmail,
            );
        }
    }

    // funcion que crea la persona, el usuario y el legajo juntos
    private function crearPersonal(
        string $nombre,
        string $apellido,
        string $sexo, // 'F' | 'M'
        string $fechaNac,
        string $localidad,
        string $cargo,
        string $area,
        string $categoria,
        string $fechaIngreso,
        string $rol,
        int $tipoContrato,
        int $numLegajo,
        int $estadoCivil,
        $areaIds,
        $cargoIds,
        $categoriaIds,
        bool $estadoAlta = true,
        ?string $motivoBaja = null,
        ?string $fechaBaja = null,
        ?string $rrhhResponsableEmail = null,
    ): Persona {
        $seed = $this->seed++;

        $dni = $this->generarDniUnico(20000000 + $numLegajo * 61 + crc32($nombre . $apellido) % 900000);
        [$cuil, ] = $this->calcularCuil($dni, $sexo);
        $email = $this->emailGenerado($nombre, $apellido);
        [$domicilio, $telefono, $telefonoEmergencia] = $this->generarContacto($localidad, $seed);

        $persona = Persona::create([
            'nombre' => $nombre,
            'apellido' => $apellido,
            'dni' => $dni,
            'cuil' => $cuil,
            'email' => $email,
            'genero' => $sexo === 'F' ? 0 : 1,
            'estado_civil' => $estadoCivil,
            'fecha_de_nacimiento' => $fechaNac,
            'domicilio' => $domicilio,
            'telefono' => $telefono,
            'telefono_emergencia' => $telefonoEmergencia,
        ]);

        User::create([
            'name' => $nombre . ' ' . $apellido,
            'email' => $email,
            'password' => Hash::make($this->passwordDemo),
            'rol' => $rol,
            'email_verified_at' => now(),
            'persona_id' => $persona->id,
        ]);

        $legajo = Legajo::create([
            'num_legajo' => $numLegajo,
            'estado' => $estadoAlta ? 1 : 0,
            'fecha_de_ingreso' => $fechaIngreso . ' 08:00:00',
            'tipo_contrato' => $tipoContrato,
            'persona_id' => $persona->id,
            'categoria_id' => $categoriaIds[$categoria],
            'cargo_id' => $cargoIds[$cargo],
            'area_id' => $areaIds[$area],
        ]);

        if (!$estadoAlta && $motivoBaja) {
            $motivos = [
                'Renuncia' => 0, 'Despido' => 1, 'VencimientoContrato' => 2,
                'Jubilacion' => 3, 'Fallecimiento' => 4, 'Incapacidad' => 5, 'Traslado' => 6,
            ];
            $rrhh = $rrhhResponsableEmail ? User::where('email', $rrhhResponsableEmail)->first() : null;
            Historialbaja::create([
                'legajo_id' => $legajo->id,
                'user_id' => $rrhh?->id ?? 1,
                'motivo' => $motivos[$motivoBaja],
                'fecha_baja' => $fechaBaja ?? now()->toDateString(),
            ]);
        }

        $anioNac = (int) explode('-', $fechaNac)[0];
        $anioIngreso = (int) explode('-', $fechaIngreso)[0];
        $esCupula = in_array($rol, ['funcionario', 'rrhh', 'administrador'], true);

        $this->agregarDatosRelacionados($persona, $legajo, $seed, $nombre, $apellido, $anioNac, $anioIngreso, $esCupula);

        return $persona;
    }

    // hace la direccion y el telefono segun la localidad
    private function generarContacto(string $localidad, int $seed): array
    {
        $calle = $this->calles[($seed * 7 + 3) % count($this->calles)];
        $altura = 50 + (($seed * 37) % 3950);
        $domicilio = "{$calle} {$altura}";

        if (in_array($localidad, ['Santiago del Estero (Capital)', 'La Banda'], true)) {
            $barrio = $this->barriosCapitalLaBanda[($seed * 11 + 5) % count($this->barriosCapitalLaBanda)];
            $domicilio .= ", B° {$barrio}";
        }
        $domicilio .= ", {$localidad}";

        $codigo = $this->codigoAreaPorLocalidad[$localidad] ?? '0385';

        $numero1 = 4000000 + (crc32($localidad . $seed . 'tel1') % 5999999);
        $numero2 = 4000000 + (crc32($localidad . $seed . 'tel2') % 5999999);

        $telefono = "{$codigo} 15-{$numero1}";
        $telefonoEmergencia = "{$codigo} 15-{$numero2}";

        return [$domicilio, $telefono, $telefonoEmergencia];
    }

    // agrega estudios, familia, cursos, idiomas y antecedentes a todos
    private function agregarDatosRelacionados(
        Persona $persona,
        Legajo $legajo,
        int $seed,
        string $nombre,
        string $apellido,
        int $anioNac,
        int $anioIngreso,
        bool $esCupula,
    ): void {
        $edad = (int) date('Y') - $anioNac;

        $institucionesEstudio = [
            'Universidad Nacional de Santiago del Estero (UNSE)',
            'Universidad Católica de Santiago del Estero (UCSE)',
            'Instituto Superior de Formación Docente N°1',
            'Instituto Superior de Formación Docente N°2',
            'Escuela Normal Superior',
            'Escuela Técnica N°1',
        ];
        $titulosPorNivel = [
            7 => ['Contador/a Público/a', 'Abogado/a', 'Ingeniero/a en Sistemas de Información', 'Licenciado/a en Economía', 'Licenciado/a en Administración', 'Arquitecto/a', 'Licenciado/a en Ciencias de la Educación'],
            6 => ['Técnico/a Superior en Administración Pública', 'Técnico/a Superior en Análisis de Sistemas', 'Profesor/a de Nivel Primario', 'Técnico/a Superior en Higiene y Seguridad'],
            8 => ['Doctor/a en Ciencias Económicas'],
            9 => ['Magíster en Administración Pública'],
        ];
        $cursosPool = [
            ['Excel Avanzado para la Gestión Pública', '40 horas'],
            ['Atención al Público y Mesa de Entradas', '20 horas'],
            ['Gestión Digital de Legajos', '30 horas'],
            ['Redacción de Actos Administrativos', '25 horas'],
            ['Ley de Procedimiento Administrativo Provincial', '15 horas'],
            ['Seguridad e Higiene Laboral', '20 horas'],
            ['Herramientas de Ofimática', '30 horas'],
            ['Archivo y Gestión Documental', '20 horas'],
            ['Alta Dirección Pública', '35 horas'],
            ['Ética y Transparencia en la Función Pública', '20 horas'],
        ];
        $empleadoresAntecedente = [
            ['Municipalidad de La Banda', 'La Banda, Santiago del Estero'],
            ['Municipalidad de Santiago del Estero', 'Santiago del Estero (Capital)'],
            ['Correo Argentino', 'Santiago del Estero (Capital)'],
            ['Instituto Provincial de Vivienda y Urbanismo (IPVU)', 'Santiago del Estero (Capital)'],
            ['EDESE (Empresa Distribuidora de Energía)', 'Santiago del Estero (Capital)'],
            ['Aguas de Santiago SAPEM', 'Santiago del Estero (Capital)'],
            ['Banco Santiago del Estero (Bansantiago)', 'Santiago del Estero (Capital)'],
            ['Poder Judicial de Santiago del Estero', 'Santiago del Estero (Capital)'],
        ];
        $motivosEgreso = ['Renuncia', 'Fin de contrato', 'Reestructuración', 'Traslado a otro organismo'];
        $idiomasPool = ['Inglés', 'Portugués', 'Quichua Santiagueño'];

        // 1) estudio y titulo
        if ($esCupula) {
            $nivelesPosibles = [7, 7, 7, 6, 8, 9]; // cúpula: mayoría universitario
        } elseif ($edad < 27) {
            $nivelesPosibles = [4, 4, 6, 7]; // más jóvenes: secundario/terciario, algún universitario
        } else {
            $nivelesPosibles = [4, 6, 6, 7, 7, 8, 9];
        }
        $nivel = $nivelesPosibles[$seed % count($nivelesPosibles)];

        if ($nivel === 4) {
            Estudio::create([
                'institucion' => 'Escuela Secundaria N°' . (($seed % 20) + 1),
                'nivel_estudio' => 4,
                'fecha_fin' => sprintf('%04d-12-15', min($anioNac + 18, $anioIngreso - 1)),
                'persona_id' => $persona->id,
            ]);
        } else {
            $institucion = $institucionesEstudio[$seed % count($institucionesEstudio)];
            $tituloOpciones = $titulosPorNivel[$nivel];
            $tituloNombre = $tituloOpciones[$seed % count($tituloOpciones)];
            $fechaFinEstudio = sprintf('%04d-%02d-15', min($anioNac + 25, $anioIngreso - 1), (($seed % 12) + 1));

            $estudio = Estudio::create([
                'institucion' => $institucion,
                'nivel_estudio' => $nivel,
                'fecha_fin' => $fechaFinEstudio,
                'persona_id' => $persona->id,
            ]);
            Titulo::create([
                'nombre' => $tituloNombre,
                'estudio_id' => $estudio->id,
            ]);
        }

        // 2) familiares
        $this->crearFamiliaresDemo($persona->id, $seed);

        // 3) cursos
        $cantCursos = $esCupula ? 2 : (1 + ($seed % 2));
        for ($c = 0; $c < $cantCursos; $c++) {
            [$cursoNombre, $duracion] = $cursosPool[($seed + $c * 3) % count($cursosPool)];
            Curso::create([
                'persona_id' => $persona->id,
                'nombre' => $cursoNombre,
                'institucion' => 'Instituto de Capacitación Provincial',
                'duracion' => $duracion,
                'fecha' => sprintf('202%d-%02d-10', (($seed + $c) % 4) + 2, ((($seed + $c) % 12) + 1)),
                'tiene_certificado' => true,
            ]);
        }

        // 4) idiomas
        Idioma::create([
            'persona_id' => $persona->id,
            'idioma' => $idiomasPool[$seed % count($idiomasPool)],
            'nivel' => 1 + ($seed % 3), // 1 Básico .. 3 Avanzado
        ]);
        if ($seed % 5 === 0) {
            Idioma::create([
                'persona_id' => $persona->id,
                'idioma' => $idiomasPool[($seed + 1) % count($idiomasPool)],
                'nivel' => $seed % 2,
            ]);
        }

        // 5) antecedente laboral
        [$empleador, $lugar] = $empleadoresAntecedente[$seed % count($empleadoresAntecedente)];
        $finAnt = $anioIngreso - 1;
        $inicioAnt = max($anioNac + 18, $finAnt - (1 + $seed % 5));
        if ($inicioAnt < $finAnt) {
            AntecedenteLaboral::create([
                'persona_id' => $persona->id,
                'empleador' => $empleador,
                'lugar_de_trabajo' => $lugar,
                'cargo' => $esCupula ? 'Coordinador/a' : 'Administrativo',
                'fecha_inicio' => sprintf('%04d-03-01', $inicioAnt),
                'fecha_fin' => sprintf('%04d-02-28', $finAnt),
                'motivo_egreso' => $motivosEgreso[$seed % count($motivosEgreso)],
            ]);
        }

        // 6) documentos (curriculum a todos, licencia a algunos)
        Documento::create([
            'descripcion' => 'Curriculum Vitae actualizado de ' . $nombre . ' ' . $apellido,
            'tipodoc' => 11, // Curriculum
            'legajo_id' => $legajo->id,
        ]);
        if ($seed % 5 === 0) {
            Documento::create([
                'descripcion' => 'Licencia solicitada por ' . $nombre . ' ' . $apellido,
                'tipodoc' => 3, // Licencia
                'legajo_id' => $legajo->id,
            ]);
        }
    }

    private function crearFamiliaresDemo(int $personaId, int $seed): void
    {
        $nombresF = ['María', 'Ana', 'Rosa', 'Elena', 'Gabriela', 'Silvia', 'Marta', 'Estela'];
        $nombresM = ['Juan', 'Pedro', 'Carlos', 'Luis', 'Ramón', 'José', 'Mario', 'Hugo'];
        $apellidos = ['Ledesma', 'Juárez', 'Paz', 'Ávila', 'Cardozo', 'Herrera', 'Gómez', 'Ibáñez'];
        $parentescoMap = ['Conyuge' => 0, 'Padres' => 1, 'Hijos' => 2, 'Sobrinos' => 3, 'Suegros' => 4];
        $parentescos = array_keys($parentescoMap);

        $cantidad = 1 + ($seed % 3); // 1 a 3 familiares
        for ($j = 0; $j < $cantidad; $j++) {
            $idx = ($seed + $j * 17);
            $esMujer = $idx % 2 === 0;
            $nombreFam = $esMujer ? $nombresF[$idx % count($nombresF)] : $nombresM[$idx % count($nombresM)];
            $apellidoFam = $apellidos[($idx * 3 + 1) % count($apellidos)];
            $parentescoNombre = $parentescos[$j % count($parentescos)];
            $dniFam = $parentescoNombre === 'Hijos' ? null : $this->generarDniUnico(50000000 + $idx * 3);

            Familiar::create([
                'nombre' => $nombreFam,
                'apellido' => $apellidoFam,
                'dni' => $dniFam,
                'fecha_de_nacimiento' => sprintf('%04d-%02d-%02d', 1955 + ($idx % 45), (($idx % 12) + 1), (($idx % 27) + 1)),
                'parentesco' => $parentescoMap[$parentescoNombre],
                'vive' => 0, // Vivo
                'persona_id' => $personaId,
            ]);
        }
    }

    private function emailGenerado(string $nombre, string $apellido): string
    {
        $primerNombre = trim(explode(' ', $nombre)[0]);
        $slug = Str::slug($primerNombre . '.' . $apellido, '.');
        $base = $slug;
        $n = 1;
        while (isset($this->usedEmails[$slug])) {
            $n++;
            $slug = $base . $n;
        }
        $this->usedEmails[$slug] = true;

        return $slug . $this->dominio;
    }

    private function generarDniUnico(int $semilla): int
    {
        $dni = $semilla;
        while ($dni < 10000000 || $dni > 45999999 || isset($this->usedDni[$dni])) {
            $dni++;
            if ($dni > 45999999) {
                $dni = 10000000;
            }
        }
        $this->usedDni[$dni] = true;

        return $dni;
    }

    // calcula el cuil (numero verificador de verdad)
    private function calcularCuil(int $dni, string $sexo): array
    {
        $prefijo = $sexo === 'F' ? '27' : '20';
        $dniPad = str_pad((string) $dni, 8, '0', STR_PAD_LEFT);
        $base = $prefijo . $dniPad;

        $pesos = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];
        $suma = 0;
        for ($i = 0; $i < 10; $i++) {
            $suma += ((int) $base[$i]) * $pesos[$i];
        }
        $resto = $suma % 11;

        if ($resto === 0) {
            $verificador = 0;
        } elseif ($resto === 1) {
            $prefijo = '23';
            $verificador = $sexo === 'F' ? 4 : 9;
        } else {
            $verificador = 11 - $resto;
        }

        $cuilCompleto = (int) ($prefijo . $dniPad . $verificador);

        return [$cuilCompleto, $prefijo];
    }
}
