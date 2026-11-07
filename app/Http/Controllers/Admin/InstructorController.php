<?php

namespace App\Http\Controllers\Admin;

use App\Models\Instructor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class InstructorController extends CrudController
{
    protected string $model = Instructor::class;

    protected string $routeName = 'admin.instructors';

    protected string $singular = 'Professor/a';

    protected string $plural = 'Professorat';

    protected string $newLabel = 'Nou professor/a';

    protected string $icon = 'user';

    protected ?string $intro = 'Qui imparteix les classes. Es mostra a la fitxa de cada classe.';

    protected function fields(?Model $model): array
    {
        return [
            ['name' => 'name', 'label' => 'Nom', 'rules' => 'required|max:80'],
            ['name' => 'is_active', 'label' => 'En actiu', 'type' => 'toggle', 'default' => true],
            ['name' => 'bio', 'label' => 'Presentació', 'type' => 'textarea', 'rows' => 5],
            ['name' => 'photo_path', 'label' => 'Foto', 'type' => 'image'],
        ];
    }

    protected function columns(): array
    {
        return [
            ['label' => '', 'key' => 'photo_url', 'type' => 'image'],
            ['label' => 'Nom', 'key' => 'name', 'link' => true, 'sub' => 'bio'],
            ['label' => 'Estat', 'key' => 'is_active', 'type' => 'bool', 'yes' => 'En actiu', 'no' => 'Inactiu'],
        ];
    }

    protected function order(Builder $query): Builder
    {
        return $query->orderBy('name');
    }

    protected function deleteBlocker(Model $model): ?string
    {
        return $model->sessions()->exists() ? 'No es pot eliminar: té classes assignades. Marca-la com a inactiva.' : null;
    }
}
