<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { Eye, EyeOff, Lock, Mail } from '@lucide/vue'
import owlLogo from '@/assets/owl-logo.svg'
import AppFormField from '@/components/ui/AppFormField.vue'
import AppInput from '@/components/ui/AppInput.vue'

import { useLoginMutation, useTwoFactorChallengeMutation } from '@/queries/authQueries'
import { useFormErrors } from '@/composables/useFormErrors'

const router = useRouter()
const { t } = useI18n()
const loginMutation = useLoginMutation()
const twoFactorChallengeMutation = useTwoFactorChallengeMutation()
const formErrors = useFormErrors()

const email = ref('')
const password = ref('')
const twoFactorCode = ref('')
const step = ref<'credentials' | 'twoFactor'>('credentials')
const showPassword = ref(false)
async function submitCredentials() {
  formErrors.clearAllErrors()
  try {
    const result = await loginMutation.mutateAsync({
      email: email.value,
      password: password.value,
    })
    if (result.twoFactorRequired) {
      step.value = 'twoFactor'
      return
    }
    router.push('/')
  } catch (e: unknown) {
    formErrors.setFromApiError(e)
  }
}

async function submitTwoFactor() {
  formErrors.clearAllErrors()
  try {
    await twoFactorChallengeMutation.mutateAsync(twoFactorCode.value)
    router.push('/')
  } catch (e: unknown) {
    formErrors.setFromApiError(e)
  }
}
</script>

<template lang="pug">
main.login
  .login__branding
    img.login__logo(:src="owlLogo" alt="ЧКУ")
    p.login__club-fullname.label-text {{ $t('auth.clubFull') }}

  .login__panel
    h2.login__title {{ $t('auth.loginTitle') }}

    form(v-if="step === 'credentials'" @submit.prevent="submitCredentials")
      AppFormField.login__field(:label="t('auth.email')" label-for="login-email" required :error="formErrors.getError('email')")
        .login__input-wrapper
          Mail.login__input-icon
          AppInput#login-email.login__input--with-icon(
            type="email"
            v-model="email"
            required
            autocomplete="email"
            :placeholder="t('auth.emailPlaceholder')"
            :aria-invalid="formErrors.hasError('email')"
          )
      AppFormField.login__field(:label="t('auth.password')" label-for="login-password" required :error="formErrors.getError('password')")
        .login__input-wrapper
          Lock.login__input-icon
          AppInput#login-password.login__input--with-icon(
            :type="showPassword ? 'text' : 'password'"
            v-model="password"
            required
            autocomplete="current-password"
            :placeholder="t('auth.passwordPlaceholder')"
            :aria-invalid="formErrors.hasError('password')"
          )
          button.login__toggle-password(
            type="button"
            @click="showPassword = !showPassword"
            :aria-label="showPassword ? $t('auth.hidePassword') : $t('auth.showPassword')"
          )
            EyeOff(v-if="showPassword")
            Eye(v-else)

      p.login__error(v-if="loginMutation.error.value && !Object.keys(formErrors.fieldErrors.value).length")
        | {{ loginMutation.error.value.message }}

      button.button.login__submit(type="submit" :disabled="loginMutation.isPending.value")
        | {{ loginMutation.isPending.value ? $t('auth.loggingIn') : $t('auth.loginBtn') }}

    form(v-else @submit.prevent="submitTwoFactor")
      AppFormField.login__field(:label="t('auth.twoFactorCode')" label-for="login-2fa" required :error="formErrors.getError('code')")
        AppInput#login-2fa.login__input--with-icon(
          type="text"
          v-model="twoFactorCode"
          required
          autocomplete="one-time-code"
          inputmode="numeric"
          pattern="[0-9]*"
          :aria-invalid="formErrors.hasError('code')"
        )
      p.login__error(v-if="twoFactorChallengeMutation.error.value && !Object.keys(formErrors.fieldErrors.value).length")
        | {{ twoFactorChallengeMutation.error.value.message }}
      button.button.login__submit(type="submit" :disabled="twoFactorChallengeMutation.isPending.value")
        | {{ twoFactorChallengeMutation.isPending.value ? $t('auth.verifying') : $t('auth.verifyBtn') }}
</template>

<style scoped lang="scss">
@use '@/styles/breakpoints' as *;

.login {
  position: relative;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  min-height: 100dvh;
  padding: var(--space-md);
  gap: var(--space-lg);
  overflow: hidden;

  @include tablet {
    padding: var(--space-xl);
    gap: var(--space-xl);
  }
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
  padding: var(--space-lg);
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-panel);
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 0.045), rgba(255, 255, 255, 0.015)),
    var(--bg-surface);
  box-shadow: var(--shadow-soft);

  @include tablet {
    padding: var(--space-xl);
  }
}

.login__title {
  margin: 0 0 var(--space-xl) 0;
  font-size: clamp(1.5rem, 5vw, 2rem);
  font-weight: 600;
  text-align: center;
}

.login__field {
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
  z-index: 1;
}

.login__input--with-icon {
  padding-left: 2.5rem;
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
  cursor: not-allowed;
}
</style>
