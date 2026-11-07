<?php

namespace App\Http\Controllers\Admin;

use App\Models\Promotion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PromotionController extends CrudController
{
    protected string $model = Promotion::class;

    protected string $routeName = 'admin.promotions';

    protected string $singular = 'Promoció';

    protected string $plural = 'Promocions';

    protected string $newLabel = 'Nova promoció';

    protected string $icon = 'tag';

    protected ?string $intro = 'Targetes que apareixen a la pantalla de resum de l\'app: ofertes, tallers i avisos.';

    protected function fields(?Model $model): array
    {
        return [
            ['type' => 'section', 'label' => 'Missatge'],
            ['name' => 'title', 'label' => 'Títol', 'rules' => 'required|max:120', 'full' => true],
            ['name' => 'body', 'label' => 'Text', 'type' => 'textarea', 'rows' => 3, 'hint' => 'Millor curt i directe.'],
            ['name' => 'type', 'label' => 'Tipus', 'type' => 'select', 'options' => ['promo' => 'Promoció', 'taller' => 'Taller', 'avis' => 'Avís'], 'rules' => 'required', 'default' => 'promo'],
            ['name' => 'color', 'label' => 'Color de la targeta', 'type' => 'color', 'default' => '#2bb5ae'],
            ['name' => 'image_path', 'label' => 'Imatge', 'type' => 'image'],
            ['type' => 'section', 'label' => 'Botó'],
            ['name' => 'cta_label', 'label' => 'Text del botó', 'placeholder' => 'Reserva ara', 'rules' => 'max:40'],
            ['name' => 'cta_url', 'label' => 'Enllaç', 'type' => 'url', 'hint' => 'Pàgina web o enllaç dins de l\'app.'],
            ['type' => 'section', 'label' => 'Quan es mostra'],
            ['name' => 'starts_at', 'label' => 'Comença', 'type' => 'datetime', 'hint' => 'Buit = ja.'],
            ['name' => 'ends_at', 'label' => 'Acaba', 'type' => 'datetime', 'hint' => 'Buit = sense data de fi.'],
            ['name' => 'is_active', 'label' => 'Activa', 'type' => 'toggle', 'default' => true],
            ['name' => 'sort_order', 'label' => 'Ordre', 'type' => 'number', 'default' => 0, 'empty' => 0, 'rules' => 'integer|min:0'],
        ];
    }

    protected function columns(): array
    {
        return [
            ['label' => 'Títol', 'key' => 'title', 'link' => true, 'sub' => 'body'],
            ['label' => 'Tipus', 'key' => 'type', 'type' => 'badge', 'map' => [
                'promo' => ['Promoció', 'teal'],
                'taller' => ['Taller', 'primary'],
                'avis' => ['Avís', 'warning'],
            ]],
            ['label' => 'Caduca', 'key' => 'ends_at', 'type' => 'date'],
            ['label' => 'Estat', 'key' => 'is_active', 'type' => 'bool', 'yes' => 'Activa', 'no' => 'Aturada'],
        ];
    }

    protected function searchable(): array
    {
        return ['title', 'body'];
    }

    protected function order(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->latest('id');
    }
}
