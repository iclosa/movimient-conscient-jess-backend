<?php

namespace App\Http\Controllers\Admin;

use App\Models\Activity;
use App\Models\Instructor;
use App\Models\Room;
use App\Models\ScheduleTemplate;
use App\Services\SessionGenerator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;

class ScheduleTemplateController extends CrudController
{
    protected string $model = ScheduleTemplate::class;

    protected string $routeName = 'admin.templates';

    protected string $singular = 'Franja horària';

    protected string $plural = 'Horari setmanal';

    protected string $newLabel = 'Nova franja';

    protected string $icon = 'repeat';

    protected ?string $intro = 'Defineix l\'horari que es repeteix cada setmana. Després genera les classes concretes perquè es puguin reservar.';

    protected string $emptyText = 'Afegeix les franges de la setmana tipus i genera les classes.';

    protected function fields(?Model $model): array
    {
        return [
            ['type' => 'section', 'label' => 'Quan i què'],
            ['name' => 'activity_id', 'label' => 'Activitat', 'type' => 'select', 'options' => Activity::query()->orderBy('name')->pluck('name', 'id')->all(), 'rules' => 'required|exists:activities,id'],
            ['name' => 'weekday', 'label' => 'Dia de la setmana', 'type' => 'select', 'options' => ScheduleTemplate::WEEKDAYS, 'rules' => 'required|integer|between:1,7', 'default' => 1],
            ['name' => 'start_time', 'label' => 'Hora d\'inici', 'type' => 'time', 'rules' => 'required|date_format:H:i', 'default' => '09:30'],
            ['name' => 'duration_minutes', 'label' => 'Durada (minuts)', 'type' => 'number', 'default' => 60, 'rules' => 'required|integer|min:5|max:480'],
            ['type' => 'section', 'label' => 'On, amb qui i quant'],
            ['name' => 'room_id', 'label' => 'Sala', 'type' => 'select', 'nullable' => true, 'options' => Room::query()->orderBy('name')->pluck('name', 'id')->all(), 'rules' => 'nullable|exists:rooms,id'],
            ['name' => 'instructor_id', 'label' => 'Professor/a', 'type' => 'select', 'nullable' => true, 'options' => Instructor::query()->orderBy('name')->pluck('name', 'id')->all(), 'rules' => 'nullable|exists:instructors,id'],
            ['name' => 'capacity', 'label' => 'Places', 'type' => 'number', 'hint' => 'Si ho deixes buit, s\'usen les de la sala.', 'rules' => 'nullable|integer|min:1'],
            ['name' => 'price_cents', 'label' => 'Preu de la classe solta', 'type' => 'money', 'hint' => 'Amb bo no es cobra res més. Deixa 0 per fer servir el preu de l\'activitat.'],
            ['type' => 'section', 'label' => 'Vigència'],
            ['name' => 'valid_from', 'label' => 'Des del', 'type' => 'date', 'hint' => 'Opcional.'],
            ['name' => 'valid_until', 'label' => 'Fins al', 'type' => 'date', 'hint' => 'Opcional. Útil per a cursos o temporades.'],
            ['name' => 'is_active', 'label' => 'Actiu', 'type' => 'toggle', 'default' => true],
        ];
    }

    protected function columns(): array
    {
        return [
            ['label' => 'Dia', 'key' => 'weekday_name', 'link' => true],
            ['label' => 'Hora', 'key' => 'start_time'],
            ['label' => 'Activitat', 'key' => 'activity.name', 'type' => 'dot', 'color' => 'activity.color'],
            ['label' => 'Durada', 'key' => 'duration_minutes'],
            ['label' => 'Sala', 'key' => 'room.name'],
            ['label' => 'Places', 'key' => 'capacity'],
            ['label' => 'Preu', 'key' => 'price_cents', 'type' => 'money'],
            ['label' => 'Estat', 'key' => 'is_active', 'type' => 'bool', 'yes' => 'Actiu', 'no' => 'Aturat'],
        ];
    }

    protected function filters(): array
    {
        return [
            ['name' => 'weekday', 'label' => 'Dia', 'options' => ScheduleTemplate::WEEKDAYS],
            ['name' => 'activity_id', 'label' => 'Activitat', 'options' => Activity::query()->orderBy('name')->pluck('name', 'id')->all()],
        ];
    }

    protected function with(): array
    {
        return ['activity', 'room'];
    }

    protected function order(Builder $query): Builder
    {
        return $query->orderBy('weekday')->orderBy('start_time');
    }

    protected function headerActions(): array
    {
        return [
            ['label' => 'Genera les properes 4 setmanes', 'url' => route('admin.templates.generate'), 'method' => 'post', 'icon' => 'repeat', 'class' => 'teal'],
        ];
    }

    public function generate(SessionGenerator $generator): RedirectResponse
    {
        $created = $generator->generate(now()->startOfDay(), now()->addWeeks(4)->endOfDay());

        return back()->with('status', $created > 0
            ? "S'han creat {$created} classes noves al calendari."
            : 'El calendari ja estava al dia: no calia crear cap classe.');
    }
}
