<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ActivityDetailResource;
use App\Http\Resources\V1\ClassSessionResource;
use App\Http\Resources\V1\EventResource;
use App\Http\Resources\V1\PassResource;
use App\Http\Resources\V1\PostResource;
use App\Http\Resources\V1\PromotionResource;
use App\Http\Resources\V1\VideoResource;
use App\Models\Activity;
use App\Models\ClassSession;
use App\Models\Event;
use App\Models\Pass;
use App\Models\Post;
use App\Models\Promotion;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

/**
 * Lectura pública (sense sessió) per a la web. Només dades que ja són públiques:
 * no inclou res de les persones ni la reproducció dels vídeos de pagament.
 */
class PublicController extends Controller
{
    /**
     * Activitats amb tot el contingut públic (català i castellà), preu i color.
     */
    public function activities(): AnonymousResourceCollection
    {
        $all = Activity::query()->active()->orderBy('sort_order')->orderBy('name')->get();

        return ActivityDetailResource::collection($all->each(fn (Activity $a) => $a->setRelation('siblings', $all)));
    }

    /**
     * Horari per rang de dates (per defecte, els pròxims 7 dies; màxim 31).
     */
    public function sessions(Request $request): AnonymousResourceCollection
    {
        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'activity_id' => ['nullable', 'integer'],
        ]);

        $from = isset($data['from']) ? Carbon::parse($data['from'])->startOfDay() : now()->startOfDay();
        $to = isset($data['to']) ? Carbon::parse($data['to'])->endOfDay() : $from->copy()->addDays(6)->endOfDay();
        if ($from->diffInDays($to) > 31) {
            $to = $from->copy()->addDays(31)->endOfDay();
        }

        $sessions = ClassSession::query()
            ->with(['activity', 'instructor', 'room'])
            ->whereBetween('starts_at', [$from, $to])
            ->where('status', 'scheduled')
            ->when($data['activity_id'] ?? null, fn ($q, $id) => $q->where('activity_id', $id))
            ->orderBy('starts_at')
            ->get();

        return ClassSessionResource::collection($sessions);
    }

    public function passes(): AnonymousResourceCollection
    {
        return PassResource::collection(Pass::query()->active()->orderBy('sort_order')->get());
    }

    public function events(): AnonymousResourceCollection
    {
        return EventResource::collection(Event::query()->published()->upcoming()->orderBy('starts_at')->get());
    }

    public function posts(): AnonymousResourceCollection
    {
        return PostResource::collection(Post::query()->live()->orderByDesc('published_at')->orderByDesc('id')->get());
    }

    public function promotions(): AnonymousResourceCollection
    {
        return PromotionResource::collection(Promotion::query()->current()->orderBy('sort_order')->get());
    }

    /**
     * Només vídeos gratuïts, amb reproducció. Els de pagament no es publiquen a la web.
     */
    public function videos(Request $request): AnonymousResourceCollection
    {
        $data = $request->validate([
            'activity_id' => ['nullable', 'integer'],
            'category' => ['nullable', 'string', 'max:100'],
        ]);

        $videos = Video::query()->published()->where('is_paid', false)->with('activity')
            ->when($data['activity_id'] ?? null, fn ($q, $v) => $q->where('activity_id', $v))
            ->when($data['category'] ?? null, fn ($q, $v) => $q->where('category', $v))
            ->orderBy('sort_order')->orderByDesc('published_at')
            ->get();

        return VideoResource::collection($videos->map(fn (Video $v) => new VideoResource($v, true, true)));
    }

    public function video(int $id): VideoResource
    {
        $video = Video::query()->published()->where('is_paid', false)->with('activity')->findOrFail($id);

        return new VideoResource($video, true, true);
    }
}
