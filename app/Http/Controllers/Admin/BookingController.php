<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\BookingException;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Child;
use App\Models\ClassSession;
use App\Models\User;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(private BookingService $bookings) {}

    /**
     * Apunta una persona (o un fill/a) a una classe desde el panel.
     */
    public function store(Request $request, int|string $sessionId): RedirectResponse
    {
        $data = $request->validate([
            'person' => ['required', 'regex:/^(user|child):\d+$/'],
            'payment' => ['required', 'in:auto,free,pending'],
        ], [], ['person' => 'persona', 'payment' => 'pagament']);

        $session = ClassSession::query()->findOrFail($sessionId);
        [$kind, $id] = explode(':', $data['person']);

        $child = $kind === 'child' ? Child::query()->findOrFail($id) : null;
        $user = $child ? $child->user : User::query()->findOrFail($id);

        try {
            $booking = $this->bookings->book(
                $user,
                $session,
                $child,
                'admin',
                $data['payment'] === 'auto' ? null : $data['payment'],
            );
        } catch (BookingException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', $booking->status === Booking::STATUS_WAITLIST
            ? 'La classe està plena: s\'ha afegit a la llista d\'espera.'
            : 'Reserva creada.');
    }

    public function attendance(Request $request, int|string $id): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:booked,attended,no_show']]);
        $booking = Booking::query()->findOrFail($id);

        $booking->update([
            'status' => $data['status'],
            'attended_at' => $data['status'] === Booking::STATUS_ATTENDED ? now() : null,
        ]);

        return back()->with('status', 'Assistència actualitzada.');
    }

    public function cancel(int|string $id): RedirectResponse
    {
        try {
            $booking = $this->bookings->cancel(Booking::query()->findOrFail($id), byAdmin: true);
        } catch (BookingException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Reserva cancel·lada i bo retornat. La llista d\'espera s\'ha actualitzat.');
    }
}
