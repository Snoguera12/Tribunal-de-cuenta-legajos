<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Resources\Legajos\Widgets\LegajoWidget;
use App\Filament\Resources\Personas\Widgets\GeneroWidget;
use App\Filament\Resources\Personas\Widgets\PersonaTotalWidget;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class LegajosPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        // ─────────────────────────────────────────────────────────────
        // ELIMINADO: FilamentView::registerRenderHook('panels::body.start', …)
        //
        // Ese hook corría en TODAS las páginas del panel, no sólo en el
        // login, e inyectaba un </div> sin apertura que rompía el DOM.
        // El bloque institucional ahora vive en la vista del login:
        // resources/views/filament/pages/auth/login.blade.php
        // ─────────────────────────────────────────────────────────────

        return $panel
            ->default()
            ->id('legajos')
            ->path('legajos')
            ->brandName('TCSE Legajos')
            ->sidebarCollapsibleOnDesktop()
            ->login(Login::class)
            ->databaseNotifications()

            // Paleta institucional en un solo lugar. Esto reemplaza los
            // `background-color: #2563eb !important` de theme.css.
            //
            // Filament v4 exige OKLCH cuando se pasa un arreglo de tonos.
            // El hex al costado de cada línea es sólo referencia para leer
            // el archivo; el valor que Filament interpreta es el oklch().
            //
            // NO se usa Color::hex(): ese helper toma el tono del hex pero
            // normaliza la luminosidad a los niveles estándar de la rampa,
            // así que un navy oscuro termina produciendo un azul común.
            //
            // La rampa de primary está corrida un casillero respecto de la
            // versión anterior: el navy #1e4270 pasó de 600 a 700. Motivo:
            // v4 elige el tono en runtime según contraste WCAG y usa el 600
            // como fondo del botón sólido en AMBOS modos. Ese navy daba
            // 1.43:1 contra el panel oscuro — el botón desaparecía. Ahora
            // el 600 es #2f5b8c: 7:1 con texto blanco en los dos modos.
            ->colors([
                'primary' => [
                    50  => 'oklch(0.972 0.008 253.9)',   // #f2f6fb
                    100 => 'oklch(0.962 0.010 252.8)',   // #eef3f9
                    200 => 'oklch(0.903 0.023 254.4)',   // #d5e0ee
                    300 => 'oklch(0.806 0.040 250.7)',   // #adc2d9
                    400 => 'oklch(0.686 0.062 252.6)',   // #7f9dc0
                    500 => 'oklch(0.569 0.082 251.6)',   // #527aa6  hover en oscuro
                    600 => 'oklch(0.464 0.094 252.7)',   // #2f5b8c  botón principal
                    700 => 'oklch(0.378 0.089 255.7)',   // #1e4270  navy institucional
                    800 => 'oklch(0.311 0.063 251.2)',   // #16324f
                    900 => 'oklch(0.263 0.058 255.6)',   // #102540
                    950 => 'oklch(0.215 0.041 254.0)',   // #0b1a2c
                ],

                // Reemplaza Color::Slate. En Filament el gris no es sólo el
                // gris de los textos: define el fondo del panel entero en
                // ambos modos. Slate daba fondo #020617 y paneles #0f172a,
                // apenas 1.13:1 entre sí. Ese era el "todo negro, apenas se
                // nota algo".
                //
                // Jerarquía en oscuro: 950 fondo, 900 paneles, 800 inputs,
                // 700 bordes. El 700 importa más de lo que parece: v4 lo
                // usa como superficie de referencia para calcular el
                // contraste de íconos y textos secundarios en oscuro.
                'gray' => [
                    50  => 'oklch(0.975 0.005 258.3)',   // #f5f7fa
                    100 => 'oklch(0.947 0.011 252.1)',   // #e8eef5
                    200 => 'oklch(0.894 0.020 252.9)',   // #d3dde9
                    300 => 'oklch(0.802 0.033 254.0)',   // #b0c0d4
                    400 => 'oklch(0.720 0.042 256.6)',   // #94a6bf  texto secundario
                    500 => 'oklch(0.591 0.050 257.6)',   // #6b7f9c
                    600 => 'oklch(0.486 0.044 253.5)',   // #4e6178
                    700 => 'oklch(0.404 0.041 255.4)',   // #3a4a5f  bordes
                    800 => 'oklch(0.334 0.038 257.8)',   // #2a374a  inputs
                    900 => 'oklch(0.282 0.048 262.0)',   // #1c2941  paneles y sidebar
                    950 => 'oklch(0.208 0.031 259.0)',   // #0f1826  fondo general
                ],
            ])

            // El modo oscuro queda ACTIVO y el selector de tema también.
            // defaultThemeMode sólo decide con cuál arranca la primera vez:
            // sin esta línea Filament sigue la preferencia del sistema
            // operativo, y entonces una PC con Windows en claro y otra en
            // oscuro nunca se ven igual.
            //
            // Para que arranque en claro: ThemeMode::Light
            // Para que siga al sistema operativo: borrar esta línea
            ->darkMode(true)
            ->defaultThemeMode(ThemeMode::Light)

            ->viteTheme('resources/css/filament/legajos/theme.css')
            ->navigationGroups([
                'Agentes',
                'Papeles',
                'Institución',
                'Departamento',
            ])
            ->discoverResources(
                in: app_path('Filament/Resources'),
                for: 'App\Filament\Resources',
            )
            ->discoverPages(
                in: app_path('Filament/Pages'),
                for: 'App\Filament\Pages',
            )
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(
                in: app_path('Filament/Widgets'),
                for: 'App\Filament\Widgets',
            )
            ->widgets([
                AccountWidget::class,
                LegajoWidget::class,
                PersonaTotalWidget::class,
                GeneroWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
