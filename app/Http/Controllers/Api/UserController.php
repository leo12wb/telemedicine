<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\User\StoreUserRequest;
use App\Http\Requests\Api\User\UpdateUserRequest;
use App\Http\Resources\Api\UserResource;
use App\Models\User;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class UserController extends Controller
{
    public function __construct(private readonly UserService $userService) {}

    /**
     * Lista todos os usuários (admin only).
     * GET /api/v1/users
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', User::class);

        $users = $this->userService->list($request->only('role', 'is_active', 'search'));

        return UserResource::collection($users);
    }

    /**
     * Cria um novo usuário (admin only).
     * POST /api/v1/users
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->userService->create($request->validated());

        return (new UserResource($user))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Exibe um usuário específico (admin ou o próprio usuário).
     * GET /api/v1/users/{user}
     */
    public function show(User $user): UserResource
    {
        $this->authorize('view', $user);

        return new UserResource($user);
    }

    /**
     * Atualiza dados de um usuário (admin ou o próprio usuário).
     * PUT /api/v1/users/{user}
     */
    public function update(UpdateUserRequest $request, User $user): UserResource
    {
        $updated = $this->userService->update($user, $request->validated());

        return new UserResource($updated);
    }

    /**
     * Alterna o status ativo/inativo (admin only).
     * PATCH /api/v1/users/{user}/toggle-active
     */
    public function toggleActive(User $user): UserResource
    {
        $this->authorize('toggleActive', User::class);

        $updated = $this->userService->toggleActive($user);

        return new UserResource($updated);
    }

    /**
     * Remove o usuário (soft delete, admin only).
     * DELETE /api/v1/users/{user}
     */
    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);

        $this->userService->delete($user);

        return response()->json(['message' => 'Usuário removido com sucesso.']);
    }
}
