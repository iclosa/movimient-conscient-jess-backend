<?php

namespace App\Http\Controllers\Admin;

use App\Models\Announcement;
use App\Services\PushNotifier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AnnouncementController extends CrudController
{
    protected string $model = Announcement::class;

    protected string $routeName = 'admin.announcements';

    protected string $singular = 'Avís';

    protected string $plural = 'Avisos a l\'app';

    protected string $newLabel = 'Nou avís';

    protected string $icon = 'megaphone';

    protected ?string $intro = 'Missatges per a l\'alumnat: promocions, canvis d\'horari, tallers. Surten a la safata d\'avisos de l\'app.';

    protected string $emptyText = 'Escriu el primer avís per al teu alumnat.';

    protected function fields(?Model $model): array
    {
        return [
            ['name' => 'title', 'label' => 'Títol', 'rules' => 'required|max:100', 'full' => true],
            ['name' => 'body', 'label' => 'Missatge', 'type' => 'textarea', 'rows' => 4, 'rules' => 'required|max:500', 'hint' => 'Màxim 500 caràcters. Les notificacions curtes funcionen millor.'],
            ['name' => 'audience', 'label' => 'A qui va dirigit', 'type' => 'select', 'options' => Announcement::AUDIENCES, 'rules' => 'required', 'default' => 'all'],
        ];
    }

    protected function columns(): array
    {
        return [
            ['label' => 'Avís', 'key' => 'title', 'link' => true, 'sub' => 'body'],
            ['label' => 'Destinataris', 'key' => 'audience', 'type' => 'badge', 'map' => collect(Announcement::AUDIENCES)->map(fn ($l) => [$l, 'primary'])->all()],
            ['label' => 'Estat', 'key' => 'status', 'type' => 'badge', 'map' => [
                'draft' => ['Esborrany', 'muted'],
                'scheduled' => ['Programat', 'warning'],
                'sent' => ['Enviat', 'success'],
            ]],
            ['label' => 'Enviat', 'key' => 'sent_at', 'type' => 'datetime'],
            ['label' => 'Persones', 'key' => 'recipients_count'],
        ];
    }

    public function rowActions(Model $row): array
    {
        if ($row->status === 'sent') {
            return [];
        }

        return [[
            'label' => 'Envia ara',
            'url' => route('admin.announcements.send', $row),
            'method' => 'post',
            'icon' => 'megaphone',
            'confirm' => 'Vols enviar aquest avís ara a tot el públic seleccionat?',
        ]];
    }

    protected function beforeSave(array $data, ?Model $model, Request $request): array
    {
        if (! $model) {
            $data['status'] = 'draft';
        }

        return $data;
    }

    protected function deleteBlocker(Model $model): ?string
    {
        return null;
    }

    public function send(int|string $id, PushNotifier $notifier): RedirectResponse
    {
        $announcement = Announcement::query()->findOrFail($id);

        if ($announcement->status === 'sent') {
            return back()->with('error', 'Aquest avís ja s\'ha enviat.');
        }

        $count = $notifier->send($announcement);

        return back()->with('status', "Avís enviat a {$count} persones. Ja es veu a la safata d'avisos de l'app.");
    }
}
