<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\VideoResource;
use App\Models\Payment;
use App\Models\Video;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class VideoController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $data = $request->validate([
            'activity_id' => ['nullable', 'integer'],
            'category' => ['nullable', 'string', 'max:100'],
            'paid' => ['nullable', 'boolean'],
        ]);

        $unlocked = $this->unlockedIds($request);

        $videos = Video::query()->published()->with('activity')
            ->when($data['activity_id'] ?? null, fn ($q, $v) => $q->where('activity_id', $v))
            ->when($data['category'] ?? null, fn ($q, $v) => $q->where('category', $v))
            ->when(array_key_exists('paid', $data), fn ($q) => $q->where('is_paid', (bool) $data['paid']))
            ->orderBy('sort_order')->orderByDesc('published_at')
            ->get();

        return VideoResource::collection($videos->map(fn (Video $v) => new VideoResource($v, in_array($v->id, $unlocked, true))));
    }

    public function show(Request $request, int $id): VideoResource
    {
        $video = Video::query()->published()->with('activity')->findOrFail($id);

        return new VideoResource($video, in_array($video->id, $this->unlockedIds($request), true), withPlayback: true);
    }

    /**
     * Inicia la compra d'un vídeo de pagament (cobrament pendent fins que es confirma).
     */
    public function purchase(Request $request, int $id): JsonResponse
    {
        $video = Video::query()->published()->findOrFail($id);

        if (! $video->is_paid || in_array($video->id, $this->unlockedIds($request), true)) {
            return response()->json(['message' => 'Aquest vídeo ja el pots veure.', 'code' => 'already_unlocked'], 409);
        }

        $payment = Payment::create([
            'user_id' => $request->user()->id,
            'amount_cents' => $video->price_cents,
            'currency' => 'EUR',
            'method' => 'stripe',
            'status' => 'pending',
            'concept' => 'Vídeo: '.$video->title,
            'payable_type' => $video->getMorphClass(),
            'payable_id' => $video->id,
        ]);

        return response()->json([
            'data' => ['payment_id' => $payment->id, 'status' => 'pending', 'checkout_url' => null],
        ], 202);
    }

    /**
     * @return array<int, int>
     */
    private function unlockedIds(Request $request): array
    {
        return $request->user()->videoAccesses()->pluck('video_id')->all();
    }
}
