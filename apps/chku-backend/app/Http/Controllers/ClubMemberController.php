<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Events\MemberDeactivated;
use App\Events\MemberJoinedClub;
use App\Models\Club;
use App\Models\ClubMember;
use App\Models\User;
use App\Http\Resources\MemberDetailResource;
use App\Http\Resources\MemberResource;
use App\Services\AuditLogService;
use App\Services\TurnOrderService;
use App\Services\UserAvatarService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Enums\ReadingCycleStatusEnum;
use App\Enums\ReadingProgressStatusEnum;
use App\Models\ReadingCycle;
use App\Models\ReadingProgress;
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
            ClubMember::with('user', 'favoriteGenres')
                ->orderByDesc('is_active')
                ->orderBy(User::select('name')->whereColumn('users.id', 'club_members.user_id'))
                ->get()
        );
    }

    public function show(ClubMember $member): MemberDetailResource
    {
        $this->authorize('view', $member);

        return new MemberDetailResource(
            $member->load('user', 'favoriteGenres', 'readingProgress', 'proposedCycles', 'meetingRsvps')
        );
    }

    public function avatar(ClubMember $member): mixed
    {
        $this->authorize('view', $member);

        $path = $member->loadMissing('user')->user?->avatar_path;

        if (! $path || ! Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return Storage::disk('public')->response($path, null, [
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    public function store(Request $request, UserAvatarService $avatars, TurnOrderService $turnOrder): MemberDetailResource
    {
        $this->authorize('create', ClubMember::class);

        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'favorite_genre_ids' => ['nullable', 'array', 'max:5'],
            'favorite_genre_ids.*' => ['integer', 'exists:genres,id'],
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
            ]);

            $member->favoriteGenres()->sync($payload['favorite_genre_ids'] ?? []);

            if (isset($payload['avatar'])) {
                $user->forceFill([
                    'avatar_path' => $avatars->store($user, $payload['avatar']),
                ])->save();
            }

            $user->assignRole($payload['role']);

            return $member;
        });

        $turnOrder->appendMember($member);

        $actor = $request->user();
        if ($actor) {
            $this->auditLog->logMemberCreated($member, $actor);
        }

        $member->load('user', 'favoriteGenres', 'club');
        event(new MemberJoinedClub($member));

        return new MemberDetailResource(
            $member->load('user', 'favoriteGenres')
        );
    }

    public function reorderTurnOrder(Request $request, TurnOrderService $turnOrder): JsonResponse
    {
        abort_unless($request->user()?->hasAnyRole(['admin', 'developer']), 403);

        $payload = $request->validate([
            'memberIds' => ['required', 'array', 'min:1'],
            'memberIds.*' => ['required', 'integer', 'exists:club_members,id'],
        ]);

        $club = Club::first();
        if (! $club) {
            abort(422, 'Клуб не найден.');
        }

        $ordered = $turnOrder->orderedTurnOrders($club->id);
        $previousOrder = $ordered->map(fn ($to) => [
            'id' => $to->club_member_id,
            'name' => $to->clubMember?->user?->name,
        ])->values()->toArray();

        $memberIds = array_map('intval', $payload['memberIds']);
        $turnOrder->reorder($club->id, $memberIds);

        $newOrdered = $turnOrder->orderedTurnOrders($club->id);
        $newOrder = $newOrdered->map(fn ($to) => [
            'id' => $to->club_member_id,
            'name' => $to->clubMember?->user?->name,
        ])->values()->toArray();

        $actor = $request->user();
        if ($actor) {
            $this->auditLog->logTurnOrderReordered($club, $actor, $previousOrder, $newOrder);
        }

        return response()->json([
            'turnOrder' => array_map(fn ($to) => [
                'memberId' => $to->club_member_id,
                'name' => $to->clubMember?->user?->name,
            ], $newOrdered->all()),
        ]);
    }

    public function deactivate(Request $request, ClubMember $member, TurnOrderService $turnOrder): JsonResponse
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

        $turnOrder->removeMember($member);

        if ($actor) {
            $this->auditLog->logMemberDeactivated($member, $actor);
        }

        $member->load('user', 'club');
        event(new MemberDeactivated($member));

        return response()->json([
            'message' => 'Участник деактивирован.',
        ]);
    }

    public function activate(Request $request, ClubMember $member, TurnOrderService $turnOrder): JsonResponse
    {
        $this->authorize('deactivate', $member);

        if ($member->user_id === $request->user()?->id) {
            return response()->json([
                'message' => 'Нельзя активировать собственный аккаунт.',
            ], 422);
        }

        if ($member->is_active) {
            return response()->json([
                'message' => 'Участник уже активен.',
            ], 422);
        }

        $member->update([
            'is_active' => true,
            'deactivated_at' => null,
        ]);

        $turnOrder->appendMember($member);

        $actor = $request->user();
        if ($actor) {
            $this->auditLog->log(
                actor: $actor,
                action: 'member_activated',
                subject: $member->user?->name ?? "Участник #{$member->id}",
                description: "{$actor->name} активировал {$member->user?->name}.",
            );
        }

        $member->load('user', 'club');
        event(new MemberJoinedClub($member));

        return response()->json([
            'message' => 'Участник активирован.',
        ]);
    }

    public function initReadingProgress(Request $request, ClubMember $member): JsonResponse
    {
        abort_unless($request->user()?->hasAnyRole(['admin', 'developer']), 403);

        $cycle = ReadingCycle::query()
            ->where('status', ReadingCycleStatusEnum::Active)
            ->first();

        if (! $cycle) {
            return response()->json([
                'message' => 'Нет активного цикла чтения.',
            ], 422);
        }

        if (! $member->is_active) {
            return response()->json([
                'message' => 'Неактивного участника нельзя добавить в лидеры.',
            ], 422);
        }

        $progress = ReadingProgress::firstOrCreate(
            [
                'reading_cycle_id' => $cycle->id,
                'club_member_id' => $member->id,
            ],
            [
                'status' => ReadingProgressStatusEnum::NotStarted,
                'progress_percent' => 0,
            ],
        );

        if ($progress->wasRecentlyCreated) {
            $actor = $request->user();
            if ($actor) {
                $this->auditLog->log(
                    actor: $actor,
                    action: 'member_init_reading_progress',
                    subject: $cycle->book?->title ?? "Цикл #{$cycle->cycle_number}",
                    description: "{$actor->name} добавил {$member->user?->name} в лидеры цикла #{$cycle->cycle_number}.",
                );
            }
        }

        return response()->json([
            'message' => $progress->wasRecentlyCreated
                ? 'Участник добавлен в лидеры цикла.'
                : 'Участник уже в лидерах цикла.',
        ]);
    }
}
