<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Club;
use App\Models\ClubMember;
use App\Models\User;
use App\Http\Resources\MemberDetailResource;
use App\Http\Resources\MemberResource;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ClubMemberController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLog,
    ) {
    }

    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ClubMember::class);

        return MemberResource::collection(
            ClubMember::with('user', 'favoriteGenre')->get()
        );
    }

    public function show(ClubMember $member): MemberDetailResource
    {
        $this->authorize('view', $member);

        return new MemberDetailResource(
            $member->load('user', 'favoriteGenre', 'readingProgress', 'proposedCycles', 'meetingRsvps')
        );
    }

    public function store(Request $request): MemberDetailResource
    {
        $this->authorize('create', ClubMember::class);

        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
            'initials' => ['required', 'string', 'max:10'],
            'favorite_genre_id' => ['nullable', 'integer', 'exists:genres,id'],
            'joined_at' => ['required', 'date'],
            'role' => ['required', 'string', 'in:member,admin,developer'],
        ]);

        $member = DB::transaction(function () use ($payload, $request) {
            $user = User::create([
                'name' => $payload['name'],
                'email' => $payload['email'],
                'password' => bcrypt($payload['password']),
            ]);

            $club = Club::first();
            if (! $club) {
                throw ValidationException::withMessages(['club' => ['Клуб не найден.']]);
            }

            $member = ClubMember::create([
                'club_id' => $club->id,
                'user_id' => $user->id,
                'initials' => $payload['initials'],
                'is_active' => true,
                'joined_at' => $payload['joined_at'],
                'favorite_genre_id' => $payload['favorite_genre_id'] ?? null,
            ]);

            $user->assignRole($payload['role']);

            return $member;
        });

        $actor = $request->user();
        if ($actor) {
            $this->auditLog->logMemberCreated($member, $actor);
        }

        return new MemberDetailResource(
            $member->load('user', 'favoriteGenre')
        );
    }

    public function deactivate(Request $request, ClubMember $member): JsonResponse
    {
        $this->authorize('deactivate', $member);

        $actor = $request->user();
        if ($actor && $member->user_id === $actor->id) {
            return response()->json([
                'message' => 'Нельзя деактивировать собственный аккаунт.',
            ], 422);
        }

        $member->update([
            'is_active' => false,
            'deactivated_at' => now(),
        ]);

        if ($actor) {
            $this->auditLog->logMemberDeactivated($member, $actor);
        }

        return response()->json([
            'message' => 'Участник деактивирован.',
        ]);
    }
}
