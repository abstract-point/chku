<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { ArrowLeft, CalendarDays, MapPin, Monitor, Pencil, Star } from '@lucide/vue'
import CycleBookForm from '@/components/books/CycleBookForm.vue'
import DiscussionBlock from '@/components/discussion/DiscussionBlock.vue'
import UserAvatar from '@/components/UserAvatar.vue'
import CollapseTransition from '@/components/ui/CollapseTransition.vue'
import { formatDateLabel, formatTimeLabel } from '@/mappers/date'
import { useCycleQuery } from '@/queries/cycleQueries'

const route = useRoute()
const router = useRouter()
const { t } = useI18n()
const cycleNumber = computed(() => String(route.params.cycleNumber ?? ''))
const cycleQuery = useCycleQuery(cycleNumber)
const cycle = computed(() => cycleQuery.data.value)
const meeting = computed(() => cycle.value?.meeting)
const isEditingBook = ref(false)

type LeaderboardMember = {
  id: number
  name: string
  avatarUrl?: string | null
  progress: number
  medal: 'gold' | 'silver' | 'bronze' | null
  finishedAt?: string | null
}

const membersWithMedals = computed<LeaderboardMember[]>(() => {
  const mapped = (cycle.value?.memberProgress ?? []).map((p) => ({
    id: p.member.id,
    name: p.member.name,
    avatarUrl: p.member.avatarUrl,
    progress: p.progressPercent ?? 0,
    finishedAt: p.finishedAt ?? null,
    medal: null as LeaderboardMember['medal'],
  }))
  const sorted = [...mapped].sort((a, b) => {
    const diff = b.progress - a.progress
    if (diff !== 0) return diff
    if (a.finishedAt && b.finishedAt) {
      const finishedAtDiff = new Date(a.finishedAt).getTime() - new Date(b.finishedAt).getTime()
      if (finishedAtDiff !== 0) return finishedAtDiff
    }
    if (a.finishedAt) return -1
    if (b.finishedAt) return 1
    return a.id - b.id
  })
  const hasFinished = sorted.some((m) => m.progress >= 100)
  if (!hasFinished) return sorted
  const medals: NonNullable<LeaderboardMember['medal']>[] = ['gold', 'silver', 'bronze']
  let medalIndex = 0
  return sorted.map((m) => {
    if (m.progress >= 100 && medalIndex < medals.length) {
      return { ...m, medal: medals[medalIndex++] }
    }
    return { ...m, medal: null }
  }) as LeaderboardMember[]
})

const leaderboardCount = computed(() => membersWithMedals.value.length)

function medalLabel(medal: LeaderboardMember['medal']) {
  if (medal === 'gold') return t('dash.medalGold')
  if (medal === 'silver') return t('dash.medalSilver')
  if (medal === 'bronze') return t('dash.medalBronze')
  return ''
}

watch(
  cycle,
  (value) => {
    if (value && value.status !== 'completed') {
      router.replace('/')
    }
  },
  { immediate: true },
)

function cancelEdit() {
  isEditingBook.value = false
}
</script>

<template lang="pug">
main.cycle-detail.container
  section.panel(v-if="cycleQuery.isLoading.value" aria-live="polite")
    p.body-text {{ $t('archiveBook.loading') }}
  section.panel.cycle-detail__missing(v-else-if="cycleQuery.error.value")
    .section-header.section-header--compact
      h1 {{ $t('cycle.notFound') }}
    p.body-text {{ $t('cycle.notFoundText') }}
    RouterLink.button.button--primary.label-text(to="/archive") {{ $t('archiveBook.backToArchive') }}
  template(v-else-if="cycle")
    nav.cycle-detail__breadcrumb.label-text(:aria-label="t('archiveBook.navAria')")
      RouterLink(to="/archive") {{ $t('nav.archive') }}
      span /
      span.cycle-detail__breadcrumb-current {{ cycle.cycleLabel }}

    .cycle-detail__hero
      CollapseTransition(mode="out-in")
        .cycle-detail__edit-panel(v-if="isEditingBook" key="form")
          CycleBookForm(
            :cycle-number="cycle.cycleNumber"
            :book="cycle.book"
            id-prefix="cycle-book"
            @cancel="cancelEdit"
            @saved="isEditingBook = false"
          )
        .cycle-detail__view(v-else key="details")
          .book-cover.cycle-detail__cover(:style="{ backgroundColor: cycle.book.coverColor ?? undefined }" :aria-label="t('archiveBook.coverAria', { title: cycle.book.title })")
            img.cycle-detail__cover-image(v-if="cycle.book.coverUrl" :src="cycle.book.coverUrl" :alt="cycle.book.title")
            .book-cover__content.cycle-detail__cover-title(v-else) {{ cycle.coverTitle }}

          .cycle-detail__info
            .cycle-detail__heading
              span.badge.label-text(:class="cycle.status === 'completed' ? 'badge--done' : 'badge--action'") {{ cycle.statusLabel }}
              button.button.button--secondary.label-text(
                v-if="cycle.canEditBook"
                type="button"
                @click="isEditingBook = true"
              )
                Pencil.cycle-detail__icon
                | {{ $t('cycle.editBook') }}
            .cycle-detail__details-wrapper
              h1.cycle-detail__title {{ cycle.book.title }}
              p.subtitle-italic {{ cycle.book.author }}
              .cycle-detail__genres(v-if="cycle.book.genres?.length")
                span.badge(v-for="g in cycle.book.genres" :key="g.id") {{ g.name }}
              .cycle-detail__meta
                .cycle-detail__meta-item
                  span.label-text.cycle-detail__muted {{ $t('archiveBook.chosenBy') }}
                  .cycle-detail__member
                  template(v-if="cycle.proposedById")
                    RouterLink.member-link(:to="`/members/${cycle.proposedById}`")
                      UserAvatar(:name="cycle.proposedBy" :avatar-url="cycle.proposerAvatarUrl" size="sm")
                      span.label-text {{ cycle.proposedBy }}
                  template(v-else)
                    UserAvatar(:name="cycle.proposedBy" :avatar-url="cycle.proposerAvatarUrl" size="sm")
                    span.label-text {{ cycle.proposedBy }}
                .cycle-detail__meta-item
                  span.label-text.cycle-detail__muted {{ $t('archiveBook.avgRating') }}
                  span.cycle-detail__rating.label-text
                    Star.cycle-detail__icon
                    | {{ cycle.rating.toFixed(1) }}/10
                .cycle-detail__meta-item
                  span.label-text.cycle-detail__muted {{ $t('archiveBook.cycle') }}
                  span.cycle-detail__cycle.label-text
                    CalendarDays.cycle-detail__icon
                    | {{ cycle.cycleLabel }}{{ cycle.completedLabel ? ` · ${cycle.completedLabel}` : '' }}
              p.body-text.cycle-detail__synopsis {{ cycle.book.description }}

    .cycle-detail__content
      section.cycle-detail__main
        section.panel(v-if="cycle.status === 'proposed' && cycle.candidate")
          .section-header
            h2 {{ $t('cycle.verification') }}
            span.label-text {{ cycle.candidate.status }}
          ul.data-list
            li.data-list__item(v-for="response in cycle.candidate.responses" :key="response.id")
              RouterLink.member-link.label-text(:to="`/members/${response.member.id}`") {{ response.member.name }}
              span.label-text {{ response.response }}
          RouterLink.button.button--secondary.label-text(to="/") {{ $t('cycle.openDashboard') }}

        section.panel(v-if="cycle.status === 'active'")
          .section-header
            h2 {{ $t('cycle.progress') }}
            span.label-text {{ cycle.memberProgress.length }}
          ul.data-list
            li.data-list__item(v-for="progress in cycle.memberProgress" :key="progress.id")
              RouterLink.member-link.label-text(:to="`/members/${progress.member.id}`") {{ progress.member.name }}
              span.label-text {{ progress.progressPercent ?? 0 }}%
          RouterLink.button.button--secondary.label-text(to="/") {{ $t('cycle.openDashboard') }}

        template(v-if="cycle.status === 'completed'")
          .section-header
            h2 {{ $t('archiveBook.reviews') }}
            span.label-text {{ $t('archiveBook.reviewN', { n: cycle.reviews.length }) }}
          article.panel.cycle-detail__review(v-for="review in cycle.reviews" :key="`${review.memberName}-${review.rating}`")
            .cycle-detail__review-header
              .cycle-detail__member
                template(v-if="review.memberId")
                  RouterLink.member-link(:to="`/members/${review.memberId}`")
                    UserAvatar(:name="review.memberName" :avatar-url="review.memberAvatarUrl" size="sm")
                    span.label-text {{ review.memberName }}
                template(v-else)
                  UserAvatar(:name="review.memberName" :avatar-url="review.memberAvatarUrl" size="sm")
                  span.label-text {{ review.memberName }}
              span.cycle-detail__rating.label-text {{ review.rating }}/10
            p.body-text {{ review.text }}

          DiscussionBlock(
            :discussion="cycle.discussion ?? []"
            :readonly="true"
          )

      aside.cycle-detail__sidebar
        section.panel
          .section-header.section-header--compact
            span.label-text {{ $t('dash.clubProgress') }}
            span.label-text {{ $t('dash.clubProgressCount', { n: leaderboardCount }) }}
          ul.data-list.cycle-detail__leaderboard
            li.data-list__item.cycle-detail__leaderboard-item(v-for="member in membersWithMedals" :key="member.id")
              .cycle-detail__leaderboard-member
                RouterLink.member-link(:to="`/members/${member.id}`")
                  UserAvatar(:name="member.name" :avatar-url="member.avatarUrl" size="sm")
                  span.label-text {{ member.name }}
                img.cycle-detail__owl(
                  v-if="member.medal"
                  :class="`cycle-detail__owl--${member.medal}`"
                  src="/owl.svg"
                  :alt="medalLabel(member.medal)"
                  :title="medalLabel(member.medal)"
                )
              .cycle-detail__leaderboard-progress
                .progress(:aria-label="`${member.name}: ${member.progress}%`")
                  .progress__bar(:style="{ '--progress-value': `${member.progress}%` }")
                span.label-text {{ member.progress }}%
        section.panel.cycle-detail__meeting(v-if="meeting")
          .section-header.section-header--compact
            span.label-text {{ $t('archiveBook.meetingArchive') }}
            CalendarDays.cycle-detail__icon
          h3.cycle-detail__meeting-title {{ meeting.title }}
          ul.data-list
            li.data-list__item
              span.label-text.cycle-detail__muted {{ $t('archiveBook.date') }}
              span.label-text {{ formatDateLabel(meeting.date ?? undefined) }} · {{ formatTimeLabel(meeting.time ?? undefined) }}
            li.data-list__item
              span.label-text.cycle-detail__muted {{ $t('archiveBook.format') }}
              span.label-text.cycle-detail__meeting-format
                Monitor.cycle-detail__icon(v-if="meeting.isOnline")
                MapPin.cycle-detail__icon(v-else)
                | {{ meeting.isOnline ? $t('archiveBook.online') : meeting.place }}
            li.data-list__item
              span.label-text.cycle-detail__muted {{ $t('archiveBook.participants') }}
              span.label-text {{ meeting.attendingCount }}/{{ meeting.rsvpCount }}
          RouterLink.button.button--secondary.label-text.cycle-detail__meeting-link(:to="`/meetings/${meeting.id}`")
            CalendarDays.cycle-detail__icon
            | {{ $t('archiveBook.openMeeting') }}
        RouterLink.button.button--secondary.label-text.cycle-detail__back(to="/archive")
          ArrowLeft.cycle-detail__icon
          | {{ $t('archiveBook.backBtn') }}
</template>

<style scoped lang="scss">
@use '@/styles/breakpoints' as *;
.cycle-detail__breadcrumb,
.cycle-detail__member,
.cycle-detail__rating,
.cycle-detail__cycle,
.cycle-detail__meeting-format {
  display: inline-flex;
  align-items: center;
  gap: var(--space-sm);
}

.cycle-detail__breadcrumb {
  flex-wrap: wrap;
  margin-bottom: var(--space-lg);
  color: var(--text-muted);
}

.cycle-detail__breadcrumb-current {
  color: var(--text-main);
}

.cycle-detail__hero {
  margin-bottom: var(--space-xl);
  padding: var(--space-lg);
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-panel);
  background: var(--bg-surface);
  box-shadow: var(--shadow-panel);
}

.cycle-detail__view {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--space-xl);

  @include tablet {
    grid-template-columns: 18rem minmax(0, 1fr);
  }
}

.cycle-detail__edit-panel {
  padding-top: var(--space-sm);
}

.cycle-detail__cover {
  position: relative;
  width: min(100%, 14rem);
  max-height: 30rem;
  overflow: hidden;

  @include tablet {
    width: 100%;
  }
}

.cycle-detail__cover-image {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.cycle-detail__cover-title {
  font-size: 1.6rem;
  white-space: pre-line;
}

.cycle-detail__heading,
.cycle-detail__review-header,
.cycle-detail__message-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-md);
}

.cycle-detail__title {
  margin: var(--space-md) 0 var(--space-xs);
}

.cycle-detail__genres {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-xs) var(--space-sm);
  margin: var(--space-md) 0;
}

.cycle-detail__genres .badge {
  font-size: 0.6rem;
  text-transform: none;
  letter-spacing: 0;
}

.cycle-detail__meta {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-lg);
  margin: var(--space-md) 0;
  padding-bottom: var(--space-md);
  border-bottom: var(--border-width) solid var(--border);
}

.cycle-detail__meta-item {
  display: flex;
  flex-direction: column;
  gap: var(--space-sm);
}

.cycle-detail__muted {
  color: var(--text-muted);
}

.cycle-detail__content {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--space-xl);

  @include tablet {
    grid-template-columns: minmax(0, 1fr) minmax(17rem, 22rem);
  }
}

.cycle-detail__main,
.cycle-detail__sidebar,
.cycle-detail__discussion {
  display: flex;
  flex-direction: column;
  gap: var(--space-lg);
}

.cycle-detail__review,
.cycle-detail__prompt {
  padding: var(--space-lg);
}

.cycle-detail__prompt-text {
  margin: var(--space-xs) 0 0;
  font-size: 1.1rem;
}

.cycle-detail__message {
  padding-bottom: var(--space-md);
  border-bottom: var(--border-width) solid var(--border);
}

.cycle-detail__leaderboard {
  margin: 0;
}

.cycle-detail__leaderboard-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-md);
  min-height: 3rem;
  padding: 0.5rem var(--space-md);
}

.cycle-detail__leaderboard-member {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  min-width: 0;
}

.cycle-detail__leaderboard-progress {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  width: 9rem;
}

.cycle-detail__leaderboard-progress .progress {
  flex: 1;
  margin: 0;
}

.cycle-detail__owl {
  width: 1rem;
  height: 1rem;
  margin-left: var(--space-xs);
  vertical-align: middle;
}

.cycle-detail__owl--gold {
  filter: invert(78%) sepia(35%) saturate(800%) hue-rotate(355deg) brightness(95%) contrast(90%);
}

.cycle-detail__owl--silver {
  filter: invert(82%) sepia(8%) saturate(200%) hue-rotate(170deg) brightness(95%);
}

.cycle-detail__owl--bronze {
  filter: invert(68%) sepia(40%) saturate(600%) hue-rotate(345deg) brightness(90%);
}

.cycle-detail__icon {
  width: 1rem;
  height: 1rem;
}

.cycle-detail__meeting-link,
.cycle-detail__back {
  width: 100%;
  justify-content: center;
}
</style>
