<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Resources\Legajos\Widgets\LegajoWidget;
use App\Filament\Resources\Personas\Widgets\GeneroWidget;
use App\Filament\Resources\Personas\Widgets\PersonaTotalWidget;
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
            // Se declaran los escalones a mano y NO se usa Color::hex():
            // ese helper toma el tono del hex pero normaliza la luminosidad
            // a los niveles estándar de la rampa, así que un navy oscuro
            // termina produciendo un azul común y corriente.
            //
            // El 600 es el fondo del botón primario en modo claro: #1e4270
            // con texto blanco rinde 8.9:1. El 500 se usa en modo oscuro.
            ->colors([
                'primary' => [
                    50  => '#eef3f9',
                    100 => '#d5e0ee',
                    200 => '#adc2d9',
                    300 => '#7f9dc0',
                    400 => '#527aa6',
                    500 => '#2f5b8c',
                    600 => '#1e4270',
                    700 => '#16324f',
                    800 => '#102540',
                    900 => '#0b1a2c',
                    950 => '#07121f',
                ],
                'gray' => Color::Slate,
            ])

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