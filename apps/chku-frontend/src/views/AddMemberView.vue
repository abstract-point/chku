<script setup lang="ts">
import { computed, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { ShieldCheck, UserPlus } from '@lucide/vue'
import { useAuthSession } from '@/queries/authQueries'
import { useCreateMemberMutation } from '@/queries/memberQueries'
import { useFormErrors } from '@/composables/useFormErrors'
import AppFormField from '@/components/ui/AppFormField.vue'
import AppInput from '@/components/ui/AppInput.vue'
import AppSelect from '@/components/ui/AppSelect.vue'

const router = useRouter()
const { isAdmin, twoFactorEnabled } = useAuthSession()
const createMemberMutation = useCreateMemberMutation()
const formErrors = useFormErrors()
const canManageMembers = computed(() => isAdmin.value && twoFactorEnabled.value)

const form = ref({
  name: '',
  email: '',
  password: '',
  avatar: null as File | null,
  favorite_genre_id: null as number | null,
  joined_at: new Date().toISOString().split('T')[0],
  role: 'member' as 'member' | 'admin' | 'developer',
})

async function submit() {
  formErrors.clearAllErrors()
  try {
    await createMemberMutation.mutateAsync(form.value)
    router.push('/members')
  } catch (e: unknown) {
    formErrors.setFromApiError(e)
  }
}

function selectAvatar(event: Event) {
  const input = event.target as HTMLInputElement
  form.value.avatar = input.files?.[0] ?? null
}

const roleOptions = [
  { label: 'Участник', value: 'member' },
  { label: 'Администратор', value: 'admin' },
  { label: 'Разработчик', value: 'developer' },
]
</script>

<template lang="pug">
main.add-member.container
  .section-header
    div
      span.label-text Управление клубом
      h1.add-member__title Добавить участника
    RouterLink.button.button--secondary.label-text(to="/members") К списку

  form.panel.add-member__panel(@submit.prevent="submit")
    template(v-if="!canManageMembers")
      .inline-alert
        ShieldCheck.add-member__notice-icon
        div
          h2.add-member__notice-title Требуется двухфакторная защита
          p.body-text Для добавления участников Админу и Разработчику нужно включить 2FA.
      .add-member__actions
        button.button.button--secondary.label-text(type="button" @click="router.back()") Назад
        RouterLink.button.button--primary.label-text(to="/profile/settings") Настроить 2FA
    template(v-else)
      .add-member__intro
        UserPlus.add-member__intro-icon
        p.body-text Новый участник получит доступ к клубному дашборду, архиву и прогрессу текущего цикла.
      .add-member__fields
        AppFormField(label="Имя" label-for="am-name" required :error="formErrors.getError('name')")
          AppInput#am-name(
            type="text"
            v-model="form.name"
            required
            autocomplete="name"
            :aria-invalid="formErrors.hasError('name')"
          )
        AppFormField(label="Email" label-for="am-email" required :error="formErrors.getError('email')")
          AppInput#am-email(
            type="email"
            v-model="form.email"
            required
            autocomplete="email"
            :aria-invalid="formErrors.hasError('email')"
          )
        AppFormField(label="Пароль" label-for="am-password" required :error="formErrors.getError('password')")
          AppInput#am-password(
            type="password"
            v-model="form.password"
            required
            minlength="8"
            autocomplete="new-password"
            :aria-invalid="formErrors.hasError('password')"
          )
        AppFormField(label="Аватар" label-for="am-avatar" :error="formErrors.getError('avatar')")
          AppInput#am-avatar(
            type="file"
            accept="image/jpeg,image/png,image/webp"
            @change="selectAvatar"
            :aria-invalid="formErrors.hasError('avatar')"
          )
        AppFormField(label="Дата вступления" label-for="am-joined" required :error="formErrors.getError('joined_at')")
          AppInput#am-joined(
            type="date"
            v-model="form.joined_at"
            required
            :aria-invalid="formErrors.hasError('joined_at')"
          )
        AppFormField(label="Роль" label-for="am-role" required :error="formErrors.getError('role')")
          AppSelect#am-role(v-model="form.role" :options="roleOptions" required :aria-invalid="formErrors.hasError('role')")
      p.add-member__error(v-if="createMemberMutation.error.value && !Object.keys(formErrors.fieldErrors.value).length")
        | {{ createMemberMutation.error.value.message }}
      .add-member__actions
        button.button.button--secondary.label-text(type="button" @click="router.back()") Отмена
        button.button.button--primary.label-text(type="submit" :disabled="createMemberMutation.isPending.value")
          | {{ createMemberMutation.isPending.value ? 'Создание...' : 'Создать участника' }}
</template>

<style scoped>
.add-member__title {
  font-size: clamp(1.8rem, 4vw, 2.2rem);
}

.add-member__panel {
  max-width: 52rem;
}

.add-member__notice-icon,
.add-member__intro-icon {
  flex: 0 0 auto;
  width: 1.25rem;
  height: 1.25rem;
  color: var(--warn);
}

.add-member__notice-title {
  margin-bottom: var(--space-xs);
  font-size: 1rem;
}

.add-member__intro {
  display: flex;
  align-items: flex-start;
  gap: var(--space-md);
  margin-bottom: var(--space-xl);
  padding: var(--space-md);
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-inner);
  background: var(--bg-panel);
}

.add-member__intro-icon {
  color: var(--accent);
}

.add-member__fields {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: var(--space-md);
}

.add-member__error {
  margin-top: var(--space-md);
  padding: var(--space-sm) var(--space-md);
  border: var(--border-width) solid rgba(224, 95, 95, 0.28);
  border-radius: var(--radius-inner);
  background: var(--danger-bg);
  color: var(--danger);
  font-size: 0.85rem;
}

.add-member__actions {
  display: flex;
  gap: var(--space-md);
  justify-content: flex-end;
  margin-top: var(--space-xl);
}

@media (max-width: 760px) {
  .add-member__fields {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 520px) {
  .add-member__actions {
    flex-direction: column-reverse;
  }
}
</style>
