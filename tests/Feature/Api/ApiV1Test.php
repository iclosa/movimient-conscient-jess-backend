<?php

namespace Tests\Feature\Api;

use App\Models\Activity;
use App\Models\Child;
use App\Models\ClassSession;
use App\Models\Event;
use App\Models\Pass;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use App\Models\UserPass;
use App\Models\Video;
use App\Models\VideoAccess;
use Database\Seeders\ActivityContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ApiV1Test extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Setting::set('waitlist_enabled', '1');
        Setting::set('cancellation_hours', '12');
    }

    private function makeSession(int $capacity = 2, bool $family = false): ClassSession
    {
        $activity = Activity::query()->create([
            'name' => 'Hatha '.uniqid(), 'slug' => 'hatha-'.uniqid(), 'is_family' => $family, 'is_active' => true,
        ]);
        $start = now()->addDays(2)->setTime(10, 0);

        return ClassSession::query()->create([
            'activity_id' => $activity->id, 'starts_at' => $start, 'ends_at' => $start->copy()->addHour(),
            'capacity' => $capacity, 'price_cents' => 1400, 'status' => 'scheduled',
        ]);
    }

    private function studentWithPass(int $classes = 5): User
    {
        $user = User::factory()->create();
        UserPass::query()->create([
            'user_id' => $user->id, 'name' => 'Bo', 'classes_total' => $classes, 'classes_remaining' => $classes,
            'starts_on' => today()->subDay(), 'expires_on' => today()->addMonth(), 'status' => 'active',
        ]);

        return $user;
    }

    public function test_register_login_and_logout(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Marta', 'email' => 'marta@exemple.cat', 'password' => 'secret-pass-1',
        ])->assertCreated()->assertJsonStructure(['token', 'user' => ['id', 'email']]);

        $this->postJson('/api/v1/auth/login', ['email' => 'marta@exemple.cat', 'password' => 'wrong'])
            ->assertUnprocessable();

        $token = $this->postJson('/api/v1/auth/login', ['email' => 'marta@exemple.cat', 'password' => 'secret-pass-1'])
            ->assertOk()->json('token');

        $this->withToken($token)->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.email', 'marta@exemple.cat');
        $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();
    }

    public function test_protected_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/home')->assertUnauthorized();
        $this->getJson('/api/v1/settings')->assertOk();
    }

    public function test_profile_update_and_children_are_scoped_to_the_owner(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $theirChild = Child::query()->create(['user_id' => $other->id, 'name' => 'Aina']);

        Sanctum::actingAs($user);

        $this->patchJson('/api/v1/me', ['phone' => '600111222'])->assertOk()->assertJsonPath('data.phone', '600111222');
        $this->postJson('/api/v1/me/children', ['name' => 'Biel'])->assertCreated();
        $this->patchJson("/api/v1/me/children/{$theirChild->id}", ['name' => 'X'])->assertNotFound();
        $this->deleteJson("/api/v1/me/children/{$theirChild->id}")->assertNotFound();
    }

    public function test_book_and_cancel_a_class_refunds_the_pass(): void
    {
        $user = $this->studentWithPass(3);
        $session = $this->makeSession();
        Sanctum::actingAs($user);

        $id = $this->postJson('/api/v1/bookings', ['class_session_id' => $session->id])
            ->assertCreated()->assertJsonPath('data.status', 'booked')->json('data.id');

        $this->assertSame(2, $user->userPasses()->first()->classes_remaining);

        $this->getJson('/api/v1/sessions?from='.today()->toDateString().'&to='.today()->addDays(5)->toDateString())
            ->assertOk()->assertJsonPath('data.0.state', 'booked');

        $this->postJson('/api/v1/bookings', ['class_session_id' => $session->id])
            ->assertStatus(409)->assertJsonPath('code', 'already_booked');

        $this->deleteJson("/api/v1/bookings/{$id}")->assertOk()->assertJsonPath('data.status', 'cancelled');
        $this->assertSame(3, $user->userPasses()->first()->classes_remaining);
    }

    public function test_booking_without_a_pass_is_pending_payment(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/bookings', ['class_session_id' => $this->makeSession()->id])
            ->assertCreated()->assertJsonPath('data.status', 'booked')->assertJsonPath('data.payment_mode', 'pending');
    }

    public function test_full_class_sends_the_person_to_the_waitlist(): void
    {
        $session = $this->makeSession(1);
        $first = $this->studentWithPass();
        $second = $this->studentWithPass();

        Sanctum::actingAs($first);
        $this->postJson('/api/v1/bookings', ['class_session_id' => $session->id])->assertCreated();

        Sanctum::actingAs($second);
        $this->postJson('/api/v1/bookings', ['class_session_id' => $session->id])
            ->assertCreated()->assertJsonPath('data.status', 'waitlist');
        $this->assertSame(5, $second->userPasses()->first()->classes_remaining);
    }

    public function test_user_cannot_cancel_someone_elses_booking(): void
    {
        $owner = $this->studentWithPass();
        $session = $this->makeSession();
        Sanctum::actingAs($owner);
        $id = $this->postJson('/api/v1/bookings', ['class_session_id' => $session->id])->json('data.id');

        Sanctum::actingAs(User::factory()->create());
        $this->deleteJson("/api/v1/bookings/{$id}")->assertNotFound();
    }

    public function test_children_can_only_join_family_classes(): void
    {
        $user = $this->studentWithPass();
        $child = Child::query()->create(['user_id' => $user->id, 'name' => 'Biel']);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/bookings', ['class_session_id' => $this->makeSession()->id, 'child_id' => $child->id])
            ->assertStatus(422)->assertJsonPath('code', 'child_not_allowed');

        $this->postJson('/api/v1/bookings', ['class_session_id' => $this->makeSession(5, true)->id, 'child_id' => $child->id])
            ->assertCreated()->assertJsonPath('data.attendee.type', 'child');
    }

    public function test_rating_requires_a_practised_class(): void
    {
        $user = $this->studentWithPass();
        Sanctum::actingAs($user);
        $id = $this->postJson('/api/v1/bookings', ['class_session_id' => $this->makeSession()->id])->json('data.id');

        $this->postJson("/api/v1/bookings/{$id}/rating", ['rating' => 5])
            ->assertStatus(422)->assertJsonPath('code', 'not_rateable');
        $this->postJson("/api/v1/bookings/{$id}/rating", ['rating' => 9])->assertUnprocessable();
    }

    public function test_home_returns_next_booking_and_passes(): void
    {
        $user = $this->studentWithPass();
        Sanctum::actingAs($user);
        $this->postJson('/api/v1/bookings', ['class_session_id' => $this->makeSession()->id])->assertCreated();

        $this->getJson('/api/v1/home')->assertOk()
            ->assertJsonPath('data.next_booking.status', 'booked')
            ->assertJsonCount(1, 'data.passes')
            ->assertJsonStructure(['data' => ['promotions', 'upcoming_events', 'booking_to_rate', 'streak' => ['weeks']]]);
    }

    public function test_paid_videos_hide_playback_until_unlocked(): void
    {
        $video = Video::query()->create([
            'title' => 'Rutina', 'slug' => 'rutina', 'provider' => 'vimeo', 'video_ref' => 'abc123',
            'is_paid' => true, 'price_cents' => 900, 'is_published' => true,
        ]);
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson("/api/v1/videos/{$video->id}")->assertOk()
            ->assertJsonPath('data.locked', true)->assertJsonMissingPath('data.playback');

        VideoAccess::query()->create(['user_id' => $user->id, 'video_id' => $video->id, 'granted_at' => now()]);

        $this->getJson("/api/v1/videos/{$video->id}")->assertOk()
            ->assertJsonPath('data.locked', false)->assertJsonPath('data.playback.ref', 'abc123');
    }

    public function test_pass_purchase_creates_a_pending_payment(): void
    {
        $pass = Pass::query()->create(['name' => 'Bo 5', 'type' => 'classes', 'classes_count' => 5, 'validity_days' => 60, 'price_cents' => 5500, 'is_active' => true]);
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/passes')->assertOk()->assertJsonCount(1, 'data');
        $this->postJson("/api/v1/passes/{$pass->id}/purchase")->assertStatus(202)->assertJsonPath('data.status', 'pending');
        $this->assertDatabaseHas('payments', ['user_id' => $user->id, 'amount_cents' => 5500, 'status' => 'pending']);
    }

    public function test_event_registration_goes_to_waitlist_when_full(): void
    {
        $event = Event::query()->create([
            'title' => 'Taller', 'slug' => 'taller', 'type' => 'workshop', 'starts_at' => now()->addWeek(),
            'capacity' => 1, 'price_cents' => 0, 'is_published' => true,
        ]);

        Sanctum::actingAs(User::factory()->create());
        $this->postJson("/api/v1/events/{$event->id}/registration")->assertCreated()->assertJsonPath('data.status', 'confirmed');

        Sanctum::actingAs(User::factory()->create());
        $this->postJson("/api/v1/events/{$event->id}/registration")->assertCreated()->assertJsonPath('data.status', 'waitlist');
        $this->postJson("/api/v1/events/{$event->id}/registration")->assertStatus(409);
    }

    public function test_gratitude_diary_is_private_per_user(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $id = $this->postJson('/api/v1/gratitude', ['body' => 'Gràcies pel dia'])->assertCreated()->json('data.id');
        $this->getJson('/api/v1/gratitude')->assertOk()->assertJsonCount(1, 'data');

        Sanctum::actingAs(User::factory()->create());
        $this->getJson('/api/v1/gratitude')->assertOk()->assertJsonCount(0, 'data');
        $this->deleteJson("/api/v1/gratitude/{$id}")->assertNotFound();
    }

    public function test_device_token_registration_is_idempotent(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/me/devices', ['token' => 'fcm-1', 'platform' => 'android'])->assertCreated();
        $this->postJson('/api/v1/me/devices', ['token' => 'fcm-1', 'platform' => 'android'])->assertCreated();
        $this->assertSame(1, $user->deviceTokens()->count());
    }

    public function test_public_endpoints_need_no_login_and_hide_paid_playback(): void
    {
        $session = $this->makeSession();
        Video::query()->create([
            'title' => 'Gratis', 'slug' => 'gratis', 'provider' => 'vimeo', 'video_ref' => 'free1',
            'is_paid' => false, 'is_published' => true,
        ]);
        Video::query()->create([
            'title' => 'Pagament', 'slug' => 'pagament', 'provider' => 'vimeo', 'video_ref' => 'secret',
            'is_paid' => true, 'price_cents' => 900, 'is_published' => true,
        ]);

        $this->getJson('/api/v1/public/sessions?from='.today()->toDateString().'&to='.today()->addDays(5)->toDateString())
            ->assertOk()->assertJsonPath('data.0.id', $session->id)->assertJsonPath('data.0.state', 'available')
            ->assertJsonMissingPath('data.0.my_bookings');

        $this->getJson('/api/v1/public/activities')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/public/passes')->assertOk();
        $this->getJson('/api/v1/public/events')->assertOk();
        $this->getJson('/api/v1/public/promotions')->assertOk();

        $this->getJson('/api/v1/public/videos')->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.playback.ref', 'free1');

        $paid = Video::query()->where('is_paid', true)->first();
        $this->getJson("/api/v1/public/videos/{$paid->id}")->assertNotFound();
    }

    public function test_public_posts_only_list_live_articles_with_both_languages(): void
    {
        Post::query()->create(['title' => 'Visible', 'slug' => 'visible', 'title_es' => 'Visible ES', 'slug_es' => 'visible-es', 'body' => 'Text', 'is_published' => true, 'published_at' => now()->subDay()]);
        Post::query()->create(['title' => 'Esborrany', 'slug' => 'esborrany', 'is_published' => false]);
        Post::query()->create(['title' => 'Programat', 'slug' => 'programat', 'is_published' => true, 'published_at' => now()->addWeek()]);

        $this->getJson('/api/v1/public/posts')->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.translations.ca.slug', 'visible')
            ->assertJsonPath('data.0.translations.es.title', 'Visible ES')
            ->assertJsonPath('data.0.translations.es.body', 'Text');
    }

    public function test_cors_allows_the_public_website_origin(): void
    {
        $this->withHeaders(['Origin' => 'http://localhost:4321'])
            ->getJson('/api/v1/public/activities')
            ->assertOk()->assertHeader('Access-Control-Allow-Origin', 'http://localhost:4321');

        $this->withHeaders(['Origin' => 'https://altra-web.example'])
            ->getJson('/api/v1/public/activities')
            ->assertOk()->assertHeaderMissing('Access-Control-Allow-Origin');
    }

    public function test_public_activities_expose_content_price_and_color_in_both_languages(): void
    {
        $this->seed(ActivityContentSeeder::class);
        $nidra = Activity::query()->where('slug', 'ioga-nidra')->firstOrFail();
        $nidra->update(['price_cents' => 1450, 'color' => '#E4B429', 'name_es' => 'Yoga Nidra', 'h1_es' => null]);

        $response = $this->getJson('/api/v1/public/activities')->assertOk()->assertJsonCount(7, 'data');

        $first = $response->json('data.0');
        $this->assertSame('ioga-nidra', $first['slug']['ca']);
        $this->assertSame('yoga-nidra', $first['slug']['es']);
        $this->assertSame('#E4B429', $first['color']);
        $this->assertSame('#17141C', $first['color_text']);
        $this->assertSame(1450, $first['price_cents']);
        $this->assertSame('14,50 €', $first['price_label']);
        $this->assertCount(4, $first['translations']['ca']['benefits']);
        $this->assertSame('M’adormiré durant la sessió?', $first['translations']['ca']['faq'][0]['q']);
        $this->assertSame('Yoga Nidra', $first['translations']['es']['name']);
        $this->assertCount(3, $first['related']);
        $this->assertNotContains($first['id'], $first['related']);
    }

    public function test_spanish_content_falls_back_to_catalan_when_not_translated(): void
    {
        $activity = Activity::query()->create(['name' => 'Ioga prenatal', 'slug' => 'ioga-prenatal', 'tagline' => 'Per a embarassades', 'is_active' => true, 'benefits' => "Un\nDos"]);

        $es = $activity->contentFor('es');

        $this->assertSame('Ioga prenatal', $es['name']);
        $this->assertSame('Per a embarassades', $es['tagline']);
        $this->assertSame(['Un', 'Dos'], $es['benefits']);
        $this->assertSame('Ioga prenatal', $es['h1']);
    }
}
