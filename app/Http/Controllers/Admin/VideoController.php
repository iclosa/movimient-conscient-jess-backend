<?php

namespace App\Http\Controllers\Admin;

use App\Models\Activity;
use App\Models\Video;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class VideoController extends CrudController
{
    public const CATEGORIES = [
        'classe' => 'Classe completa',
        'rutina' => 'Rutina',
        'respiracio' => 'Respiració',
        'meditacio' => 'Meditació',
    ];

    protected string $model = Video::class;

    protected string $routeName = 'admin.videos';

    protected string $singular = 'Vídeo';

    protected string $plural = 'Vídeos';

    protected string $newLabel = 'Nou vídeo';

    protected string $icon = 'video';

    protected ?string $intro = 'Classes i rutines en línia per practicar a casa. Alguns poden ser de pagament.';

    protected function fields(?Model $model): array
    {
        return [
            ['type' => 'section', 'label' => 'Contingut'],
            ['name' => 'title', 'label' => 'Títol', 'rules' => 'required|max:140', 'full' => true],
            ['name' => 'description', 'label' => 'Descripció', 'type' => 'textarea', 'rows' => 4],
            ['name' => 'category', 'label' => 'Categoria', 'type' => 'select', 'options' => self::CATEGORIES, 'rules' => 'required', 'default' => 'classe'],
            ['name' => 'activity_id', 'label' => 'Activitat', 'type' => 'select', 'nullable' => true, 'options' => Activity::query()->orderBy('name')->pluck('name', 'id')->all(), 'rules' => 'nullable|exists:activities,id'],
            ['name' => 'duration_minutes', 'label' => 'Durada (minuts)', 'type' => 'number', 'rules' => 'nullable|integer|min:1'],
            ['name' => 'level', 'label' => 'Nivell', 'placeholder' => 'Tots els nivells', 'rules' => 'max:60'],
            ['type' => 'section', 'label' => 'Vídeo'],
            ['name' => 'provider', 'label' => 'On està allotjat', 'type' => 'select', 'options' => ['vimeo' => 'Vimeo', 'bunny' => 'Bunny Stream', 'youtube' => 'YouTube (no llistat)', 'url' => 'Enllaç directe'], 'rules' => 'required', 'default' => 'vimeo'],
            ['name' => 'video_ref', 'label' => 'Identificador o enllaç', 'rules' => 'required|max:255', 'hint' => 'Vimeo: el número de l\'enllaç. Bunny: l\'ID del vídeo. Altres: l\'URL completa.'],
            ['name' => 'thumbnail_path', 'label' => 'Miniatura', 'type' => 'image'],
            ['type' => 'section', 'label' => 'Accés i publicació'],
            ['name' => 'is_paid', 'label' => 'De pagament', 'type' => 'toggle', 'hint' => 'L\'alumnat l\'ha de comprar per veure\'l.'],
            ['name' => 'price_cents', 'label' => 'Preu', 'type' => 'money', 'hint' => 'Només si és de pagament.'],
            ['name' => 'is_published', 'label' => 'Publicat a l\'app', 'type' => 'toggle', 'default' => false],
            ['name' => 'published_at', 'label' => 'Publica el', 'type' => 'datetime', 'hint' => 'Opcional. Per programar-lo.'],
            ['name' => 'sort_order', 'label' => 'Ordre', 'type' => 'number', 'default' => 0, 'empty' => 0, 'rules' => 'integer|min:0'],
        ];
    }

    protected function columns(): array
    {
        return [
            ['label' => '', 'key' => 'thumbnail_url', 'type' => 'image'],
            ['label' => 'Vídeo', 'key' => 'title', 'link' => true],
            ['label' => 'Categoria', 'key' => 'category', 'type' => 'badge', 'map' => collect(self::CATEGORIES)->map(fn ($l) => [$l, 'primary'])->all()],
            ['label' => 'Activitat', 'key' => 'activity.name'],
            ['label' => 'Accés', 'key' => 'is_paid', 'type' => 'bool', 'yes' => 'De pagament', 'no' => 'Gratuït'],
            ['label' => 'Estat', 'key' => 'is_published', 'type' => 'bool', 'yes' => 'Publicat', 'no' => 'Esborrany'],
        ];
    }

    protected function searchable(): array
    {
        return ['title', 'description'];
    }

    protected function filters(): array
    {
        return [
            ['name' => 'category', 'label' => 'Categoria', 'options' => self::CATEGORIES],
            ['name' => 'is_paid', 'label' => 'Accés', 'options' => ['0' => 'Gratuïts', '1' => 'De pagament']],
            ['name' => 'is_published', 'label' => 'Estat', 'options' => ['1' => 'Publicats', '0' => 'Esborranys']],
        ];
    }

    protected function with(): array
    {
        return ['activity'];
    }

    protected function order(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->latest('id');
    }

    protected function beforeSave(array $data, ?Model $model, Request $request): array
    {
        $data['slug'] = $model?->slug ?: $this->uniqueSlug($data['title'], $model);

        if (! $data['is_paid']) {
            $data['price_cents'] = 0;
        }

        if ($data['is_published'] && empty($data['published_at']) && ! $model?->published_at) {
            $data['published_at'] = now();
        }

        return $data;
    }

    protected function deleteBlocker(Model $model): ?string
    {
        return $model->accesses()->exists()
            ? 'Hi ha alumnes que ja l\'han comprat. Si no vols que es vegi, desmarca "Publicat".'
            : null;
    }
}
