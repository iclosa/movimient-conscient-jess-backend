<?php

namespace App\Http\Controllers\Admin;

use App\Models\Activity;
use App\Models\Booking;
use App\Models\ClassSession;
use App\Models\Instructor;
use App\Models\Room;
use App\Models\Setting;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ClassSessionController extends CrudController
{
    protected string $model = ClassSession::class;

    protected string $routeName = 'admin.sessions';

    protected string $singular = 'Classe';

    protected string $plural = 'Classes';

    protected string $newLabel = 'Nova classe puntual';

    protected string $icon = 'clock';

    protected ?string $intro = 'Totes les classes del calendari. Obre una classe per veure qui hi ha apuntat i passar llista.';

    protected function fields(?Model $model): array
    {
        return [
            ['type' => 'section', 'label' => 'Classe'],
            ['name' => 'activity_id', 'label' => 'Activitat', 'type' => 'select', 'options' => Activity::query()->orderBy('name')->pluck('name', 'id')->all(), 'rules' => 'required|exists:activities,id'],
            ['name' => 'title', 'label' => 'Títol propi', 'hint' => 'Opcional. Per a classes especials ("Hatha d\'estiu").', 'rules' => 'max:120'],
            ['name' => 'starts_at', 'label' => 'Comença', 'type' => 'datetime', 'rules' => 'required|date'],
            ['name' => 'ends_at', 'label' => 'Acaba', 'type' => 'datetime', 'rules' => 'required|date|after:starts_at'],
            ['type' => 'section', 'label' => 'Lloc, persones i preu'],
            ['name' => 'room_id', 'label' => 'Sala', 'type' => 'select', 'nullable' => true, 'options' => Room::query()->orderBy('name')->pluck('name', 'id')->all(), 'rules' => 'nullable|exists:rooms,id'],
            ['name' => 'instructor_id', 'label' => 'Professor/a', 'type' => 'select', 'nullable' => true, 'options' => Instructor::query()->orderBy('name')->pluck('name', 'id')->all(), 'rules' => 'nullable|exists:instructors,id'],
            ['name' => 'capacity', 'label' => 'Places', 'type' => 'number', 'default' => (int) Setting::get('default_capacity', '12'), 'rules' => 'required|integer|min:1|max:500'],
            ['name' => 'price_cents', 'label' => 'Preu de la classe solta', 'type' => 'money'],
            ['type' => 'section', 'label' => 'Estat'],
            ['name' => 'status', 'label' => 'Estat', 'type' => 'select', 'options' => ['scheduled' => 'Programada', 'cancelled' => 'Cancel·lada'], 'default' => 'scheduled', 'rules' => 'required|in:scheduled,cancelled'],
            ['name' => 'notes', 'label' => 'Notes internes', 'type' => 'textarea', 'rows' => 3],
        ];
    }

    protected function columns(): array
    {
        return [
            ['label' => 'Data', 'key' => 'starts_at', 'type' => 'datetime', 'route' => 'admin.sessions.show'],
            ['label' => 'Activitat', 'key' => 'activity.name', 'type' => 'dot', 'color' => 'activity.color'],
            ['label' => 'Professor/a', 'key' => 'instructor.name'],
            ['label' => 'Sala', 'key' => 'room.name'],
            ['label' => 'Ocupació', 'key' => 'occupancy_label'],
            ['label' => 'Estat', 'key' => 'status', 'type' => 'badge', 'map' => [
                'scheduled' => ['Programada', 'success'],
                'cancelled' => ['Cancel·lada', 'danger'],
            ]],
        ];
    }

    protected function filters(): array
    {
        return [
            ['name' => 'period', 'label' => 'Període', 'empty' => 'Properes classes', 'custom' => true, 'options' => ['past' => 'Classes passades', 'all' => 'Totes']],
            ['name' => 'activity_id', 'label' => 'Activitat', 'options' => Activity::query()->orderBy('name')->pluck('name', 'id')->all()],
            ['name' => 'status', 'label' => 'Estat', 'options' => ['scheduled' => 'Programada', 'cancelled' => 'Cancel·lada']],
        ];
    }

    protected function with(): array
    {
        return ['activity', 'instructor', 'room'];
    }

    protected function baseQuery(): Builder
    {
        $period = request('period', 'upcoming');

        return ClassSession::query()
            ->withCount('occupyingBookings')
            ->when($period === 'upcoming', fn (Builder $q) => $q->where('starts_at', '>=', today()))
            ->when($period === 'past', fn (Builder $q) => $q->where('starts_at', '<', today()));
    }

    protected function order(Builder $query): Builder
    {
        return request('period') === 'past'
            ? $query->orderByDesc('starts_at')
            : $query->orderBy('starts_at');
    }

    public function rowActions(Model $row): array
    {
        return [['label' => 'Obre', 'url' => route('admin.sessions.show', $row), 'icon' => 'eye']];
    }

    protected function deleteBlocker(Model $model): ?string
    {
        return $model->bookings()->exists()
            ? 'No es pot eliminar: té reserves. Cancel·la la classe i s\'avisarà el calendari.'
            : null;
    }

    protected function redirectAfterSave(Model $model): RedirectResponse
    {
        return redirect()->route('admin.sessions.show', $model);
    }

    public function show(int|string $id): View
    {
        $session = ClassSession::query()
            ->with(['activity', 'room', 'instructor'])
            ->withCount('occupyingBookings')
            ->findOrFail($id);

        $bookings = $session->bookings()
            ->with(['user', 'child', 'payment'])
            ->whereIn('status', [...Booking::OCCUPYING_STATUSES])
            ->orderBy('created_at')
            ->get();

        $waitlist = $session->waitlistBookings()->with(['user', 'child'])->get();

        $cancelled = $session->bookings()->with(['user', 'child'])->where('status', Booking::STATUS_CANCELLED)->latest('cancelled_at')->get();

        // Personas que se pueden apuntar: alumnado activo y, en clases familiares, sus hijos/as.
        $students = User::query()->students()->where('is_active', true)->with('children')->orderBy('name')->get();
        $takenKeys = $session->bookings()->active()->get()->map(fn (Booking $b) => $b->child_id ? 'child:'.$b->child_id : 'user:'.$b->user_id)->all();

        return view('admin.sessions.show', [
            'session' => $session,
            'bookings' => $bookings,
            'waitlist' => $waitlist,
            'cancelled' => $cancelled,
            'students' => $students,
            'takenKeys' => $takenKeys,
            'averageRating' => $session->bookings()->whereNotNull('rating')->avg('rating'),
        ]);
    }

    public function cancel(int|string $id, BookingService $bookings): RedirectResponse
    {
        $session = ClassSession::query()->findOrFail($id);
        $session->update(['status' => ClassSession::STATUS_CANCELLED]);

        $count = 0;
        foreach ($session->bookings()->whereIn('status', [Booking::STATUS_BOOKED, Booking::STATUS_WAITLIST])->get() as $booking) {
            $bookings->cancel($booking, byAdmin: true);
            $count++;
        }

        return back()->with('status', "Classe cancel·lada. S'han alliberat {$count} reserves i s'han retornat els bons.");
    }

    public function reactivate(int|string $id): RedirectResponse
    {
        ClassSession::query()->findOrFail($id)->update(['status' => ClassSession::STATUS_SCHEDULED]);

        return back()->with('status', 'La classe torna a estar programada. Les reserves cancel·lades no es recuperen soles.');
    }
}
