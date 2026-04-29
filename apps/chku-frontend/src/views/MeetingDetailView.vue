<script setup lang="ts">
import { computed, ref, watchEffect } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { BookOpen, CalendarDays, Link as LinkIcon, MapPin, Send, Users } from '@lucide/vue'
import { useMeetingQuery } from '@/queries/meetingQueries'

const route = useRoute()
const meetingId = computed(() => String(route.params.id ?? ''))
const meetingQuery = useMeetingQuery(meetingId)
const meeting = computed(() => meetingQuery.data.value)
const newTopic = ref('')
const rsvpStatus = ref<'attending' | 'not_attending' | 'pending'>('pending')

watchEffect(() => {
  rsvpStatus.value = meeting.value?.rsvpStatus ?? 'pending'
})

function setRsvp(status: 'attending' | 'not_attending') {
  rsvpStatus.value = status
  // TODO: отправить запрос на бэкенд для сохранения RSVP
}

function submitTopic() {
  const topic = newTopic.value.trim()
  if (!topic || !meeting.value) return

  meeting.value.topics.push(topic)
  newTopic.value = ''

  // TODO: отправить запрос на бэкенд для сохранения темы
}
</script>

<template lang="pug">
main.meeting-detail.container
  section.panel(v-if="meetingQuery.isLoading.value" aria-live="polite")
    p.body-text Загружаем встречу...
  section.panel.meeting-detail__missing(v-else-if="meetingQuery.error.value")
    .section-header.section-header--compact
      h1 Встреча не найдена
    p.body-text Возможно, ссылка устарела или встреча ещё не назначена.
    RouterLink.button.button--primary.label-text(to="/") Вернуться на дашборд
  template(v-else-if="meeting")
    nav.meeting-detail__breadcrumb.label-text(aria-label="Навигация")
      RouterLink(to="/") Дашборд
      span /
      span {{ meeting.cycleLabel }}
      span /
      span.meeting-detail__breadcrumb-current {{ meeting.title }}

    .section-header
      h1.meeting-detail__title {{ meeting.title }}
      span.label-text {{ meeting.cycleLabel }}

    .meeting-detail__grid
      .meeting-detail__main
        .panel.meeting-detail__hero
          .meeting-detail__hero-section.meeting-detail__hero-section--primary
            CalendarDays.meeting-detail__hero-icon
            div
              span.label-text Дата и время
              h2.meeting-detail__hero-heading {{ meeting.dateLabel }}
              p.subtitle-italic {{ meeting.timeLabel }}

          .meeting-detail__hero-section
            MapPin.meeting-detail__hero-icon
            div
              span.label-text Место
              h3 {{ meeting.place }}
              p.body-text(v-if="meeting.placeAddress") {{ meeting.placeAddress }}
              p.body-text(v-if="meeting.placeReservation") {{ meeting.placeReservation }}

          .meeting-detail__hero-section(v-if="meeting.meetingLink")
            LinkIcon.meeting-detail__hero-icon
            div
              span.label-text Ссылка на встречу
              .meeting-detail__link-box
                LinkIcon
                span.body-text {{ meeting.meetingLink }}

        .section-header.meeting-detail__topics-header
          h2 Темы для обсуждения

        ol.meeting-detail__topics(v-if="meeting.topics.length")
          li.meeting-detail__topic(v-for="(topic, index) in meeting.topics" :key="`${topic}-${index}`") {{ topic }}

        .meeting-detail__add-topic
          span.label-text Добавить тему
          .meeting-detail__add-topic-row
            input.meeting-detail__input(
              class="field-control"
              v-model="newTopic"
              type="text"
              placeholder="Предложить вопрос..."
              @keydown.enter.prevent="submitTopic"
            )
            button.button.button--secondary.label-text(type="button" @click="submitTopic")
              Send.meeting-detail__button-icon
              | Отправить

      aside.meeting-detail__sidebar(aria-label="Сводка встречи")
        .panel
          .section-header.section-header--compact
            span.label-text Ваш RSVP
          .meeting-detail__rsvp
            button.button.label-text(
              type="button"
              :class="rsvpStatus === 'attending' ? 'button--primary' : 'button--secondary'"
              @click="setRsvp('attending')"
            ) Буду
            button.button.label-text(
              type="button"
              :class="rsvpStatus === 'not_attending' ? 'button--primary' : 'button--secondary'"
              @click="setRsvp('not_attending')"
            ) Не смогу

        .panel
          .section-header.section-header--compact
            span.label-text Участники ({{ meeting.attendees.length }})
            Users.meeting-detail__button-icon
          ul.data-list
            li.data-list__item(v-for="attendee in meeting.attendees" :key="attendee.initials")
              .meeting-detail__attendee
                span.avatar {{ attendee.initials }}
                span.body-text {{ attendee.name }}

        .panel
          .section-header.section-header--compact
            span.label-text Книга в фокусе
            BookOpen.meeting-detail__button-icon
          h3.meeting-detail__book-title {{ meeting.book.title }}
          p.subtitle-italic {{ meeting.book.author }}
          RouterLink.button.button--ghost.label-text(
            v-if="meeting.book.cycleSlug"
            :to="`/archive/${meeting.book.cycleSlug}`"
          ) Подробности о цикле

  section.panel.meeting-detail__missing(v-else)
    .section-header.section-header--compact
      h1 Встреча не найдена
    p.body-text Возможно, ссылка устарела или встреча ещё не назначена.
    RouterLink.button.button--primary.label-text(to="/") Вернуться на дашборд
</template>

<style scoped>
.meeting-detail__breadcrumb {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: var(--space-sm);
  margin-bottom: var(--space-lg);
  color: var(--text-muted);
}

.meeting-detail__breadcrumb a:hover,
.meeting-detail__breadcrumb-current {
  color: var(--text-main);
}

.meeting-detail__title {
  margin-bottom: 0;
}

.meeting-detail__grid {
  display: grid;
  grid-template-columns: minmax(0, 2fr) minmax(18rem, 1fr);
  gap: var(--space-xl);
  align-items: start;
}

.meeting-detail__hero {
  display: flex;
  flex-direction: column;
  gap: var(--space-md);
}

.meeting-detail__hero-section {
  display: flex;
  align-items: flex-start;
  gap: var(--space-md);
  padding: var(--space-md);
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-inner);
  background: var(--bg-panel);
}

.meeting-detail__hero-section--primary {
  border-color: var(--accent-border);
  background:
    linear-gradient(180deg, rgba(67, 224, 125, 0.07), rgba(67, 224, 125, 0.018)),
    var(--bg-panel);
}

.meeting-detail__hero-icon,
.meeting-detail__button-icon {
  flex: 0 0 auto;
  width: 1rem;
  height: 1rem;
  color: var(--text-subtle);
}

.meeting-detail__hero-section--primary .meeting-detail__hero-icon {
  color: var(--accent);
}

.meeting-detail__hero-heading {
  margin-bottom: 0;
  font-size: clamp(1.8rem, 4vw, 2.4rem);
}

.meeting-detail__link-box {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  padding: var(--space-md);
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-inner);
  background: var(--bg-surface);
  margin-top: var(--space-sm);
}

.meeting-detail__link-box > svg {
  flex-shrink: 0;
  color: var(--accent-dim);
  width: 1rem;
  height: 1rem;
}

.meeting-detail__topics-header {
  margin-top: var(--space-xl);
}

.meeting-detail__topics {
  display: grid;
  gap: var(--space-sm);
  margin-bottom: var(--space-lg);
  padding-left: 0;
  color: var(--text-muted);
  font-size: 0.9rem;
  line-height: 1.6;
  list-style: none;
}

.meeting-detail__topic {
  padding: var(--space-md);
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-inner);
  background: var(--bg-surface);
}

.meeting-detail__add-topic {
  display: flex;
  flex-direction: column;
  gap: var(--space-sm);
}

.meeting-detail__add-topic-row {
  display: flex;
  gap: var(--space-sm);
}

.meeting-detail__input {
  flex: 1;
  padding: 0.75rem 0.9rem;
}

.meeting-detail__sidebar {
  display: flex;
  flex-direction: column;
  gap: var(--space-lg);
}

.meeting-detail__rsvp {
  display: flex;
  flex-direction: column;
  gap: var(--space-sm);
}

.meeting-detail__attendee {
  display: inline-flex;
  align-items: center;
  gap: var(--space-sm);
}

.meeting-detail__book-title {
  margin-bottom: var(--space-xs);
  font-size: 1.1rem;
}

.meeting-detail__missing {
  max-width: 36rem;
}

.meeting-detail__missing .button {
  margin-top: var(--space-md);
}

@media (max-width: 960px) {
  .meeting-detail__grid {
    grid-template-columns: 1fr;
  }
}
</style>
