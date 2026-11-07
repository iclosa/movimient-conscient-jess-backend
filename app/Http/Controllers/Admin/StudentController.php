<?php

namespace App\Http\Controllers\Admin;

use App\Models\Booking;
use App\Models\Child;
use App\Models\Pass;
use App\Models\User;
use App\Services\PracticeStreak;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class StudentController extends CrudController
{
    protected string $model = User::class;

    protected string $routeName = 'admin.students';

    protected string $singular = 'Alumne/a';

    protected string $plural = 'Alumnes';

    protected string $newLabel = 'Nou alumne/a';

    protected string $icon = 'users';

    protected ?string $intro = 'Les persones que fan servir l\'app. Obre una fitxa per veure bons, reserves i cobraments.';

    protected function fields(?Model $model): array
    {
        return [
            ['type' => 'section', 'label' => 'Dades personals'],
            ['name' => 'name', 'label' => 'Nom i cognoms', 'rules' => 'required|max:120'],
            ['name' => 'email', 'label' => 'Correu electrònic', 'type' => 'email', 'rules' => fn (?Model $m) => ['required', Rule::unique('users', 'email')->ignore($m?->id)]],
            ['name' => 'phone', 'label' => 'Telèfon', 'rules' => 'max:30'],
            ['name' => 'birth_date', 'label' => 'Data de naixement', 'type' => 'date'],
            ['type' => 'section', 'label' => 'Accés a l\'app'],
            ['name' => 'password', 'label' => 'Contrasenya', 'type' => 'password', 'hint' => $model ? 'Deixa-ho buit per no canviar-la.' : 'Mínim 8 caràcters. L\'alumne la podrà canviar a l\'app.'],
            ['name' => 'locale', 'label' => 'Idioma', 'type' => 'select', 'options' => ['ca' => 'Català', 'es' => 'Castellà'], 'rules' => 'required|in:ca,es', 'default' => 'ca'],
            ['name' => 'is_active', 'label' => 'Compte actiu', 'type' => 'toggle', 'default' => true, 'hint' => 'Si el desactives, no podrà reservar ni entrar a l\'app.'],
            ['name' => 'marketing_consent', 'label' => 'Accepta rebre promocions', 'type' => 'toggle', 'default' => false],
            ['type' => 'section', 'label' => 'Notes internes'],
            ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea', 'rows' => 4, 'hint' => 'Només les veus tu. Lesions, preferències, etc.'],
        ];
    }

    protected function columns(): array
    {
        return [
            ['label' => 'Nom', 'key' => 'name', 'route' => 'admin.students.show', 'sub' => 'email'],
            ['label' => 'Telèfon', 'key' => 'phone'],
            ['label' => 'Bo', 'key' => 'pass_summary'],
            ['label' => 'Reserves', 'key' => 'bookings_count'],
            ['label' => 'Compte', 'key' => 'is_active', 'type' => 'bool', 'yes' => 'Actiu', 'no' => 'Desactivat'],
        ];
    }

    protected function searchable(): array
    {
        return ['name', 'email', 'phone'];
    }

    protected function filters(): array
    {
        return [
            ['name' => 'is_active', 'label' => 'Compte', 'options' => ['1' => 'Actius', '0' => 'Desactivats']],
        ];
    }

    protected function baseQuery(): Builder
    {
        return User::query()->students()->withCount('bookings')->with('userPasses');
    }

    protected function order(Builder $query): Builder
    {
        return $query->orderBy('name');
    }

    public function rowActions(Model $row): array
    {
        return [['label' => 'Fitxa', 'url' => route('admin.students.show', $row), 'icon' => 'eye']];
    }

    protected function beforeSave(array $data, ?Model $model, Request $request): array
    {
        if (! $model) {
            $data['role'] = User::ROLE_STUDENT;
            $data['email_verified_at'] = now();
        }

        return $data;
    }

    protected function redirectAfterSave(Model $model): RedirectResponse
    {
        return redirect()->route('admin.students.show', $model);
    }

    protected function deleteBlocker(Model $model): ?string
    {
        return ($model->bookings()->exists() || $model->payments()->exists())
            ? 'No es pot eliminar: té reserves o cobraments. Desactiva el compte i conserves l\'historial.'
            : null;
    }

    public function show(int|string $id, PracticeStreak $streak): View
    {
        $student = User::query()->students()->with(['children', 'userPasses.pass'])->findOrFail($id);

        $upcoming = $student->bookings()
            ->with(['classSession.activity', 'child'])
            ->active()
            ->whereHas('classSession', fn (Builder $q) => $q->where('starts_at', '>=', now()))
            ->get()
            ->sortBy('classSession.starts_at');

        $history = $student->bookings()
            ->with(['classSession.activity', 'child'])
            ->whereHas('classSession', fn (Builder $q) => $q->where('starts_at', '<', now()))
            ->latest('id')
            ->limit(10)
            ->get();

        return view('admin.students.show', [
            'student' => $student,
            'upcoming' => $upcoming,
            'history' => $history,
            'streak' => $streak->for($student),
            'payments' => $student->payments()->latest('paid_at')->limit(6)->get(),
            'paidTotal' => (int) $student->payments()->where('status', 'paid')->sum('amount_cents'),
            'catalog' => Pass::query()->active()->orderBy('sort_order')->get(),
        ]);
    }

    public function storeChild(Request $request, int|string $userId): RedirectResponse
    {
        $student = User::query()->students()->findOrFail($userId);

        $data = $request->validate([
            'name' => ['required', 'max:80'],
            'birth_date' => ['nullable', 'date', 'before:today'],
        ], [], ['name' => 'nom', 'birth_date' => 'data de naixement']);

        $student->children()->create($data);

        return back()->with('status', 'Perfil d\'infant afegit.');
    }

    public function destroyChild(int|string $childId): RedirectResponse
    {
        $child = Child::query()->findOrFail($childId);

        if ($child->bookings()->where('status', Booking::STATUS_BOOKED)->exists()) {
            return back()->with('error', 'Té reserves actives. Cancel·la-les abans d\'eliminar el perfil.');
        }

        $child->delete();

        return back()->with('status', 'Perfil eliminat.');
    }
}
