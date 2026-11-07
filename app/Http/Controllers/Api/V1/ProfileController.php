<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ChildResource;
use App\Http\Resources\V1\UserResource;
use App\Models\Child;
use App\Services\PracticeStreak;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function show(Request $request): UserResource
    {
        return new UserResource($request->user()->load('children'));
    }

    public function update(Request $request): UserResource
    {
        $user = $request->user();

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'locale' => ['sometimes', 'in:ca,es'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'marketing_consent' => ['sometimes', 'boolean'],
        ]);

        $user->update($data);

        return new UserResource($user->load('children'));
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', Password::min(8), 'confirmed'],
        ]);

        if (! Hash::check($data['current_password'], $request->user()->password)) {
            return response()->json(['message' => 'La contrasenya actual no és correcta.', 'errors' => ['current_password' => ['La contrasenya actual no és correcta.']]], 422);
        }

        $request->user()->update(['password' => $data['password']]);

        return response()->json(['message' => 'Contrasenya actualitzada.']);
    }

    public function destroy(Request $request): JsonResponse
    {
        $request->validate(['password' => ['required', 'string']]);

        if (! Hash::check($request->input('password'), $request->user()->password)) {
            return response()->json(['message' => 'La contrasenya no és correcta.'], 422);
        }

        $user = $request->user();
        $user->tokens()->delete();
        $user->update(['is_active' => false, 'marketing_consent' => false]);

        return response()->json(['message' => 'Compte desactivat.']);
    }

    public function streak(Request $request, PracticeStreak $streak): JsonResponse
    {
        return response()->json(['data' => $streak->for($request->user())]);
    }

    public function storeChild(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date', 'before:today'],
        ]);

        return (new ChildResource($request->user()->children()->create($data)))
            ->response()->setStatusCode(201);
    }

    public function updateChild(Request $request, Child $child): ChildResource
    {
        abort_unless($child->user_id === $request->user()->id, 404);

        $child->update($request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date', 'before:today'],
        ]));

        return new ChildResource($child);
    }

    public function destroyChild(Request $request, Child $child): JsonResponse
    {
        abort_unless($child->user_id === $request->user()->id, 404);

        $child->delete();

        return response()->json(['message' => 'Perfil eliminat.']);
    }

    public function storeDeviceToken(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:500'],
            'platform' => ['required', 'in:android,ios'],
        ]);

        $request->user()->deviceTokens()->updateOrCreate(
            ['token' => $data['token']],
            ['platform' => $data['platform'], 'last_used_at' => now()],
        );

        return response()->json(['message' => 'Dispositiu registrat.'], 201);
    }

    public function destroyDeviceToken(Request $request): JsonResponse
    {
        $request->validate(['token' => ['required', 'string']]);

        $request->user()->deviceTokens()->where('token', $request->input('token'))->delete();

        return response()->json(['message' => 'Dispositiu eliminat.']);
    }
}
