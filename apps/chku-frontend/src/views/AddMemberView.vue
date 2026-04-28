<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
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
    h1.add-member__title Добавить участника

  form.panel(@submit.prevent="submit")
    template(v-if="!canManageMembers")
      p.body-text Для добавления участников Админу и Разработчику нужно включить 2FA.
      .add-member__actions
        button.button.button--secondary.label-text(type="button" @click="router.back()") Назад
        RouterLink.button.button--primary.label-text(to="/profile/settings") Настроить 2FA
    template(v-else)
      .add-member__group
        label.label-text(for="am-name") Имя
        input#am-name.add-member__input(type="text" v-model="form.name" required)
      .add-member__group
        label.label-text(for="am-email") Email
        input#am-email.add-member__input(type="email" v-model="form.email" required)
      .add-member__group
        label.label-text(for="am-password") Пароль
        input#am-password.add-member__input(type="password" v-model="form.password" required minlength="8")
      .add-member__group
        label.label-text(for="am-initials") Инициалы
        input#am-initials.add-member__input(type="text" v-model="form.initials" required maxlength="10")
      .add-member__group
        label.label-text(for="am-joined") Дата вступления
        input#am-joined.add-member__input(type="date" v-model="form.joined_at" required)
      .add-member__group
        label.label-text(for="am-role") Роль
        select#am-role.add-member__input(v-model="form.role" required)
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

.add-member__group {
  display: flex;
  flex-direction: column;
  gap: var(--space-xs);
  margin-bottom: var(--space-md);
}

.add-member__input {
  width: 100%;
  padding: 0.75rem;
  border: var(--border-width) solid var(--border);
  border-radius: 0;
  background: var(--bg-surface);
  color: var(--text-main);
  outline: none;
}

.add-member__input:focus {
  border-color: var(--text-main);
}

.add-member__error {
  margin-bottom: var(--space-md);
  color: var(--warn);
  font-size: 0.85rem;
}

.add-member__actions {
  display: flex;
  gap: var(--space-md);
  justify-content: flex-end;
}
</style>
