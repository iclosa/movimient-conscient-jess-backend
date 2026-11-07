<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TeamController extends CrudController
{
    protected string $model = User::class;

    protected string $routeName = 'admin.team';

    protected string $singular = 'Persona de l\'equip';

    protected string $plural = 'Equip';

    protected string $newLabel = 'Afegeix una persona';

    protected string $icon = 'shield';

    protected ?string $intro = 'Qui pot entrar a aquest panell. Tothom amb accés pot gestionar-ho tot.';

    protected function fields(?Model $model): array
    {
        return [
            ['name' => 'name', 'label' => 'Nom', 'rules' => 'required|max:120'],
            ['name' => 'email', 'label' => 'Correu electrònic', 'type' => 'email', 'rules' => fn (?Model $m) => ['required', Rule::unique('users', 'email')->ignore($m?->id)]],
            ['name' => 'password', 'label' => 'Contrasenya', 'type' => 'password', 'hint' => $model ? 'Deixa-ho buit per no canviar-la.' : 'Mínim 8 caràcters.'],
            ['name' => 'is_active', 'label' => 'Accés actiu', 'type' => 'toggle', 'default' => true],
        ];
    }

    protected function columns(): array
    {
        return [
            ['label' => 'Nom', 'key' => 'name', 'link' => true],
            ['label' => 'Correu', 'key' => 'email'],
            ['label' => 'Accés', 'key' => 'is_active', 'type' => 'bool', 'yes' => 'Actiu', 'no' => 'Bloquejat'],
        ];
    }

    protected function baseQuery(): Builder
    {
        return User::query()->where('role', User::ROLE_ADMIN);
    }

    protected function order(Builder $query): Builder
    {
        return $query->orderBy('name');
    }

    protected function beforeSave(array $data, ?Model $model, Request $request): array
    {
        $data['role'] = User::ROLE_ADMIN;
        $data['email_verified_at'] = $model?->email_verified_at ?? now();

        if ($model && $model->is($request->user()) && ! $data['is_active']) {
            $data['is_active'] = true; // no et pots bloquejar a tu mateix/a
        }

        return $data;
    }

    protected function deleteBlocker(Model $model): ?string
    {
        if ($model->is(request()->user())) {
            return 'No pots eliminar el teu propi accés.';
        }

        return User::query()->where('role', User::ROLE_ADMIN)->count() <= 1
            ? 'Ha de quedar com a mínim una persona administradora.'
            : null;
    }
}
