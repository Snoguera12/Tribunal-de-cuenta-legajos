{{--
    resources/views/filament/pages/auth/login.blade.php

    Acceso — Sistema de Legajos, Tribunal de Cuentas de Santiago del Estero

    Todo el bloque institucional vive acá, no en un render hook: sólo debe
    aparecer en esta página. La clase raíz .tcse-login es además el gancho
    con el que theme.css neutraliza el layout simple de Filament, sin
    necesidad de !important.
--}}

<div class="tcse-login">

    {{-- ══════════════════════════════════════════════════════════════
         Columna institucional — oculta por debajo de 1024px
         ══════════════════════════════════════════════════════════════ --}}
    <aside class="tcse-login__brand">

        <header class="tcse-brand">
            <img
                class="tcse-brand__crest"
                src="{{ asset('images/escudo-tcse.svg') }}"
                alt=""
                aria-hidden="true"
                width="72"
                height="88"
                onerror="this.style.display='none'"
            >

            {{-- Nace en el borde derecho del escudo y sale por la esquina.
                 Ya no es un corte flotante: tiene punto de origen. --}}
            <span class="tcse-brand__ray" aria-hidden="true"></span>

            <p class="tcse-brand__wordmark">
                <span class="tcse-brand__name">Tribunal de Cuentas</span>
                <span class="tcse-brand__place">
                    Santiago del Estero<span class="tcse-brand__diamond" aria-hidden="true">◆</span>
                </span>
            </p>

            <p class="tcse-brand__description">
                Sistema institucional de gestión documental para el personal del organismo.
            </p>
        </header>

        {{-- La foto ya existe en public/images/ pero no estaba referenciada
             en ningún lado. object-position controla el reencuadre; el
             degradado del ::after funde la base con el azul de la franja. --}}
        <figure class="tcse-login__photo">
            <img
                src="{{ asset('images/tribunal-edificio.jpg') }}"
                alt="Fachada de la sede del Tribunal de Cuentas de Santiago del Estero"
                loading="eager"
                decoding="async"
            >
        </figure>

        <ul class="tcse-values">
            <li class="tcse-values__item">
                @svg('heroicon-o-eye', 'tcse-values__icon')
                <span>Transparencia</span>
            </li>
            <li class="tcse-values__item">
                @svg('heroicon-o-shield-check', 'tcse-values__icon')
                <span>Control</span>
            </li>
            <li class="tcse-values__item">
                @svg('heroicon-o-bolt', 'tcse-values__icon')
                <span>Eficiencia</span>
            </li>
            <li class="tcse-values__item">
                @svg('heroicon-o-scale', 'tcse-values__icon')
                <span>Responsabilidad</span>
            </li>
        </ul>
    </aside>

    {{-- ══════════════════════════════════════════════════════════════
         Columna de acceso
         ══════════════════════════════════════════════════════════════ --}}
    <main class="tcse-login__access">

        {{-- Identidad reducida: sólo en mobile, donde la columna izquierda
             no se muestra. Sin esto la marca desaparece por completo. --}}
        <div class="tcse-brand--compact">
            <img
                src="{{ asset('images/escudo-tcse.svg') }}"
                alt=""
                aria-hidden="true"
                width="40"
                height="49"
            >
            <p class="tcse-brand__wordmark">
                <span class="tcse-brand__name">Tribunal de Cuentas</span>
                <span class="tcse-brand__place">
                    Santiago del Estero<span class="tcse-brand__diamond" aria-hidden="true">◆</span>
                </span>
            </p>
        </div>

        <section class="tcse-card">
            <header class="tcse-card__header">
                <h1 class="tcse-card__title">Iniciar sesión</h1>
                <p class="tcse-card__subtitle">Accedé al sistema de Legajos</p>
            </header>

            {{--
                v4 renderiza el formulario desde el schema de la página
                (content(), en Filament\Auth\Pages\Login). Esto incluye ya:
                  · los render hooks AUTH_LOGIN_FORM_BEFORE / _AFTER
                  · el formulario y sus acciones
                  · el desafío de doble factor, si está habilitado

                NO ensamblar esto a mano. getCachedFormActions() es de v3 y
                no existe en v4.11.4; hasFullWidthFormActions() es protected
                y no se puede llamar desde el Blade.
            --}}
            {{ $this->content }}

            {{--
                SSO — DESACTIVADO
                La ruta 'sso.redirect' no existe todavía (no figura en
                `php artisan route:list`). Descomentá este bloque recién
                cuando la tengas, o el render falla con RouteNotFoundException.

            <div class="tcse-sep" role="separator" aria-orientation="horizontal">
                <span class="tcse-sep__rule"></span>
                <span class="tcse-sep__label">o</span>
                <span class="tcse-sep__rule"></span>
            </div>

            <a href="{{ route('sso.redirect') }}" class="tcse-btn-sso">
                @svg('heroicon-m-shield-check', 'tcse-btn-sso__icon')
                <span>Iniciar sesión con SSO</span>
            </a>
            --}}
        </section>

        <footer class="tcse-footer">
            {{-- Cuando exista la ruta de ayuda, reemplazar el <span> por:
                 <a href="{{ route('ayuda') }}" class="tcse-footer__link">Ayuda</a> --}}
            <span class="tcse-footer__link">Ayuda</span>
            <span class="tcse-footer__dot" aria-hidden="true">·</span>
            <span class="tcse-footer__secure">
                @svg('heroicon-m-lock-closed', 'tcse-footer__icon')
                <span>Sistema seguro</span>
            </span>
        </footer>
    </main>

</div>