<?php

namespace App\Http\Controllers\Admin;

use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PaymentController extends CrudController
{
    protected string $model = Payment::class;

    protected string $routeName = 'admin.payments';

    protected string $singular = 'Cobrament';

    protected string $plural = 'Cobraments';

    protected string $newLabel = 'Registra un cobrament';

    protected string $icon = 'euro';

    protected ?string $intro = 'Registre de tot el que es cobra: efectiu, Bizum, transferència i, quan es connecti, els pagaments de l\'app.';

    protected string $emptyText = 'Quan assignis un bo o cobris una classe, apareixerà aquí.';

    protected function fields(?Model $model): array
    {
        $students = User::query()->students()->orderBy('name')->pluck('name', 'id')->all();

        return [
            ['name' => 'user_id', 'label' => 'Alumne/a', 'type' => 'select', 'options' => $students, 'rules' => 'required|exists:users,id', 'default' => request('user')],
            ['name' => 'concept', 'label' => 'Concepte', 'rules' => 'required|max:140', 'placeholder' => 'Bo 10 classes'],
            ['name' => 'amount_cents', 'label' => 'Import', 'type' => 'money', 'rules' => 'required'],
            ['name' => 'method', 'label' => 'Forma de pagament', 'type' => 'select', 'options' => Payment::METHODS, 'rules' => 'required', 'default' => 'cash'],
            ['name' => 'status', 'label' => 'Estat', 'type' => 'select', 'options' => Payment::STATUSES, 'rules' => 'required', 'default' => 'paid'],
            ['name' => 'paid_at', 'label' => 'Data del cobrament', 'type' => 'datetime', 'default' => now()],
            ['name' => 'notes', 'label' => 'Notes', 'type' => 'textarea', 'rows' => 3],
        ];
    }

    protected function columns(): array
    {
        return [
            ['label' => 'Data', 'key' => 'paid_at', 'type' => 'datetime', 'route' => 'admin.payments.edit'],
            ['label' => 'Alumne/a', 'key' => 'user.name'],
            ['label' => 'Concepte', 'key' => 'concept'],
            ['label' => 'Import', 'key' => 'amount_cents', 'type' => 'money'],
            ['label' => 'Forma', 'key' => 'method', 'type' => 'badge', 'map' => collect(Payment::METHODS)->map(fn ($l) => [$l, 'muted'])->all()],
            ['label' => 'Estat', 'key' => 'status', 'type' => 'badge', 'map' => collect(Payment::STATUSES)
                ->map(fn ($label, $status) => [$label, Payment::STATUS_TONES[$status]])->all()],
        ];
    }

    protected function searchable(): array
    {
        return ['concept', 'user.name', 'user.email'];
    }

    protected function filters(): array
    {
        return [
            ['name' => 'status', 'label' => 'Estat', 'options' => Payment::STATUSES],
            ['name' => 'method', 'label' => 'Forma', 'options' => Payment::METHODS],
        ];
    }

    protected function with(): array
    {
        return ['user'];
    }

    protected function order(Builder $query): Builder
    {
        return $query->orderByDesc('paid_at')->orderByDesc('id');
    }
}
