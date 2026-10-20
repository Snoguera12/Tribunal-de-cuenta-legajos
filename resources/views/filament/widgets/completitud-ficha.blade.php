{{-- Estilos en línea a propósito: así el widget se ve bien aunque no se recompile el tema de Tailwind. --}}
<x-filament-widgets::widget>
    <x-filament::section
        heading="Mi ficha"
        description="Qué datos de tu legajo digital están cargados y cuáles faltan."
        icon="heroicon-o-clipboard-document-check"
    >
        @if (! $persona)
            <p style="font-size: 0.875rem;">
                Tu usuario todavía no está vinculado a una persona. Avisale a RRHH para que complete tu ficha.
            </p>
        @else
            <div style="margin-bottom: 1rem;">
                <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 0.375rem; font-size: 0.875rem;">
                    <span>{{ $completos }} de {{ $total }} secciones completas</span>
                    <strong>{{ $porcentaje }}%</strong>
                </div>

                <div
                    role="progressbar"
                    aria-valuemin="0"
                    aria-valuemax="100"
                    aria-valuenow="{{ $porcentaje }}"
                    style="height: 0.5rem; border-radius: 9999px; overflow: hidden; background: color-mix(in oklab, currentColor 12%, transparent);"
                >
                    <div style="height: 100%; width: {{ $porcentaje }}%; background: var(--primary-600); border-radius: 9999px;"></div>
                </div>
            </div>

            <ul style="list-style: none; margin: 0; padding: 0;">
                @foreach ($items as $item)
                    <li style="display: flex; align-items: center; justify-content: space-between; gap: 0.75rem; padding: 0.625rem 0; {{ $loop->last ? '' : 'border-bottom: 1px solid color-mix(in oklab, currentColor 10%, transparent);' }}">
                        <div style="display: flex; align-items: center; gap: 0.625rem; min-width: 0;">
                            <x-filament::icon
                                :icon="$item['ok'] ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-circle'"
                                style="width: 1.5rem; height: 1.5rem; flex-shrink: 0; color: var({{ $item['ok'] ? '--success-500' : '--warning-500' }});"
                            />

                            <div style="min-width: 0;">
                                <div style="font-size: 0.875rem; font-weight: 500; line-height: 1.25rem;">{{ $item['titulo'] }}</div>
                                <div style="font-size: 0.75rem; line-height: 1rem; opacity: 0.7;">{{ $item['detalle'] }}</div>
                            </div>
                        </div>

                        <x-filament::link :href="$item['url']" size="sm" style="flex-shrink: 0;">Ver</x-filament::link>
                    </li>
                @endforeach
            </ul>

            @if ($completos < $total)
                <p style="margin-top: 0.75rem; font-size: 0.75rem; opacity: 0.7;">
                    Si algún dato falta o está desactualizado, pedile a RRHH que lo cargue.
                </p>
            @endif
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
