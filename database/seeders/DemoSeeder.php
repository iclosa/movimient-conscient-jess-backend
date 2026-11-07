<?php

namespace Database\Seeders;

use App\Exceptions\BookingException;
use App\Models\Activity;
use App\Models\Booking;
use App\Models\Child;
use App\Models\ClassSession;
use App\Models\Event;
use App\Models\Instructor;
use App\Models\Pass;
use App\Models\Payment;
use App\Models\Promotion;
use App\Models\Room;
use App\Models\ScheduleTemplate;
use App\Models\User;
use App\Models\UserPass;
use App\Models\Video;
use App\Services\BookingService;
use App\Services\SessionGenerator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

/**
 * Dades de mostra per veure el panell ple. No s'ha d'executar en producció.
 */
class DemoSeeder extends Seeder
{
    public function run(BookingService $bookings, SessionGenerator $generator): void
    {
        $room = Room::query()->first();
        $jess = Instructor::query()->first();
        $activities = Activity::query()->pluck('id', 'slug');

        // Preus d'exemple de la classe solta (es canvien des del panell).
        Activity::query()->whereNull('price_cents')->orWhere('price_cents', 0)->update(['price_cents' => 1400]);
        Activity::query()->where('slug', 'acroioga')->update(['price_cents' => 1600]);
        Activity::query()->where('slug', 'ioga-familiar')->update(['price_cents' => 1200]);

        $this->schedule($activities, $room, $jess);
        $generator->generate(now()->subWeeks(2), now()->addWeeks(4));

        $students = $this->students();
        $this->passesAndPayments($students);
        $this->bookings($bookings, $students);
        $this->content($activities);
    }

    /**
     * @param  Collection<string, int>  $activities
     */
    private function schedule($activities, Room $room, Instructor $jess): void
    {
        // [weekday, hora, activitat, durada, preu classe solta (cèntims)]
        $weekly = [
            [1, '09:30', 'hatha-ioga', 60, 1400],
            [1, '19:00', 'vinyasa-ioga', 60, 1400],
            [2, '10:00', 'ioga-nidra', 60, 1400],
            [2, '19:30', 'exercici-funcional', 55, 1400],
            [3, '19:30', 'ioga-hit', 55, 1400],
            [4, '09:30', 'hatha-ioga', 60, 1400],
            [4, '18:30', 'acroioga', 75, 1600],
            [5, '10:00', 'vinyasa-ioga', 60, 1400],
            [6, '10:30', 'ioga-familiar', 60, 1200],
        ];

        foreach ($weekly as [$weekday, $time, $slug, $minutes, $price]) {
            ScheduleTemplate::query()->firstOrCreate(
                ['weekday' => $weekday, 'start_time' => $time, 'activity_id' => $activities[$slug]],
                [
                    'room_id' => $room->id,
                    'instructor_id' => $jess->id,
                    'duration_minutes' => $minutes,
                    'price_cents' => $price,
                ],
            );
        }
    }

    /**
     * @return Collection<int, User>
     */
    private function students()
    {
        $names = [
            'Marta Puig', 'Anna Ferrer', 'Laia Soler', 'Jordi Vidal',
            'Núria Camps', 'Pau Roca', 'Sílvia Mas', 'Albert Costa',
        ];

        $students = collect($names)->map(function (string $name, int $i) {
            return User::query()->firstOrCreate(
                ['email' => 'alumne'.($i + 1).'@exemple.cat'],
                [
                    'name' => $name,
                    'password' => 'password',
                    'phone' => '6'.fake()->numerify('## ## ## ##'),
                    'marketing_consent' => $i % 2 === 0,
                ],
            );
        });

        $family = $students->first();
        Child::query()->firstOrCreate(['user_id' => $family->id, 'name' => 'Biel'], ['birth_date' => now()->subYears(6)]);
        Child::query()->firstOrCreate(['user_id' => $family->id, 'name' => 'Aina'], ['birth_date' => now()->subYears(4)]);

        return $students;
    }

    /**
     * @param  Collection<int, User>  $students
     */
    private function passesAndPayments($students): void
    {
        $pack10 = Pass::query()->where('name', 'Bo 10 classes')->first();
        $pack5 = Pass::query()->where('name', 'Bo 5 classes')->first();
        $monthly = Pass::query()->where('name', 'Subscripció mensual')->first();

        foreach ($students->take(6) as $i => $student) {
            if ($student->userPasses()->exists()) {
                continue;
            }

            $pass = match (true) {
                $i === 4 => $monthly,
                $i % 2 === 0 => $pack10,
                default => $pack5,
            };

            $payment = Payment::query()->create([
                'user_id' => $student->id,
                'amount_cents' => $pass->price_cents,
                'method' => ['cash', 'bizum', 'card'][$i % 3],
                'status' => 'paid',
                'concept' => $pass->name,
                'paid_at' => now()->subDays(10 - $i),
            ]);

            UserPass::query()->create([
                'user_id' => $student->id,
                'pass_id' => $pass->id,
                'name' => $pass->name,
                'classes_total' => $pass->classes_count,
                'classes_remaining' => $pass->classes_count,
                'starts_on' => today()->subDays(10 - $i),
                'expires_on' => today()->subDays(10 - $i)->addDays($pass->validity_days),
                'payment_id' => $payment->id,
            ]);
        }
    }

    /**
     * @param  Collection<int, User>  $students
     */
    private function bookings(BookingService $service, $students): void
    {
        $sessions = ClassSession::query()->orderBy('starts_at')->get();

        foreach ($sessions as $session) {
            // Entre 2 i 7 persones per classe, repartides de manera estable.
            $count = 2 + ($session->id % 6);

            foreach ($students->shuffle()->take($count) as $student) {
                try {
                    $child = $session->activity->is_family && $student->children()->exists()
                        ? $student->children()->first()
                        : null;

                    $service->book($student, $session, $child, 'admin');
                } catch (BookingException) {
                    // Sense bo o duplicat: es descarta sense soroll.
                }
            }

            if ($session->isPast()) {
                Booking::query()
                    ->where('class_session_id', $session->id)
                    ->where('status', Booking::STATUS_BOOKED)
                    ->get()
                    ->each(function (Booking $booking) {
                        $booking->update([
                            'status' => Booking::STATUS_ATTENDED,
                            'attended_at' => $booking->classSession->starts_at,
                            'rating' => fake()->boolean(70) ? fake()->numberBetween(4, 5) : null,
                            'rated_at' => now(),
                        ]);
                    });
            }
        }
    }

    /**
     * @param  Collection<string, int>  $activities
     */
    private function content($activities): void
    {
        $videos = [
            ['Respiració per començar el dia', 'respiracio', null, 12, false],
            ['Hatha suau per a l\'esquena', 'classe', 'hatha-ioga', 35, false],
            ['Vinyasa en 20 minuts', 'rutina', 'vinyasa-ioga', 20, true],
            ['Yoga Nidra per dormir millor', 'meditacio', 'ioga-nidra', 25, true],
        ];

        foreach ($videos as $i => [$title, $category, $slug, $minutes, $paid]) {
            Video::query()->firstOrCreate(
                ['slug' => str($title)->slug()->value()],
                [
                    'title' => $title,
                    'description' => 'Vídeo de mostra. Substitueix l\'identificador pel del teu Vimeo o Bunny.',
                    'activity_id' => $slug ? $activities[$slug] : null,
                    'category' => $category,
                    'provider' => 'vimeo',
                    'video_ref' => '000000000',
                    'duration_minutes' => $minutes,
                    'is_paid' => $paid,
                    'price_cents' => $paid ? 800 : 0,
                    'is_published' => true,
                    'published_at' => now()->subDays(5),
                    'sort_order' => $i + 1,
                ],
            );
        }

        Promotion::query()->firstOrCreate(
            ['title' => 'Primera classe de prova gratuïta'],
            [
                'body' => 'Vine a conèixer-nos i prova una classe sense compromís.',
                'type' => 'promo',
                'cta_label' => 'Reserva ara',
                'color' => '#2BB5AE',
                'ends_at' => now()->addMonth(),
            ],
        );

        Promotion::query()->firstOrCreate(
            ['title' => 'Taller d\'educació postural'],
            [
                'body' => 'Dolor d\'esquena? Un dissabte per entendre el teu cos.',
                'type' => 'taller',
                'cta_label' => 'Més informació',
                'color' => '#7C5CBF',
            ],
        );

        Event::query()->firstOrCreate(
            ['slug' => 'taller-educacio-postural'],
            [
                'title' => 'Taller d\'educació postural',
                'type' => 'taller',
                'description' => 'Taller pràctic de tres hores. Places limitades.',
                'location' => 'Moviment Conscient, Vallirana',
                'starts_at' => now()->next('Saturday')->setTime(10, 0),
                'ends_at' => now()->next('Saturday')->setTime(13, 0),
                'capacity' => 14,
                'price_cents' => 3500,
                'is_published' => true,
            ],
        );
    }
}
