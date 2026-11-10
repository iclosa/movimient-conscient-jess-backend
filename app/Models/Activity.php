<?php

namespace App\Models;

use App\Support\Color;
use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

#[Unguarded]
class Activity extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_family' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(ClassSession::class);
    }

    public function scheduleTemplates(): HasMany
    {
        return $this->hasMany(ScheduleTemplate::class);
    }

    public function videos(): HasMany
    {
        return $this->hasMany(Video::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? Storage::disk(config('filesystems.media_disk'))->url($this->image_path) : null;
    }

    public function getPriceLabelAttribute(): ?string
    {
        return $this->price_cents ? Money::format($this->price_cents) : null;
    }

    public function getColorTextAttribute(): string
    {
        return Color::readableText($this->color);
    }

    /**
     * Contingut en un idioma. El castellà recupera el català en els camps que encara no s'han traduït.
     *
     * @return array{name: string, tagline: ?string, description: ?string, level: ?string, h1: string, meta_title: string, meta_description: ?string, for_who: ?string, expect: ?string, benefits: list<string>, faq: list<array{q: string, a: string}>}
     */
    public function contentFor(string $locale): array
    {
        $pick = fn (string $field) => $locale === 'es'
            ? (filled($this->{$field.'_es'}) ? $this->{$field.'_es'} : $this->{$field})
            : $this->{$field};

        $name = $pick('name');

        return [
            'name' => $name,
            'tagline' => $pick('tagline'),
            'description' => $pick('description'),
            'level' => $pick('level'),
            'h1' => $pick('h1') ?: $name,
            'meta_title' => $pick('meta_title') ?: $name,
            'meta_description' => $pick('meta_description') ?: $pick('tagline'),
            'for_who' => $pick('for_who'),
            'expect' => $pick('expect'),
            'benefits' => self::parseList($pick('benefits')),
            'faq' => self::parseFaq($pick('faq')),
        ];
    }

    /**
     * Una línia per element.
     *
     * @return list<string>
     */
    public static function parseList(?string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $text) ?: [])));
    }

    /**
     * Preguntes freqüents: blocs separats per una línia en blanc; la primera línia és la pregunta i la resta la resposta.
     *
     * @return list<array{q: string, a: string}>
     */
    public static function parseFaq(?string $text): array
    {
        $items = [];

        foreach (preg_split('/\R\s*\R/', trim((string) $text)) ?: [] as $block) {
            $lines = preg_split('/\R/', trim($block)) ?: [];
            $question = trim((string) array_shift($lines));
            $answer = trim(implode(' ', array_map('trim', $lines)));

            if ($question !== '' && $answer !== '') {
                $items[] = ['q' => $question, 'a' => $answer];
            }
        }

        return $items;
    }

    /**
     * Activitats relacionades: les 3 següents (visibles) en l'ordre del panell.
     *
     * @param  Collection<int, Activity>  $all
     * @return list<int>
     */
    public function relatedIds($all): array
    {
        $ids = $all->pluck('id')->values();
        $position = $ids->search($this->id);

        if ($position === false) {
            return [];
        }

        return $ids->count() <= 1 ? [] : collect(range(1, min(3, $ids->count() - 1)))
            ->map(fn (int $step) => $ids[($position + $step) % $ids->count()])
            ->all();
    }
}
