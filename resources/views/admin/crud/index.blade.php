@extends('admin.layouts.app')
@section('title', $plural)

@section('content')
    <x-page-header :title="$plural" :subtitle="$intro">
        <x-slot:actions>
            @foreach ($headerActions as $action)
                @if (($action['method'] ?? 'get') === 'post')
                    <form method="post" action="{{ $action['url'] }}" style="display:inline">
                        @csrf
                        <button type="submit" class="btn {{ $action['class'] ?? 'ghost' }}">
                            @isset($action['icon'])<x-icon :name="$action['icon']" class="sm" />@endisset
                            {{ $action['label'] }}
                        </button>
                    </form>
                @else
                    <a href="{{ $action['url'] }}" class="btn {{ $action['class'] ?? 'ghost' }}">
                        @isset($action['icon'])<x-icon :name="$action['icon']" class="sm" />@endisset
                        {{ $action['label'] }}
                    </a>
                @endif
            @endforeach
            <a href="{{ route($routeName.'.create') }}" class="btn primary"><x-icon name="plus" class="sm" /> {{ $newLabel }}</a>
        </x-slot:actions>
    </x-page-header>

    <div class="card">
        @if ($searchable || count($filters))
            <div class="toolbar">
                <form method="get">
                    @if ($searchable)
                        <label class="search">
                            <x-icon name="search" />
                            <input type="search" name="q" value="{{ request('q') }}" placeholder="Cerca…" aria-label="Cerca">
                        </label>
                    @endif
                    @foreach ($filters as $filter)
                        <select name="{{ $filter['name'] }}" data-autosubmit aria-label="{{ $filter['label'] }}">
                            <option value="">{{ $filter['empty'] ?? $filter['label'].': tots' }}</option>
                            @foreach ($filter['options'] as $value => $label)
                                <option value="{{ $value }}" @selected((string) request($filter['name']) === (string) $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    @endforeach
                    @if ($searchable)
                        <button class="btn ghost sm" type="submit">Cerca</button>
                    @endif
                    @if (request()->hasAny(array_merge(['q'], collect($filters)->pluck('name')->all())) && (request('q') || collect($filters)->contains(fn ($f) => request($f['name']))))
                        <a href="{{ route($routeName.'.index') }}" class="btn ghost sm"><x-icon name="x" class="sm" /> Neteja</a>
                    @endif
                </form>
            </div>
        @endif

        @if ($rows->count())
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            @foreach ($columns as $col)
                                <th>{{ $col['label'] }}</th>
                            @endforeach
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr>
                                @foreach ($columns as $col)
                                    <td>@include('admin.crud.cell', ['col' => $col, 'row' => $row])</td>
                                @endforeach
                                <td class="actions">
                                    @foreach ($controller->rowActions($row) as $action)
                                        @if (($action['method'] ?? 'get') === 'post')
                                            <form method="post" action="{{ $action['url'] }}" @isset($action['confirm']) data-confirm="{{ $action['confirm'] }}" @endisset>
                                                @csrf
                                                <button type="submit" class="btn ghost sm">
                                                    @isset($action['icon'])<x-icon :name="$action['icon']" class="sm" />@endisset
                                                    {{ $action['label'] }}
                                                </button>
                                            </form>
                                        @else
                                            <a href="{{ $action['url'] }}" class="btn ghost sm">
                                                @isset($action['icon'])<x-icon :name="$action['icon']" class="sm" />@endisset
                                                {{ $action['label'] }}
                                            </a>
                                        @endif
                                    @endforeach
                                    <a href="{{ route($routeName.'.edit', $row) }}" class="btn icon-only" title="Edita" aria-label="Edita"><x-icon name="edit" class="sm" /></a>
                                    <form method="post" action="{{ route($routeName.'.destroy', $row) }}" data-confirm="Segur que vols eliminar-ho? Aquesta acció no es pot desfer.">
                                        @csrf @method('delete')
                                        <button type="submit" class="btn danger icon-only" title="Elimina" aria-label="Elimina"><x-icon name="trash" class="sm" /></button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $rows->links() }}
        @else
            <div class="empty">
                <div class="ring"><x-icon :name="$icon" /></div>
                <h3>{{ request('q') ? 'Cap resultat' : 'Tot buit per ara' }}</h3>
                <p>{{ request('q') ? 'Prova amb una altra cerca.' : $emptyText }}</p>
                @unless (request('q'))
                    <a href="{{ route($routeName.'.create') }}" class="btn primary"><x-icon name="plus" class="sm" /> {{ $newLabel }}</a>
                @endunless
            </div>
        @endif
    </div>
@endsection
