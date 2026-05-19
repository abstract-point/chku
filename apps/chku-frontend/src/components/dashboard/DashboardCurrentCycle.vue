<script setup lang="ts">
import { computed, ref } from 'vue'
import { RouterLink } from 'vue-router'
import AppModal from '@/components/ui/AppModal.vue'
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
const isConfirmModalOpen = ref(false)
const pendingProgressPercent = ref(0)
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

const membersWithMedals = computed(() => {
  const sorted = [...props.members].sort((a, b) => (b.progress ?? 0) - (a.progress ?? 0))
  const hasFinished = sorted.some((m) => m.progress === 100)
  if (!hasFinished) {
    return sorted.map((m) => ({ ...m, medal: null as BookProgressMember['medal'] }))
  }
  const medals: NonNullable<BookProgressMember['medal']>[] = ['gold', 'silver', 'bronze']
  let medalIndex = 0
  return sorted.map((m) => {
    if (m.progress === 100 && medalIndex < medals.length) {
      return { ...m, medal: medals[medalIndex++] }
    }
    return { ...m, medal: null }
  })
})

function openProgressForm() {
  updateProgressMutation.reset()
  isProgressFormOpen.value = true
}

function closeProgressForm() {
  updateProgressMutation.reset()
  isProgressFormOpen.value = false
}

function saveProgress(progressPercent: number) {
  if (progressPercent >= 100) {
    pendingProgressPercent.value = progressPercent
    isConfirmModalOpen.value = true
    return
  }
  sendProgress(progressPercent)
}

function sendProgress(progressPercent: number) {
  updateProgressMutation.mutate(
    { progressPercent },
    {
      onSuccess: () => {
        isProgressFormOpen.value = false
        isConfirmModalOpen.value = false
      },
    },
  )
}

function confirmProgress() {
  sendProgress(pendingProgressPercent.value)
}

function medalLabel(medal: BookProgressMember['medal']) {
  if (medal === 'gold') return 'Золото'
  if (medal === 'silver') return 'Серебро'
  if (medal === 'bronze') return 'Бронза'
  return ''
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
        template(v-if="book.progress === 100")
          .current-book__progress-header
            span.label-text Мой прогресс
            span.label-text {{ book.progressLabel }}
          .progress(:aria-label="`Мой прогресс чтения ${book.progress}%`")
            .progress__bar(:style="{ '--progress-value': `${book.progress}%` }")
          .current-book__done-message
            span.label-text Ты молодец, дочитал книгу!
            p.body-text Ждём остальных участников клуба.
        template(v-else-if="!isProgressFormOpen")
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
    li.data-list__item.club-progress__item(v-for="member in membersWithMedals" :key="member.name")
      .member-status
        UserAvatar(:name="member.name" :avatar-url="member.avatarUrl" size="sm")
        span.member-status__name {{ member.name }}
        img.club-progress__owl(
          v-if="member.medal"
          :class="`club-progress__owl--${member.medal}`"
          src="/favicon.svg"
          :alt="medalLabel(member.medal)"
          :title="medalLabel(member.medal)"
        )
        span.member-status__medal.label-text(v-if="member.medal") {{ medalLabel(member.medal) }}
      .member-status__progress(v-if="member.progress && member.progress > 0")
        .progress.member-status__progress-track(:aria-label="`${member.name}: ${member.progress}%`")
          .progress__bar(:style="{ '--progress-value': `${member.progress}%` }")
        span.label-text {{ member.progress }}%
      span.label-text(v-else) Не начал
  RouterLink.button.button--ghost.label-text.club-progress__link(to="/members") Все участники клуба

  AppModal(
    :is-open="isConfirmModalOpen"
    title="Подтверждение прочтения"
    @close="isConfirmModalOpen = false"
  )
    template(#default)
      p.body-text Ты уверен, что дочитал книгу?
      p.body-text Это фиксирует время завершения и влияет на раздачу сов. Отменить будет нельзя.
    template(#footer)
      button.button.button--secondary.label-text(type="button" @click="isConfirmModalOpen = false") Отмена
      button.button.button--primary.label-text(type="button" @click="confirmProgress") Да, я дочитал
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

.current-book__done-message {
  display: flex;
  flex-direction: column;
  gap: var(--space-xs);
  margin-top: var(--space-sm);
  padding: var(--space-sm) var(--space-md);
  border: var(--border-width) solid var(--accent-border);
  border-radius: var(--radius-inner);
  background: var(--accent-bg);
}

.current-book__done-message .label-text {
  color: var(--accent);
}

.current-book__done-message .body-text {
  margin: 0;
  color: var(--text-muted);
  font-size: 0.9rem;
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

.club-progress__owl {
  width: 1rem;
  height: 1rem;
  margin-left: var(--space-xs);
  vertical-align: middle;
}

.club-progress__owl--gold {
  filter: invert(78%) sepia(35%) saturate(800%) hue-rotate(355deg) brightness(95%) contrast(90%);
}

.club-progress__owl--silver {
  filter: invert(82%) sepia(8%) saturate(200%) hue-rotate(170deg) brightness(95%);
}

.club-progress__owl--bronze {
  filter: invert(68%) sepia(40%) saturate(600%) hue-rotate(345deg) brightness(90%);
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

.member-status__medal {
  color: var(--text-subtle);
  font-size: 0.58rem;
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
