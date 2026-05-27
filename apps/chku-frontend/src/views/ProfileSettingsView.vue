<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { KeyRound, UserRound } from '@lucide/vue'
import FilePicker from '@/components/ui/FilePicker.vue'
import AppFormField from '@/components/ui/AppFormField.vue'
import AppInput from '@/components/ui/AppInput.vue'
import AppSelect from '@/components/ui/AppSelect.vue'
import {
  useUpdateAvatarMutation,
  useUpdatePasswordMutation,
  useUpdateProfileMutation,
} from '@/queries/authQueries'
import { useGenresQuery } from '@/queries/genreQueries'
import { useCurrentUserQuery } from '@/queries/memberQueries'
import { useFormErrors } from '@/composables/useFormErrors'
import TwoFactorSetup from '@/components/TwoFactorSetup.vue'

const { t } = useI18n()
const currentUserQuery = useCurrentUserQuery()
const genresQuery = useGenresQuery()
const updateProfileMutation = useUpdateProfileMutation()
const updateAvatarMutation = useUpdateAvatarMutation()
const updatePasswordMutation = useUpdatePasswordMutation()

const currentMember = computed(() => currentUserQuery.data.value)

const profileForm = reactive({
  name: '',
  email: '',
  favoriteGenreId: null as number | null,
})

const passwordForm = reactive({
  currentPassword: '',
  password: '',
  passwordConfirmation: '',
})

const profileMessage = ref('')
const passwordMessage = ref('')
const profileErrors = useFormErrors()
const passwordErrors = useFormErrors()
const avatarFile = ref<File | null>(null)

const genreOptions = computed(() => [
  { label: t('settings.notSelected'), value: null as number | null },
  ...(genresQuery.data.value?.map((g) => ({ label: g.name, value: g.id })) ?? []),
])

watch(
  currentMember,
  (member) => {
    if (!member) return

    profileForm.name = member.name
    profileForm.email = member.email
    profileForm.favoriteGenreId = member.favoriteGenreId ?? null
  },
  { immediate: true },
)

async function saveProfile() {
  profileErrors.clearAllErrors()
  profileMessage.value = ''

  try {
    await updateProfileMutation.mutateAsync({
      name: profileForm.name,
      email: profileForm.email,
      favorite_genre_id: profileForm.favoriteGenreId,
    })

    if (avatarFile.value) {
      await updateAvatarMutation.mutateAsync(avatarFile.value)
      avatarFile.value = null
    }

    profileMessage.value = t('settings.updated')
  } catch (error) {
    profileErrors.setFromApiError(error)
  }
}

async function savePassword() {
  passwordErrors.clearAllErrors()
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
    passwordMessage.value = t('settings.passwordUpdated')
  } catch (error) {
    passwordErrors.setFromApiError(error)
  }
}
</script>

<template lang="pug">
main.profile-settings.container
  .section-header
    h1.profile-settings__title {{ $t('settings.title') }}
    span.label-text {{ $t('settings.subtitle') }}

  section.panel(v-if="currentUserQuery.isLoading.value" aria-live="polite")
    p.body-text {{ $t('settings.loading') }}
  section.panel(v-else-if="currentUserQuery.error.value || !currentMember" aria-live="polite")
    p.body-text {{ $t('settings.error') }}
  .profile-settings__grid(v-else)
    form.panel.profile-settings__section(@submit.prevent="saveProfile")
      .section-header.section-header--compact
        h2 {{ $t('settings.profileData') }}
        UserRound.profile-settings__icon
      AppFormField(:label="t('settings.avatar')" label-for="settings-avatar" :error="profileErrors.getError('avatar')")
        FilePicker#settings-avatar(
          v-model="avatarFile"
          variant="avatar"
          :existing-url="currentMember.avatarUrl"
          :name="profileForm.name || currentMember.name"
        )
      AppFormField(:label="t('settings.name')" label-for="settings-name" required :error="profileErrors.getError('name')")
        AppInput#settings-name(
          type="text"
          v-model="profileForm.name"
          required
          autocomplete="name"
          :aria-invalid="profileErrors.hasError('name')"
        )
      AppFormField(:label="t('settings.email')" label-for="settings-email" required :error="profileErrors.getError('email')")
        AppInput#settings-email(
          type="email"
          v-model="profileForm.email"
          required
          autocomplete="email"
          :aria-invalid="profileErrors.hasError('email')"
        )
      AppFormField(:label="t('settings.favGenre')" label-for="settings-genre" :error="profileErrors.getError('favorite_genre_id')")
        AppSelect#settings-genre(
          v-model="profileForm.favoriteGenreId"
          :options="genreOptions"
          :disabled="genresQuery.isLoading.value"
          :aria-invalid="profileErrors.hasError('favorite_genre_id')"
        )
      p.profile-settings__message(v-if="profileMessage") {{ profileMessage }}
      p.profile-settings__error(v-if="updateProfileMutation.error.value && !Object.keys(profileErrors.fieldErrors.value).length")
        | {{ updateProfileMutation.error.value.message }}
      button.button.button--primary.label-text.profile-settings__submit(
        type="submit"
        :disabled="updateProfileMutation.isPending.value || updateAvatarMutation.isPending.value"
      )
        | {{ updateProfileMutation.isPending.value || updateAvatarMutation.isPending.value ? $t('settings.saving') : $t('settings.saveProfile') }}

    form.panel.profile-settings__section(@submit.prevent="savePassword")
      .section-header.section-header--compact
        h2 {{ $t('settings.passwordSection') }}
        KeyRound.profile-settings__icon
      AppFormField(:label="t('settings.currentPassword')" label-for="settings-current-password" required :error="passwordErrors.getError('current_password')")
        AppInput#settings-current-password(
          type="password"
          v-model="passwordForm.currentPassword"
          required
          autocomplete="current-password"
          :aria-invalid="passwordErrors.hasError('current_password')"
        )
      AppFormField(:label="t('settings.newPassword')" label-for="settings-password" required :error="passwordErrors.getError('password')")
        AppInput#settings-password(
          type="password"
          v-model="passwordForm.password"
          required
          minlength="8"
          autocomplete="new-password"
          :aria-invalid="passwordErrors.hasError('password')"
        )
      AppFormField(:label="t('settings.confirmPassword')" label-for="settings-password-confirmation" required :error="passwordErrors.getError('password_confirmation')")
        AppInput#settings-password-confirmation(
          type="password"
          v-model="passwordForm.passwordConfirmation"
          required
          minlength="8"
          autocomplete="new-password"
          :aria-invalid="passwordErrors.hasError('password_confirmation')"
        )
      p.profile-settings__message(v-if="passwordMessage") {{ passwordMessage }}
      p.profile-settings__error(v-if="updatePasswordMutation.error.value && !Object.keys(passwordErrors.fieldErrors.value).length")
        | {{ updatePasswordMutation.error.value.message }}
      button.button.button--secondary.label-text.profile-settings__submit(type="submit" :disabled="updatePasswordMutation.isPending.value")
        | {{ updatePasswordMutation.isPending.value ? $t('settings.updating') : $t('settings.updatePassword') }}

    TwoFactorSetup.profile-settings__section--wide
</template>

<style scoped lang="scss">
@use '@/styles/breakpoints' as *;

.profile-settings__title {
  font-size: clamp(1.8rem, 4vw, 2.2rem);
}

.profile-settings__grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--space-lg);

  @include tablet {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

.profile-settings__section {
  display: flex;
  flex-direction: column;
  gap: var(--space-md);
}

.profile-settings__icon {
  width: 1rem;
  height: 1rem;
  color: var(--text-subtle);
}

.profile-settings__section--wide {
  grid-column: 1 / -1;
}

.profile-settings__submit {
  width: 100%;
  margin-top: auto;
}

.profile-settings__message,
.profile-settings__error {
  padding: var(--space-sm) var(--space-md);
  border-radius: var(--radius-inner);
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
</style>
