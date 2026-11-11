<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

#[Unguarded]
class Announcement extends Model
{
    public const AUDIENCES = [
        'all' => 'Tot l\'alumnat',
        'with_pass' => 'Alumnat amb bo actiu',
        'inactive' => 'Alumnat que fa temps que no ve',
    ];

    public const STATUSES = [
        'draft' => 'Esborrany',
        'scheduled' => 'Programat',
        'sent' => 'Enviat',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    /**
     * Avisos enviats que veu aquesta persona: els generals posteriors a la seva alta
     * i els personals (com la benvinguda). Afegeix `read_at` (null si encara no l'ha llegit).
     *
     * @param  Builder<Announcement>  $query
     * @return Builder<Announcement>
     */
    public function scopeInboxOf(Builder $query, User $user): Builder
    {
        return $query->where('announcements.status', 'sent')
            ->where(function (Builder $q) use ($user) {
                $q->where('announcements.user_id', $user->id)
                    ->orWhere(function (Builder $q) use ($user) {
                        $q->whereNull('announcements.user_id')->where('announcements.sent_at', '>=', $user->created_at);
                    });
            })
            ->leftJoin('announcement_reads as r', function ($join) use ($user) {
                $join->on('r.announcement_id', '=', 'announcements.id')->where('r.user_id', '=', $user->id);
            })
            ->select('announcements.*', 'r.read_at as read_at');
    }

    /**
     * Avís de benvinguda personal, en l'idioma de la persona.
     */
    public static function welcomeFor(User $user): self
    {
        $first = explode(' ', trim($user->name))[0] ?: $user->name;
        $es = $user->locale === 'es';

        return self::query()->create([
            'user_id' => $user->id,
            'title' => $es ? "¡Te damos la bienvenida, {$first}!" : "Et donem la benvinguda, {$first}!",
            'body' => $es
                ? 'Gracias por unirte a Moviment Conscient. Aquí encontrarás tus clases y reservas, tus bonos y los avisos del centro. Cuando quieras, reserva tu primera clase. ¡Nos vemos en la sala!'
                : 'Gràcies per unir-te a Moviment Conscient. Aquí trobaràs les teves classes i reserves, els bons i els avisos del centre. Quan vulguis, reserva la teva primera classe. Ens veiem a la sala!',
            'audience' => 'all',
            'status' => 'sent',
            'sent_at' => now(),
            'recipients_count' => 1,
        ]);
    }
}
