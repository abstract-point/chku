<script setup lang="ts">
import { computed, nextTick, onMounted, ref, TransitionGroup, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { Pencil } from '@lucide/vue'
import CycleBookForm from '@/components/books/CycleBookForm.vue'
import DashboardReadingProgressForm from '@/components/dashboard/DashboardReadingProgressForm.vue'
import AppModal from '@/components/ui/AppModal.vue'
import CollapseTransition from '@/components/ui/CollapseTransition.vue'
import UserAvatar from '@/components/UserAvatar.vue'
import { useUpdateReadingProgressMutation } from '@/queries/dashboardQueries'
import type { BookProgressMember, CurrentBook } from '@/types/dashboard'

const { t } = useI18n()
const route = useRoute()
const props = defineProps<{
  book: CurrentBook
  members: BookProgressMember[]
}>()

const coverTitleLines = computed(() => props.book.coverTitle.split('\n'))
const progressPanel = ref<HTMLElement | null>(null)
const isProgressFormOpen = ref(false)
const hasHandledProgressActionRoute = ref(false)
const isBookFormOpen = ref(false)
const isConfirmModalOpen = ref(false)
const pendingProgressPercent = ref(0)
const updateProgressMutation = useUpdateReadingProgressMutation()

const progressErrorMessage = computed(() =>
  updateProgressMutation.error.value ? t('dash.progressError') : '',
)

const cycleHeaderLabel = computed(() => {
  if (props.book.cycleStatus === 'active') {
    return t('dash.cycleNum', { n: props.book.cycleNumber })
  }
  return null
})

const activeProgressCount = computed(() => props.members.length)

const membersWithMedals = computed(() => {
  const sorted = [...props.members].sort((a, b) => {
    const progressDiff = (b.progress ?? 0) - (a.progress ?? 0)
    if (progressDiff !== 0) return progressDiff
    if (a.finishedAt && b.finishedAt) {
      return new Date(a.finishedAt).getTime() - new Date(b.finishedAt).getTime()
    }
    if (a.finishedAt) return -1
    if (b.finishedAt) return 1
    return 0
  })
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

async function openProgressFormFromRoute() {
  if (props.book.progress === 100) return
  if (route?.query.action !== 'update-progress' || route?.hash !== '#reading-progress') return
  if (hasHandledProgressActionRoute.value) return

  hasHandledProgressActionRoute.value = true
  openProgressForm()
  await nextTick()
  progressPanel.value?.scrollIntoView({ behavior: 'smooth', block: 'center' })
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
  if (medal === 'gold') return t('dash.medalGold')
  if (medal === 'silver') return t('dash.medalSilver')
  if (medal === 'bronze') return t('dash.medalBronze')
  return ''
}

onMounted(openProgressFormFromRoute)

watch(
  () => [route?.query.action, route?.hash, props.book.progress],
  () => {
    if (route?.query.action !== 'update-progress' || route?.hash !== '#reading-progress') {
      hasHandledProgressActionRoute.value = false
    }
    void openProgressFormFromRoute()
  },
)
</script>

<template lang="pug">
section.dashboard__main(aria-labelledby="current-cycle-title")
  .section-header
    h2#current-cycle-title {{ $t('dash.currentCycle') }}
    span.label-text(v-if="cycleHeaderLabel") {{ cycleHeaderLabel }}
    button.button.button--secondary.label-text(
      v-if="book.canEditBook && !isBookFormOpen"
      type="button"
      @click="isBookFormOpen = true; isProgressFormOpen = false"
    )
      Pencil.current-book__button-icon
      | {{ $t('cycle.editBook') }}

  article.current-book
    CollapseTransition(mode="out-in")
      .current-book__edit(v-if="isBookFormOpen" key="form")
        CycleBookForm(
          :cycle-number="book.cycleNumber"
          :book="book"
          id-prefix="dashboard-book"
          @cancel="isBookFormOpen = false"
          @saved="isBookFormOpen = false"
        )
      .current-book__view(v-else key="details")
        .book-cover.current-book__cover(:style="{ backgroundColor: book.coverColor ?? undefined }" :aria-label="$t('archive.coverAria', { title: book.title })")
          img.current-book__cover-image(v-if="book.coverUrl" :src="book.coverUrl" :alt="book.title")
          .book-cover__content(v-else)
            span.current-book__cover-label.label-text {{ $t('dash.selectedBy', { name: book.selectedBy }) }}
            template(v-for="line in coverTitleLines" :key="line")
              | {{ line }}
              br

        .current-book__details
          .current-book__meta
            h1 {{ book.title }}
            p.current-book__author — {{ book.author }}
          .current-book__genres(v-if="book.genres?.length")
            span.badge(v-for="g in book.genres" :key="g.id") {{ g.name }}
          p.body-text.current-book__description
            | {{ book.description }}

          .panel.panel--filled.current-book__progress(
            id="reading-progress"
            ref="progressPanel"
          )
            template(v-if="book.progress === 100")
              .current-book__progress-header
                span.label-text {{ $t('dash.myProgress') }}
                span.label-text {{ book.progressLabel }}
              .progress(:aria-label="$t('dash.myProgressAria', { pct: book.progress })")
                .progress__bar(:style="{ '--progress-value': `${book.progress}%` }")
              .current-book__done-message
                span.label-text {{ $t('dash.doneMsg') }}
                p.body-text {{ $t('dash.doneSub') }}
            CollapseTransition(mode="out-in" v-if="book.progress !== 100")
              .current-book__progress-content(v-if="!isProgressFormOpen" key="progress")
                .current-book__progress-header
                  span.label-text {{ $t('dash.myProgress') }}
                  span.label-text {{ book.progressLabel }}
                .progress(:aria-label="$t('dash.myProgressAria', { pct: book.progress })")
                  .progress__bar(:style="{ '--progress-value': `${book.progress}%` }")
                button.button.button--secondary.label-text(
                  type="button"
                  @click="openProgressForm"
                ) {{ $t('dash.updateProgress') }}
              .current-book__progress-form(v-else key="form")
                DashboardReadingProgressForm(
                  :model-value="book.progress"
                  :is-saving="updateProgressMutation.isPending.value"
                  :error-message="progressErrorMessage"
                  @cancel="closeProgressForm"
                  @save="saveProgress"
                )

  .section-header.dashboard__section-spaced
    h3 {{ $t('dash.clubProgress') }}
    span.label-text {{ $t('dash.clubProgressCount', { n: activeProgressCount }) }}

  TransitionGroup.data-list.club-progress(name="list" tag="ul" role="list")
    li.data-list__item.club-progress__item(v-for="member in membersWithMedals" :key="member.name")
      .member-status
        RouterLink.member-link(:to="`/members/${member.id}`")
          UserAvatar(:name="member.name" :avatar-url="member.avatarUrl" size="sm")
          span.member-status__name {{ member.name }}
        img.club-progress__owl(
          v-if="member.medal"
          :class="`club-progress__owl--${member.medal}`"
          src="/owl.svg"
          :alt="medalLabel(member.medal)"
          :title="medalLabel(member.medal)"
        )
        span.member-status__medal.label-text(v-if="member.medal") {{ medalLabel(member.medal) }}
      .member-status__progress(v-if="member.progress && member.progress > 0")
        .progress.member-status__progress-track(:aria-label="`${member.name}: ${member.progress}%`")
          .progress__bar(:style="{ '--progress-value': `${member.progress}%` }")
        span.label-text {{ member.progress }}%
      span.label-text(v-else) {{ $t('dash.notStarted') }}
  .club-progress__links
    RouterLink.button.button--ghost.label-text(to="/members") {{ $t('dash.allMembers') }}

  AppModal(
    :is-open="isConfirmModalOpen"
    :title="t('dash.confirmReadTitle')"
    @close="isConfirmModalOpen = false"
  )
    template(#default)
      p.body-text {{ $t('dash.confirmReadText') }}
      p.body-text {{ $t('dash.confirmReadHint') }}
    template(#footer)
      button.button.button--secondary.label-text(type="button" @click="isConfirmModalOpen = false") {{ $t('common.cancel') }}
      button.button.button--primary.label-text(type="button" @click="confirmProgress") {{ $t('dash.confirmReadBtn') }}
</template>

<style scoped lang="scss">
@use '@/styles/breakpoints' as *;

.dashboard__main {
  min-width: 0;
  padding: var(--space-lg);
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-panel);
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 0.04), rgba(255, 255, 255, 0.012)),
    var(--bg-surface);
  box-shadow: var(--shadow-panel);

  @include tablet {
    padding: var(--space-xl);
  }
}

.dashboard__section-spaced {
  margin-top: var(--space-xl);
}

.current-book {
  margin-bottom: var(--space-xl);
}

.current-book__view {
  display: flex;
  flex-direction: column;
  gap: clamp(var(--space-lg), 4vw, var(--space-xl));

  @include tablet {
    display: grid;
    grid-template-columns: minmax(11rem, 14rem) minmax(0, 1fr);
  }
}

.current-book__edit {
  padding-top: var(--space-sm);
}

.current-book__cover {
  position: relative;
  overflow: hidden;
  width: min(100%, 13rem);

  @include tablet {
    width: 100%;
  }
}

.current-book__cover-image {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.current-book__cover-label {
  display: block;
  margin-bottom: var(--space-sm);
  font-size: 0.5rem;
  opacity: 0.7;
}

.current-book__button-icon {
  width: 1rem;
  height: 1rem;
}

.current-book__details {
  display: flex;
  flex-direction: column;
  gap: var(--space-md);
}

.current-book__meta h1 {
  font-size: clamp(2.4rem, 5vw, 4.25rem);
  line-height: 1;
}

.current-book__meta {
  margin-bottom: var(--space-md);
}

.current-book__meta h1 {
  font-size: clamp(2.2rem, 4.5vw, 3.5rem);
  line-height: 1.12;
  letter-spacing: -0.01em;
}

.current-book__author {
  margin-top: var(--space-xs);
  color: var(--text-muted);
  font-size: 1rem;
  font-weight: 400;
  line-height: 1.4;
}

.current-book__genres {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-xs) var(--space-sm);
  margin: var(--space-xs) 0;
}

.current-book__genres .badge {
  font-size: 0.6rem;
  text-transform: none;
  letter-spacing: 0;
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

.club-progress__links {
  display: grid;
  gap: var(--space-sm);
  margin-top: var(--space-md);
}

.club-progress__links .button {
  width: 100%;
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
  width: 100%;

  @include tablet {
    width: 9.5rem;
  }
}

.member-status__progress-track {
  flex: 1;
  margin: 0;
}
</style>
