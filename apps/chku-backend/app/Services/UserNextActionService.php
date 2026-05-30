<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\NextAction;
use App\Enums\BookCandidateResponseEnum;
use App\Enums\BookCandidateStatusEnum;
use App\Enums\MeetingRsvpStatusEnum;
use App\Enums\ReadingProgressStatusEnum;
use App\Models\BookCandidate;
use App\Models\ClubMember;
use App\Models\Meeting;
use App\Models\Rating;
use App\Models\ReadingCycle;
use App\Models\ReadingProgress;
use App\Models\Review;
use Illuminate\Support\Collection;

final class UserNextActionService
{
    public function resolve(
        ClubMember $member,
        ?ReadingCycle $currentCycle,
        ?ReadingProgress $currentUserProgress,
        ?Meeting $nextMeeting,
        ?BookCandidate $activeCandidate,
        ?ClubMember $nextSelector,
        bool $nextSelectorQueueEmpty,
        Collection $missingRatings,
    ): NextAction {
        if ($activeCandidate) {
            $candidateAction = $this->candidateAction($member, $activeCandidate);
            if ($candidateAction) {
                return $candidateAction;
            }
        }

        if (
            $nextSelector?->id === $member->id
            && $nextSelectorQueueEmpty
            && $activeCandidate?->proposer_id !== $member->id
        ) {
            return new NextAction(
                type: 'add_book_to_queue',
                priority: 90,
                title: 'Добавь книгу в очередь',
                description: 'Твоя очередь выбора близко, но в личной очереди нет доступных книг.',
                actionUrl: '/propose-selection',
            );
        }

        if ($nextMeeting) {
            $meetingAction = $this->meetingAction($member, $currentCycle, $nextMeeting, $missingRatings);
            if ($meetingAction) {
                return $meetingAction;
            }
        }

        if (
            $currentCycle
            && (! $currentUserProgress || $currentUserProgress->status !== ReadingProgressStatusEnum::Finished)
            && (! $currentUserProgress || $currentUserProgress->updated_at->lte(now()->subWeek()))
        ) {
            return new NextAction(
                type: 'update_progress',
                priority: 50,
                title: 'Обнови прогресс чтения',
                description: 'Отметь, на каком проценте книги ты сейчас. Это помогает клубу понимать темп чтения.',
                actionUrl: '/?action=update-progress#reading-progress',
            );
        }

        return new NextAction(
            type: 'none',
            priority: 0,
            title: '',
            description: '',
            actionUrl: '/',
        );
    }

    private function candidateAction(ClubMember $member, BookCandidate $candidate): ?NextAction
    {
        if (
            $candidate->status === BookCandidateStatusEnum::AwaitingOwnerConfirmation
            && $candidate->proposer_id === $member->id
        ) {
            return new NextAction(
                type: 'confirm_candidate',
                priority: 95,
                title: 'Подтверди свою книгу',
                description: 'Все ответили, что не читали книгу. Осталось подтвердить выбор и начать цикл.',
                actionUrl: '/',
            );
        }

        if ($candidate->status !== BookCandidateStatusEnum::Pending) {
            return null;
        }

        $response = $candidate->responses->firstWhere('club_member_id', $member->id);

        if (! $response || $response->response === BookCandidateResponseEnum::Pending) {
            return new NextAction(
                type: 'respond_candidate',
                priority: 100,
                title: 'Ответь по книге-кандидату',
                description: 'Клуб ждёт твой ответ: читал ли ты эту книгу раньше.',
                actionUrl: '/',
            );
        }

        return null;
    }

    private function meetingAction(
        ClubMember $member,
        ?ReadingCycle $currentCycle,
        Meeting $meeting,
        Collection $missingRatings,
    ): ?NextAction {
        $rsvp = $meeting->rsvps->firstWhere('club_member_id', $member->id);

        if (! $rsvp || $rsvp->status === MeetingRsvpStatusEnum::Pending) {
            return new NextAction(
                type: 'rsvp_meeting',
                priority: 80,
                title: 'Отметь участие во встрече',
                description: 'Подтверди, будешь ли ты на встрече по текущей книге.',
                actionUrl: "/meetings/{$meeting->id}",
            );
        }

        if ($rsvp->status !== MeetingRsvpStatusEnum::Attending || ! $currentCycle) {
            return null;
        }

        if ($meeting->started_at && $missingRatings->contains('id', $member->id)) {
            return new NextAction(
                type: 'rate_book',
                priority: 70,
                title: 'Поставь оценку книге',
                description: 'Встреча уже началась. Чтобы закрыть цикл, нужна твоя оценка.',
                actionUrl: "/meetings/{$meeting->id}",
            );
        }

        $hasRating = Rating::query()
            ->where('reading_cycle_id', $currentCycle->id)
            ->where('club_member_id', $member->id)
            ->exists();

        $hasReview = Review::query()
            ->where('reading_cycle_id', $currentCycle->id)
            ->where('club_member_id', $member->id)
            ->exists();

        if ($meeting->started_at && $hasRating && ! $hasReview) {
            return new NextAction(
                type: 'write_review',
                priority: 60,
                title: 'Оставь короткий отзыв',
                description: 'Оценка уже есть. Можно оставить пару слов о книге для истории клуба.',
                actionUrl: "/meetings/{$meeting->id}",
            );
        }

        return null;
    }
}
