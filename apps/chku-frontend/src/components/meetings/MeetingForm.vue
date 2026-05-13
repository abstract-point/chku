<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Plus, Trash2 } from '@lucide/vue'
import type { MeetingDetail } from '@/types/dashboard'

const props = defineProps<{
  meeting?: MeetingDetail
  cycleId: number
  isSubmitting: boolean
}>()

const emit = defineEmits<{
  submit: [payload: Record<string, unknown>]
}>()

const title = ref('')
const day = ref<number | null>(null)
const month = ref(new Date().getMonth() + 1)
const year = ref(new Date().getFullYear())
const time = ref('19:00')
const isOnline = ref(false)
const place = ref('')
const address = ref('')
const reservation = ref('')
const link = ref('')
const topics = ref<string[]>([])
const newTopic = ref('')
const notes = ref('')

const months = [
  'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь',
  'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь',
]

const years = computed(() => {
  const current = new Date().getFullYear()
  return [current - 1, current, current + 1]
})

const isEdit = computed(() => props.meeting !== undefined)

const dateString = computed(() => {
  const d = day.value ? String(day.value).padStart(2, '0') : '01'
  const m = String(month.value).padStart(2, '0')
  const y = String(year.value)
  return `${y}-${m}-${d}`
})

watch(
  () => props.meeting,
  (meeting) => {
    if (!meeting) return
    title.value = meeting.title
    time.value = meeting.timeLabel
    isOnline.value = meeting.isOnline
    place.value = meeting.place ?? ''
    address.value = meeting.placeAddress ?? ''
    reservation.value = meeting.placeReservation ?? ''
    link.value = meeting.meetingLink ?? ''
    topics.value = [...meeting.topics]
  },
  { immediate: true },
)

function addTopic() {
  const topic = newTopic.value.trim()
  if (!topic) return
  topics.value = [...topics.value, topic]
  newTopic.value = ''
}

function removeTopic(index: number) {
  topics.value = topics.value.filter((_, i) => i !== index)
}

function onSubmit() {
  const payload: Record<string, unknown> = {
    reading_cycle_id: props.cycleId,
    title: title.value,
    date: dateString.value,
    time: time.value,
    is_online: isOnline.value,
    topics: topics.value.length ? topics.value : undefined,
    notes: notes.value || undefined,
  }

  if (isOnline.value) {
    payload.link = link.value || undefined
  } else {
    payload.place = place.value || undefined
    payload.address = address.value || undefined
    payload.reservation = reservation.value || undefined
    payload.link = link.value || undefined
  }

  emit('submit', payload)
}
</script>

<template lang="pug">
form.meeting-form(@submit.prevent="onSubmit" novalidate)
  .section-header
    h1 {{ isEdit ? 'Редактировать встречу' : 'Новая встреча' }}
    span.label-text Цикл #{{ cycleId }}

  .meeting-form__fields
    fieldset.meeting-form__field
      label.label-text(for="meeting-title") Название
      input#meeting-title.field-control(type="text" v-model="title" placeholder="Ноябрьская встреча" required)

    fieldset.meeting-form__field
      legend.label-text Дата
      .meeting-form__date-row
        select.field-control(v-model.number="day")
          option(:value="null") —
          option(v-for="d in 31" :key="d" :value="d") {{ d }}
        select.field-control(v-model.number="month" required)
          option(v-for="(name, i) in months" :key="i + 1" :value="i + 1") {{ name }}
        select.field-control(v-model.number="year" required)
          option(v-for="y in years" :key="y" :value="y") {{ y }}

    fieldset.meeting-form__field
      label.label-text(for="meeting-time") Время
      input#meeting-time.field-control(type="time" v-model="time" required)

    fieldset.meeting-form__field
      legend.label-text Формат встречи
      .meeting-form__radio-group
        label.meeting-form__radio
          input(type="radio" name="meeting-format" :value="false" v-model="isOnline")
          span Очно
        label.meeting-form__radio
          input(type="radio" name="meeting-format" :value="true" v-model="isOnline")
          span Онлайн

    template(v-if="!isOnline")
      fieldset.meeting-form__field
        label.label-text(for="meeting-place") Место
        input#meeting-place.field-control(type="text" v-model="place" placeholder="Библиотека имени Некрасова" required)
      fieldset.meeting-form__field
        label.label-text(for="meeting-address") Адрес
        input#meeting-address.field-control(type="text" v-model="address" placeholder="ул. Бауманская, 58/25с14")
      fieldset.meeting-form__field
        label.label-text(for="meeting-reservation") Бронь
        input#meeting-reservation.field-control(type="text" v-model="reservation" placeholder="Зал «Сад», стол у окна")

    fieldset.meeting-form__field(v-if="isOnline")
      label.label-text(for="meeting-link") Ссылка
      input#meeting-link.field-control(type="url" v-model="link" placeholder="https://zoom.us/j/..." :required="isOnline")

    fieldset.meeting-form__field
      label.label-text(for="meeting-link-optional") Ссылка (дополнительно)
      input#meeting-link-optional.field-control(type="url" v-model="link" placeholder="https://..." :required="false" v-if="!isOnline")

    fieldset.meeting-form__field
      legend.label-text Темы для обсуждения
      .meeting-form__topics-list(v-if="topics.length")
        .meeting-form__topic(v-for="(topic, index) in topics" :key="index")
          span.meeting-form__topic-text {{ topic }}
          button.meeting-form__topic-remove(type="button" @click="removeTopic(index)" :title="'Удалить тему'")
            Trash2(:size="14")
      .meeting-form__add-topic
        input.field-control(type="text" v-model="newTopic" placeholder="Предложить тему..." @keydown.enter.prevent="addTopic")
        button.button.button--secondary.label-text(type="button" @click="addTopic")
          Plus(:size="16")
          | Добавить

    fieldset.meeting-form__field
      label.label-text(for="meeting-notes") Заметки
      textarea#meeting-notes.field-control.meeting-form__textarea(v-model="notes" placeholder="Дополнительная информация о встрече...")

  button.button.button--primary.label-text.meeting-form__submit(type="submit" :disabled="isSubmitting")
    | {{ isSubmitting ? 'Сохраняем...' : (isEdit ? 'Сохранить изменения' : 'Создать встречу') }}
</template>

<style scoped>
.meeting-form {
  display: grid;
  gap: var(--space-lg);
}

.meeting-form__fields {
  display: grid;
  gap: var(--space-lg);
}

.meeting-form__field {
  border: 0;
  padding: 0;
  display: grid;
  gap: var(--space-sm);
}

.meeting-form__date-row {
  display: grid;
  grid-template-columns: auto 1fr auto;
  gap: var(--space-sm);
}

.meeting-form__radio-group {
  display: flex;
  gap: var(--space-lg);
}

.meeting-form__radio {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  cursor: pointer;
  font-size: 0.95rem;
  color: var(--text-main);
}

.meeting-form__radio input[type="radio"] {
  accent-color: var(--accent);
  width: 1rem;
  height: 1rem;
}

.meeting-form__topics-list {
  display: grid;
  gap: var(--space-sm);
}

.meeting-form__topic {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  padding: var(--space-sm) var(--space-md);
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-inner);
  background: var(--bg-surface);
}

.meeting-form__topic-text {
  flex: 1;
  font-size: 0.9rem;
  color: var(--text-main);
}

.meeting-form__topic-remove {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0.25rem;
  border-radius: 4px;
  color: var(--text-subtle);
}

.meeting-form__topic-remove:hover {
  color: var(--danger);
  background: var(--danger-bg);
}

.meeting-form__add-topic {
  display: flex;
  gap: var(--space-sm);
}

.meeting-form__add-topic .field-control {
  flex: 1;
}

.meeting-form__textarea {
  min-height: 6rem;
}

.meeting-form__submit {
  justify-self: start;
}
</style>
