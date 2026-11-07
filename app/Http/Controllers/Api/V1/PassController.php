<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\PassResource;
use App\Http\Resources\V1\UserPassResource;
use App\Models\Pass;
use App\Models\Payment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PassController extends Controller
{
    public function catalogue(): AnonymousResourceCollection
    {
        return PassResource::collection(Pass::query()->active()->orderBy('sort_order')->get());
    }

    public function mine(Request $request): AnonymousResourceCollection
    {
        return UserPassResource::collection($request->user()->userPasses()->latest('starts_on')->get());
    }

    /**
     * Inicia la compra d'un bo: crea un cobrament pendent (Stripe encara no està integrat).
     * El bo s'activa quan l'equip registra el cobrament des del panell.
     */
    public function purchase(Request $request, int $id): JsonResponse
    {
        $pass = Pass::query()->active()->findOrFail($id);

        $payment = Payment::create([
            'user_id' => $request->user()->id,
            'amount_cents' => $pass->price_cents,
            'currency' => 'EUR',
            'method' => 'stripe',
            'status' => 'pending',
            'concept' => 'Bo: '.$pass->name,
            'payable_type' => $pass->getMorphClass(),
            'payable_id' => $pass->id,
        ]);

        return response()->json([
            'data' => [
                'payment_id' => $payment->id,
                'status' => $payment->status,
                'amount_cents' => $payment->amount_cents,
                'checkout_url' => null,
                'message' => 'Sol·licitud registrada. T\'avisarem quan el bo estigui actiu.',
            ],
        ], 202);
    }
}
