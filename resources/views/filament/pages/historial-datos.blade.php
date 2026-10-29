<x-filament-panels::page>

    <div style="display:flex; gap:1rem; flex-wrap:wrap">

        <x-filament::input.wrapper>
            <x-filament::input.select wire:model.live="tabla">
                @foreach ($this->tablas() as $t)
                    <option value="{{ $t }}">{{ $t }}</option>
                @endforeach
            </x-filament::input.select>
        </x-filament::input.wrapper>

        <x-filament::input.wrapper>
            <x-filament::input type="number" wire:model.live.debounce.500ms="registro" placeholder="Id del registro (vacío = últimos cambios)" />
        </x-filament::input.wrapper>

    </div>


    @php($versiones = $this->versiones())

    @forelse ($versiones as $v)

        <x-filament::section collapsible collapsed>

            <x-slot name="heading">
                #{{ $v->id }} · versión {{ $v->hist_version }} · {{ $v->hist_operacion }}
            </x-slot>

            <x-slot name="description">
                {{ $v->hist_fecha }} · {{ $v->hist_user_nombre ?? 'sistema' }} · {{ $v->hist_ip ?? '-' }}
            </x-slot>

            <table class="w-full text-sm">
                @foreach ((array) $v as $campo => $valor)
                    @continue(in_array($campo, ['password']))
                    <tr>
                        <td class="font-medium pr-4 py-1">{{ $campo }}</td>
                        <td class="py-1">{{ $valor }}</td>
                    </tr>
                @endforeach
            </table>

            @if ($registro)
                <div class="mt-4">
                    <x-filament::button color="warning" size="sm"
                        wire:click="restaurar({{ $v->hist_version }})"
                        wire:confirm="Volver este registro a la versión {{ $v->hist_version }}?">
                        Restaurar esta versión
                    </x-filament::button>
                </div>
            @endif

        </x-filament::section>

    @empty

        <p class="text-sm text-gray-500">No hay versiones guardadas todavía.</p>

    @endforelse

</x-filament-panels::page>
