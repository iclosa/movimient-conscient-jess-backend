<?php

namespace Tests\Feature\Admin;

use App\Models\Activity;
use App\Models\Announcement;
use App\Models\Booking;
use App\Models\ClassSession;
use App\Models\Event;
use App\Models\Instructor;
use App\Models\Pass;
use App\Models\Payment;
use App\Models\Post;
use App\Models\Promotion;
use App\Models\Room;
use App\Models\ScheduleTemplate;
use App\Models\User;
use App\Models\Video;
use App\Models\WellnessItem;
use Database\Seeders\BaseSeeder;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([BaseSeeder::class, DemoSeeder::class]);
        $this->admin = User::query()->where('role', User::ROLE_ADMIN)->firstOrFail();
    }

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_students_cannot_enter_the_panel(): void
    {
        $student = User::factory()->create();

        $this->actingAs($student)->get('/admin')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_admin_can_log_in_with_the_seeded_account(): void
    {
        $this->post(route('login.attempt'), [
            'email' => $this->admin->email,
            'password' => 'canvia-aquesta-clau',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($this->admin);
    }

    public function test_wrong_password_is_rejected(): void
    {
        $this->post(route('login.attempt'), ['email' => $this->admin->email, 'password' => 'xxx'])
            ->assertSessionHasErrors('email');
    }

    public function test_every_panel_screen_renders(): void
    {
        $announcement = Announcement::query()->create(['title' => 'Prova', 'body' => 'Text', 'audience' => 'all']);

        $routes = [
            ['admin.dashboard', []],
            ['admin.calendar', []],
            ['admin.calendar', ['data' => now()->addWeek()->toDateString()]],
            ['admin.sessions.index', []],
            ['admin.sessions.index', ['period' => 'past']],
            ['admin.sessions.create', []],
            ['admin.sessions.show', ClassSession::query()->first()],
            ['admin.sessions.edit', ClassSession::query()->first()],
            ['admin.templates.index', []],
            ['admin.templates.create', []],
            ['admin.templates.edit', ScheduleTemplate::query()->first()],
            ['admin.activities.index', []],
            ['admin.activities.create', []],
            ['admin.activities.edit', Activity::query()->first()],
            ['admin.rooms.index', []],
            ['admin.rooms.create', []],
            ['admin.rooms.edit', Room::query()->first()],
            ['admin.instructors.index', []],
            ['admin.instructors.create', []],
            ['admin.instructors.edit', Instructor::query()->first()],
            ['admin.students.index', []],
            ['admin.students.index', ['q' => 'Marta']],
            ['admin.students.create', []],
            ['admin.students.show', User::query()->students()->first()],
            ['admin.students.edit', User::query()->students()->first()],
            ['admin.passes.index', []],
            ['admin.passes.create', []],
            ['admin.passes.edit', Pass::query()->first()],
            ['admin.payments.index', []],
            ['admin.payments.create', ['user' => User::query()->students()->first()->id]],
            ['admin.payments.edit', Payment::query()->first()],
            ['admin.ratings.index', []],
            ['admin.videos.index', []],
            ['admin.videos.create', []],
            ['admin.videos.edit', Video::query()->first()],
            ['admin.promotions.index', []],
            ['admin.promotions.create', []],
            ['admin.promotions.edit', Promotion::query()->first()],
            ['admin.posts.index', []],
            ['admin.posts.create', []],
            ['admin.posts.edit', Post::query()->first()],
            ['admin.events.index', []],
            ['admin.events.create', []],
            ['admin.events.edit', Event::query()->first()],
            ['admin.events.registrations', Event::query()->first()],
            ['admin.wellness.index', []],
            ['admin.wellness.create', []],
            ['admin.wellness.edit', WellnessItem::query()->where('type', 'breathing')->first()],
            ['admin.announcements.index', []],
            ['admin.announcements.create', []],
            ['admin.announcements.edit', $announcement],
            ['admin.settings.edit', []],
            ['admin.team.index', []],
            ['admin.team.create', []],
            ['admin.team.edit', $this->admin],
        ];

        foreach ($routes as [$name, $parameters]) {
            $response = $this->actingAs($this->admin)->get(route($name, $parameters));

            $this->assertSame(200, $response->getStatusCode(), "La pantalla {$name} no carrega (HTTP {$response->getStatusCode()}).");
        }
    }

    public function test_activity_can_be_created_and_updated(): void
    {
        $this->actingAs($this->admin)->post(route('admin.activities.store'), [
            'name' => 'Yoga prenatal',
            'tagline' => 'Per a l\'embaràs',
            'color' => '#aa33cc',
            'is_active' => '1',
            'sort_order' => '12',
        ])->assertRedirect(route('admin.activities.index'));

        $activity = Activity::query()->where('slug', 'yoga-prenatal')->firstOrFail();
        $this->assertSame('#aa33cc', $activity->color);
        $this->assertFalse($activity->is_family);

        $this->actingAs($this->admin)->put(route('admin.activities.update', $activity), [
            'name' => 'Yoga prenatal',
            'color' => '#aa33cc',
            'is_family' => '1',
        ])->assertRedirect(route('admin.activities.index'));

        $this->assertTrue($activity->fresh()->is_family);
        $this->assertFalse($activity->fresh()->is_active);
    }

    public function test_activity_content_price_and_spanish_slug_are_saved(): void
    {
        $this->actingAs($this->admin)->post(route('admin.activities.store'), [
            'name' => 'Ioga prenatal',
            'name_es' => 'Yoga prenatal',
            'price_cents' => '15,50',
            'color' => '#0E6B52',
            'benefits' => "Millora la respiració\nAlleuja l'esquena",
            'faq' => "Puc començar sense experiència?\nSí.\n\nQuè he de portar?\nRoba còmoda.",
            'is_active' => '1',
        ])->assertRedirect(route('admin.activities.index'));

        $activity = Activity::query()->where('slug', 'ioga-prenatal')->firstOrFail();

        $this->assertSame(1550, $activity->price_cents);
        $this->assertSame('yoga-prenatal', $activity->slug_es);
        $this->assertSame(['Millora la respiració', "Alleuja l'esquena"], $activity->contentFor('ca')['benefits']);
        $this->assertCount(2, $activity->contentFor('ca')['faq']);
        $this->assertSame('Yoga prenatal', $activity->contentFor('es')['name']);
    }

    public function test_validation_errors_come_back_in_catalan(): void
    {
        $this->actingAs($this->admin)
            ->from(route('admin.activities.create'))
            ->post(route('admin.activities.store'), ['name' => ''])
            ->assertRedirect(route('admin.activities.create'))
            ->assertSessionHasErrors(['name' => 'El camp nom és obligatori.']);
    }

    public function test_money_fields_accept_a_decimal_comma(): void
    {
        $student = User::query()->students()->first();

        $this->actingAs($this->admin)->post(route('admin.payments.store'), [
            'user_id' => $student->id,
            'concept' => 'Classe solta',
            'amount_cents' => '12,50',
            'method' => 'bizum',
            'status' => 'paid',
        ])->assertRedirect(route('admin.payments.index'));

        $this->assertSame(1250, Payment::query()->where('concept', 'Classe solta')->value('amount_cents'));
    }

    public function test_assigning_a_pass_creates_balance_and_payment(): void
    {
        $student = User::factory()->create();
        $pass = Pass::query()->where('name', 'Bo 10 classes')->firstOrFail();

        $this->actingAs($this->admin)->post(route('admin.user-passes.store', $student), [
            'pass_id' => $pass->id,
            'starts_on' => today()->toDateString(),
            'method' => 'cash',
        ])->assertSessionHasNoErrors();

        $userPass = $student->userPasses()->firstOrFail();
        $this->assertSame(10, $userPass->classes_remaining);
        $this->assertTrue($userPass->expires_on->isSameDay(today()->addDays($pass->validity_days)));
        $this->assertSame($pass->price_cents, $student->payments()->value('amount_cents'));
    }

    public function test_admin_books_a_student_and_cancelling_the_class_refunds_the_pass(): void
    {
        $student = User::factory()->create();
        $pass = Pass::query()->where('name', 'Bo 5 classes')->firstOrFail();
        $this->actingAs($this->admin)->post(route('admin.user-passes.store', $student), [
            'pass_id' => $pass->id, 'starts_on' => today()->toDateString(), 'method' => 'none',
        ]);

        $session = ClassSession::query()->scheduled()->where('starts_at', '>', now()->addDay())->whereDoesntHave('bookings')->firstOrFail();
        $session->activity()->update(['is_family' => false]);

        $this->actingAs($this->admin)->post(route('admin.bookings.store', $session), [
            'person' => 'user:'.$student->id,
            'payment' => 'auto',
        ])->assertSessionHas('status');

        $this->assertSame(4, $student->userPasses()->first()->classes_remaining);

        $this->actingAs($this->admin)->post(route('admin.sessions.cancel', $session))->assertSessionHas('status');

        $this->assertSame(5, $student->userPasses()->first()->classes_remaining);
        $this->assertSame(Booking::STATUS_CANCELLED, $student->bookings()->first()->status);
        $this->assertTrue($session->fresh()->isCancelled());
    }

    public function test_generating_classes_does_not_duplicate_them(): void
    {
        $this->actingAs($this->admin)->post(route('admin.templates.generate'));
        $count = ClassSession::query()->count();

        $this->actingAs($this->admin)->post(route('admin.templates.generate'));

        $this->assertSame($count, ClassSession::query()->count());
    }

    public function test_the_last_admin_cannot_be_deleted(): void
    {
        $this->actingAs($this->admin)
            ->delete(route('admin.team.destroy', $this->admin))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    }
}
