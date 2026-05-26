<?php

declare(strict_types=1);

namespace App\Services;

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
        $name = $this->escape($event->member->user->name);

        return sprintf('%s *%s*', $this->escape('К нам присоединился новый участник:'), $name);
    }

    private function formatMemberDeactivated(MemberDeactivated $event): string
    {
        $name = $this->escape($event->member->user->name);

        return sprintf('%s *%s*', $this->escape('Участник покинул клуб:'), $name);
    }

    private function formatCandidateProposed(BookCandidateProposed $event): string
    {
        $candidate = $event->candidate;
        $candidate->loadMissing(['book', 'proposer.user']);
        $proposer = $this->escape($candidate->proposer->user->name);
        $book = $this->escape($candidate->book->title);
        $author = $candidate->book->author ? $this->escape($candidate->book->author) : null;
        $reason = $candidate->reason ? "\n" . $this->escape('Почему: ') . $this->escape($candidate->reason) : '';

        $line = sprintf(
            '%s',
            $this->escape('Новая книга на проверку!')
        );
        $line .= sprintf(
            "\n%s *%s*",
            $this->escape('Предложил:'),
            $proposer
        );
        $line .= sprintf(
            "\n%s *%s*",
            $this->escape('Книга:'),
            $book
        );
        if ($author) {
            $line .= sprintf(
                "\n%s *%s*",
                $this->escape('Автор:'),
                $author
            );
        }
        $line .= "\n" . $this->escape('Ответьте, читали ли вы её — проверьте раздел кандидатов.');
        if ($reason) {
            $line .= $reason;
        }

        return $line;
    }

    private function formatCandidateRejected(BookCandidateRejected $event): string
    {
        $candidate = $event->candidate;
        $candidate->loadMissing(['book', 'proposer.user']);
        $proposer = $this->escape($candidate->proposer->user->name);
        $book = $this->escape($candidate->book->title);

        return sprintf(
            "%s\n%s *%s*\n%s *%s*",
            $this->escape('Кандидат отклонён — кто-то уже читал эту книгу.'),
            $this->escape('Предложил:'),
            $proposer,
            $this->escape('Книга:'),
            $book
        );
    }

    private function formatCandidateAwaiting(BookCandidateAwaitingConfirmation $event): string
    {
        $candidate = $event->candidate;
        $candidate->loadMissing(['book', 'proposer.user']);
        $proposer = $this->escape($candidate->proposer->user->name);
        $book = $this->escape($candidate->book->title);

        return sprintf(
            "%s\n%s *%s*\n%s *%s*\n%s",
            $this->escape('Все подтвердили — книгу никто не читал!'),
            $this->escape('Предложил:'),
            $proposer,
            $this->escape('Книга:'),
            $book,
            $this->escape('Теперь автор может подтвердить книгу и начать цикл чтения.')
        );
    }

    private function formatCandidateConfirmed(BookCandidateConfirmed $event): string
    {
        $candidate = $event->candidate;
        $candidate->loadMissing(['book', 'proposer.user', 'readingCycle']);
        $proposer = $this->escape($candidate->proposer->user->name);
        $book = $this->escape($candidate->book->title);
        $author = $candidate->book->author ? $this->escape($candidate->book->author) : null;
        $cycleNumber = $candidate->readingCycle?->cycle_number;

        $line = sprintf(
            '%s',
            $this->escape('Цикл чтения начался! Приятного чтения.')
        );
        $line .= sprintf(
            "\n%s *%s*",
            $this->escape('Книга:'),
            $book
        );
        if ($author) {
            $line .= sprintf(
                "\n%s *%s*",
                $this->escape('Автор:'),
                $author
            );
        }
        if ($cycleNumber) {
            $line .= sprintf(
                "\n%s %s",
                $this->escape('Цикл'),
                $this->escape('#' . (string) $cycleNumber)
            );
        }
        $line .= sprintf(
            "\n%s *%s*",
            $this->escape('Выбрал:'),
            $proposer
        );

        return $line;
    }

    private function formatCandidateReplaced(BookCandidateReplaced $event): string
    {
        $candidate = $event->candidate;
        $candidate->loadMissing(['book', 'proposer.user']);
        $proposer = $this->escape($candidate->proposer->user->name);
        $book = $this->escape($candidate->book->title);

        return sprintf(
            "%s\n%s *%s*\n%s *%s*",
            $this->escape('Кандидат заменён на новую книгу.'),
            $this->escape('Предложил:'),
            $proposer,
            $this->escape('Новая книга:'),
            $book
        );
    }

    private function formatProgressUpdated(ReadingProgressUpdated $event): string
    {
        $progress = $event->progress;
        $progress->loadMissing(['clubMember.user', 'readingCycle.book']);
        $name = $this->escape($progress->clubMember->user->name);
        $book = $this->escape($progress->readingCycle->book->title);
        $percent = $progress->progress_percent;

        return sprintf(
            "%s *%s*\n%s *%s* — %s%d%%",
            $this->escape('Прогресс чтения обновлён:'),
            $name,
            $this->escape('Книга:'),
            $book,
            $this->escape(''),
            $percent
        );
    }

    private function formatMemberFinished(MemberFinishedReading $event): string
    {
        $progress = $event->progress;
        $progress->loadMissing(['clubMember.user', 'readingCycle.book']);
        $name = $this->escape($progress->clubMember->user->name);
        $book = $this->escape($progress->readingCycle->book->title);

        return sprintf(
            "%s\n%s *%s*\n%s *%s*",
            $this->escape('Участник дочитал книгу!'),
            $this->escape('Кто:'),
            $name,
            $this->escape('Книга:'),
            $book
        );
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

        return sprintf(
            "%s *%s*\n%s *%s*\n%s %s, %s\n%s %s",
            $this->escape('Назначена встреча по книге:'),
            $book,
            $this->escape('Тема:'),
            $title,
            $this->escape('Когда:'),
            $date,
            $time,
            $this->escape('Где:'),
            $place
        );
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

        return sprintf(
            "%s *%s*\n%s %s %s → %s %s",
            $this->escape('Встреча перенесена:'),
            $book,
            $this->escape('Новое время:'),
            $oldDate,
            $oldTime,
            $newDate,
            $newTime
        );
    }

    private function formatMeetingStarted(MeetingStarted $event): string
    {
        $meeting = $event->meeting;
        $meeting->loadMissing(['readingCycle.book']);
        $book = $this->escape($meeting->readingCycle->book->title);

        return sprintf(
            "%s *%s*",
            $this->escape('Встреча началась! Обсуждаем книгу:'),
            $book
        );
    }

    private function formatMeetingFinished(MeetingFinished $event): string
    {
        $meeting = $event->meeting;
        $meeting->loadMissing(['readingCycle.book', 'readingCycle.proposer.user']);
        $book = $this->escape($meeting->readingCycle->book->title);
        $proposer = $this->escape($meeting->readingCycle->proposer->user->name);

        return sprintf(
            "%s\n%s *%s*\n%s *%s*",
            $this->escape('Встреча завершена! Книга уходит в архив.'),
            $this->escape('Книга:'),
            $book,
            $this->escape('Выбрал:'),
            $proposer
        );
    }

    private function formatCycleCompleted(CycleCompleted $event): string
    {
        $cycle = $event->cycle;
        $cycle->loadMissing(['book', 'proposer.user']);
        $book = $this->escape($cycle->book->title);
        $proposer = $this->escape($cycle->proposer->user->name);
        $cycleNumber = $cycle->cycle_number;

        return sprintf(
            "%s %s\n%s *%s*\n%s *%s*",
            $this->escape('Цикл'),
            $this->escape('#' . (string) $cycleNumber . ' завершён.'),
            $this->escape('Книга:'),
            $book,
            $this->escape('Выбрал:'),
            $proposer
        );
    }

    private function formatOwlAwards(OwlAwardsAssigned $event): string
    {
        if (empty($event->awards)) {
            return $this->escape('Совы не назначены — никто из присутствовавших не дочитал.');
        }

        $medals = [
            'gold' => 'Золотая сова',
            'silver' => 'Серебряная сова',
            'bronze' => 'Бронзовая сова',
        ];

        $lines = [$this->escape('Награды за этот цикл:')];
        foreach ($event->awards as $award) {
            $medalLabel = $medals[$award['medal']] ?? $award['medal'];
            $lines[] = sprintf(
                '%s *%s* — %s',
                $this->escape($medalLabel . ':'),
                $this->escape($award['memberName']),
                ''
            );
        }

        return implode("\n", $lines);
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
