<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { KeyRound, UserRound } from '@lucide/vue'
import { useUpdatePasswordMutation, useUpdateProfileMutation } from '@/queries/authQueries'
import { useGenresQuery } from '@/queries/genreQueries'
import { useCurrentUserQuery } from '@/queries/memberQueries'
import TwoFactorSetup from '@/components/TwoFactorSetup.vue'

const currentUserQuery = useCurrentUserQuery()
const genresQuery = useGenresQuery()
const updateProfileMutation = useUpdateProfileMutation()
const updatePasswordMutation = useUpdatePasswordMutation()

const currentMember = computed(() => currentUserQuery.data.value)

const profileForm = reactive({
  name: '',
  initials: '',
  favoriteGenreId: null as number | null,
})

const passwordForm = reactive({
  currentPassword: '',
  password: '',
  passwordConfirmation: '',
})

const profileMessage = ref('')
const passwordMessage = ref('')
const profileError = ref('')
const passwordError = ref('')

watch(
  currentMember,
  (member) => {
    if (!member) return

    profileForm.name = member.name
    profileForm.initials = member.initials
    profileForm.favoriteGenreId = member.favoriteGenreId ?? null
  },
  { immediate: true },
)

function errorMessage(error: unknown, fallback: string) {
  return error instanceof Error ? error.message : fallback
}

async function saveProfile() {
  profileError.value = ''
  profileMessage.value = ''

  try {
    await updateProfileMutation.mutateAsync({
      name: profileForm.name,
      initials: profileForm.initials,
      favorite_genre_id: profileForm.favoriteGenreId,
    })
    profileMessage.value = 'Профиль обновлён.'
  } catch (error) {
    profileError.value = errorMessage(error, 'Не удалось сохранить профиль.')
  }
}

async function savePassword() {
  passwordError.value = ''
  passwordMessage.value = ''

  try {
    await updatePasswordMutation.mutateAsync({
      current_password: passwordForm.currentPassword,
      password: passwordForm.password,
      password_confirmation: passwordForm.passwordConfirmation,
    })
    passwordForm.currentPassword = ''
    passwordForm.password = ''
    passwordForm.passwordConfirmation = ''
    passwordMessage.value = 'Пароль обновлён.'
  } catch (error) {
    passwordError.value = errorMessage(error, 'Не удалось обновить пароль.')
  }
}
</script>

<template lang="pug">
main.profile-settings.container
  .section-header
    h1.profile-settings__title Настройки профиля
    span.label-text Аккаунт и безопасность

  section.panel(v-if="currentUserQuery.isLoading.value" aria-live="polite")
    p.body-text Загружаем настройки...
  section.panel(v-else-if="currentUserQuery.error.value || !currentMember" aria-live="polite")
    p.body-text Не удалось загрузить профиль.
  .profile-settings__grid(v-else)
    form.panel.profile-settings__section(@submit.prevent="saveProfile")
      .section-header.section-header--compact
        h2 Данные профиля
        UserRound.profile-settings__icon
      .profile-settings__group
        label.label-text(for="settings-name") Имя
        input#settings-name.field-control.profile-settings__input(type="text" v-model="profileForm.name" required autocomplete="name")
      .profile-settings__group
        label.label-text(for="settings-initials") Инициалы
        input#settings-initials.field-control.profile-settings__input(type="text" v-model="profileForm.initials" required maxlength="10")
      .profile-settings__group
        label.label-text(for="settings-genre") Любимый жанр
        select#settings-genre.field-control.profile-settings__input(v-model="profileForm.favoriteGenreId" :disabled="genresQuery.isLoading.value")
          option(:value="null") Не выбран
          option(v-for="genre in genresQuery.data.value ?? []" :key="genre.id" :value="genre.id") {{ genre.name }}
      p.profile-settings__message(v-if="profileMessage") {{ profileMessage }}
      p.profile-settings__error(v-if="profileError") {{ profileError }}
      button.button.button--primary.label-text.profile-settings__submit(type="submit" :disabled="updateProfileMutation.isPending.value")
        | {{ updateProfileMutation.isPending.value ? 'Сохранение...' : 'Сохранить профиль' }}

    form.panel.profile-settings__section(@submit.prevent="savePassword")
      .section-header.section-header--compact
        h2 Пароль
        KeyRound.profile-settings__icon
      .profile-settings__group
        label.label-text(for="settings-current-password") Текущий пароль
        input#settings-current-password.field-control.profile-settings__input(type="password" v-model="passwordForm.currentPassword" required autocomplete="current-password")
      .profile-settings__group
        label.label-text(for="settings-password") Новый пароль
        input#settings-password.field-control.profile-settings__input(type="password" v-model="passwordForm.password" required minlength="8" autocomplete="new-password")
      .profile-settings__group
        label.label-text(for="settings-password-confirmation") Повтор нового пароля
        input#settings-password-confirmation.field-control.profile-settings__input(type="password" v-model="passwordForm.passwordConfirmation" required minlength="8" autocomplete="new-password")
      p.profile-settings__message(v-if="passwordMessage") {{ passwordMessage }}
      p.profile-settings__error(v-if="passwordError") {{ passwordError }}
      button.button.button--secondary.label-text.profile-settings__submit(type="submit" :disabled="updatePasswordMutation.isPending.value")
        | {{ updatePasswordMutation.isPending.value ? 'Обновление...' : 'Обновить пароль' }}

    TwoFactorSetup.profile-settings__section--wide
</template>

<style scoped>
.profile-settings__title {
  font-size: clamp(1.8rem, 4vw, 2.2rem);
}

.profile-settings__grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: var(--space-lg);
}

.profile-settings__section {
  display: flex;
  flex-direction: column;
}

.profile-settings__icon {
  width: 1rem;
  height: 1rem;
  color: var(--text-subtle);
}

.profile-settings__section--wide {
  grid-column: 1 / -1;
}

.profile-settings__group {
  display: flex;
  flex-direction: column;
  gap: var(--space-xs);
  margin-bottom: var(--space-md);
}

.profile-settings__input {
  width: 100%;
  padding: 0.75rem 0.9rem;
}

.profile-settings__submit {
  width: 100%;
  margin-top: auto;
}

.profile-settings__message,
.profile-settings__error {
  padding: var(--space-sm) var(--space-md);
  border-radius: var(--radius-inner);
  margin-bottom: var(--space-md);
  font-size: 0.85rem;
}

.profile-settings__message {
  border: var(--border-width) solid var(--accent-border);
  background: var(--accent-bg);
  color: var(--accent);
}

.profile-settings__error {
  border: var(--border-width) solid rgba(224, 95, 95, 0.28);
  background: var(--danger-bg);
  color: var(--danger);
}

@media (max-width: 760px) {
  .profile-settings__grid {
    grid-template-columns: 1fr;
  }
}
</style>
