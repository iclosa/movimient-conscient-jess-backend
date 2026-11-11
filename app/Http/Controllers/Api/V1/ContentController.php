<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\AnnouncementResource;
use App\Http\Resources\V1\EventResource;
use App\Http\Resources\V1\GratitudeEntryResource;
use App\Http\Resources\V1\PromotionResource;
use App\Http\Resources\V1\WellnessItemResource;
use App\Models\Announcement;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\Promotion;
use App\Models\Setting;
use App\Models\WellnessItem;
use App\Services\EventRegistrationService;
use App\Services\SunTimes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ContentController extends Controller
{
    public function settings(): JsonResponse
    {
        $keys = ['center_name', 'center_address', 'center_phone', 'center_email', 'center_instagram', 'center_website',
            'cancellation_hours', 'booking_open_days', 'waitlist_enabled', 'welcome_message', 'terms_url'];

        return response()->json(['data' => collect($keys)->mapWithKeys(fn ($k) => [$k => Setting::get($k)])]);
    }

    public function promotions(): AnonymousResourceCollection
    {
        return PromotionResource::collection(Promotion::query()->current()->orderBy('sort_order')->get());
    }

    public function events(Request $request): AnonymousResourceCollection
    {
        $events = Event::query()->published()->upcoming()
            ->with(['registrations' => fn ($q) => $q->where('user_id', $request->user()->id)->where('status', '!=', 'cancelled')])
            ->orderBy('starts_at')->get();

        return EventResource::collection($events);
    }

    public function registerForEvent(Request $request, int $id, EventRegistrationService $registrations): JsonResponse
    {
        $event = Event::query()->published()->upcoming()->findOrFail($id);
        $user = $request->user();

        $existing = EventRegistration::where('event_id', $event->id)->where('user_id', $user->id)->first();
        if ($existing && $existing->status !== 'cancelled') {
            return response()->json(['message' => 'Ja estàs inscrit/a a aquest esdeveniment.', 'code' => 'already_registered'], 409);
        }

        $registration = $registrations->register($event, $user);

        return response()->json(['data' => ['status' => $registration->status]], 201);
    }

    public function cancelEventRegistration(Request $request, int $id, EventRegistrationService $registrations): JsonResponse
    {
        $registration = EventRegistration::where('event_id', $id)
            ->where('user_id', $request->user()->id)->where('status', '!=', 'cancelled')->firstOrFail();

        $registrations->cancel($registration);

        return response()->json(['message' => 'Inscripció cancel·lada.']);
    }

    public function wellness(): AnonymousResourceCollection
    {
        return WellnessItemResource::collection(WellnessItem::query()->active()->orderBy('sort_order')->get());
    }

    /**
     * Hores del sol al centre. `date` (per defecte avui) i `days` (1-7, per defecte 1).
     */
    public function sunrise(Request $request, SunTimes $sun): JsonResponse
    {
        $data = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'days' => ['nullable', 'integer', 'between:1,7'],
        ]);

        return response()->json(['data' => $sun->forCenter($data['date'] ?? null, (int) ($data['days'] ?? 1))]);
    }

    public function announcements(Request $request): AnonymousResourceCollection
    {
        $announcements = Announcement::query()->where('status', 'sent')
            ->where('sent_at', '>=', $request->user()->created_at)
            ->latest('sent_at')->limit(50)->get();

        return AnnouncementResource::collection($announcements);
    }

    public function gratitudeIndex(Request $request): AnonymousResourceCollection
    {
        return GratitudeEntryResource::collection(
            $request->user()->gratitudeEntries()->orderByDesc('entry_date')->orderByDesc('id')->paginate(30)
        );
    }

    public function gratitudeStore(Request $request): JsonResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
            'entry_date' => ['nullable', 'date', 'before_or_equal:today'],
        ]);

        $entry = $request->user()->gratitudeEntries()->create([
            'body' => $data['body'],
            'entry_date' => $data['entry_date'] ?? now()->toDateString(),
        ]);

        return (new GratitudeEntryResource($entry))->response()->setStatusCode(201);
    }

    public function gratitudeUpdate(Request $request, int $id): GratitudeEntryResource
    {
        $entry = $request->user()->gratitudeEntries()->findOrFail($id);
        $entry->update($request->validate(['body' => ['required', 'string', 'max:2000']]));

        return new GratitudeEntryResource($entry);
    }

    public function gratitudeDestroy(Request $request, int $id): JsonResponse
    {
        $request->user()->gratitudeEntries()->findOrFail($id)->delete();

        return response()->json(['message' => 'Entrada eliminada.']);
    }
}
