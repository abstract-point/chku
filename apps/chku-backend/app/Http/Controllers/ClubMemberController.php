<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Club;
use App\Models\ClubMember;
use App\Models\User;
use App\Http\Resources\MemberDetailResource;
use App\Http\Resources\MemberResource;
use App\Services\AuditLogService;
use App\Services\UserAvatarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
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
            ClubMember::with('user', 'favoriteGenre')
                ->orderByDesc('is_active')
                ->orderBy(User::select('name')->whereColumn('users.id', 'club_members.user_id'))
                ->get()
        );
    }

    public function show(ClubMember $member): MemberDetailResource
    {
        $this->authorize('view', $member);

        return new MemberDetailResource(
            $member->load('user', 'favoriteGenre', 'readingProgress', 'proposedCycles', 'meetingRsvps')
        );
    }

    public function avatar(ClubMember $member): mixed
    {
        $this->authorize('view', $member);

        $path = $member->loadMissing('user')->user?->avatar_path;

        if (! $path || ! Storage::disk('local')->exists($path)) {
            abort(404);
        }

        return Storage::disk('local')->response($path, null, [
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    public function store(Request $request, UserAvatarService $avatars): MemberDetailResource
    {
        $this->authorize('create', ClubMember::class);

        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'favorite_genre_id' => ['nullable', 'integer', 'exists:genres,id'],
            'joined_at' => ['required', 'date'],
            'role' => ['required', 'string', 'in:member,admin,developer'],
        ]);

        $member = DB::transaction(function () use ($payload, $avatars) {
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
                'is_active' => true,
                'joined_at' => $payload['joined_at'],
                'favorite_genre_id' => $payload['favorite_genre_id'] ?? null,
            ]);

            if (isset($payload['avatar'])) {
                $user->forceFill([
                    'avatar_path' => $avatars->store($user, $payload['avatar']),
                ])->save();
            }

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
