<script setup lang="ts">
import { computed, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { ShieldCheck, UserPlus } from '@lucide/vue'
import { http } from '@/api/http'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()
const canManageMembers = computed(() => auth.isAdmin && auth.twoFactorEnabled)

const form = ref({
  name: '',
  email: '',
  password: '',
  initials: '',
  favorite_genre_id: null as number | null,
  joined_at: new Date().toISOString().split('T')[0],
  role: 'member' as 'member' | 'admin' | 'developer',
})

const error = ref('')
const isSubmitting = ref(false)

async function submit() {
  error.value = ''
  isSubmitting.value = true
  try {
    await http.post('/members', form.value)
    router.push('/members')
  } catch (e: unknown) {
    error.value = (e as Error).message || 'Ошибка создания участника'
  } finally {
    isSubmitting.value = false
  }
}
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
        .add-member__group
          label.label-text(for="am-name") Имя
          input#am-name.field-control.add-member__input(type="text" v-model="form.name" required autocomplete="name")
        .add-member__group
          label.label-text(for="am-email") Email
          input#am-email.field-control.add-member__input(type="email" v-model="form.email" required autocomplete="email")
        .add-member__group
          label.label-text(for="am-password") Пароль
          input#am-password.field-control.add-member__input(type="password" v-model="form.password" required minlength="8" autocomplete="new-password")
        .add-member__group
          label.label-text(for="am-initials") Инициалы
          input#am-initials.field-control.add-member__input(type="text" v-model="form.initials" required maxlength="10")
        .add-member__group
          label.label-text(for="am-joined") Дата вступления
          input#am-joined.field-control.add-member__input(type="date" v-model="form.joined_at" required)
        .add-member__group
          label.label-text(for="am-role") Роль
          select#am-role.field-control.add-member__input(v-model="form.role" required)
            option(value="member") Участник
            option(value="admin") Администратор
            option(value="developer") Разработчик
      p.add-member__error(v-if="error") {{ error }}
      .add-member__actions
        button.button.button--secondary.label-text(type="button" @click="router.back()") Отмена
        button.button.button--primary.label-text(type="submit" :disabled="isSubmitting")
          | {{ isSubmitting ? 'Создание...' : 'Создать участника' }}
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

.add-member__group {
  display: flex;
  flex-direction: column;
  gap: var(--space-xs);
}

.add-member__input {
  width: 100%;
  padding: 0.75rem 0.9rem;
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
