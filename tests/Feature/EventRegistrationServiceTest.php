<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Payment;
use App\Models\User;
use App\Services\EventRegistrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventRegistrationServiceTest extends TestCase
{
    use RefreshDatabase;

    private EventRegistrationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(EventRegistrationService::class);
    }

    private function makeEvent(int $price = 3500, ?int $capacity = 10, string $type = 'taller'): Event
    {
        return Event::query()->create([
            'title' => 'Retir '.uniqid(),
            'slug' => 'retir-'.uniqid(),
            'type' => $type,
            'starts_at' => now()->addWeeks(2),
            'capacity' => $capacity,
            'price_cents' => $price,
            'is_published' => true,
        ]);
    }

    public function test_registering_opens_a_pending_cash_payment(): void
    {
        $user = User::factory()->create();

        $registration = $this->service->register($this->makeEvent(type: 'retir'), $user);

        $this->assertSame('confirmed', $registration->status);
        $this->assertSame('cash', $registration->payment->method);
        $this->assertSame('pending', $registration->payment->status);
        $this->assertSame(3500, $registration->payment->amount_cents);
    }

    public function test_free_events_open_a_collected_payment_of_zero(): void
    {
        $registration = $this->service->register($this->makeEvent(price: 0), User::factory()->create());

        $this->assertSame('free', $registration->payment->method);
        $this->assertSame('paid', $registration->payment->status);
        $this->assertSame(0, $registration->payment->amount_cents);
    }

    public function test_cancelling_with_collected_money_leaves_the_payment_pending_refund(): void
    {
        $registration = $this->service->register($this->makeEvent(), User::factory()->create());
        $registration->payment->update(['status' => 'paid']);

        $this->service->cancel($registration);

        $this->assertSame('refund_pending', $registration->payment->fresh()->status);
    }

    public function test_registering_again_while_a_refund_is_pending_opens_a_new_payment(): void
    {
        $event = $this->makeEvent();
        $user = User::factory()->create();
        $first = $this->service->register($event, $user);
        $first->payment->update(['status' => 'paid']);
        $this->service->cancel($first);

        $again = $this->service->register($event, $user);

        $this->assertSame('pending', $again->fresh()->payment->status);
        $this->assertSame('refund_pending', Payment::query()->find($first->payment_id)->status);
    }

    public function test_cancelling_cancels_the_pending_payment(): void
    {
        $registration = $this->service->register($this->makeEvent(), User::factory()->create());

        $this->service->cancel($registration);

        $this->assertSame('cancelled', $registration->fresh()->status);
        $this->assertSame('cancelled', $registration->payment->fresh()->status);
    }

    public function test_registering_again_after_cancelling_opens_a_new_payment(): void
    {
        $event = $this->makeEvent();
        $user = User::factory()->create();

        $first = $this->service->register($event, $user);
        $this->service->cancel($first);
        $again = $this->service->register($event, $user);

        $this->assertSame('confirmed', $again->status);
        $this->assertNotSame($first->payment_id, $again->fresh()->payment_id);
        $this->assertSame('pending', $again->fresh()->payment->status);
        $this->assertSame(2, Payment::query()->count());
    }

    public function test_waitlist_person_gets_the_payment_when_a_place_is_freed(): void
    {
        $event = $this->makeEvent(capacity: 1);
        $first = $this->service->register($event, User::factory()->create());
        $waiting = $this->service->register($event, User::factory()->create());
        $this->assertSame('waitlist', $waiting->status);
        $this->assertNull($waiting->payment_id);

        $promoted = $this->service->cancel($first);

        $this->assertSame($waiting->id, $promoted->id);
        $this->assertSame('confirmed', $promoted->fresh()->status);
        $this->assertSame('pending', $promoted->fresh()->payment->status);
    }
}
