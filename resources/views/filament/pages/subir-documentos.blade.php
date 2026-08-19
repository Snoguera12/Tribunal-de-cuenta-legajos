<x-filament-panels::page>
    <form wire:submit="guardar" id="form">
        {{ $this->form }}
        
        <x-filament::actions
            :actions="$this->getFormActions()"
        />
    </form>
</x-filament-panels::page>