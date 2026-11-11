<?php

namespace App\Http\Controllers\Admin;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\User;
use App\Services\EventRegistrationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventController extends CrudController
{
    public const TYPES = ['taller' => 'Taller', 'retir' => 'Retir', 'esdeveniment' => 'Esdeveniment'];

    protected string $model = Event::class;

    protected string $routeName = 'admin.events';

    protected string $singular = 'Esdeveniment';

    protected string $plural = 'Tallers i retirs';

    protected string $newLabel = 'Nou taller o retir';

    protected string $icon = 'sparkles';

    protected ?string $intro = 'Activitats especials fora de l\'horari habitual, amb inscripció i places limitades.';

    protected function fields(?Model $model): array
    {
        return [
            ['type' => 'section', 'label' => 'Activitat'],
            ['name' => 'title', 'label' => 'Títol', 'rules' => 'required|max:140', 'full' => true],
            ['name' => 'type', 'label' => 'Tipus', 'type' => 'select', 'options' => self::TYPES, 'rules' => 'required', 'default' => 'taller'],
            ['name' => 'location', 'label' => 'Lloc', 'rules' => 'max:160', 'placeholder' => 'Moviment Conscient, Vallirana'],
            ['name' => 'description', 'label' => 'Descripció', 'type' => 'textarea', 'rows' => 6],
            ['name' => 'image_path', 'label' => 'Imatge', 'type' => 'image'],
            ['type' => 'section', 'label' => 'Data i places'],
            ['name' => 'starts_at', 'label' => 'Comença', 'type' => 'datetime', 'rules' => 'required|date'],
            ['name' => 'ends_at', 'label' => 'Acaba', 'type' => 'datetime', 'rules' => 'nullable|date|after:starts_at'],
            ['name' => 'capacity', 'label' => 'Places', 'type' => 'number', 'hint' => 'Buit = sense límit.', 'rules' => 'nullable|integer|min:1'],
            ['name' => 'price_cents', 'label' => 'Preu', 'type' => 'money', 'hint' => '0 = gratuït.'],
            ['name' => 'is_published', 'label' => 'Publicat a l\'app', 'type' => 'toggle', 'default' => false],
        ];
    }

    protected function columns(): array
    {
        return [
            ['label' => 'Títol', 'key' => 'title', 'link' => true],
            ['label' => 'Tipus', 'key' => 'type', 'type' => 'badge', 'map' => collect(self::TYPES)->map(fn ($l) => [$l, 'primary'])->all()],
            ['label' => 'Data', 'key' => 'starts_at', 'type' => 'datetime'],
            ['label' => 'Inscrits', 'key' => 'inscrits'],
            ['label' => 'Preu', 'key' => 'price_cents', 'type' => 'money'],
            ['label' => 'Estat', 'key' => 'is_published', 'type' => 'bool', 'yes' => 'Publicat', 'no' => 'Esborrany'],
        ];
    }

    protected function searchable(): array
    {
        return ['title', 'location'];
    }

    protected function baseQuery(): Builder
    {
        return Event::query()->withCount('confirmedRegistrations');
    }

    protected function order(Builder $query): Builder
    {
        return $query->orderByDesc('starts_at');
    }

    public function rowActions(Model $row): array
    {
        return [['label' => 'Inscrits', 'url' => route('admin.events.registrations', $row), 'icon' => 'users']];
    }

    protected function beforeSave(array $data, ?Model $model, Request $request): array
    {
        $data['slug'] = $model?->slug ?: $this->uniqueSlug($data['title'], $model);

        return $data;
    }

    protected function deleteBlocker(Model $model): ?string
    {
        return $model->registrations()->exists() ? 'Hi ha persones inscrites. Despublica\'l en lloc d\'eliminar-lo.' : null;
    }

    public function registrations(int|string $id): View
    {
        $event = Event::query()->withCount('confirmedRegistrations')->findOrFail($id);

        return view('admin.events.registrations', [
            'event' => $event,
            'registrations' => $event->registrations()->with(['user', 'payment'])->orderBy('created_at')->get(),
            'students' => User::query()->students()->where('is_active', true)->orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function register(Request $request, int|string $id, EventRegistrationService $registrations): RedirectResponse
    {
        $event = Event::query()->findOrFail($id);
        $data = $request->validate(['user_id' => ['required', 'exists:users,id']], [], ['user_id' => 'alumne/a']);

        if (EventRegistration::query()->where('event_id', $event->id)->where('user_id', $data['user_id'])->where('status', '!=', 'cancelled')->exists()) {
            return back()->with('error', 'Aquesta persona ja està inscrita.');
        }

        $registration = $registrations->register($event, User::query()->findOrFail($data['user_id']));
        $full = $registration->status === 'waitlist';

        return back()->with('status', $full ? 'El taller està ple: s\'ha afegit a la llista d\'espera.' : 'Inscripció feta.');
    }

    public function unregister(int|string $registrationId, EventRegistrationService $registrations): RedirectResponse
    {
        $next = $registrations->cancel(EventRegistration::query()->findOrFail($registrationId));

        return back()->with('status', 'Inscripció cancel·lada.'.($next ? ' Ha entrat la primera persona de la llista d\'espera.' : ''));
    }
}
