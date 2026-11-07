<?php

namespace App\Http\Controllers\Admin;

use App\Models\Room;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class RoomController extends CrudController
{
    protected string $model = Room::class;

    protected string $routeName = 'admin.rooms';

    protected string $singular = 'Sala';

    protected string $plural = 'Sales';

    protected string $newLabel = 'Nova sala';

    protected string $icon = 'door';

    protected ?string $intro = 'Espais on es fan les classes i les places que tenen.';

    protected function fields(?Model $model): array
    {
        return [
            ['name' => 'name', 'label' => 'Nom', 'rules' => 'required|max:80'],
            ['name' => 'capacity', 'label' => 'Places', 'type' => 'number', 'default' => 12, 'rules' => 'required|integer|min:1|max:500', 'attrs' => ['min' => 1]],
            ['name' => 'is_active', 'label' => 'Disponible', 'type' => 'toggle', 'default' => true],
        ];
    }

    protected function columns(): array
    {
        return [
            ['label' => 'Sala', 'key' => 'name', 'link' => true],
            ['label' => 'Places', 'key' => 'capacity'],
            ['label' => 'Estat', 'key' => 'is_active', 'type' => 'bool', 'yes' => 'Disponible', 'no' => 'No disponible'],
        ];
    }

    protected function order(Builder $query): Builder
    {
        return $query->orderBy('name');
    }

    protected function deleteBlocker(Model $model): ?string
    {
        return $model->sessions()->exists() ? 'No es pot eliminar: hi ha classes en aquesta sala.' : null;
    }
}
