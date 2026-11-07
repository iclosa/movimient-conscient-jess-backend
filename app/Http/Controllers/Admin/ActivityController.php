<?php

namespace App\Http\Controllers\Admin;

use App\Models\Activity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class ActivityController extends CrudController
{
    protected string $model = Activity::class;

    protected string $routeName = 'admin.activities';

    protected string $singular = 'Activitat';

    protected string $plural = 'Activitats';

    protected string $newLabel = 'Nova activitat';

    protected string $icon = 'leaf';

    protected ?string $intro = 'Les disciplines que ofereix el centre. Aquí es defineix tot el que veuen l\'app i la web: textos, preu i color.';

    protected function fields(?Model $model): array
    {
        $es = 'Si ho deixes buit, la web fa servir el text en català.';

        return [
            ['type' => 'section', 'label' => 'Informació (català)'],
            ['name' => 'name', 'label' => 'Nom', 'rules' => 'required|max:80'],
            ['name' => 'tagline', 'label' => 'Frase curta', 'rules' => 'max:140', 'hint' => 'Surt sota el nom a l\'app i a les targetes de la web.'],
            ['name' => 'description', 'label' => 'Presentació', 'type' => 'textarea', 'rows' => 5, 'hint' => 'Text d\'introducció de la pàgina de l\'activitat.'],
            ['name' => 'level', 'label' => 'Nivell', 'placeholder' => 'Tots els nivells', 'rules' => 'max:60'],
            ['type' => 'section', 'label' => 'Preu i aspecte'],
            ['name' => 'price_cents', 'label' => 'Preu de la classe solta', 'type' => 'money', 'hint' => 'Es mostra a la web i és el preu de les classes noves d\'aquesta activitat. 0 = no mostrar preu.'],
            ['name' => 'color', 'label' => 'Color de l\'activitat', 'type' => 'color', 'default' => '#7c5cbf', 'hint' => 'Es fa servir a l\'app i a la web (etiquetes, punts i botons). Tria un color prou fosc perquè es llegeixi bé.'],
            ['name' => 'image_path', 'label' => 'Imatge', 'type' => 'image'],
            ['type' => 'section', 'label' => 'Pàgina de la web (català)'],
            ['name' => 'h1', 'label' => 'Títol de la pàgina', 'rules' => 'max:160', 'placeholder' => 'Ioga Nidra a Vallirana'],
            ['name' => 'meta_title', 'label' => 'Títol per a Google', 'rules' => 'max:160'],
            ['name' => 'meta_description', 'label' => 'Descripció per a Google', 'type' => 'textarea', 'rows' => 3, 'rules' => 'max:320'],
            ['name' => 'benefits', 'label' => 'Beneficis', 'type' => 'textarea', 'rows' => 5, 'hint' => 'Un benefici per línia.'],
            ['name' => 'for_who', 'label' => 'Per a qui és', 'type' => 'textarea', 'rows' => 4],
            ['name' => 'expect', 'label' => 'Què t\'hi trobaràs', 'type' => 'textarea', 'rows' => 4],
            ['name' => 'faq', 'label' => 'Preguntes freqüents', 'type' => 'textarea', 'rows' => 8, 'hint' => 'Escriu la pregunta en una línia i la resposta a sota. Separa cada pregunta de la següent amb una línia en blanc.'],
            ['type' => 'section', 'label' => 'Versió en castellà'],
            ['name' => 'name_es', 'label' => 'Nom', 'rules' => 'max:80', 'hint' => $es],
            ['name' => 'tagline_es', 'label' => 'Frase curta', 'rules' => 'max:140'],
            ['name' => 'description_es', 'label' => 'Presentació', 'type' => 'textarea', 'rows' => 5],
            ['name' => 'level_es', 'label' => 'Nivell', 'rules' => 'max:60'],
            ['name' => 'h1_es', 'label' => 'Títol de la pàgina', 'rules' => 'max:160'],
            ['name' => 'meta_title_es', 'label' => 'Títol per a Google', 'rules' => 'max:160'],
            ['name' => 'meta_description_es', 'label' => 'Descripció per a Google', 'type' => 'textarea', 'rows' => 3, 'rules' => 'max:320'],
            ['name' => 'benefits_es', 'label' => 'Beneficis', 'type' => 'textarea', 'rows' => 5, 'hint' => 'Un benefici per línia.'],
            ['name' => 'for_who_es', 'label' => 'Per a qui és', 'type' => 'textarea', 'rows' => 4],
            ['name' => 'expect_es', 'label' => 'Què t\'hi trobaràs', 'type' => 'textarea', 'rows' => 4],
            ['name' => 'faq_es', 'label' => 'Preguntes freqüents', 'type' => 'textarea', 'rows' => 8, 'hint' => 'Mateix format que en català.'],
            ['type' => 'section', 'label' => 'Opcions'],
            ['name' => 'is_family', 'label' => 'Permet apuntar infants', 'type' => 'toggle', 'hint' => 'Activa els perfils de fills per a aquesta activitat (yoga familiar).', 'default' => false],
            ['name' => 'is_active', 'label' => 'Visible a l\'app i a la web', 'type' => 'toggle', 'default' => true],
            ['name' => 'sort_order', 'label' => 'Ordre', 'type' => 'number', 'default' => 0, 'empty' => 0, 'rules' => 'integer|min:0'],
        ];
    }

    protected function columns(): array
    {
        return [
            ['label' => 'Activitat', 'key' => 'name', 'type' => 'dot', 'color' => 'color', 'link' => true],
            ['label' => 'Frase', 'key' => 'tagline', 'limit' => 50],
            ['label' => 'Preu', 'key' => 'price_cents', 'type' => 'money'],
            ['label' => 'Infants', 'key' => 'is_family', 'type' => 'bool'],
            ['label' => 'Estat', 'key' => 'is_active', 'type' => 'bool', 'yes' => 'Visible', 'no' => 'Amagada'],
        ];
    }

    protected function searchable(): array
    {
        return ['name', 'tagline'];
    }

    protected function order(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    protected function beforeSave(array $data, ?Model $model, Request $request): array
    {
        $data['slug'] = $this->uniqueSlug($data['name'], $model);
        $data['slug_es'] = $this->uniqueSlug(filled($data['name_es'] ?? null) ? $data['name_es'] : $data['name'], $model, 'slug_es');

        return $data;
    }

    protected function deleteBlocker(Model $model): ?string
    {
        return $model->sessions()->exists()
            ? 'No es pot eliminar: té classes associades. Amaga-la i deixarà de sortir a l\'app.'
            : null;
    }
}
