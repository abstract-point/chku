<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

const email = ref('')
const password = ref('')
const twoFactorCode = ref('')
const step = ref<'credentials' | 'twoFactor'>('credentials')
const error = ref('')

async function submitCredentials() {
  error.value = ''
  try {
    const result = await auth.login(email.value, password.value)
    if (result.twoFactorRequired) {
      step.value = 'twoFactor'
      return
    }
    router.push('/')
  } catch (e: unknown) {
    error.value = (e as Error).message || 'Ошибка входа'
  }
}

async function submitTwoFactor() {
  error.value = ''
  try {
    await auth.confirmTwoFactor(twoFactorCode.value)
    router.push('/')
  } catch (e: unknown) {
    error.value = (e as Error).message || 'Неверный код'
  }
}
</script>

<template lang="pug">
main.login.container
  .login__panel
    h1.login__title Читальный клуб умничек
    p.login__subtitle Вход в систему

    form(v-if="step === 'credentials'" @submit.prevent="submitCredentials")
      .login__group
        label.label-text(for="login-email") Email
        input#login-email.login__input(type="email" v-model="email" required autocomplete="email")
      .login__group
        label.label-text(for="login-password") Пароль
        input#login-password.login__input(type="password" v-model="password" required autocomplete="current-password")
      p.login__error(v-if="error") {{ error }}
      button.button.button--primary.label-text.login__submit(type="submit" :disabled="auth.isLoading")
        | {{ auth.isLoading ? 'Вход...' : 'Войти' }}

    form(v-else @submit.prevent="submitTwoFactor")
      .login__group
        label.label-text(for="login-2fa") Код из приложения-аутентификатора
        input#login-2fa.login__input(type="text" v-model="twoFactorCode" required autocomplete="one-time-code" inputmode="numeric" pattern="[0-9]*")
      p.login__error(v-if="error") {{ error }}
      button.button.button--primary.label-text.login__submit(type="submit" :disabled="auth.isLoading")
        | {{ auth.isLoading ? 'Проверка...' : 'Подтвердить' }}
</template>

<style scoped>
.login {
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 70vh;
}

.login__panel {
  width: 100%;
  max-width: 24rem;
  padding: var(--space-xl);
  border: var(--border-width) solid var(--border);
  background: var(--bg-surface);
}

.login__title {
  margin-bottom: var(--space-xs);
  font-size: 1.5rem;
}

.login__subtitle {
  margin-bottom: var(--space-lg);
  color: var(--text-muted);
}

.login__group {
  display: flex;
  flex-direction: column;
  gap: var(--space-xs);
  margin-bottom: var(--space-md);
}

.login__input {
  width: 100%;
  padding: 0.75rem;
  border: var(--border-width) solid var(--border);
  border-radius: 0;
  background: var(--bg-base);
  color: var(--text-main);
  outline: none;
}

.login__input:focus {
  border-color: var(--text-main);
}

.login__error {
  margin-bottom: var(--space-md);
  color: var(--warn);
  font-size: 0.85rem;
}

.login__submit {
  width: 100%;
}
</style>
