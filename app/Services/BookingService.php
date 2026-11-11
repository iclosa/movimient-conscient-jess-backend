<?php

namespace App\Services;

use App\Exceptions\BookingException;
use App\Models\Booking;
use App\Models\Child;
use App\Models\ClassSession;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserPass;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BookingService
{
    /**
     * Reserva una plaza (o entra en lista de espera si la clase está llena).
     *
     * @param  string|null  $forcedPayment  Solo admin: 'free' | 'pending' para saltarse el bono.
     *
     * @throws BookingException
     */
    public function book(
        User $user,
        ClassSession $session,
        ?Child $child = null,
        string $source = 'app',
        ?string $forcedPayment = null,
    ): Booking {
        return DB::transaction(function () use ($user, $session, $child, $source, $forcedPayment) {
            $session = ClassSession::query()->with('activity')->lockForUpdate()->findOrFail($session->id);
            $isAdmin = $source === 'admin';

            if ($session->isCancelled()) {
                throw new BookingException('Aquesta classe s\'ha cancel·lat.', 'session_cancelled');
            }

            if ($session->isPast() && ! $isAdmin) {
                throw new BookingException('Aquesta classe ja ha començat.', 'session_past');
            }

            if (! $isAdmin) {
                $openDays = (int) Setting::get('booking_open_days', '14');
                if ($session->starts_at->gt(now()->addDays($openDays))) {
                    throw new BookingException("Les reserves s'obren {$openDays} dies abans de la classe.", 'booking_not_open');
                }
            }

            if ($child !== null) {
                if ($child->user_id !== $user->id) {
                    throw new BookingException('Aquest perfil no és teu.', 'child_forbidden', 403);
                }
                if (! $session->activity->is_family) {
                    throw new BookingException('Aquesta classe no és per a infants.', 'child_not_allowed');
                }
            }

            $alreadyBooked = Booking::query()
                ->where('user_id', $user->id)
                ->where('class_session_id', $session->id)
                ->where('child_id', $child?->id)
                ->active()
                ->exists();

            if ($alreadyBooked) {
                throw new BookingException('Ja tens plaça en aquesta classe.', 'already_booked', 409);
            }

            $isFull = $session->occupyingBookings()->count() >= $session->capacity;

            if ($isFull) {
                if (Setting::get('waitlist_enabled') !== '1') {
                    throw new BookingException('La classe està completa.', 'session_full', 409);
                }

                return Booking::create([
                    'user_id' => $user->id,
                    'class_session_id' => $session->id,
                    'child_id' => $child?->id,
                    'status' => Booking::STATUS_WAITLIST,
                    'payment_mode' => Booking::PAYMENT_PENDING,
                    'source' => $source,
                ]);
            }

            [$mode, $pass] = $this->resolvePayment($user, $session, $forcedPayment);

            $pass?->consume();

            $booking = Booking::create([
                'user_id' => $user->id,
                'class_session_id' => $session->id,
                'child_id' => $child?->id,
                'user_pass_id' => $pass?->id,
                'status' => Booking::STATUS_BOOKED,
                'payment_mode' => $mode,
                'source' => $source,
            ]);

            $this->openPayment($booking, $session);

            return $booking;
        });
    }

    /**
     * Cancela una reserva. Si es a tiempo devuelve la clase al bono y promueve a la lista de espera.
     *
     * @throws BookingException
     */
    public function cancel(Booking $booking, bool $byAdmin = false): Booking
    {
        return DB::transaction(function () use ($booking, $byAdmin) {
            $booking->load('classSession', 'userPass');

            if (! in_array($booking->status, [Booking::STATUS_BOOKED, Booking::STATUS_WAITLIST], true)) {
                throw new BookingException('Aquesta reserva ja no es pot cancel·lar.', 'not_cancellable');
            }

            $session = $booking->classSession;

            if ($session->isPast() && ! $byAdmin) {
                throw new BookingException('La classe ja ha començat.', 'session_past');
            }

            $wasBooked = $booking->status === Booking::STATUS_BOOKED;
            $hours = (int) Setting::get('cancellation_hours', '12');
            $late = $wasBooked && ! $byAdmin && now()->addHours($hours)->gt($session->starts_at);

            if ($wasBooked && ! $late) {
                $booking->userPass?->refund();
            }

            $booking->update([
                'status' => Booking::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'late_cancellation' => $late,
            ]);

            $booking->payment?->cancelIfPending();

            if ($wasBooked) {
                $this->promoteWaitlist($session);
            }

            return $booking->refresh();
        });
    }

    /**
     * Pasa personas de la lista de espera a reservadas mientras haya plazas.
     * Se salta a quien no pueda pagar la clase (sin bono y clase de pago).
     *
     * @return Collection<int, Booking> Reservas promovidas (para avisar a la persona).
     */
    public function promoteWaitlist(ClassSession $session): Collection
    {
        $promoted = collect();

        if ($session->isCancelled() || $session->isPast()) {
            return $promoted;
        }

        $waiting = $session->waitlistBookings()->with('user')->get();

        foreach ($waiting as $candidate) {
            if ($session->occupyingBookings()->count() >= $session->capacity) {
                break;
            }

            try {
                [$mode, $pass] = $this->resolvePayment($candidate->user, $session, null);
            } catch (BookingException) {
                continue;
            }

            $pass?->consume();

            $candidate->update([
                'status' => Booking::STATUS_BOOKED,
                'payment_mode' => $mode,
                'user_pass_id' => $pass?->id,
            ]);

            $this->openPayment($candidate, $session);

            $promoted->push($candidate);
        }

        return $promoted;
    }

    /**
     * Guarda la valoración de una clase ya hecha.
     *
     * @throws BookingException
     */
    public function rate(Booking $booking, int $rating, ?string $comment = null): Booking
    {
        $session = $booking->classSession;

        $practised = $booking->status === Booking::STATUS_ATTENDED
            || ($booking->status === Booking::STATUS_BOOKED && $session->isPast());

        if (! $practised) {
            throw new BookingException('Només pots valorar classes que ja has fet.', 'not_rateable');
        }

        $booking->update([
            'rating' => max(1, min(5, $rating)),
            'rating_comment' => $comment,
            'rated_at' => now(),
        ]);

        return $booking->refresh();
    }

    /**
     * Genera el cobrament d'una reserva que ocupa plaça: efectiu i pendent per defecte.
     * Només si hi ha alguna cosa a cobrar: les reserves amb bo o gratuïtes no en generen
     * (el bo ja va tenir el seu cobrament en comprar-lo).
     */
    private function openPayment(Booking $booking, ClassSession $session): void
    {
        if ($booking->payment_mode !== Booking::PAYMENT_PENDING || $session->price_cents <= 0) {
            return;
        }

        $session->loadMissing('activity');

        $payment = Payment::create([
            'user_id' => $booking->user_id,
            'amount_cents' => $session->price_cents,
            'currency' => 'EUR',
            'method' => 'cash',
            'status' => 'pending',
            'concept' => 'Classe: '.$session->activity->name.' ('.$session->starts_at->format('d/m/Y H:i').')',
            'payable_type' => $booking->getMorphClass(),
            'payable_id' => $booking->id,
        ]);

        $booking->update(['payment_id' => $payment->id]);
    }

    /**
     * Decide cómo se paga la plaza: bono, gratis, o modo forzado por la administración.
     *
     * @return array{0: string, 1: UserPass|null}
     *
     * @throws BookingException
     */
    private function resolvePayment(User $user, ClassSession $session, ?string $forced): array
    {
        if ($forced !== null && in_array($forced, [Booking::PAYMENT_FREE, Booking::PAYMENT_PENDING], true)) {
            return [$forced, null];
        }

        $pass = $user->bestUsablePass();

        if ($pass !== null) {
            return [Booking::PAYMENT_PASS, $pass];
        }

        if ($session->price_cents === 0) {
            return [Booking::PAYMENT_FREE, null];
        }

        // Sense bo: la plaça queda reservada i pendent de pagar (es cobra a la classe o amb un bo).
        return [Booking::PAYMENT_PENDING, null];
    }
}
