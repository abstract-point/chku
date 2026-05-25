<script setup lang="ts">
import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { KeyRound, UserRound } from '@lucide/vue'
import UserAvatar from '@/components/UserAvatar.vue'
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
const avatarPreviewUrl = ref<string | null>(null)

const genreOptions = computed(() => [
  { label: t('settings.notSelected'), value: null as number | null },
  ...(genresQuery.data.value?.map((g) => ({ label: g.name, value: g.id })) ?? []),
])

watch(
  currentMember,
  (member) => {
    if (!member) return

    profileForm.name = member.name
    profileForm.favoriteGenreId = member.favoriteGenreId ?? null
  },
  { immediate: true },
)

watch(avatarFile, (file, _oldFile, onCleanup) => {
  if (!file) {
    avatarPreviewUrl.value = null
    return
  }

  const url = URL.createObjectURL(file)
  avatarPreviewUrl.value = url
  onCleanup(() => URL.revokeObjectURL(url))
})

onBeforeUnmount(() => {
  if (avatarPreviewUrl.value) {
    URL.revokeObjectURL(avatarPreviewUrl.value)
  }
})

async function saveProfile() {
  profileErrors.clearAllErrors()
  profileMessage.value = ''

  try {
    await updateProfileMutation.mutateAsync({
      name: profileForm.name,
      favorite_genre_id: profileForm.favoriteGenreId,
    })

    if (avatarFile.value) {
      await updateAvatarMutation.mutateAsync(avatarFile.value)
      avatarFile.value = null
      avatarPreviewUrl.value = null
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

function selectAvatar(event: Event) {
  const input = event.target as HTMLInputElement
  avatarFile.value = input.files?.[0] ?? null
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
      .profile-settings__avatar
        UserAvatar(
          :name="profileForm.name || currentMember.name"
          :avatar-url="avatarPreviewUrl ?? currentMember.avatarUrl"
          size="lg"
        )
        AppFormField(:label="t('settings.avatar')" label-for="settings-avatar" :error="profileErrors.getError('avatar')")
          AppInput#settings-avatar(
            type="file"
            accept="image/jpeg,image/png,image/webp"
            @change="selectAvatar"
            :aria-invalid="profileErrors.hasError('avatar')"
          )
      AppFormField(:label="t('settings.name')" label-for="settings-name" required :error="profileErrors.getError('name')")
        AppInput#settings-name(
          type="text"
          v-model="profileForm.name"
          required
          autocomplete="name"
          :aria-invalid="profileErrors.hasError('name')"
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

.profile-settings__avatar {
  display: flex;
  align-items: center;
  gap: var(--space-md);
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

@media (max-width: 760px) {
  .profile-settings__grid {
    grid-template-columns: 1fr;
  }
}
</style>
