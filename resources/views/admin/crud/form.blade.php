@extends('admin.layouts.app')
@section('title', $model->exists ? 'Edita' : $newLabel)

@section('content')
    <x-page-header
        :title="$model->exists ? 'Edita: '.\Illuminate\Support\Str::limit((string) ($model->name ?? $model->title ?? $singular), 40) : $newLabel"
        :back="route($routeName.'.index')"
        :backLabel="$plural" />

    @if ($errors->any())
        <div class="alert error" role="alert"><x-icon name="alert" />Revisa els camps marcats en vermell.</div>
    @endif

    <form method="post" enctype="multipart/form-data" class="card"
          action="{{ $model->exists ? route($routeName.'.update', $model) : route($routeName.'.store') }}">
        @csrf
        @if ($model->exists) @method('put') @endif

        <div class="card-pad">
            <div class="form-grid">
                @foreach ($fields as $field)
                    @include('admin.crud.field', ['f' => $field])
                @endforeach
            </div>
        </div>

        <div class="form-actions">
            <a href="{{ route($routeName.'.index') }}" class="btn ghost">Cancel·la</a>
            <button type="submit" class="btn primary"><x-icon name="check" class="sm" /> Desa</button>
        </div>
    </form>
@endsection
