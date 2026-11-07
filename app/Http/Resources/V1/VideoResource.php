<?php

namespace App\Http\Resources\V1;

use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Els vídeos de pagament només exposen `playback` si la persona hi té accés.
 * L'accés s'indica des del controlador amb additional(['unlocked' => bool]) per vídeo.
 *
 * @mixin Video
 */
class VideoResource extends JsonResource
{
    public function __construct($resource, private bool $unlocked = false, private bool $withPlayback = false)
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $unlocked = ! $this->is_paid || $this->unlocked;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'category' => $this->category,
            'level' => $this->level,
            'duration_minutes' => $this->duration_minutes,
            'published_at' => $this->published_at?->toIso8601String(),
            'thumbnail_url' => $this->thumbnail_url ?: $this->autoThumbnail(),
            'is_paid' => $this->is_paid,
            'price_cents' => $this->is_paid ? $this->price_cents : 0,
            'price_label' => $this->price_label,
            'locked' => ! $unlocked,
            'activity' => $this->whenLoaded('activity', fn () => $this->activity ? [
                'id' => $this->activity->id,
                'slug' => $this->activity->slug,
                'name' => $this->activity->name,
                'color' => $this->activity->color,
            ] : null),
            'playback' => $this->when($this->withPlayback && $unlocked, fn () => [
                'provider' => $this->provider,
                'ref' => $this->video_ref,
            ]),
        ];
    }

    /**
     * Miniatura automàtica quan no n'hi ha cap de pujada: YouTube la publica a una URL
     * coneguda i Vimeo la dona via oEmbed (es guarda a la memòria cau un dia).
     */
    private function autoThumbnail(): ?string
    {
        $ref = trim((string) $this->video_ref);

        if ($ref === '' || preg_match('/^0+$/', $ref)) {
            return null;
        }

        $provider = preg_match('~youtu\.be/|youtube\.com/~i', $ref) ? 'youtube' : (preg_match('~vimeo\.com/~i', $ref) ? 'vimeo' : $this->provider);

        if ($provider === 'youtube') {
            $id = preg_match('~(?:youtu\.be/|v=|embed/|shorts/)([\w-]{11})~', $ref, $m) ? $m[1] : (preg_match('/^[\w-]{11}$/', $ref) ? $ref : null);

            return $id ? "https://i.ytimg.com/vi/{$id}/hqdefault.jpg" : null;
        }

        if ($provider === 'vimeo') {
            $url = str_starts_with($ref, 'http') ? $ref : "https://vimeo.com/{$ref}";

            return Cache::remember('video.thumb.'.md5($url), now()->addDay(), function () use ($url) {
                try {
                    $response = Http::timeout(3)->get('https://vimeo.com/api/oembed.json', ['url' => $url, 'width' => 640]);

                    return $response->successful() ? ($response->json('thumbnail_url') ?: null) : null;
                } catch (\Throwable) {
                    return null;
                }
            });
        }

        return null;
    }
}
