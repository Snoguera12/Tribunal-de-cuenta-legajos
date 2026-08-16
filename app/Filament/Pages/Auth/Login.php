<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login as BaseLogin;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class Login extends BaseLogin
{
    public function getHeading(): string | Htmlable
    {
        return new HtmlString(
            '<div class="tcse-login-heading">
                <div class="tcse-login-brand">TCSE Legajos</div>
                <div class="tcse-login-welcome">Bienvenido de nuevo</div>
            </div>'
        );
    }

    public function getSubheading(): string | Htmlable | null
    {
        return 'Ingrese sus credenciales para continuar';
    }
}