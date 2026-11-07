<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

#[Unguarded]
class Post extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'is_published' => 'boolean',
        ];
    }

    /**
     * Articles publicats i amb data de publicació ja arribada.
     */
    public function scopeLive(Builder $query): Builder
    {
        return $query->where('is_published', true)
            ->where(fn (Builder $q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    /**
     * Text de l'article en un idioma ('ca' | 'es'); si falta la traducció, cau al català.
     *
     * @return array{slug: string, title: string, excerpt: ?string, intro: ?string, body: ?string}
     */
    public function contentFor(string $lang): array
    {
        $es = $lang === 'es';
        $pick = fn (string $field) => $es && filled($this->{$field.'_es'}) ? $this->{$field.'_es'} : $this->{$field};

        return [
            'slug' => $pick('slug'),
            'title' => $pick('title'),
            'excerpt' => $pick('excerpt'),
            'intro' => $pick('intro'),
            'body' => $pick('body'),
        ];
    }
}
