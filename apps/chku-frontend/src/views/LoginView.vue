<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { Eye, EyeOff, Lock, Mail } from '@lucide/vue'
import owlLogo from '@/assets/owl-logo.svg'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

const email = ref('')
const password = ref('')
const twoFactorCode = ref('')
const step = ref<'credentials' | 'twoFactor'>('credentials')
const error = ref('')
const showPassword = ref(false)
const remember = ref(false)

async function submitCredentials() {
  error.value = ''
  try {
    const result = await auth.login(email.value, password.value, remember.value)
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
main.login
  .login__branding
    img.login__logo(:src="owlLogo" alt="ЧКУ")
    p.login__club-fullname.label-text Читальный клуб умничек

  .login__panel
    h2.login__title Вход в систему

    form(v-if="step === 'credentials'" @submit.prevent="submitCredentials")
      .login__field
        label.label-text(for="login-email") Email
        .login__input-wrapper
          Mail.login__input-icon
          input#login-email.login__input(
            type="email"
            v-model="email"
            required
            autocomplete="email"
            placeholder="you@example.com"
          )
      .login__field
        label.label-text(for="login-password") Пароль
        .login__input-wrapper
          Lock.login__input-icon
          input#login-password.login__input(
            :type="showPassword ? 'text' : 'password'"
            v-model="password"
            required
            autocomplete="current-password"
            placeholder="Введите пароль"
          )
          button.login__toggle-password(
            type="button"
            @click="showPassword = !showPassword"
            :aria-label="showPassword ? 'Скрыть пароль' : 'Показать пароль'"
          )
            EyeOff(v-if="showPassword")
            Eye(v-else)

      .login__options
        label.login__remember
          input.login__checkbox(type="checkbox" v-model="remember")
          span Запомнить меня
        button.login__forgot(type="button" disabled) Забыли пароль?

      p.login__error(v-if="error") {{ error }}

      button.button.login__submit(type="submit" :disabled="auth.isLoading")
        | {{ auth.isLoading ? 'Вход...' : 'Войти' }}

    form(v-else @submit.prevent="submitTwoFactor")
      .login__field
        label.label-text(for="login-2fa") Код из приложения-аутентификатора
        input#login-2fa.login__input(
          type="text"
          v-model="twoFactorCode"
          required
          autocomplete="one-time-code"
          inputmode="numeric"
          pattern="[0-9]*"
        )
      p.login__error(v-if="error") {{ error }}
      button.button.login__submit(type="submit" :disabled="auth.isLoading")
        | {{ auth.isLoading ? 'Проверка...' : 'Подтвердить' }}
</template>

<style scoped>
.login {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 100dvh;
  padding: var(--space-xl);
  gap: var(--space-xl);
  overflow: hidden;
}

.login::before {
  position: absolute;
  inset: 8% auto auto 50%;
  width: min(42rem, 80vw);
  height: min(42rem, 80vw);
  content: '';
  transform: translateX(-50%);
  border: var(--border-width) solid var(--border);
  border-radius: 999px;
  background:
    radial-gradient(circle, rgba(67, 224, 125, 0.075), transparent 58%),
    radial-gradient(circle at 70% 30%, rgba(216, 137, 43, 0.055), transparent 46%);
  opacity: 0.9;
  pointer-events: none;
}

.login__branding {
  position: relative;
  z-index: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--space-sm);
}

.login__logo {
  width: 4rem;
  height: 4rem;
  filter: invert(0);
}

[data-theme='dark'] .login__logo {
  filter: invert(1);
}

.login__club-fullname {
  font-size: 0.85rem;
  letter-spacing: 0.08em;
  color: var(--text-muted);
  margin: 0;
}

.login__panel {
  position: relative;
  z-index: 1;
  width: 100%;
  max-width: 28rem;
  padding: var(--space-xl);
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-panel);
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 0.045), rgba(255, 255, 255, 0.015)),
    var(--bg-surface);
  box-shadow: var(--shadow-soft);
}

.login__title {
  margin: 0 0 var(--space-xl) 0;
  font-size: clamp(1.5rem, 5vw, 2rem);
  font-weight: 600;
  text-align: center;
}

.login__field {
  display: flex;
  flex-direction: column;
  gap: var(--space-xs);
  margin-bottom: var(--space-lg);
}

.login__input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.login__input-icon {
  position: absolute;
  left: 0.75rem;
  width: 1rem;
  height: 1rem;
  color: var(--text-subtle);
  pointer-events: none;
}

.login__input {
  width: 100%;
  padding: 0.75rem 0.75rem 0.75rem 2.5rem;
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-inner);
  background: var(--bg-panel);
  color: var(--text-main);
  outline: none;
  font-size: 0.9rem;
  transition:
    border-color 0.15s ease,
    box-shadow 0.15s ease,
    background-color 0.15s ease;
}

.login__input::placeholder {
  color: var(--text-subtle);
}

.login__input:focus {
  border-color: var(--accent-border);
  box-shadow: 0 0 0 3px var(--accent-bg);
}

.login__toggle-password {
  position: absolute;
  right: 0.5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  width: 2.1rem;
  height: 2.1rem;
  border: var(--border-width) solid transparent;
  border-radius: 0.65rem;
  background: transparent;
  color: var(--text-subtle);
  cursor: pointer;
  padding: 0;
}

.login__toggle-password:hover {
  border-color: var(--border);
  background: var(--bg-hover);
  color: var(--text-main);
}

.login__toggle-password svg {
  width: 1rem;
  height: 1rem;
}

.login__options {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: var(--space-lg);
  font-size: 0.85rem;
}

.login__remember {
  display: flex;
  align-items: center;
  gap: var(--space-xs);
  color: var(--text-muted);
  cursor: pointer;
}

.login__checkbox {
  width: 1rem;
  height: 1rem;
  accent-color: var(--accent);
  cursor: pointer;
}

.login__forgot {
  color: var(--accent);
  font-size: 0.85rem;
  text-align: right;
}

.login__forgot:disabled {
  color: var(--text-subtle);
  cursor: not-allowed;
}

.login__error {
  margin-bottom: var(--space-lg);
  padding: var(--space-sm) var(--space-md);
  border: var(--border-width) solid rgba(224, 95, 95, 0.28);
  border-radius: var(--radius-inner);
  background: var(--danger-bg);
  color: var(--warn);
  font-size: 0.85rem;
}

.login__submit {
  width: 100%;
  margin-top: var(--space-sm);
  background: var(--accent);
  border-color: var(--accent);
  color: var(--text-inverse);
  font-size: 0.85rem;
  letter-spacing: 0.05em;
  min-height: 2.75rem;
  border-radius: var(--radius-inner);
}

.login__submit:hover {
  background: var(--accent-dim);
  border-color: var(--accent-dim);
  color: var(--text-inverse);
}

.login__submit:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

@media (max-width: 640px) {
  .login {
    padding: var(--space-md);
    gap: var(--space-lg);
  }

  .login__panel {
    padding: var(--space-lg);
  }

  .login__options {
    flex-direction: column;
    align-items: flex-start;
    gap: var(--space-sm);
  }
}
</style>
