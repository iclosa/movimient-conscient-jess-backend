<?php

namespace App\Http\Controllers\Admin;

use App\Models\Pass;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PassController extends CrudController
{
    protected string $model = Pass::class;

    protected string $routeName = 'admin.passes';

    protected string $singular = 'Bo';

    protected string $plural = 'Bons i subscripcions';

    protected string $newLabel = 'Nou bo';

    protected string $icon = 'ticket';

    protected ?string $intro = 'El catàleg de bons que pot comprar l\'alumnat. Quan s\'assigna un bo a una persona, es crea el seu saldo de classes.';

    protected function fields(?Model $model): array
    {
        return [
            ['type' => 'section', 'label' => 'El bo'],
            ['name' => 'name', 'label' => 'Nom', 'rules' => 'required|max:80', 'placeholder' => 'Bo 10 classes'],
            ['name' => 'type', 'label' => 'Tipus', 'type' => 'select', 'options' => [Pass::TYPE_PACK => 'Bo de classes', Pass::TYPE_SUBSCRIPTION => 'Subscripció'], 'rules' => 'required|in:pack,subscription', 'default' => Pass::TYPE_PACK],
            ['name' => 'description', 'label' => 'Descripció', 'type' => 'textarea', 'rows' => 3],
            ['type' => 'section', 'label' => 'Condicions'],
            ['name' => 'classes_count', 'label' => 'Nombre de classes', 'type' => 'number', 'hint' => 'Deixa-ho buit per a classes il·limitades (subscripció).', 'rules' => 'nullable|integer|min:1', 'attrs' => ['min' => 1]],
            ['name' => 'validity_days', 'label' => 'Validesa (dies)', 'type' => 'number', 'default' => 90, 'rules' => 'required|integer|min:1', 'hint' => 'Des de la data d\'inici.'],
            ['name' => 'price_cents', 'label' => 'Preu', 'type' => 'money', 'rules' => 'required'],
            ['type' => 'section', 'label' => 'A l\'app'],
            ['name' => 'is_featured', 'label' => 'Destacat', 'type' => 'toggle', 'hint' => 'Es mostra primer amb l\'etiqueta "Més triat".'],
            ['name' => 'is_active', 'label' => 'Es pot comprar', 'type' => 'toggle', 'default' => true],
            ['name' => 'sort_order', 'label' => 'Ordre', 'type' => 'number', 'default' => 0, 'empty' => 0, 'rules' => 'integer|min:0'],
        ];
    }

    protected function columns(): array
    {
        return [
            ['label' => 'Bo', 'key' => 'name', 'link' => true, 'sub' => 'description'],
            ['label' => 'Tipus', 'key' => 'type', 'type' => 'badge', 'map' => [
                'pack' => ['Bo', 'primary'],
                'subscription' => ['Subscripció', 'teal'],
            ]],
            ['label' => 'Classes', 'key' => 'classes_count'],
            ['label' => 'Validesa', 'key' => 'validity_days'],
            ['label' => 'Preu', 'key' => 'price_cents', 'type' => 'money'],
            ['label' => 'Estat', 'key' => 'is_active', 'type' => 'bool', 'yes' => 'A la venda', 'no' => 'Retirat'],
        ];
    }

    protected function order(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('price_cents');
    }

    protected function deleteBlocker(Model $model): ?string
    {
        return $model->userPasses()->exists()
            ? 'No es pot eliminar: ja s\'ha assignat a alumnes. Retira\'l de la venda i deixarà de sortir a l\'app.'
            : null;
    }
}
