<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Base de los módulos del panel: cada controlador solo declara campos y columnas.
 *
 * Tipos de campo: text, email, url, number, money, textarea, select, color, toggle,
 * date, time, datetime, image, password y section (título de bloque).
 *
 * Tipos de columna: text, badge, bool, money, date, datetime, color, image, dot, stars.
 */
abstract class CrudController extends Controller
{
    /** @var class-string<Model> */
    protected string $model;

    protected string $routeName;

    protected string $singular;

    protected string $plural;

    protected string $newLabel = 'Afegeix';

    protected string $icon = 'leaf';

    protected int $perPage = 20;

    protected ?string $intro = null;

    protected string $emptyText = 'Encara no hi ha res aquí.';

    /**
     * @return array<int, array<string, mixed>>
     */
    abstract protected function fields(?Model $model): array;

    /**
     * @return array<int, array<string, mixed>>
     */
    abstract protected function columns(): array;

    /**
     * Columnas (o relaciones "rel.columna") donde busca el cuadro de búsqueda.
     *
     * @return array<int, string>
     */
    protected function searchable(): array
    {
        return [];
    }

    /**
     * Filtros desplegables: ['name' => columna, 'label' => texto, 'options' => [valor => etiqueta]].
     *
     * @return array<int, array<string, mixed>>
     */
    protected function filters(): array
    {
        return [];
    }

    /**
     * @return array<int, string>
     */
    protected function with(): array
    {
        return [];
    }

    protected function baseQuery(): Builder
    {
        return $this->model::query();
    }

    protected function order(Builder $query): Builder
    {
        return $query->latest('id');
    }

    /**
     * Botones extra en la cabecera del listado: [['label' =>, 'url' =>, 'icon' =>, 'class' =>]].
     *
     * @return array<int, array<string, string>>
     */
    protected function headerActions(): array
    {
        return [];
    }

    /**
     * Acciones extra por fila: [['label' =>, 'url' =>, 'icon' =>]].
     *
     * @return array<int, array<string, string>>
     */
    public function rowActions(Model $row): array
    {
        return [];
    }

    /**
     * Devuelve un mensaje si el registro no se puede borrar.
     */
    protected function deleteBlocker(Model $model): ?string
    {
        return null;
    }

    /**
     * Ajusta los datos validados antes de guardar (slugs, valores calculados...).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function beforeSave(array $data, ?Model $model, Request $request): array
    {
        return $data;
    }

    protected function afterSave(Model $model, Request $request, bool $created): void
    {
        //
    }

    protected function redirectAfterSave(Model $model): RedirectResponse
    {
        return redirect()->route($this->routeName.'.index');
    }

    public function index(Request $request): View
    {
        $query = $this->baseQuery()->with($this->with());

        $term = trim((string) $request->query('q'));
        if ($term !== '' && $this->searchable() !== []) {
            $query->where(function (Builder $q) use ($term) {
                foreach ($this->searchable() as $column) {
                    if (str_contains($column, '.')) {
                        [$relation, $field] = explode('.', $column, 2);
                        $q->orWhereHas($relation, fn (Builder $r) => $r->where($field, 'like', "%{$term}%"));
                    } else {
                        $q->orWhere($column, 'like', "%{$term}%");
                    }
                }
            });
        }

        foreach ($this->filters() as $filter) {
            if ($filter['custom'] ?? false) {
                continue; // lo gestiona baseQuery()
            }

            $value = $request->query($filter['name']);
            if ($value !== null && $value !== '') {
                $query->where($filter['name'], $value);
            }
        }

        $rows = $this->order($query)->paginate($this->perPage)->withQueryString();

        return view('admin.crud.index', [
            'rows' => $rows,
            'columns' => $this->columns(),
            'filters' => $this->filters(),
            'searchable' => $this->searchable() !== [],
            'headerActions' => $this->headerActions(),
            'controller' => $this,
            ...$this->labels(),
        ]);
    }

    public function create(): View
    {
        $model = new $this->model;
        foreach ($this->fields(null) as $field) {
            if (isset($field['name']) && array_key_exists('default', $field)) {
                $model->setAttribute($field['name'], $field['default']);
            }
        }

        return $this->form($model);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->beforeSave($this->prepare($request, null), null, $request);

        $model = new $this->model;
        $model->forceFill($data)->save();
        $this->afterSave($model, $request, true);

        return $this->redirectAfterSave($model)->with('status', 'S\'ha creat correctament.');
    }

    public function edit(int|string $id): View
    {
        return $this->form($this->model::query()->findOrFail($id));
    }

    public function update(Request $request, int|string $id): RedirectResponse
    {
        $model = $this->model::query()->findOrFail($id);
        $data = $this->beforeSave($this->prepare($request, $model), $model, $request);

        $model->forceFill($data)->save();
        $this->afterSave($model, $request, false);

        return $this->redirectAfterSave($model)->with('status', 'Canvis desats correctament.');
    }

    public function destroy(int|string $id): RedirectResponse
    {
        $model = $this->model::query()->findOrFail($id);

        if ($message = $this->deleteBlocker($model)) {
            return back()->with('error', $message);
        }

        foreach ($this->fields($model) as $field) {
            if (($field['type'] ?? null) === 'image' && $model->{$field['name']}) {
                Storage::disk('public')->delete($model->{$field['name']});
            }
        }

        $model->delete();

        return redirect()->route($this->routeName.'.index')->with('status', 'S\'ha eliminat correctament.');
    }

    /**
     * @return array<string, string>
     */
    protected function labels(): array
    {
        return [
            'routeName' => $this->routeName,
            'singular' => $this->singular,
            'plural' => $this->plural,
            'newLabel' => $this->newLabel,
            'icon' => $this->icon,
            'intro' => $this->intro,
            'emptyText' => $this->emptyText,
        ];
    }

    protected function form(Model $model): View
    {
        $fields = collect($this->fields($model->exists ? $model : null))->map(function (array $field) use ($model) {
            $field['required'] = in_array('required', $this->rulesFor($field, $model->exists ? $model : null), true);

            return $field;
        })->all();

        return view('admin.crud.form', [
            'model' => $model,
            'fields' => $fields,
            ...$this->labels(),
        ]);
    }

    /**
     * Valida la petición y la convierte en atributos listos para guardar.
     *
     * @return array<string, mixed>
     */
    protected function prepare(Request $request, ?Model $model): array
    {
        $fields = collect($this->fields($model))->filter(fn ($f) => isset($f['name']) && ! ($f['virtual'] ?? false));

        // "12,50" → "12.50" antes de validar.
        foreach ($fields->where('type', 'money') as $field) {
            if ($request->filled($field['name'])) {
                $request->merge([$field['name'] => str_replace(',', '.', (string) $request->input($field['name']))]);
            }
        }

        $rules = [];
        $attributes = [];
        foreach ($fields as $field) {
            $rules[$field['name']] = $this->rulesFor($field, $model);
            $attributes[$field['name']] = Str::lower($field['label'] ?? $field['name']);
        }

        $request->validate($rules, [], $attributes);

        $data = [];
        foreach ($fields as $field) {
            $name = $field['name'];
            $type = $field['type'] ?? 'text';

            switch ($type) {
                case 'toggle':
                    $data[$name] = $request->boolean($name);
                    break;

                case 'money':
                    $data[$name] = Money::toCents($request->input($name));
                    break;

                case 'password':
                    if ($request->filled($name)) {
                        $data[$name] = $request->input($name);
                    }
                    break;

                case 'date':
                case 'datetime':
                    $data[$name] = $request->filled($name) ? Carbon::parse($request->input($name)) : null;
                    break;

                case 'image':
                    $file = $request->file($name);
                    $current = $model?->{$name};
                    if ($file) {
                        if ($current) {
                            Storage::disk('public')->delete($current);
                        }
                        $data[$name] = $file->store(Str::after($this->routeName, 'admin.'), 'public');
                    } elseif ($request->boolean($name.'_remove') && $current) {
                        Storage::disk('public')->delete($current);
                        $data[$name] = null;
                    }
                    break;

                case 'number':
                    $data[$name] = $request->filled($name)
                        ? $request->input($name)
                        : ($field['empty'] ?? null);
                    break;

                default:
                    $data[$name] = $request->filled($name) ? $request->input($name) : ($field['empty'] ?? null);
            }
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array<int, mixed>
     */
    protected function rulesFor(array $field, ?Model $model): array
    {
        if (! isset($field['name'])) {
            return [];
        }

        $custom = $field['rules'] ?? [];
        if ($custom instanceof \Closure) {
            $custom = $custom($model);
        }
        if (is_string($custom)) {
            $custom = explode('|', $custom);
        }

        $type = $field['type'] ?? 'text';
        $base = match ($type) {
            'toggle' => ['boolean'],
            'select' => ['nullable'],
            'image' => ['nullable', 'image', 'max:4096'],
            'money' => ['nullable', 'numeric', 'min:0'],
            'number' => ['nullable', 'numeric'],
            'email' => ['email'],
            'url' => ['nullable', 'url'],
            'date', 'datetime' => ['nullable', 'date'],
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'password' => $model ? ['nullable', 'min:8'] : ['required', 'min:8'],
            default => ['nullable', 'string', 'max:5000'],
        };

        $rules = [...$custom, ...$base];

        if (in_array('required', $rules, true)) {
            $rules = array_values(array_filter($rules, fn ($r) => $r !== 'nullable'));
        }

        return array_values(array_unique($rules, SORT_REGULAR));
    }

    /**
     * Genera un slug único para el modelo.
     */
    protected function uniqueSlug(string $source, ?Model $model, string $column = 'slug'): string
    {
        $base = Str::slug($source) ?: 'element';
        $slug = $base;
        $i = 2;

        while ($this->model::query()
            ->where($column, $slug)
            ->when($model?->exists, fn (Builder $q) => $q->whereKeyNot($model->getKey()))
            ->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
