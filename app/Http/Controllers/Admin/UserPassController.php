<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Pass;
use App\Models\Payment;
use App\Models\User;
use App\Models\UserPass;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserPassController extends Controller
{
    /**
     * Assigna un bo a una persona y, si toca, registra el cobrament.
     */
    public function store(Request $request, int|string $userId): RedirectResponse
    {
        $student = User::query()->students()->findOrFail($userId);

        if ($request->filled('amount')) {
            $request->merge(['amount' => str_replace(',', '.', (string) $request->input('amount'))]);
        }

        $data = $request->validate([
            'pass_id' => ['required', 'exists:passes,id'],
            'starts_on' => ['required', 'date'],
            'method' => ['required', 'in:none,cash,bizum,transfer,card,other'],
            'amount' => ['nullable', 'numeric', 'min:0'],
        ], [], ['pass_id' => 'bo', 'starts_on' => 'data d\'inici', 'method' => 'forma de pagament', 'amount' => 'import']);

        $pass = Pass::query()->findOrFail($data['pass_id']);
        $start = Carbon::parse($data['starts_on'])->startOfDay();

        DB::transaction(function () use ($student, $pass, $start, $data) {
            $payment = null;

            if ($data['method'] !== 'none') {
                $payment = Payment::query()->create([
                    'user_id' => $student->id,
                    'amount_cents' => isset($data['amount']) && $data['amount'] !== null
                        ? Money::toCents($data['amount'])
                        : $pass->price_cents,
                    'method' => $data['method'],
                    'status' => 'paid',
                    'concept' => $pass->name,
                    'paid_at' => now(),
                ]);
            }

            UserPass::query()->create([
                'user_id' => $student->id,
                'pass_id' => $pass->id,
                'name' => $pass->name,
                'classes_total' => $pass->classes_count,
                'classes_remaining' => $pass->classes_count,
                'starts_on' => $start,
                'expires_on' => $start->copy()->addDays($pass->validity_days),
                'payment_id' => $payment?->id,
            ]);
        });

        return back()->with('status', "Bo «{$pass->name}» assignat a {$student->name}.");
    }

    /**
     * Corrige saldo o caducidad de un bono ya asignado.
     */
    public function update(Request $request, int|string $id): RedirectResponse
    {
        $userPass = UserPass::query()->findOrFail($id);

        $data = $request->validate([
            'classes_remaining' => ['nullable', 'integer', 'min:0'],
            'expires_on' => ['nullable', 'date'],
        ], [], ['classes_remaining' => 'classes restants', 'expires_on' => 'caducitat']);

        $userPass->update([
            'classes_remaining' => $userPass->isUnlimited() ? null : ($data['classes_remaining'] ?? $userPass->classes_remaining),
            'expires_on' => $data['expires_on'] ?? $userPass->expires_on,
        ]);

        return back()->with('status', 'Bo actualitzat.');
    }

    public function destroy(int|string $id): RedirectResponse
    {
        UserPass::query()->findOrFail($id)->update(['status' => UserPass::STATUS_CANCELLED]);

        return back()->with('status', 'Bo anul·lat. Les reserves ja fetes es mantenen.');
    }
}
