<script setup lang="ts">
import { computed, ref } from 'vue'
import { RouterLink } from 'vue-router'
import DashboardReadingProgressForm from '@/components/dashboard/DashboardReadingProgressForm.vue'
import UserAvatar from '@/components/UserAvatar.vue'
import { useUpdateReadingProgressMutation } from '@/queries/dashboardQueries'
import type { BookProgressMember, CurrentBook } from '@/types/dashboard'

const props = defineProps<{
  book: CurrentBook
  members: BookProgressMember[]
  nextSelectorName?: string | null
}>()

const coverTitleLines = computed(() => props.book.coverTitle.split('\n'))
const isProgressFormOpen = ref(false)
const updateProgressMutation = useUpdateReadingProgressMutation()

const progressErrorMessage = computed(() =>
  updateProgressMutation.error.value ? 'Не удалось сохранить прогресс. Попробуй ещё раз.' : '',
)

const cycleHeaderLabel = computed(() => {
  if (props.book.cycleStatus === 'active') {
    return `Цикл #${props.book.cycleNumber}`
  }
  return null
})

const nextSelectorLabel = computed(() => {
  if (props.nextSelectorName) {
    return `Выбирает следующую: ${props.nextSelectorName}`
  }
  return null
})

const activeProgressCount = computed(() => props.members.length)

function medalLabel(medal?: BookProgressMember['medal']) {
  const labels: Record<string, string> = {
    gold: 'Золото',
    silver: 'Серебро',
    bronze: 'Бронза',
  }

  return medal ? labels[medal] : null
}

function openProgressForm() {
  updateProgressMutation.reset()
  isProgressFormOpen.value = true
}

function closeProgressForm() {
  updateProgressMutation.reset()
  isProgressFormOpen.value = false
}

function saveProgress(progressPercent: number) {
  updateProgressMutation.mutate(
    { progressPercent },
    {
      onSuccess: () => {
        isProgressFormOpen.value = false
      },
    },
  )
}
</script>

<template lang="pug">
section.dashboard__main(aria-labelledby="current-cycle-title")
  .section-header
    h2#current-cycle-title Текущий цикл
    span.label-text(v-if="cycleHeaderLabel") {{ cycleHeaderLabel }}
    span.label-text(v-if="nextSelectorLabel") {{ nextSelectorLabel }}

  article.current-book
    .book-cover.current-book__cover(:aria-label="`Обложка книги ${book.title}`")
      .book-cover__content
        span.current-book__cover-label.label-text Выбрал {{ book.selectedBy }}
        template(v-for="line in coverTitleLines" :key="line")
          | {{ line }}
          br

    .current-book__details
      .current-book__meta
        h1 {{ book.title }}
        p.subtitle-italic {{ book.author }}
      p.body-text.current-book__description
        | {{ book.description }}

      .panel.panel--filled.current-book__progress
        template(v-if="!isProgressFormOpen")
          .current-book__progress-header
            span.label-text Мой прогресс
            span.label-text {{ book.progressLabel }}
          .progress(:aria-label="`Мой прогресс чтения ${book.progress}%`")
            .progress__bar(:style="{ '--progress-value': `${book.progress}%` }")
          button.button.button--secondary.label-text(
            type="button"
            @click="openProgressForm"
          ) Обновить прогресс
        DashboardReadingProgressForm(
          v-else
          :model-value="book.progress"
          :is-saving="updateProgressMutation.isPending.value"
          :error-message="progressErrorMessage"
          @cancel="closeProgressForm"
          @save="saveProgress"
        )

  .section-header.dashboard__section-spaced
    h3 Прогресс клуба
    span.label-text {{ activeProgressCount }} участников

  ul.data-list.club-progress(role="list")
    li.data-list__item.club-progress__item(v-for="member in members" :key="member.name")
      .member-status
        .club-progress__rank(:class="member.medal ? `club-progress__rank--${member.medal}` : ''")
          img.club-progress__owl(v-if="member.medal" src="/favicon.svg" alt="" aria-hidden="true")
          span.label-text {{ member.rank ?? '—' }}
        UserAvatar(:name="member.name" :avatar-url="member.avatarUrl" size="sm")
        span.member-status__name
          | {{ member.name }}
          span.club-progress__medal(v-if="member.medal") {{ medalLabel(member.medal) }}
      span.label-text(v-if="member.status") {{ member.status }}
      .member-status__progress(v-else-if="member.progress !== undefined && member.progress !== null")
        .progress.member-status__progress-track(:aria-label="`${member.name}: ${member.progress}%`")
          .progress__bar(:style="{ '--progress-value': `${member.progress}%` }")
        span.label-text {{ member.progress }}%
      span.badge.badge--reading.label-text(v-else) {{ member.badge }}
  RouterLink.button.button--ghost.label-text.club-progress__link(to="/members") Все участники клуба
</template>

<style scoped>
.dashboard__main {
  padding: var(--space-xl);
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-panel);
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 0.04), rgba(255, 255, 255, 0.012)),
    var(--bg-surface);
  box-shadow: var(--shadow-panel);
}

.dashboard__main {
  min-width: 0;
}

.dashboard__section-spaced {
  margin-top: var(--space-xl);
}

.current-book {
  display: grid;
  grid-template-columns: minmax(11rem, 14rem) minmax(0, 1fr);
  gap: clamp(var(--space-lg), 4vw, var(--space-xl));
  margin-bottom: var(--space-xl);
}

.current-book__cover {
  width: 100%;
}

.current-book__cover-label {
  display: block;
  margin-bottom: var(--space-sm);
  font-size: 0.5rem;
  opacity: 0.7;
}

.current-book__details {
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.current-book__meta h1 {
  font-size: clamp(2.4rem, 5vw, 4.25rem);
  line-height: 1;
}

.current-book__meta {
  margin-bottom: var(--space-md);
}

.current-book__description {
  max-width: 36rem;
  margin-bottom: var(--space-lg);
  color: var(--text-muted);
  font-size: 1rem;
}

.current-book__progress {
  padding: var(--space-md);
  border-radius: var(--radius-inner);
}

.current-book__progress-header {
  display: flex;
  justify-content: space-between;
  gap: var(--space-md);
}

.current-book__progress > .button {
  width: 100%;
  margin-top: var(--space-sm);
}

.club-progress {
  overflow: hidden;
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-inner);
}

.club-progress__item {
  min-height: 3.4rem;
  padding: 0.7rem var(--space-md);
}

.club-progress__rank {
  position: relative;
  display: grid;
  place-items: center;
  width: 2rem;
  height: 2rem;
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-inner);
  background: var(--bg-panel);
  overflow: hidden;
}

.club-progress__rank--gold {
  border-color: rgba(226, 184, 92, 0.55);
  background: linear-gradient(180deg, rgba(226, 184, 92, 0.22), rgba(226, 184, 92, 0.06));
}

.club-progress__rank--silver {
  border-color: rgba(188, 198, 205, 0.5);
  background: linear-gradient(180deg, rgba(188, 198, 205, 0.18), rgba(188, 198, 205, 0.05));
}

.club-progress__rank--bronze {
  border-color: rgba(196, 131, 82, 0.5);
  background: linear-gradient(180deg, rgba(196, 131, 82, 0.18), rgba(196, 131, 82, 0.05));
}

.club-progress__owl {
  position: absolute;
  inset: 0.22rem;
  width: calc(100% - 0.44rem);
  height: calc(100% - 0.44rem);
  opacity: 0.2;
}

.club-progress__rank .label-text {
  position: relative;
  color: var(--text-main);
}

.club-progress__medal {
  display: block;
  margin-top: 0.08rem;
  color: var(--text-subtle);
  font-family: var(--font-mono);
  font-size: 0.62rem;
}

.club-progress__link {
  width: 100%;
  margin-top: var(--space-md);
}

.member-status {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
}

.member-status__name {
  color: var(--text-main);
  font-size: 0.82rem;
}

.member-status__progress {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  width: 9.5rem;
}

.member-status__progress-track {
  flex: 1;
  margin: 0;
}

@media (max-width: 760px) {
  .dashboard__main {
    padding: var(--space-lg);
  }

  .current-book {
    grid-template-columns: 1fr;
  }

  .current-book__cover {
    width: min(100%, 13rem);
  }

  .current-book__progress-header {
    flex-direction: column;
    gap: var(--space-xs);
  }

  .member-status__progress {
    width: 100%;
  }
}
</style>
