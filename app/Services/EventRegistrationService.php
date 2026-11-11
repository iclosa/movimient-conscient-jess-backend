<?php

namespace App\Services;

use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EventRegistrationService
{
    /**
     * Inscriu una persona a un taller, retir o esdeveniment (o a la llista d'espera si està ple).
     * Si queda confirmada, genera el cobrament: efectiu i pendent si és de pagament, o 0 € cobrat si és gratuït.
     */
    public function register(Event $event, User $user): EventRegistration
    {
        return DB::transaction(function () use ($event, $user) {
            $full = $event->capacity !== null
                && $event->confirmedRegistrations()->count() >= $event->capacity;

            $registration = EventRegistration::query()->updateOrCreate(
                ['event_id' => $event->id, 'user_id' => $user->id],
                ['status' => $full ? 'waitlist' : 'confirmed'],
            );

            if ($registration->status === 'confirmed') {
                $this->openPayment($registration, $event);
            }

            return $registration;
        });
    }

    /**
     * Cancel·la una inscripció i el seu cobrament si encara és pendent.
     * Si alliberava una plaça, entra la primera persona de la llista d'espera.
     *
     * @return EventRegistration|null La persona que ha entrat des de la llista d'espera.
     */
    public function cancel(EventRegistration $registration): ?EventRegistration
    {
        return DB::transaction(function () use ($registration) {
            $wasConfirmed = $registration->status === 'confirmed';

            $registration->update(['status' => 'cancelled']);
            $registration->payment?->cancelUnlessCollected();

            if (! $wasConfirmed) {
                return null;
            }

            $next = EventRegistration::query()
                ->where('event_id', $registration->event_id)
                ->where('status', 'waitlist')
                ->oldest()
                ->first();

            if ($next !== null) {
                $next->update(['status' => 'confirmed']);
                $this->openPayment($next, $registration->event);
            }

            return $next;
        });
    }

    private function openPayment(EventRegistration $registration, Event $event): void
    {
        // Si es torna a inscriure després de cancel·lar, el cobrament anul·lat no val: se'n crea un de nou.
        if ($registration->payment !== null && $registration->payment->status !== 'cancelled') {
            return;
        }

        $payment = Payment::create([
            'user_id' => $registration->user_id,
            'amount_cents' => $event->price_cents,
            'currency' => 'EUR',
            'method' => $event->price_cents > 0 ? 'cash' : 'free',
            'status' => $event->price_cents > 0 ? 'pending' : 'paid',
            'concept' => 'Esdeveniment: '.$event->title,
            'payable_type' => $event->getMorphClass(),
            'payable_id' => $event->id,
        ]);

        $registration->update(['payment_id' => $payment->id]);
    }
}
