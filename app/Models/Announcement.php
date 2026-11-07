<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
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
}
