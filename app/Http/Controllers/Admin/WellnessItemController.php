<?php

namespace App\Http\Controllers\Admin;

use App\Models\WellnessItem;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class WellnessItemController extends CrudController
{
    protected string $model = WellnessItem::class;

    protected string $routeName = 'admin.wellness';

    protected string $singular = 'Proposta de benestar';

    protected string $plural = 'Més (benestar)';

    protected string $newLabel = 'Nova proposta';

    protected string $icon = 'heart';

    protected ?string $intro = 'Les eines de la pestanya "Més" de l\'app: mindfulness, respiració, temporitzador de meditació, diari de gratitud…';

    protected function fields(?Model $model): array
    {
        return [
            ['type' => 'section', 'label' => 'Proposta'],
            ['name' => 'type', 'label' => 'Tipus d\'eina', 'type' => 'select', 'options' => WellnessItem::TYPES, 'rules' => 'required', 'default' => 'custom', 'hint' => 'Cada tipus té una pantalla pròpia a l\'app.'],
            ['name' => 'title', 'label' => 'Títol', 'rules' => 'required|max:120'],
            ['name' => 'description', 'label' => 'Descripció curta', 'type' => 'textarea', 'rows' => 2],
            ['name' => 'content', 'label' => 'Contingut', 'type' => 'textarea', 'rows' => 8, 'hint' => 'Text de la pràctica o instruccions (opcional).'],
            ['type' => 'section', 'label' => 'Recursos'],
            ['name' => 'media_url', 'label' => 'Àudio o vídeo', 'type' => 'url', 'hint' => 'Enllaç a una meditació guiada, per exemple.'],
            ['name' => 'duration_minutes', 'label' => 'Durada (minuts)', 'type' => 'number', 'rules' => 'nullable|integer|min:1'],
            ['name' => 'icon', 'label' => 'Icona', 'placeholder' => 'wind', 'rules' => 'max:40', 'hint' => 'Nom d\'icona de l\'app (sparkles, sun, wind, bell, heart…).'],
            ['name' => 'config', 'label' => 'Configuració avançada (JSON)', 'type' => 'textarea', 'rows' => 5, 'rules' => 'nullable|json', 'hint' => 'Ex. respiració: {"inhale":4,"hold":4,"exhale":6,"rounds":6}', 'full' => true],
            ['type' => 'section', 'label' => 'Visibilitat'],
            ['name' => 'is_active', 'label' => 'Visible a l\'app', 'type' => 'toggle', 'default' => true],
            ['name' => 'sort_order', 'label' => 'Ordre', 'type' => 'number', 'default' => 0, 'empty' => 0, 'rules' => 'integer|min:0'],
        ];
    }

    protected function columns(): array
    {
        return [
            ['label' => 'Proposta', 'key' => 'title', 'link' => true, 'sub' => 'description'],
            ['label' => 'Tipus', 'key' => 'type', 'type' => 'badge', 'map' => collect(WellnessItem::TYPES)->map(fn ($l) => [$l, 'primary'])->all()],
            ['label' => 'Durada', 'key' => 'duration_minutes'],
            ['label' => 'Estat', 'key' => 'is_active', 'type' => 'bool', 'yes' => 'Visible', 'no' => 'Amagada'],
        ];
    }

    protected function order(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    protected function beforeSave(array $data, ?Model $model, Request $request): array
    {
        $data['config'] = ! empty($data['config']) ? json_decode($data['config'], true) : null;

        return $data;
    }
}
