<script setup lang="ts">
import { computed, ref, watch } from 'vue'

const props = defineProps<{
  modelValue: number
  isSaving?: boolean
  errorMessage?: string
}>()

const emit = defineEmits<{
  cancel: []
  save: [value: number]
}>()

const progress = ref(props.modelValue)

const normalizedProgress = computed(() => Math.min(100, Math.max(0, Number(progress.value) || 0)))

watch(
  () => props.modelValue,
  (value) => {
    progress.value = value
  },
)

function updateProgress(value: string | number) {
  progress.value = Math.min(100, Math.max(0, Number(value) || 0))
}

function updateFromEvent(event: Event) {
  updateProgress((event.target as HTMLInputElement).value)
}

function submitForm() {
  emit('save', normalizedProgress.value)
}
</script>

<template lang="pug">
form.reading-progress-form(@submit.prevent="submitForm")
  .reading-progress-form__header
    span.label-text Обновить прогресс
    p.reading-progress-form__value
      | Текущий прогресс:
      span {{ normalizedProgress }}%

  .reading-progress-form__slider
    input(
      type="range"
      min="0"
      max="100"
      step="1"
      :value="normalizedProgress"
      :style="{ '--range-value': `${normalizedProgress}%` }"
      aria-label="Прогресс чтения в процентах"
      @input="updateFromEvent"
    )
    .reading-progress-form__scale
      span.label-text 0%
      span.label-text 100%

  label.reading-progress-form__field
    span.label-text Процент
    .reading-progress-form__number
      input.field-control(
        type="number"
        min="0"
        max="100"
        step="1"
        inputmode="numeric"
        :value="normalizedProgress"
        @input="updateFromEvent"
      )
      span.label-text %
  p.body-text.reading-progress-form__hint Укажи прогресс ползунком или введи значение вручную
  p.body-text.reading-progress-form__error(v-if="errorMessage" aria-live="polite") {{ errorMessage }}

  .reading-progress-form__actions
    button.button.button--secondary.label-text(type="button" :disabled="isSaving" @click="emit('cancel')") Отмена
    button.button.button--primary.label-text(type="submit" :disabled="isSaving")
      | {{ isSaving ? 'Сохраняем' : 'Сохранить прогресс' }}
</template>

<style scoped>
.reading-progress-form {
  display: grid;
  gap: var(--space-md);
  margin-top: var(--space-md);
  padding: var(--space-md);
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-inner);
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 0.035), rgba(255, 255, 255, 0.01)),
    var(--bg-surface-2);
}

.reading-progress-form__header {
  display: flex;
  align-items: baseline;
  justify-content: space-between;
  gap: var(--space-md);
}

.reading-progress-form__value {
  margin: 0;
  color: var(--text-main);
  font-size: 1rem;
  font-weight: 500;
}

.reading-progress-form__value span {
  margin-left: 0.35rem;
  color: var(--accent);
  font-variant-numeric: tabular-nums;
}

.reading-progress-form__slider {
  display: grid;
  gap: var(--space-sm);
}

.reading-progress-form__slider input {
  width: 100%;
  height: 1.7rem;
  accent-color: var(--accent);
  cursor: pointer;
}

.reading-progress-form__slider input::-webkit-slider-runnable-track {
  height: 0.34rem;
  border-radius: 999px;
  background: linear-gradient(
    90deg,
    var(--accent) 0 var(--range-value),
    rgba(255, 255, 255, 0.16) var(--range-value) 100%
  );
}

.reading-progress-form__slider input::-webkit-slider-thumb {
  width: 1.1rem;
  height: 1.1rem;
  margin-top: -0.38rem;
  border: 0.18rem solid var(--accent);
  border-radius: 50%;
  background: var(--bg-surface);
  appearance: none;
}

.reading-progress-form__slider input::-moz-range-track {
  height: 0.34rem;
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.16);
}

.reading-progress-form__slider input::-moz-range-progress {
  height: 0.34rem;
  border-radius: 999px;
  background: var(--accent);
}

.reading-progress-form__slider input::-moz-range-thumb {
  width: 0.8rem;
  height: 0.8rem;
  border: 0.18rem solid var(--accent);
  border-radius: 50%;
  background: var(--bg-surface);
}

.reading-progress-form__scale,
.reading-progress-form__actions {
  display: flex;
  justify-content: space-between;
  gap: var(--space-md);
}

.reading-progress-form__field {
  display: grid;
  grid-template-columns: minmax(7rem, 0.35fr) minmax(0, 1fr);
  align-items: center;
  gap: var(--space-md);
  padding: 0.45rem;
  border: var(--border-width) solid var(--border-strong);
  border-radius: var(--radius-inner);
}

.reading-progress-form__number {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 2.5rem;
  overflow: hidden;
  border: var(--border-width) solid var(--border);
  border-radius: calc(var(--radius-inner) - 4px);
}

.reading-progress-form__number .field-control {
  min-height: 2.7rem;
  border: 0;
  border-radius: 0;
}

.reading-progress-form__number span {
  display: grid;
  place-items: center;
  border-left: var(--border-width) solid var(--border);
  background: rgba(255, 255, 255, 0.025);
}

.reading-progress-form__hint {
  margin: calc(var(--space-sm) * -1) 0 0;
  color: var(--text-soft);
}

.reading-progress-form__error {
  margin: 0;
  color: var(--danger);
}

.reading-progress-form__actions .button {
  flex: 1;
}

@media (max-width: 640px) {
  .reading-progress-form__header,
  .reading-progress-form__actions {
    flex-direction: column;
    align-items: stretch;
  }

  .reading-progress-form__field {
    grid-template-columns: 1fr;
    gap: var(--space-sm);
  }
}
</style>
