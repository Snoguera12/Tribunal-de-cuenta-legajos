<x-filament-panels::page>

    @if (! config('sistema.backup.password'))
        <x-filament::section>
            <p class="text-sm" style="color: #b45309">
                Los backups no van cifrados ni incluyen el .env. Configurar BACKUP_ARCHIVE_PASSWORD.
            </p>
        </x-filament::section>
    @endif


    <x-filament::section>

        <x-slot name="heading">Backups guardados</x-slot>

        <table class="w-full text-sm">

            <thead>
                <tr class="text-left">
                    <th class="py-2">Archivo</th>
                    <th>Tipo</th>
                    <th>Fecha</th>
                    <th>Tamaño</th>
                    <th>Hash</th>
                    <th></th>
                </tr>
            </thead>

            <tbody>
                @forelse ($this->copias() as $c)
                    <tr>
                        <td class="py-1">{{ $c['nombre'] }}</td>
                        <td>{{ $c['tipo'] }}</td>
                        <td>{{ $c['fecha']->format('d/m/Y H:i') }}</td>
                        <td>{{ number_format($c['bytes'] / 1048576, 2) }} MB</td>
                        <td>{{ $c['tiene_hash'] ? 'sí' : 'falta' }}</td>
                        <td style="display:flex; gap:.5rem">
                            <x-filament::button size="xs" color="gray" wire:click="verificar('{{ $c['nombre'] }}')">Verificar</x-filament::button>
                            <x-filament::button size="xs" wire:click="descargar('{{ $c['nombre'] }}')">Descargar</x-filament::button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-3 text-gray-500">Todavía no hay backups.</td></tr>
                @endforelse
            </tbody>

        </table>

        <p class="text-xs text-gray-500 mt-4">
            Restaurar se hace por consola: php artisan sistema:restaurar nombre.zip
        </p>

    </x-filament::section>

</x-filament-panels::page>
