@props([
    'currentPageOptionProperty' => 'tableRecordsPerPage',
    'extremeLinks' => false,
    'paginator',
    'pageOptions' => [],
])

@php
    use Illuminate\Contracts\Pagination\CursorPaginator;

    $isRtl = __('filament-panels::layout.direction') === 'rtl';
    $isSimple = ! $paginator instanceof \Illuminate\Pagination\LengthAwarePaginator;
@endphp

<nav
    aria-label="{{ __('filament::components/pagination.label') }}"
    role="navigation"
    {{
        $attributes->class([
            'fi-pagination',
            'fi-simple' => $isSimple,
        ])
    }}
>
    @if (! $paginator->onFirstPage())
        @php
            if ($paginator instanceof CursorPaginator) {
                $wireClickAction = "setPage('{$paginator->previousCursor()->encode()}', '{$paginator->getCursorName()}')";
            } else {
                $wireClickAction = "previousPage('{$paginator->getPageName()}')";
            }
        @endphp

        <x-filament::button
            color="gray"
            rel="prev"
            :wire:click="$wireClickAction"
            :wire:key="$this->getId() . '.pagination.previous'"
            class="fi-pagination-previous-btn"
        >
            {{ __('filament::components/pagination.actions.previous.label') }}
        </x-filament::button>
    @endif

    @if (! $isSimple)
        <span class="fi-pagination-overview">
            {{
                trans_choice(
                    'filament::components/pagination.overview',
                    $paginator->total(),
                    [
                        'first' => \Illuminate\Support\Number::format($paginator->firstItem() ?? 0),
                        'last' => \Illuminate\Support\Number::format($paginator->lastItem() ?? 0),
                        'total' => \Illuminate\Support\Number::format($paginator->total()),
                    ],
                )
            }}
        </span>
    @endif

    @if (count($pageOptions) > 1)
        <div class="fi-pagination-records-per-page-select-ctn">
            <label class="fi-pagination-records-per-page-select fi-compact">
                <x-filament::input.wrapper>
                    <x-filament::input.select
                        :wire:model.live="$currentPageOptionProperty"
                    >
                        @foreach ($pageOptions as $option)
                            <option value="{{ $option }}">
                                {{ $option === 'all' ? __('filament::components/pagination.fields.records_per_page.options.all') : $option }}
                            </option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>

                <span class="fi-sr-only">
                    {{ __('filament::components/pagination.fields.records_per_page.label') }}
                </span>
            </label>

            <label class="fi-pagination-records-per-page-select">
                <x-filament::input.wrapper
                    :prefix="__('filament::components/pagination.fields.records_per_page.label')"
                >
                    <x-filament::input.select
                        :wire:model.live="$currentPageOptionProperty"
                    >
                        @foreach ($pageOptions as $option)
                            <option value="{{ $option }}">
                                {{ $option === 'all' ? __('filament::components/pagination.fields.records_per_page.options.all') : $option }}
                            </option>
                        @endforeach
                    </x-filament::input.select>
                </x-filament::input.wrapper>
            </label>
        </div>
    @endif

    @if ($paginator->hasMorePages())
        @php
            if ($paginator instanceof CursorPaginator) {
                $wireClickAction = "setPage('{$paginator->nextCursor()->encode()}', '{$paginator->getCursorName()}')";
            } else {
                $wireClickAction = "nextPage('{$paginator->getPageName()}')";
            }
        @endphp

        <x-filament::button
            color="gray"
            rel="next"
            :wire:click="$wireClickAction"
            :wire:key="$this->getId() . '.pagination.next'"
            class="fi-pagination-next-btn"
        >
            {{ __('filament::components/pagination.actions.next.label') }}
        </x-filament::button>
    @endif

    @if ((! $isSimple) && $paginator->hasPages())
        <ol class="fi-pagination-items">
            @if (! $paginator->onFirstPage())
                @if ($extremeLinks)
                    <x-filament::pagination.item
                        :aria-label="__('filament::components/pagination.actions.first.label')"
                        :icon="$isRtl ? \Filament\Support\Icons\Heroicon::ChevronDoubleRight : \Filament\Support\Icons\Heroicon::ChevronDoubleLeft"
                        :icon-alias="
                            $isRtl
                            ? \Filament\Support\View\SupportIconAlias::PAGINATION_FIRST_BUTTON_RTL
                            : \Filament\Support\View\SupportIconAlias::PAGINATION_FIRST_BUTTON
                        "
                        rel="first"
                        :wire:click="'gotoPage(1, \'' . $paginator->getPageName() . '\')'"
                        :wire:key="$this->getId() . '.pagination.first'"
                    />
                @endif

                <x-filament::pagination.item
                    :aria-label="__('filament::components/pagination.actions.previous.label')"
                    :icon="$isRtl ? \Filament\Support\Icons\Heroicon::ChevronRight : \Filament\Support\Icons\Heroicon::ChevronLeft"
                    {{-- @deprecated Use `SupportIconAlias::PAGINATION_PREVIOUS_BUTTON_RTL` instead of `SupportIconAlias::PAGINATION_PREVIOUS_BUTTON` for RTL. --}}
                    :icon-alias="
                        $isRtl
                        ? [
                            \Filament\Support\View\SupportIconAlias::PAGINATION_PREVIOUS_BUTTON_RTL,
                            \Filament\Support\View\SupportIconAlias::PAGINATION_PREVIOUS_BUTTON,
                        ]
                        : \Filament\Support\View\SupportIconAlias::PAGINATION_PREVIOUS_BUTTON
                    "
                    rel="prev"
                    :wire:click="'previousPage(\'' . $paginator->getPageName() . '\')'"
                    :wire:key="$this->getId() . '.pagination.previous'"
                />
            @endif

            @php
                // Filament deja onEachSide(0): solo se ve la pagina actual y no se puede ir a las de al lado.
                // Con 2 se ven dos a cada lado: 1 2 ... 34 35 [36] 37 38 ... 100 101
                if (method_exists($paginator, 'onEachSide')) {
                    $paginator->onEachSide(2);
                }
            @endphp

            @foreach ($paginator->render()->offsetGet('elements') as $element)
                @if (is_string($element))
                    <x-filament::pagination.item disabled :label="$element" />
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        <x-filament::pagination.item
                            :active="$page === $paginator->currentPage()"
                            :aria-label="trans_choice('filament::components/pagination.actions.go_to_page.label', $page, ['page' => \Illuminate\Support\Number::format($page)])"
                            :label="\Illuminate\Support\Number::format($page)"
                            :wire:click="'gotoPage(' . $page . ', \'' . $paginator->getPageName() . '\')'"
                            :wire:key="$this->getId() . '.pagination.' . $paginator->getPageName() . '.' . $page"
                        />
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <x-filament::pagination.item
                    :aria-label="__('filament::components/pagination.actions.next.label')"
                    :icon="$isRtl ? \Filament\Support\Icons\Heroicon::ChevronLeft : \Filament\Support\Icons\Heroicon::ChevronRight"
                    {{-- @deprecated Use `SupportIconAlias::PAGINATION_NEXT_BUTTON_RTL` instead of `SupportIconAlias::PAGINATION_NEXT_BUTTON` for RTL. --}}
                    :icon-alias="
                        $isRtl
                        ? [
                            \Filament\Support\View\SupportIconAlias::PAGINATION_NEXT_BUTTON_RTL,
                            \Filament\Support\View\SupportIconAlias::PAGINATION_NEXT_BUTTON,
                        ]
                        : \Filament\Support\View\SupportIconAlias::PAGINATION_NEXT_BUTTON
                    "
                    rel="next"
                    :wire:click="'nextPage(\'' . $paginator->getPageName() . '\')'"
                    :wire:key="$this->getId() . '.pagination.next'"
                />

                @if ($extremeLinks)
                    <x-filament::pagination.item
                        :aria-label="__('filament::components/pagination.actions.last.label')"
                        :icon="$isRtl ? \Filament\Support\Icons\Heroicon::ChevronDoubleLeft : \Filament\Support\Icons\Heroicon::ChevronDoubleRight"
                        :icon-alias="
                            $isRtl
                            ? \Filament\Support\View\SupportIconAlias::PAGINATION_LAST_BUTTON_RTL
                            : \Filament\Support\View\SupportIconAlias::PAGINATION_LAST_BUTTON
                        "
                        rel="last"
                        :wire:click="'gotoPage(' . $paginator->lastPage() . ', \'' . $paginator->getPageName() . '\')'"
                        :wire:key="$this->getId() . '.pagination.last'"
                    />
                @endif
            @endif
        </ol>

        @if ($paginator->lastPage() > 5)
            <div
                class="fi-pagination-ir-a"
                style="display: flex; align-items: center; gap: 0.5rem;"
                x-data="{
                    pagina: '',
                    ir() {
                        const ultima = {{ $paginator->lastPage() }}
                        const numero = Math.min(Math.max(parseInt(this.pagina, 10) || 1, 1), ultima)

                        this.pagina = ''
                        $wire.gotoPage(numero, @js($paginator->getPageName()))
                    },
                }"
            >
                <label
                    for="{{ $this->getId() }}-ir-a-pagina"
                    style="font-size: 0.875rem; white-space: nowrap;"
                >
                    Ir a la página
                </label>

                <x-filament::input.wrapper style="width: 5.5rem;">
                    <x-filament::input
                        type="number"
                        min="1"
                        max="{{ $paginator->lastPage() }}"
                        inputmode="numeric"
                        :placeholder="'1-' . $paginator->lastPage()"
                        id="{{ $this->getId() }}-ir-a-pagina"
                        x-model="pagina"
                        x-on:keydown.enter.prevent="ir()"
                    />
                </x-filament::input.wrapper>

                <x-filament::button
                    color="gray"
                    size="sm"
                    x-on:click="ir()"
                >
                    Ir
                </x-filament::button>
            </div>
        @endif
    @endif
</nav>
