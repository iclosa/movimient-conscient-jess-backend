<?php

namespace App\Http\Controllers\Admin;

use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class PostController extends CrudController
{
    protected string $model = Post::class;

    protected string $routeName = 'admin.posts';

    protected string $singular = 'Article';

    protected string $plural = 'Blog';

    protected string $newLabel = 'Nou article';

    protected string $icon = 'book';

    protected ?string $intro = 'Articles del blog de la web. Escriu en català i, si vols, tradueix-los al castellà.';

    protected function fields(?Model $model): array
    {
        $format = 'Paràgrafs separats per una línia buida. Subtítol: comença la línia amb "## ". Enllaç: [text](/activitats/hatha-ioga/).';

        return [
            ['type' => 'section', 'label' => 'Català'],
            ['name' => 'title', 'label' => 'Títol', 'rules' => 'required|max:160', 'full' => true],
            ['name' => 'excerpt', 'label' => 'Resum (per a Google i la llista)', 'type' => 'textarea', 'rows' => 3, 'rules' => 'max:320'],
            ['name' => 'intro', 'label' => 'Introducció', 'type' => 'textarea', 'rows' => 4],
            ['name' => 'body', 'label' => 'Text', 'type' => 'textarea', 'rows' => 16, 'hint' => $format],
            ['type' => 'section', 'label' => 'Castellà (opcional)', 'hint' => 'Si queda buit, la web mostra la versió en català.'],
            ['name' => 'title_es', 'label' => 'Títol', 'rules' => 'max:160', 'full' => true],
            ['name' => 'excerpt_es', 'label' => 'Resum', 'type' => 'textarea', 'rows' => 3, 'rules' => 'max:320'],
            ['name' => 'intro_es', 'label' => 'Introducció', 'type' => 'textarea', 'rows' => 4],
            ['name' => 'body_es', 'label' => 'Text', 'type' => 'textarea', 'rows' => 16, 'hint' => $format],
            ['type' => 'section', 'label' => 'Publicació'],
            ['name' => 'image_path', 'label' => 'Imatge', 'type' => 'image'],
            ['name' => 'published_at', 'label' => 'Data de publicació', 'type' => 'datetime', 'hint' => 'Buit = la data en què el publiquis. Una data futura el programa.'],
            ['name' => 'is_published', 'label' => 'Publicat a la web', 'type' => 'toggle', 'default' => false],
        ];
    }

    protected function columns(): array
    {
        return [
            ['label' => 'Títol', 'key' => 'title', 'link' => true, 'sub' => 'excerpt'],
            ['label' => 'Data', 'key' => 'published_at', 'type' => 'date'],
            ['label' => 'Estat', 'key' => 'is_published', 'type' => 'bool', 'yes' => 'Publicat', 'no' => 'Esborrany'],
        ];
    }

    protected function searchable(): array
    {
        return ['title', 'excerpt', 'body'];
    }

    protected function order(Builder $query): Builder
    {
        return $query->orderByDesc('published_at')->orderByDesc('id');
    }

    protected function beforeSave(array $data, ?Model $model, Request $request): array
    {
        $data['slug'] = $this->uniqueSlug($data['title'], $model);
        $data['slug_es'] = filled($data['title_es'] ?? null) ? $this->uniqueSlug($data['title_es'], $model, 'slug_es') : null;

        if (! empty($data['is_published']) && empty($data['published_at'])) {
            $data['published_at'] = $model?->published_at ?? now();
        }

        return $data;
    }
}
