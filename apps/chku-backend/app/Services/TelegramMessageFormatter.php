<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\MeetingRsvpStatusEnum;
use App\Enums\ReadingProgressStatusEnum;
use App\Events\BookCandidateAwaitingConfirmation;
use App\Events\BookCandidateConfirmed;
use App\Events\BookCandidateProposed;
use App\Events\BookCandidateRejected;
use App\Events\BookCandidateReplaced;
use App\Events\CycleCompleted;
use App\Events\MeetingFinished;
use App\Events\MeetingRescheduled;
use App\Events\MeetingScheduled;
use App\Events\MeetingStarted;
use App\Events\MemberDeactivated;
use App\Events\MemberFinishedReading;
use App\Events\MemberJoinedClub;
use App\Events\OwlAwardsAssigned;
use App\Events\ReadingProgressUpdated;
use RuntimeException;

final class TelegramMessageFormatter
{
    public function format(object $event): string
    {
        return match ($event::class) {
            MemberJoinedClub::class => $this->formatMemberJoined($event),
            MemberDeactivated::class => $this->formatMemberDeactivated($event),
            BookCandidateProposed::class => $this->formatCandidateProposed($event),
            BookCandidateRejected::class => $this->formatCandidateRejected($event),
            BookCandidateAwaitingConfirmation::class => $this->formatCandidateAwaiting($event),
            BookCandidateConfirmed::class => $this->formatCandidateConfirmed($event),
            BookCandidateReplaced::class => $this->formatCandidateReplaced($event),
            ReadingProgressUpdated::class => $this->formatProgressUpdated($event),
            MemberFinishedReading::class => $this->formatMemberFinished($event),
            MeetingScheduled::class => $this->formatMeetingScheduled($event),
            MeetingRescheduled::class => $this->formatMeetingRescheduled($event),
            MeetingStarted::class => $this->formatMeetingStarted($event),
            MeetingFinished::class => $this->formatMeetingFinished($event),
            CycleCompleted::class => $this->formatCycleCompleted($event),
            OwlAwardsAssigned::class => $this->formatOwlAwards($event),
            default => throw new RuntimeException('Unknown event: ' . $event::class),
        };
    }

    public function eventName(object $event): string
    {
        return match ($event::class) {
            MemberJoinedClub::class => 'member_joined_club',
            MemberDeactivated::class => 'member_deactivated',
            BookCandidateProposed::class => 'book_candidate_proposed',
            BookCandidateRejected::class => 'book_candidate_rejected',
            BookCandidateAwaitingConfirmation::class => 'book_candidate_awaiting_confirmation',
            BookCandidateConfirmed::class => 'book_candidate_confirmed',
            BookCandidateReplaced::class => 'book_candidate_replaced',
            ReadingProgressUpdated::class => 'reading_progress_updated',
            MemberFinishedReading::class => 'member_finished_reading',
            MeetingScheduled::class => 'meeting_scheduled',
            MeetingRescheduled::class => 'meeting_rescheduled',
            MeetingStarted::class => 'meeting_started',
            MeetingFinished::class => 'meeting_finished',
            CycleCompleted::class => 'cycle_completed',
            OwlAwardsAssigned::class => 'owl_awards_assigned',
            default => throw new RuntimeException('Unknown event: ' . $event::class),
        };
    }

    private function formatMemberJoined(MemberJoinedClub $event): string
    {
        $member = $event->member;
        $member->loadMissing('user');

        return implode("\n", [
            '👤 ' . $this->escape('К нам присоединился новый участник!'),
            '',
            $this->escape('Имя: ') . $this->memberLink($member),
        ]);
    }

    private function formatMemberDeactivated(MemberDeactivated $event): string
    {
        $name = $this->escape($event->member->user->name);

        return implode("\n", [
            '🚪 ' . $this->escape('Участник покинул клуб.'),
            '',
            $this->escape('Имя: ') . '*' . $name . '*',
        ]);
    }

    private function formatCandidateProposed(BookCandidateProposed $event): string
    {
        $candidate = $event->candidate;
        $candidate->loadMissing(['book', 'proposer.user']);
        $proposer = $this->memberLink($candidate->proposer);
        $book = $this->escape($candidate->book->title);
        $author = $candidate->book->author ? $this->escape($candidate->book->author) : null;
        $reason = $candidate->reason ? "\n" . $this->escape('Почему: ') . '*' . $this->escape($candidate->reason) . '*' : '';

        $lines = [
            '📚 ' . $this->escape('Новая книга на проверку!'),
            '',
            $this->escape('Книга: ') . '*' . $book . '*',
        ];

        if ($author) {
            $lines[] = $this->escape('Автор: ') . '*' . $author . '*';
        }

        $lines[] = $this->escape('Предложил: ') . $proposer;
        $lines[] = '';
        $lines[] = $this->escape('Ответьте, читали ли вы её — проверьте раздел кандидатов.');
        $lines[] = $this->escape('👉 Открыть на сайте: ') . $this->link($this->frontendUrl('/'), $this->frontendUrl('/'));

        if ($reason) {
            $lines[] = $reason;
        }

        return implode("\n", $lines);
    }

    private function formatCandidateRejected(BookCandidateRejected $event): string
    {
        $candidate = $event->candidate;
        $candidate->loadMissing(['book', 'proposer.user']);
        $proposer = $this->memberLink($candidate->proposer);
        $book = $this->escape($candidate->book->title);

        return implode("\n", [
            '❌ ' . $this->escape('Кандидат отклонён — кто-то уже читал эту книгу.'),
            '',
            $this->escape('Книга: ') . '*' . $book . '*',
            $this->escape('Предложил: ') . $proposer,
        ]);
    }

    private function formatCandidateAwaiting(BookCandidateAwaitingConfirmation $event): string
    {
        $candidate = $event->candidate;
        $candidate->loadMissing(['book', 'proposer.user']);
        $proposer = $this->memberLink($candidate->proposer);
        $book = $this->escape($candidate->book->title);

        return implode("\n", [
            '✅ ' . $this->escape('Все подтвердили — книгу никто не читал!'),
            '',
            $this->escape('Книга: ') . '*' . $book . '*',
            $this->escape('Предложил: ') . $proposer,
            '',
            $this->escape('Теперь автор может подтвердить книгу и начать цикл чтения.'),
            $this->escape('👉 Открыть на сайте: ') . $this->link($this->frontendUrl('/'), $this->frontendUrl('/')),
        ]);
    }

    private function formatCandidateConfirmed(BookCandidateConfirmed $event): string
    {
        $candidate = $event->candidate;
        $candidate->loadMissing(['book', 'proposer.user', 'readingCycle']);
        $proposer = $this->memberLink($candidate->proposer);
        $book = $this->escape($candidate->book->title);
        $author = $candidate->book->author ? $this->escape($candidate->book->author) : null;
        $cycleNumber = $candidate->readingCycle?->cycle_number;

        $lines = [
            '🎉 ' . $this->escape('Цикл чтения начался! Приятного чтения.'),
            '',
            $this->escape('Книга: ') . '*' . $book . '*',
        ];

        if ($author) {
            $lines[] = $this->escape('Автор: ') . '*' . $author . '*';
        }

        if ($cycleNumber) {
            $lines[] = $this->escape('Цикл: ') . '*' . $this->escape('#' . (string) $cycleNumber) . '*';
        }

        $lines[] = $this->escape('Выбрал: ') . $proposer;
        $lines[] = '';
        $lines[] = $this->escape('👉 Открыть на сайте: ') . $this->link($this->frontendUrl('/'), $this->frontendUrl('/'));

        return implode("\n", $lines);
    }

    private function formatCandidateReplaced(BookCandidateReplaced $event): string
    {
        $candidate = $event->candidate;
        $candidate->loadMissing(['book', 'proposer.user']);
        $proposer = $this->memberLink($candidate->proposer);
        $book = $this->escape($candidate->book->title);

        return implode("\n", [
            '🔄 ' . $this->escape('Кандидат заменён на новую книгу.'),
            '',
            $this->escape('Новая книга: ') . '*' . $book . '*',
            $this->escape('Предложил: ') . $proposer,
        ]);
    }

    private function formatProgressUpdated(ReadingProgressUpdated $event): string
    {
        $progress = $event->progress;
        $progress->loadMissing(['clubMember.user', 'readingCycle.book']);
        $member = $this->memberLink($progress->clubMember);
        $book = $this->escape($progress->readingCycle->book->title);
        $percent = $progress->progress_percent;

        return implode("\n", [
            '📈 ' . $this->escape('Прогресс чтения обновлён'),
            '',
            $this->escape('Участник: ') . $member,
            $this->escape('Книга: ') . '*' . $book . '*',
            $this->escape('Прогресс: ') . '*' . $percent . '%' . '*',
        ]);
    }

    private function formatMemberFinished(MemberFinishedReading $event): string
    {
        $progress = $event->progress;
        $progress->loadMissing(['clubMember.user', 'readingCycle.book']);
        $member = $this->memberLink($progress->clubMember);
        $book = $this->escape($progress->readingCycle->book->title);

        return implode("\n", [
            '🏁 ' . $this->escape('Участник дочитал книгу!'),
            '',
            $this->escape('Кто: ') . $member,
            $this->escape('Книга: ') . '*' . $book . '*',
        ]);
    }

    private function formatMeetingScheduled(MeetingScheduled $event): string
    {
        $meeting = $event->meeting;
        $meeting->loadMissing(['readingCycle.book']);
        $book = $this->escape($meeting->readingCycle->book->title);
        $date = $this->escape($meeting->date->format('d.m.Y'));
        $time = $this->escape($meeting->time);
        $place = $meeting->place ? $this->escape($meeting->place) : $this->escape('Онлайн');
        $title = $this->escape($meeting->title);

        return implode("\n", [
            '📅 ' . $this->escape('Назначена встреча'),
            '',
            $this->escape('Книга: ') . '*' . $book . '*',
            $this->escape('Тема: ') . '*' . $title . '*',
            $this->escape('Когда: ') . '*' . $date . ', ' . $time . '*',
            $this->escape('Где: ') . '*' . $place . '*',
            '',
            $this->escape('👉 Открыть встречу: ') . $this->meetingLink($meeting),
        ]);
    }

    private function formatMeetingRescheduled(MeetingRescheduled $event): string
    {
        $meeting = $event->meeting;
        $meeting->loadMissing(['readingCycle.book']);
        $book = $this->escape($meeting->readingCycle->book->title);
        $oldDate = $event->oldDate ? $this->escape($event->oldDate) : '—';
        $oldTime = $event->oldTime ? $this->escape($event->oldTime) : '—';
        $newDate = $event->newDate ? $this->escape($event->newDate) : '—';
        $newTime = $event->newTime ? $this->escape($event->newTime) : '—';

        return implode("\n", [
            '🕒 ' . $this->escape('Встреча перенесена'),
            '',
            $this->escape('Книга: ') . '*' . $book . '*',
            $this->escape('Было: ') . '*' . $oldDate . ' ' . $oldTime . '*',
            $this->escape('Стало: ') . '*' . $newDate . ' ' . $newTime . '*',
            '',
            $this->escape('👉 Открыть встречу: ') . $this->meetingLink($meeting),
        ]);
    }

    private function formatMeetingStarted(MeetingStarted $event): string
    {
        $meeting = $event->meeting;
        $meeting->loadMissing(['readingCycle.book']);
        $book = $this->escape($meeting->readingCycle->book->title);

        return implode("\n", [
            '▶️ ' . $this->escape('Встреча началась!'),
            '',
            $this->escape('Обсуждаем книгу: ') . '*' . $book . '*',
            '',
            $this->escape('👉 Открыть встречу: ') . $this->meetingLink($meeting),
        ]);
    }

    private function formatMeetingFinished(MeetingFinished $event): string
    {
        $meeting = $event->meeting;
        $meeting->loadMissing(['readingCycle.book', 'readingCycle.proposer.user']);
        $book = $this->escape($meeting->readingCycle->book->title);
        $proposer = $this->memberLink($meeting->readingCycle->proposer);
        $cycle = $meeting->readingCycle;

        $lines = [
            '🏆 ' . $this->escape('Встреча завершена! Книга уходит в архив.'),
            '',
            $this->escape('Книга: ') . '*' . $book . '*',
            $this->escape('Выбрал: ') . $proposer,
        ];

        if ($cycle->cycle_number) {
            $lines[] = '';
            $lines[] = $this->escape('👉 Открыть архив: ') . $this->link($this->frontendUrl('/archive'), $this->frontendUrl('/archive'));
            $lines[] = $this->escape('👉 Открыть цикл: ') . $this->cycleLink($cycle);
        }

        return implode("\n", $lines);
    }

    private function formatCycleCompleted(CycleCompleted $event): string
    {
        $cycle = $event->cycle;
        $cycle->loadMissing(['book', 'proposer.user', 'meeting.rsvps', 'ratings', 'readingProgress.clubMember.user']);
        $book = $this->escape($cycle->book->title);
        $proposer = $this->memberLink($cycle->proposer);
        $cycleNumber = $cycle->cycle_number;

        $lines = [
            '🏆 ' . $this->escape('Цикл ') . '*' . $this->escape('#' . (string) $cycleNumber) . '* ' . $this->escape('завершён! Книга уходит в архив.'),
            '',
            $this->escape('Книга: ') . '*' . $book . '*',
            $this->escape('Выбрал: ') . $proposer,
        ];

        $meeting = $cycle->meeting;
        if ($meeting?->rsvps !== null) {
            $attendingIds = $meeting->rsvps
                ->filter(fn ($r) => $r->status === MeetingRsvpStatusEnum::Attending)
                ->pluck('club_member_id');

            if ($attendingIds->isNotEmpty()) {
                $avg = round((float) $cycle->ratings->avg('rating'), 1);
                $lines[] = '';
                $lines[] = '📊 ' . $this->escape('Оценки клуба:');
                $lines[] = $this->escape('Средняя: ') . '*' . $avg . '/10*';

                $finished = $cycle->readingProgress
                    ->filter(fn ($p) => $p->status === ReadingProgressStatusEnum::Finished && $p->finished_at !== null && $attendingIds->contains($p->club_member_id))
                    ->sortBy('finished_at')
                    ->take(3)
                    ->values();

                if ($finished->isNotEmpty()) {
                    $medals = [
                        0 => ['🥇', 'Золотая сова'],
                        1 => ['🥈', 'Серебряная сова'],
                        2 => ['🥉', 'Бронзовая сова'],
                    ];

                    $lines[] = '';
                    $lines[] = '🦉 ' . $this->escape('Награды за этот цикл:');

                    foreach ($finished as $i => $progress) {
                        $medal = $medals[$i] ?? [null, null];
                        $name = $progress->clubMember?->user?->name ?? 'Участник';
                        $memberUrl = $this->frontendUrl('/members/' . $progress->club_member_id);
                        $memberLink = $this->link($name, $memberUrl);
                        $lines[] = ($medal[0] ?? '') . ' ' . $this->escape(($medal[1] ?? '') . ': ') . $memberLink;
                    }
                }
            }
        }

        $lines[] = '';
        $lines[] = $this->escape('👉 Открыть в архиве: ') . $this->cycleLink($cycle);
        $lines[] = $this->escape('👉 Все циклы: ') . $this->link($this->frontendUrl('/archive'), $this->frontendUrl('/archive'));

        return implode("\n", $lines);
    }

    private function formatOwlAwards(OwlAwardsAssigned $event): string
    {
        if (empty($event->awards)) {
            return $this->escape('🦉 Совы не назначены — никто из присутствовавших не дочитал.');
        }

        $medals = [
            'gold' => ['🥇', 'Золотая сова'],
            'silver' => ['🥈', 'Серебряная сова'],
            'bronze' => ['🥉', 'Бронзовая сова'],
        ];

        $lines = ['🦉 ' . $this->escape('Награды за этот цикл:'), ''];

        foreach ($event->awards as $award) {
            $medal = $medals[$award['medal']] ?? [null, $award['medal']];
            $label = $medal[1];
            $emoji = $medal[0] ?? '';
            $memberUrl = $this->frontendUrl('/members/' . $award['memberId']);
            $memberLink = $this->link($award['memberName'], $memberUrl);
            $lines[] = $emoji . ' ' . $this->escape($label . ': ') . $memberLink;
        }

        if ($event->cycle !== null) {
            $lines[] = '';
            $lines[] = $this->escape('👉 Открыть цикл: ') . $this->cycleLink($event->cycle);
        }

        return implode("\n", $lines);
    }

    private function frontendUrl(string $path): string
    {
        $base = rtrim((string) config('telegram.frontend_url', config('app.url', 'http://localhost')), '/');

        return $base . '/' . ltrim($path, '/');
    }

    private function link(string $text, string $url): string
    {
        return '[' . $this->escape($text) . '](' . $url . ')';
    }

    private function memberLink($member): string
    {
        $member->loadMissing('user');
        $name = $member->user?->name ?? 'Участник';
        $url = $this->frontendUrl('/members/' . $member->id);

        return $this->link($name, $url);
    }

    private function cycleLink($cycle, ?string $text = null): string
    {
        $label = $text ?? ($cycle->book?->title ?? ('Цикл #' . $cycle->cycle_number));
        $url = $this->frontendUrl('/cycles/' . $cycle->cycle_number);

        return $this->link($label, $url);
    }

    private function meetingLink($meeting): string
    {
        $bookTitle = $meeting->readingCycle?->book?->title ?? ('Встреча #' . $meeting->id);
        $url = $this->frontendUrl('/meetings/' . $meeting->id);

        return $this->link($bookTitle, $url);
    }

    private function escape(string $text): string
    {
        $replacements = [
            '_' => '\\_',
            '*' => '\\*',
            '[' => '\\[',
            ']' => '\\]',
            '(' => '\\(',
            ')' => '\\)',
            '~' => '\\~',
            '`' => '\\`',
            '>' => '\\>',
            '#' => '\\#',
            '+' => '\\+',
            '-' => '\\-',
            '=' => '\\=',
            '|' => '\\|',
            '{' => '\\{',
            '}' => '\\}',
            '.' => '\\.',
            '!' => '\\!',
        ];

        return strtr($text, $replacements);
    }
}
