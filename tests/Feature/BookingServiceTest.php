<?php

namespace Tests\Feature;

use App\Exceptions\BookingException;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\Child;
use App\Models\ClassSession;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserPass;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingServiceTest extends TestCase
{
    use RefreshDatabase;

    private BookingService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(BookingService::class);
        Setting::set('waitlist_enabled', '1');
        Setting::set('cancellation_hours', '12');
    }

    private function makeSession(int $capacity = 2, int $price = 1400, bool $family = false, ?\DateTimeInterface $at = null): ClassSession
    {
        $activity = Activity::query()->create([
            'name' => 'Hatha '.uniqid(), 'slug' => 'hatha-'.uniqid(), 'is_family' => $family,
        ]);
        $start = $at ? Carbon::instance($at) : now()->addDays(3)->setTime(10, 0);

        return ClassSession::query()->create([
            'activity_id' => $activity->id,
            'starts_at' => $start,
            'ends_at' => $start->copy()->addHour(),
            'capacity' => $capacity,
            'price_cents' => $price,
        ]);
    }

    private function studentWithPass(?int $classes = 5): User
    {
        $user = User::factory()->create();
        UserPass::query()->create([
            'user_id' => $user->id, 'name' => 'Bo', 'classes_total' => $classes, 'classes_remaining' => $classes,
            'starts_on' => today()->subDay(), 'expires_on' => today()->addMonth(),
        ]);

        return $user;
    }

    public function test_booking_with_a_pass_consumes_one_class(): void
    {
        $user = $this->studentWithPass(3);

        $booking = $this->service->book($user, $this->makeSession());

        $this->assertSame(Booking::STATUS_BOOKED, $booking->status);
        $this->assertSame('pass', $booking->payment->method);
        $this->assertSame(2, $user->userPasses()->first()->classes_remaining);
    }

    public function test_the_last_class_marks_the_pass_as_exhausted(): void
    {
        $user = $this->studentWithPass(1);

        $this->service->book($user, $this->makeSession());

        $this->assertSame(UserPass::STATUS_EXHAUSTED, $user->userPasses()->first()->status);
    }

    public function test_paid_class_without_a_pass_is_pending_payment(): void
    {
        $booking = $this->service->book(User::factory()->create(), $this->makeSession());

        $this->assertSame(Booking::STATUS_BOOKED, $booking->status);
        $this->assertSame('pending', $booking->payment->status);
    }

    public function test_free_class_needs_no_pass(): void
    {
        $booking = $this->service->book(User::factory()->create(), $this->makeSession(price: 0));

        $this->assertSame('free', $booking->payment->method);
    }

    public function test_the_same_person_cannot_book_twice(): void
    {
        $user = $this->studentWithPass();
        $session = $this->makeSession();
        $this->service->book($user, $session);

        try {
            $this->service->book($user, $session);
            $this->fail('Hauria de rebutjar la reserva duplicada.');
        } catch (BookingException $e) {
            $this->assertSame('already_booked', $e->errorCode);
        }
    }

    public function test_a_full_class_sends_people_to_the_waitlist_and_a_cancellation_promotes_them(): void
    {
        $session = $this->makeSession(capacity: 1);
        $first = $this->studentWithPass();
        $second = $this->studentWithPass();

        $booked = $this->service->book($first, $session);
        $waiting = $this->service->book($second, $session);

        $this->assertSame(Booking::STATUS_WAITLIST, $waiting->status);
        $this->assertSame(5, $second->userPasses()->first()->classes_remaining, 'En espera no es gasta el bo.');

        $this->service->cancel($booked);

        $this->assertSame(Booking::STATUS_BOOKED, $waiting->fresh()->status);
        $this->assertSame(4, $second->userPasses()->first()->classes_remaining);
        $this->assertSame(5, $first->userPasses()->first()->classes_remaining, 'Cancel·lar a temps retorna el bo.');
    }

    public function test_waitlist_promotes_in_order_even_without_a_pass(): void
    {
        $session = $this->makeSession(capacity: 1);
        $first = $this->studentWithPass();
        $broke = User::factory()->create();
        $next = $this->studentWithPass();

        $booked = $this->service->book($first, $session);
        // Entra en espera sin bo (admin) i després en queda un altre amb bo.
        $this->service->book($broke, $session, source: 'admin');
        $this->service->book($next, $session);

        $this->service->cancel($booked);

        $this->assertSame(Booking::STATUS_BOOKED, $broke->bookings()->first()->status);
        $this->assertSame('pending', $broke->bookings()->first()->payment->status);
        $this->assertSame(Booking::STATUS_WAITLIST, $next->bookings()->first()->status);
    }

    public function test_booking_without_a_pass_opens_a_pending_cash_payment(): void
    {
        $user = User::factory()->create();

        $booking = $this->service->book($user, $this->makeSession(price: 1400));

        $payment = $booking->payment;
        $this->assertNotNull($payment);
        $this->assertSame('cash', $payment->method);
        $this->assertSame('pending', $payment->status);
        $this->assertSame(1400, $payment->amount_cents);
        $this->assertSame($user->id, $payment->user_id);
    }

    public function test_bookings_with_a_pass_or_free_open_a_collected_payment_of_zero(): void
    {
        $withPass = $this->service->book($this->studentWithPass(), $this->makeSession());
        $free = $this->service->book(User::factory()->create(), $this->makeSession(price: 0));

        $this->assertSame(['pass', 'paid', 0], [$withPass->payment->method, $withPass->payment->status, $withPass->payment->amount_cents]);
        $this->assertSame(['free', 'paid', 0], [$free->payment->method, $free->payment->status, $free->payment->amount_cents]);
        $this->assertNotNull($withPass->payment->paid_at);
    }

    public function test_cancelling_a_pass_booking_cancels_its_zero_payment(): void
    {
        $booking = $this->service->book($this->studentWithPass(), $this->makeSession());

        $this->service->cancel($booking);

        $this->assertSame('cancelled', $booking->payment->fresh()->status);
    }

    public function test_marking_a_payment_as_paid_sets_the_date_and_back_to_pending_clears_it(): void
    {
        $booking = $this->service->book(User::factory()->create(), $this->makeSession());
        $payment = $booking->payment;
        $this->assertNull($payment->paid_at);

        $payment->update(['status' => 'paid']);
        $this->assertNotNull($payment->fresh()->paid_at);
        $this->assertSame(1400, (int) Payment::query()->where('status', 'paid')
            ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('amount_cents'));

        $payment->update(['status' => 'pending']);
        $this->assertNull($payment->fresh()->paid_at);
    }

    public function test_cancelling_a_booking_cancels_its_pending_payment(): void
    {
        $booking = $this->service->book(User::factory()->create(), $this->makeSession());

        $this->service->cancel($booking);

        $this->assertSame('cancelled', $booking->payment->fresh()->status);
    }

    public function test_cancelling_a_booking_with_collected_money_leaves_the_payment_pending_refund(): void
    {
        $booking = $this->service->book(User::factory()->create(), $this->makeSession());
        $booking->payment->update(['status' => 'paid']);

        $this->service->cancel($booking);

        $this->assertSame('refund_pending', $booking->payment->fresh()->status);
        $this->assertSame(1400, $booking->payment->fresh()->amount_cents);
        $this->assertSame(0, (int) Payment::query()->where('status', 'paid')->sum('amount_cents'));
    }

    public function test_waitlist_promotion_opens_the_payment(): void
    {
        $session = $this->makeSession(capacity: 1);
        $first = $this->service->book(User::factory()->create(), $session);
        $waiting = $this->service->book(User::factory()->create(), $session);
        $this->assertNull($waiting->payment_id);

        $this->service->cancel($first);

        $promoted = $waiting->fresh();
        $this->assertSame(Booking::STATUS_BOOKED, $promoted->status);
        $this->assertSame('pending', $promoted->payment->status);
    }

    public function test_late_cancellation_keeps_the_class_consumed(): void
    {
        $user = $this->studentWithPass(3);
        $booking = $this->service->book($user, $this->makeSession(at: now()->addHours(3)));

        $cancelled = $this->service->cancel($booking);

        $this->assertTrue($cancelled->late_cancellation);
        $this->assertSame(2, $user->userPasses()->first()->classes_remaining);
    }

    public function test_admin_cancellation_always_refunds(): void
    {
        $user = $this->studentWithPass(3);
        $booking = $this->service->book($user, $this->makeSession(at: now()->addHours(3)));

        $this->service->cancel($booking, byAdmin: true);

        $this->assertSame(3, $user->userPasses()->first()->classes_remaining);
    }

    public function test_children_can_only_join_family_classes(): void
    {
        $parent = $this->studentWithPass();
        $child = Child::query()->create(['user_id' => $parent->id, 'name' => 'Biel']);

        try {
            $this->service->book($parent, $this->makeSession(family: false), $child);
            $this->fail('Una classe no familiar no hauria d\'acceptar infants.');
        } catch (BookingException $e) {
            $this->assertSame('child_not_allowed', $e->errorCode);
        }

        $booking = $this->service->book($parent, $this->makeSession(family: true), $child);
        $this->assertSame($child->id, $booking->child_id);
    }

    public function test_bookings_outside_the_opening_window_are_refused(): void
    {
        Setting::set('booking_open_days', '7');

        try {
            $this->service->book($this->studentWithPass(), $this->makeSession(at: now()->addDays(20)));
            $this->fail('Fora de la finestra de reserves.');
        } catch (BookingException $e) {
            $this->assertSame('booking_not_open', $e->errorCode);
        }
    }

    public function test_only_practised_classes_can_be_rated(): void
    {
        $user = $this->studentWithPass();
        $booking = $this->service->book($user, $this->makeSession());

        try {
            $this->service->rate($booking, 5);
            $this->fail('Una classe futura no es pot valorar.');
        } catch (BookingException $e) {
            $this->assertSame('not_rateable', $e->errorCode);
        }

        $booking->update(['status' => Booking::STATUS_ATTENDED]);
        $rated = $this->service->rate($booking->fresh(), 9, 'Genial');

        $this->assertSame(5, $rated->rating, 'La nota es limita a 1-5.');
    }
}
