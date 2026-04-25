<script setup lang="ts">
import { computed, ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { meetingDetail } from '@/data/meetings/meetingDetail'
import type { MeetingDetail } from '@/types/dashboard'

const route = useRoute()

const meeting = computed<MeetingDetail | null>(() => {
  if (route.params.id !== meetingDetail.id) return null
  return meetingDetail
})

const newTopic = ref('')
const rsvpStatus = ref<'attending' | 'not_attending' | 'pending'>(meetingDetail.rsvpStatus)

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
  template(v-if="meeting")
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
          .meeting-detail__hero-section
            span.label-text Дата и время
            h2.meeting-detail__hero-heading {{ meeting.dateLabel }}
            p.subtitle-italic {{ meeting.timeLabel }}

          .meeting-detail__hero-section
            span.label-text Место
            h3 {{ meeting.place }}
            p.body-text(v-if="meeting.placeAddress") {{ meeting.placeAddress }}
            p.body-text(v-if="meeting.placeReservation") {{ meeting.placeReservation }}

          .meeting-detail__hero-section(v-if="meeting.meetingLink")
            span.label-text Ссылка на встречу
            .meeting-detail__link-box
              svg(width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2")
                path(d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71")
                path(d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71")
              span.body-text {{ meeting.meetingLink }}

        .section-header.meeting-detail__topics-header
          h2 Темы для обсуждения

        ol.meeting-detail__topics(v-if="meeting.topics.length")
          li.meeting-detail__topic(v-for="(topic, index) in meeting.topics" :key="`${topic}-${index}`") {{ topic }}

        .meeting-detail__add-topic
          span.label-text Добавить тему
          .meeting-detail__add-topic-row
            input.meeting-detail__input(
              v-model="newTopic"
              type="text"
              placeholder="Предложить вопрос..."
              @keydown.enter.prevent="submitTopic"
            )
            button.button.button--secondary.label-text(type="button" @click="submitTopic") Отправить

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
          ul.data-list
            li.data-list__item(v-for="attendee in meeting.attendees" :key="attendee.initials")
              .meeting-detail__attendee
                span.avatar {{ attendee.initials }}
                span.body-text {{ attendee.name }}

        .panel
          .section-header.section-header--compact
            span.label-text Книга в фокусе
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
  gap: var(--space-lg);
}

.meeting-detail__hero-section {
  display: flex;
  flex-direction: column;
  gap: var(--space-xs);
}

.meeting-detail__hero-heading {
  margin-bottom: 0;
  font-size: 1.6rem;
}

.meeting-detail__link-box {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  padding: var(--space-md);
  border: var(--border-width) solid var(--border);
  background: var(--bg-surface);
}

.meeting-detail__link-box svg {
  flex-shrink: 0;
  color: var(--accent-dim);
}

.meeting-detail__topics-header {
  margin-top: var(--space-xl);
}

.meeting-detail__topics {
  margin-bottom: var(--space-lg);
  padding-left: var(--space-lg);
  color: var(--text-muted);
  font-size: 0.9rem;
  line-height: 1.8;
}

.meeting-detail__topic {
  padding-left: var(--space-sm);
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
  padding: 0.75rem;
  border: var(--border-width) solid var(--border);
  border-radius: 0;
  background: var(--bg-surface);
  color: var(--text-main);
  outline: none;
  transition: border-color 0.2s ease;
}

.meeting-detail__input:focus {
  border-color: var(--text-main);
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
