<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import AppFormField from '@/components/ui/AppFormField.vue'
import AppInput from '@/components/ui/AppInput.vue'
import AppSelect from '@/components/ui/AppSelect.vue'
import AppTextarea from '@/components/ui/AppTextarea.vue'
import AppRadioGroup from '@/components/ui/AppRadioGroup.vue'
import type { MeetingDetail } from '@/types/dashboard'

const { t, tm } = useI18n()

const props = defineProps<{
  meeting?: MeetingDetail
  cycleId: number
  isSubmitting: boolean
  errors?: Record<string, string>
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
const notes = ref('')

const dayOptions = computed(() => [
  { label: '—', value: null as number | null },
  ...Array.from({ length: 31 }, (_, i) => ({ label: String(i + 1), value: i + 1 })),
])

const monthOptions = computed(() =>
  tm('meetings.months').map((name: string, i: number) => ({ label: name, value: i + 1 })),
)

const years = computed(() => {
  const current = new Date().getFullYear()
  return [current - 1, current, current + 1]
})

const yearOptions = computed(() => years.value.map((y) => ({ label: String(y), value: y })))

const formatOptions = computed(() => [
  { label: t('meetings.formOffline'), value: false },
  { label: t('meetings.formOnline'), value: true },
])

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
  },
  { immediate: true },
)

function onSubmit() {
  const payload: Record<string, unknown> = {
    reading_cycle_id: props.cycleId,
    title: title.value,
    date: dateString.value,
    time: time.value,
    is_online: isOnline.value,
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

function fieldError(field: string): string | undefined {
  return props.errors?.[field]
}

function isInvalid(field: string): boolean {
  return !!fieldError(field)
}
</script>

<template lang="pug">
form.meeting-form(@submit.prevent="onSubmit" novalidate)
  .section-header
    h1 {{ isEdit ? $t('meetings.formEdit') : $t('meetings.formNew') }}
    span.label-text {{ $t('meetings.formCycle', { id: cycleId }) }}

  .meeting-form__fields
    AppFormField(:label="t('meetings.formTitle')" label-for="meeting-title" required :error="fieldError('title')")
      AppInput#meeting-title(
        v-model="title"
        required
        :placeholder="t('meetings.formTitlePlaceholder')"
        :aria-invalid="isInvalid('title')"
      )

    .meeting-form__datetime-row
      AppFormField(:label="t('meetings.formDate')" label-for="meeting-day" required :error="fieldError('date')")
        .meeting-form__date-selects
          AppSelect#meeting-day(v-model.number="day" :options="dayOptions")
          AppSelect(v-model.number="month" :options="monthOptions" required)
          AppSelect(v-model.number="year" :options="yearOptions" required)
      AppFormField(:label="t('meetings.formTime')" label-for="meeting-time" required :error="fieldError('time')")
        AppInput#meeting-time(type="time" v-model="time" required :aria-invalid="isInvalid('time')")

    AppFormField(:label="t('meetings.formFormat')")
      AppRadioGroup(name="meeting-format" v-model="isOnline" :options="formatOptions")

    template(v-if="!isOnline")
      AppFormField(:label="t('meetings.formPlace')" label-for="meeting-place" required :error="fieldError('place')")
        AppInput#meeting-place(
          v-model="place"
          required
          :placeholder="t('meetings.formPlacePlaceholder')"
          :aria-invalid="isInvalid('place')"
        )
      AppFormField(:label="t('meetings.formAddress')" label-for="meeting-address" :error="fieldError('address')")
        AppInput#meeting-address(
          v-model="address"
          :placeholder="t('meetings.formAddressPlaceholder')"
          :aria-invalid="isInvalid('address')"
        )
      AppFormField(:label="t('meetings.formReservation')" label-for="meeting-reservation" :error="fieldError('reservation')")
        AppInput#meeting-reservation(
          v-model="reservation"
          :placeholder="t('meetings.formReservationPlaceholder')"
          :aria-invalid="isInvalid('reservation')"
        )

    AppFormField(v-if="isOnline" :label="t('meetings.formLink')" label-for="meeting-link" required :error="fieldError('link')")
      AppInput#meeting-link(
        type="url"
        v-model="link"
        :placeholder="t('meetings.formLink')"
        :required="isOnline"
        :aria-invalid="isInvalid('link')"
      )

    AppFormField(v-if="!isOnline" :label="t('meetings.formLinkOptional')" label-for="meeting-link-optional" :error="fieldError('link')")
      AppInput#meeting-link-optional(
        type="url"
        v-model="link"
        :placeholder="t('meetings.formLink')"
        :aria-invalid="isInvalid('link')"
      )

    AppFormField(:label="t('meetings.formNotes')" label-for="meeting-notes" :error="fieldError('notes')")
      AppTextarea#meeting-notes(
        v-model="notes"
        :placeholder="t('meetings.formNotesPlaceholder')"
        :aria-invalid="isInvalid('notes')"
      )

  button.button.button--primary.label-text.meeting-form__submit(type="submit" :disabled="isSubmitting")
    | {{ isSubmitting ? $t('meetings.formSaving') : (isEdit ? $t('meetings.formSaveChanges') : $t('meetings.formCreate')) }}
</template>

<style scoped lang="scss">
@use '@/styles/breakpoints' as *;

.meeting-form {
  display: grid;
  gap: var(--space-lg);
}

.meeting-form__fields {
  display: grid;
  gap: var(--space-lg);
}

.meeting-form__datetime-row {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--space-md);

  @include tablet {
    grid-template-columns: 2fr 1fr;
  }
}

.meeting-form__date-selects {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: var(--space-sm);

  @include tablet {
    grid-template-columns: auto 1fr auto;
  }
}

.meeting-form__submit {
  justify-self: start;
}
</style>
