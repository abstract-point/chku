<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { useQueryClient } from '@tanstack/vue-query'
import { authApi } from '@/api/endpoints/auth'
import { useGenresQuery } from '@/queries/genreQueries'
import { useCurrentUserQuery } from '@/queries/memberQueries'
import { queryKeys } from '@/queries/keys'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const queryClient = useQueryClient()
const currentUserQuery = useCurrentUserQuery()
const genresQuery = useGenresQuery()

const currentMember = computed(() => currentUserQuery.data.value)
const isTwoFactorEnabled = computed(() => auth.twoFactorEnabled)

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

const twoFactorCode = ref('')
const qrCodeSvg = ref('')
const secretKey = ref('')
const recoveryCodes = ref<string[]>([])
const isProfileSaving = ref(false)
const isPasswordSaving = ref(false)
const isTwoFactorBusy = ref(false)
const profileMessage = ref('')
const passwordMessage = ref('')
const twoFactorMessage = ref('')
const profileError = ref('')
const passwordError = ref('')
const twoFactorError = ref('')

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

async function refreshUser() {
  await auth.fetchUser()
  await queryClient.invalidateQueries({ queryKey: queryKeys.currentUser })
}

async function saveProfile() {
  profileError.value = ''
  profileMessage.value = ''
  isProfileSaving.value = true

  try {
    await authApi.updateProfile({
      name: profileForm.name,
      initials: profileForm.initials,
      favorite_genre_id: profileForm.favoriteGenreId,
    })
    await refreshUser()
    profileMessage.value = 'Профиль обновлён.'
  } catch (error) {
    profileError.value = errorMessage(error, 'Не удалось сохранить профиль.')
  } finally {
    isProfileSaving.value = false
  }
}

async function savePassword() {
  passwordError.value = ''
  passwordMessage.value = ''
  isPasswordSaving.value = true

  try {
    await authApi.updatePassword({
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
  } finally {
    isPasswordSaving.value = false
  }
}

async function loadTwoFactorSetup() {
  const [qrCode, secret] = await Promise.all([authApi.twoFactorQrCode(), authApi.twoFactorSecretKey()])
  qrCodeSvg.value = qrCode.svg
  secretKey.value = secret.secretKey
}

async function enableTwoFactor() {
  twoFactorError.value = ''
  twoFactorMessage.value = ''
  isTwoFactorBusy.value = true

  try {
    await authApi.enableTwoFactor()
    await loadTwoFactorSetup()
    twoFactorMessage.value = 'Отсканируй QR-код и введи код подтверждения.'
  } catch (error) {
    twoFactorError.value = errorMessage(error, 'Не удалось начать настройку 2FA.')
  } finally {
    isTwoFactorBusy.value = false
  }
}

async function confirmTwoFactor() {
  twoFactorError.value = ''
  twoFactorMessage.value = ''
  isTwoFactorBusy.value = true

  try {
    await authApi.confirmTwoFactorSetup(twoFactorCode.value)
    recoveryCodes.value = await authApi.twoFactorRecoveryCodes()
    qrCodeSvg.value = ''
    secretKey.value = ''
    twoFactorCode.value = ''
    await refreshUser()
    twoFactorMessage.value = '2FA включена. Сохрани резервные коды.'
  } catch (error) {
    twoFactorError.value = errorMessage(error, 'Код подтверждения не подошёл.')
  } finally {
    isTwoFactorBusy.value = false
  }
}

async function loadRecoveryCodes() {
  twoFactorError.value = ''
  isTwoFactorBusy.value = true

  try {
    recoveryCodes.value = await authApi.twoFactorRecoveryCodes()
  } catch (error) {
    twoFactorError.value = errorMessage(error, 'Не удалось загрузить резервные коды.')
  } finally {
    isTwoFactorBusy.value = false
  }
}

async function regenerateRecoveryCodes() {
  twoFactorError.value = ''
  twoFactorMessage.value = ''
  isTwoFactorBusy.value = true

  try {
    await authApi.regenerateTwoFactorRecoveryCodes()
    recoveryCodes.value = await authApi.twoFactorRecoveryCodes()
    twoFactorMessage.value = 'Резервные коды обновлены.'
  } catch (error) {
    twoFactorError.value = errorMessage(error, 'Не удалось обновить резервные коды.')
  } finally {
    isTwoFactorBusy.value = false
  }
}

async function disableTwoFactor() {
  twoFactorError.value = ''
  twoFactorMessage.value = ''
  isTwoFactorBusy.value = true

  try {
    await authApi.disableTwoFactor()
    recoveryCodes.value = []
    qrCodeSvg.value = ''
    secretKey.value = ''
    await refreshUser()
    twoFactorMessage.value = '2FA выключена.'
  } catch (error) {
    twoFactorError.value = errorMessage(error, 'Не удалось выключить 2FA.')
  } finally {
    isTwoFactorBusy.value = false
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
      .profile-settings__group
        label.label-text(for="settings-name") Имя
        input#settings-name.profile-settings__input(type="text" v-model="profileForm.name" required autocomplete="name")
      .profile-settings__group
        label.label-text(for="settings-initials") Инициалы
        input#settings-initials.profile-settings__input(type="text" v-model="profileForm.initials" required maxlength="10")
      .profile-settings__group
        label.label-text(for="settings-genre") Любимый жанр
        select#settings-genre.profile-settings__input(v-model="profileForm.favoriteGenreId" :disabled="genresQuery.isLoading.value")
          option(:value="null") Не выбран
          option(v-for="genre in genresQuery.data.value ?? []" :key="genre.id" :value="genre.id") {{ genre.name }}
      p.profile-settings__message(v-if="profileMessage") {{ profileMessage }}
      p.profile-settings__error(v-if="profileError") {{ profileError }}
      button.button.button--primary.label-text.profile-settings__submit(type="submit" :disabled="isProfileSaving")
        | {{ isProfileSaving ? 'Сохранение...' : 'Сохранить профиль' }}

    form.panel.profile-settings__section(@submit.prevent="savePassword")
      .section-header.section-header--compact
        h2 Пароль
      .profile-settings__group
        label.label-text(for="settings-current-password") Текущий пароль
        input#settings-current-password.profile-settings__input(type="password" v-model="passwordForm.currentPassword" required autocomplete="current-password")
      .profile-settings__group
        label.label-text(for="settings-password") Новый пароль
        input#settings-password.profile-settings__input(type="password" v-model="passwordForm.password" required minlength="8" autocomplete="new-password")
      .profile-settings__group
        label.label-text(for="settings-password-confirmation") Повтор нового пароля
        input#settings-password-confirmation.profile-settings__input(type="password" v-model="passwordForm.passwordConfirmation" required minlength="8" autocomplete="new-password")
      p.profile-settings__message(v-if="passwordMessage") {{ passwordMessage }}
      p.profile-settings__error(v-if="passwordError") {{ passwordError }}
      button.button.button--secondary.label-text.profile-settings__submit(type="submit" :disabled="isPasswordSaving")
        | {{ isPasswordSaving ? 'Обновление...' : 'Обновить пароль' }}

    section.panel.profile-settings__section.profile-settings__section--wide(aria-labelledby="two-factor-title")
      .section-header.section-header--compact
        h2#two-factor-title Двухфакторная защита
        span.badge.label-text(:class="isTwoFactorEnabled ? 'badge--reading' : 'badge--action'")
          | {{ isTwoFactorEnabled ? 'Включена' : 'Выключена' }}

      p.body-text(v-if="!isTwoFactorEnabled")
        | Для Админа и Разработчика 2FA обязательна перед управлением участниками.
      p.body-text(v-else)
        | При входе система будет запрашивать одноразовый код из приложения-аутентификатора.

      .profile-settings__two-factor(v-if="!isTwoFactorEnabled")
        button.button.button--primary.label-text(type="button" :disabled="isTwoFactorBusy" @click="enableTwoFactor")
          | {{ qrCodeSvg ? 'Обновить QR-код' : 'Включить 2FA' }}
        .profile-settings__setup(v-if="qrCodeSvg")
          .profile-settings__qr(v-html="qrCodeSvg")
          .profile-settings__secret
            span.label-text Ручной ключ
            code {{ secretKey }}
          .profile-settings__group
            label.label-text(for="settings-2fa-code") Код из приложения
            input#settings-2fa-code.profile-settings__input(type="text" v-model="twoFactorCode" required autocomplete="one-time-code" inputmode="numeric" pattern="[0-9]*")
          button.button.button--secondary.label-text(type="button" :disabled="isTwoFactorBusy || !twoFactorCode" @click="confirmTwoFactor")
            | Подтвердить 2FA

      .profile-settings__two-factor(v-else)
        .profile-settings__actions
          button.button.button--secondary.label-text(type="button" :disabled="isTwoFactorBusy" @click="loadRecoveryCodes") Показать резервные коды
          button.button.button--secondary.label-text(type="button" :disabled="isTwoFactorBusy" @click="regenerateRecoveryCodes") Обновить коды
          button.button.button--ghost.label-text(type="button" :disabled="isTwoFactorBusy" @click="disableTwoFactor") Выключить 2FA

      ul.profile-settings__codes(v-if="recoveryCodes.length" role="list" aria-label="Резервные коды")
        li(v-for="code in recoveryCodes" :key="code")
          code {{ code }}

      p.profile-settings__message(v-if="twoFactorMessage") {{ twoFactorMessage }}
      p.profile-settings__error(v-if="twoFactorError") {{ twoFactorError }}
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
  padding: 0.75rem;
  border: var(--border-width) solid var(--border);
  border-radius: 0;
  background: var(--bg-base);
  color: var(--text-main);
  outline: none;
}

.profile-settings__input:focus {
  border-color: var(--text-main);
}

.profile-settings__submit {
  width: 100%;
  margin-top: auto;
}

.profile-settings__message,
.profile-settings__error {
  margin-bottom: var(--space-md);
  font-size: 0.85rem;
}

.profile-settings__message {
  color: var(--accent-dim);
}

.profile-settings__error {
  color: var(--warn);
}

.profile-settings__two-factor {
  display: grid;
  gap: var(--space-md);
  margin-top: var(--space-lg);
}

.profile-settings__setup {
  display: grid;
  grid-template-columns: auto minmax(0, 1fr);
  gap: var(--space-lg);
  align-items: start;
}

.profile-settings__qr {
  width: 11rem;
  padding: var(--space-sm);
  border: var(--border-width) solid var(--border);
  background: #ffffff;
}

.profile-settings__secret {
  display: grid;
  gap: var(--space-xs);
  align-self: center;
}

.profile-settings__secret code,
.profile-settings__codes code {
  color: var(--text-main);
  font-size: 0.8rem;
  overflow-wrap: anywhere;
}

.profile-settings__actions {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-md);
}

.profile-settings__codes {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(12rem, 1fr));
  gap: var(--space-sm);
  margin: var(--space-lg) 0 0;
  list-style: none;
}

.profile-settings__codes li {
  padding: var(--space-sm);
  border: var(--border-width) solid var(--border);
  background: var(--bg-panel);
}

@media (max-width: 760px) {
  .profile-settings__grid,
  .profile-settings__setup {
    grid-template-columns: 1fr;
  }

  .profile-settings__qr {
    width: 100%;
    max-width: 14rem;
  }
}
</style>
