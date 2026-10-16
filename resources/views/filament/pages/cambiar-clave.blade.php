<x-filament-panels::page>

    @if (auth()->user()->debe_cambiar_clave)
        <x-filament::section>
            <p class="text-sm">
                Tu clave actual es provisoria (primer ingreso o clave cambiada por un administrador).
                Elegí una propia para poder usar el sistema.
            </p>
        </x-filament::section>
    @endif

    <form wire:submit="guardar" id="form">
        {{ $this->form }}

        <x-filament::actions :actions="$this->getFormActions()" />
    </form>

</x-filament-panels::page>
