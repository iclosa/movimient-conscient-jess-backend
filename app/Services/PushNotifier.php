<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

/**
 * Punto único de envío de notificaciones push.
 *
 * Todavía no hay credenciales de Firebase: `deliver()` solo registra el envío en el log.
 * Cuando se conecte FCM, basta con implementar ese método.
 */
class PushNotifier
{
    /**
     * Personas que recibirán un aviso según su audiencia.
     */
    public function recipients(Announcement $announcement): Builder
    {
        $query = User::query()->students()->where('is_active', true);

        return match ($announcement->audience) {
            'with_pass' => $query->whereHas('userPasses', fn (Builder $q) => $q->usable()),
            'inactive' => $query->whereDoesntHave('bookings', fn (Builder $q) => $q->where('created_at', '>=', now()->subDays(45))),
            default => $query,
        };
    }

    /**
     * Marca el aviso como enviado y entrega los push a los dispositivos registrados.
     *
     * @return int Personas alcanzadas.
     */
    public function send(Announcement $announcement): int
    {
        $users = $this->recipients($announcement)->pluck('id');

        $tokens = DeviceToken::query()->whereIn('user_id', $users)->pluck('token')->all();
        $this->deliver($tokens, $announcement->title, $announcement->body);

        $announcement->update([
            'status' => 'sent',
            'sent_at' => now(),
            'recipients_count' => $users->count(),
        ]);

        return $users->count();
    }

    /**
     * @param  array<int, string>  $tokens
     */
    protected function deliver(array $tokens, string $title, string $body): void
    {
        // TODO: integrar Firebase Cloud Messaging.
        Log::info('Push (pendiente de integrar FCM)', ['dispositivos' => count($tokens), 'titulo' => $title]);
    }
}
