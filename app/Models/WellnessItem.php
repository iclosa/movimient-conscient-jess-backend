<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Unguarded]
class WellnessItem extends Model
{
    public const TYPES = [
        'mindfulness' => 'Mindfulness',
        'sunrise' => 'Sortida del sol',
        'breathing' => 'Respiració guiada',
        'meditation_timer' => 'Temporitzador de meditació',
        'gratitude' => 'Diari de gratitud',
        'custom' => 'Altres propostes',
    ];

    /**
     * Pràctiques inicials de Mindfulness. S'editen des del panell (camp «config»).
     *
     * @var array<string, array<int, array<string, mixed>>>
     */
    public const DEFAULT_MINDFULNESS = [
        'practices' => [
            [
                'title' => 'Atenció a la respiració',
                'duration_minutes' => 3,
                'steps' => [
                    'Seu còmodament i deixa que les espatlles baixin.',
                    'Porta l\'atenció a l\'aire que entra i surt pel nas, sense canviar-lo.',
                    'Quan la ment marxi, nota-ho i torna suaument a la respiració.',
                    'Abans d\'acabar, obre l\'atenció a tot el cos assegut.',
                ],
            ],
            [
                'title' => 'Escaneig corporal',
                'duration_minutes' => 5,
                'steps' => [
                    'Estira\'t o seu i tanca els ulls si t\'és còmode.',
                    'Porta l\'atenció als peus i nota què hi sents, sense jutjar-ho.',
                    'Puja lentament per les cames, la pelvis i l\'esquena.',
                    'Continua pel ventre, el pit, els braços, el coll i la cara.',
                    'Acaba sentint el cos sencer, respirant.',
                ],
            ],
            [
                'title' => 'Els cinc sentits',
                'duration_minutes' => 5,
                'steps' => [
                    'Anomena en silenci cinc coses que veus.',
                    'Nota quatre coses que pots tocar o sentir al cos.',
                    'Escolta tres sons, els propers i els llunyans.',
                    'Busca dues olors i una sensació al paladar.',
                    'Respira fondo i torna al present.',
                ],
            ],
        ],
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'config' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
