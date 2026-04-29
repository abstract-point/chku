<script setup lang="ts">
import { computed, ref } from 'vue'
import { useQueryClient } from '@tanstack/vue-query'
import { Copy, Info, RotateCcw, ShieldCheck } from '@lucide/vue'
import { authApi } from '@/api/endpoints/auth'
import { queryKeys } from '@/queries/keys'
import { useAuthStore } from '@/stores/auth'
import type { ComponentPublicInstance } from 'vue'

const auth = useAuthStore()
const queryClient = useQueryClient()

const isTwoFactorEnabled = computed(() => auth.twoFactorEnabled)

const qrCodeSvg = ref('')
const secretKey = ref('')
const recoveryCodes = ref<string[]>([])

const codeDigits = ref(['', '', '', '', '', ''])
const codeInputRefs = ref<HTMLInputElement[]>([])

const isLoadingQr = ref(false)
const isBusy = ref(false)
const message = ref('')
const error = ref('')
const copiedSecret = ref(false)
const copiedRecovery = ref(false)

const verificationCode = computed(() => codeDigits.value.join(''))
const canConfirm = computed(() => /^\d{6}$/.test(verificationCode.value))

function getErrorMessage(err: unknown, fallback: string) {
  return err instanceof Error ? err.message : fallback
}

function clearFeedback() {
  message.value = ''
  message.value = ''
  error.value = ''
}

function setCodeInputRef(el: Element | ComponentPublicInstance | null, index: number) {
  if (el instanceof HTMLInputElement) {
    codeInputRefs.value[index] = el
  }
}

async function refreshUser() {
  await auth.fetchUser()
  await queryClient.invalidateQueries({ queryKey: queryKeys.currentUser })
}

async function loadQrAndSecret() {
  isLoadingQr.value = true
  try {
    const [qr, secret] = await Promise.all([
      authApi.twoFactorQrCode(),
      authApi.twoFactorSecretKey(),
    ])
    qrCodeSvg.value = qr.svg
    secretKey.value = secret.secretKey
  } catch (err) {
    error.value = getErrorMessage(err, 'Не удалось загрузить QR-код.')
  } finally {
    isLoadingQr.value = false
  }
}

async function startSetup() {
  clearFeedback()
  isBusy.value = true
  try {
    await authApi.enableTwoFactor()
    await loadQrAndSecret()
    codeDigits.value = ['', '', '', '', '', '']
    message.value = 'Отсканируй QR-код и введи код подтверждения.'
  } catch (err) {
    error.value = getErrorMessage(err, 'Не удалось начать настройку 2FA.')
  } finally {
    isBusy.value = false
  }
}

async function refreshQr() {
  clearFeedback()
  if (
    !window.confirm(
      'Обновить QR-код? Старый код станет недействителен, и понадобится заново настроить аутентификатор.',
    )
  ) {
    return
  }
  isBusy.value = true
  try {
    await authApi.enableTwoFactor()
    await loadQrAndSecret()
    codeDigits.value = ['', '', '', '', '', '']
    message.value = 'QR-код обновлён. Отсканируй новый код.'
  } catch (err) {
    error.value = getErrorMessage(err, 'Не удалось обновить QR-код.')
  } finally {
    isBusy.value = false
  }
}

async function confirmSetup() {
  clearFeedback()
  isBusy.value = true
  try {
    await authApi.confirmTwoFactorSetup(verificationCode.value)
    try {
      recoveryCodes.value = await authApi.twoFactorRecoveryCodes()
    } catch {
      // TODO: backend may not support recovery codes yet
      recoveryCodes.value = []
    }
    qrCodeSvg.value = ''
    secretKey.value = ''
    codeDigits.value = ['', '', '', '', '', '']
    await refreshUser()
    message.value = '2FA включена. Сохрани резервные коды.'
  } catch (err) {
    error.value = getErrorMessage(err, 'Код подтверждения не подошёл.')
  } finally {
    isBusy.value = false
  }
}

async function showRecoveryCodes() {
  clearFeedback()
  isBusy.value = true
  try {
    recoveryCodes.value = await authApi.twoFactorRecoveryCodes()
  } catch (err) {
    error.value = getErrorMessage(err, 'Не удалось загрузить резервные коды.')
  } finally {
    isBusy.value = false
  }
}

async function regenerateRecoveryCodes() {
  clearFeedback()
  isBusy.value = true
  try {
    await authApi.regenerateTwoFactorRecoveryCodes()
    recoveryCodes.value = await authApi.twoFactorRecoveryCodes()
    message.value = 'Резервные коды обновлены.'
  } catch (err) {
    error.value = getErrorMessage(err, 'Не удалось обновить резервные коды.')
  } finally {
    isBusy.value = false
  }
}

async function disable() {
  clearFeedback()
  if (!window.confirm('Выключить двухфакторную защиту? Это снизит безопасность аккаунта.')) {
    return
  }
  isBusy.value = true
  try {
    await authApi.disableTwoFactor()
    recoveryCodes.value = []
    qrCodeSvg.value = ''
    secretKey.value = ''
    codeDigits.value = ['', '', '', '', '', '']
    await refreshUser()
    message.value = '2FA выключена.'
  } catch (err) {
    error.value = getErrorMessage(err, 'Не удалось выключить 2FA.')
  } finally {
    isBusy.value = false
  }
}

async function copyToClipboard(text: string) {
  try {
    await navigator.clipboard.writeText(text)
  } catch {
    const ta = document.createElement('textarea')
    ta.value = text
    document.body.appendChild(ta)
    ta.select()
    document.execCommand('copy')
    document.body.removeChild(ta)
  }
}

async function copySecret() {
  if (!secretKey.value) return
  await copyToClipboard(secretKey.value)
  copiedSecret.value = true
  setTimeout(() => (copiedSecret.value = false), 2000)
}

async function copyRecoveryCodes() {
  if (!recoveryCodes.value.length) return
  await copyToClipboard(recoveryCodes.value.join('\n'))
  copiedRecovery.value = true
  setTimeout(() => (copiedRecovery.value = false), 2000)
}

function onDigitInput(index: number) {
  const digit = codeDigits.value[index].replace(/\D/g, '').slice(0, 1)
  codeDigits.value[index] = digit
  if (digit && index < 5) {
    codeInputRefs.value[index + 1]?.focus()
  }
}

function onDigitKeydown(event: KeyboardEvent, index: number) {
  if (event.key === 'Backspace' && !codeDigits.value[index] && index > 0) {
    codeDigits.value[index - 1] = ''
    codeInputRefs.value[index - 1]?.focus()
  }
}

function onDigitPaste(event: ClipboardEvent, index: number) {
  event.preventDefault()
  const text = event.clipboardData?.getData('text') ?? ''
  const digits = text.replace(/\D/g, '').slice(0, 6).split('')
  digits.forEach((d, i) => {
    if (index + i < 6) {
      codeDigits.value[index + i] = d
    }
  })
  const focusIndex = Math.min(index + digits.length, 5)
  codeInputRefs.value[focusIndex]?.focus()
}
</script>

<template lang="pug">
section.panel.two-factor-setup(aria-labelledby="two-factor-title")
  .section-header.section-header--compact
    h2#two-factor-title Двухфакторная защита
    span.badge.label-text(:class="isTwoFactorEnabled ? 'badge--reading' : 'badge--action'")
      ShieldCheck.two-factor-setup__badge-icon
      | {{ isTwoFactorEnabled ? '2FA включена' : '2FA выключена' }}

  .two-factor-setup__body
    template(v-if="!isTwoFactorEnabled")
      p.body-text.two-factor-setup__lead
        | Подтверждай вход одноразовым кодом из приложения-аутентификатора. Для администраторов и разработчиков 2FA обязательна перед управлением участниками клуба.

      .two-factor-setup__start(v-if="!qrCodeSvg && !isLoadingQr")
        button.button.button--primary.label-text(type="button" :disabled="isBusy" @click="startSetup")
          | Включить 2FA

      .two-factor-setup__loading(v-if="isLoadingQr")
        p.body-text Загружаем QR-код…

      .two-factor-setup__card(v-if="qrCodeSvg")
        .two-factor-setup__grid
          .two-factor-setup__left
            .two-factor-setup__qr-row
              .two-factor-setup__qr-card(aria-label="QR-код для настройки аутентификатора")
                .two-factor-setup__qr(v-html="qrCodeSvg")

              .two-factor-setup__secret(v-if="secretKey")
                span.label-text Ручной ключ
                p.body-text.two-factor-setup__secret-hint
                  | Если QR-код не сканируется, добавь ключ вручную.
                .two-factor-setup__secret-box
                  code.two-factor-setup__secret-key {{ secretKey }}
                  button.two-factor-setup__copy-icon(type="button" @click="copySecret" :title="copiedSecret ? 'Скопировано' : 'Скопировать'")
                    Copy

            button.button.button--secondary.label-text.two-factor-setup__refresh(type="button" :disabled="isBusy" @click="refreshQr")
              RotateCcw.two-factor-setup__button-icon
              | Обновить QR-код

          .two-factor-setup__divider

          .two-factor-setup__right
            ol.two-factor-setup__steps
              li
                span.two-factor-setup__step-num 1.
                | Открой приложение-аутентификатор
              li
                span.two-factor-setup__step-num 2.
                | Отсканируй QR-код
              li
                span.two-factor-setup__step-num 3.
                | Введи 6-значный код

            .two-factor-setup__code-group
              label.label-text Код из приложения
              .two-factor-setup__code-inputs
                input.two-factor-setup__digit(
                  v-for="(_, index) in 3"
                  :key="`left-${index}`"
                  :ref="(el) => setCodeInputRef(el, index)"
                  v-model="codeDigits[index]"
                  type="text"
                  inputmode="numeric"
                  maxlength="1"
                  pattern="[0-9]"
                  :autocomplete="index === 0 ? 'one-time-code' : 'off'"
                  :disabled="isBusy"
                  @input="onDigitInput(index)"
                  @keydown="onDigitKeydown($event, index)"
                  @paste="onDigitPaste($event, index)"
                )
                span.two-factor-setup__code-sep -
                input.two-factor-setup__digit(
                  v-for="(_, index) in 3"
                  :key="`right-${index}`"
                  :ref="(el) => setCodeInputRef(el, index + 3)"
                  v-model="codeDigits[index + 3]"
                  type="text"
                  inputmode="numeric"
                  maxlength="1"
                  pattern="[0-9]"
                  autocomplete="off"
                  :disabled="isBusy"
                  @input="onDigitInput(index + 3)"
                  @keydown="onDigitKeydown($event, index + 3)"
                  @paste="onDigitPaste($event, index + 3)"
                )
              p.two-factor-setup__field-error(v-if="error" aria-live="polite") {{ error }}

            button.button.label-text.two-factor-setup__cta(
              type="button"
              :disabled="isBusy || !canConfirm"
              @click="confirmSetup"
            )
              | {{ isBusy ? 'Проверка…' : 'Включить 2FA' }}


        .two-factor-setup__footer(v-if="message" )
          Info.two-factor-setup__info-icon(aria-hidden="true")
          p.two-factor-setup__message(aria-live="polite") {{ message }}

    template(v-else)
      p.body-text.two-factor-setup__lead
        | При входе система будет запрашивать одноразовый код из приложения-аутентификатора.

      .two-factor-setup__actions
        button.button.button--secondary.label-text(type="button" :disabled="isBusy" @click="showRecoveryCodes")
          | Показать резервные коды
        button.button.button--secondary.label-text(type="button" :disabled="isBusy" @click="regenerateRecoveryCodes")
          | Обновить коды

      .two-factor-setup__recovery(v-if="recoveryCodes.length")
        .two-factor-setup__recovery-header
          span.label-text Резервные коды
          button.button.button--ghost.label-text(type="button" @click="copyRecoveryCodes")
            | {{ copiedRecovery ? 'Скопированы' : 'Скопировать все' }}
        ul.two-factor-setup__codes(role="list")
          li(v-for="code in recoveryCodes" :key="code")
            code {{ code }}

      .two-factor-setup__disable
        button.button.button--ghost.label-text.two-factor-setup__disable-btn(type="button" :disabled="isBusy" @click="disable")
          | Выключить 2FA


</template>

<style scoped>
.two-factor-setup__body {
  margin-top: var(--space-md);
}

.two-factor-setup__lead {
  margin-bottom: var(--space-lg);
}

.two-factor-setup__start {
  margin-top: var(--space-md);
}

.two-factor-setup__loading {
  margin-top: var(--space-md);
}

.two-factor-setup__card {
  margin-top: var(--space-lg);
}

.two-factor-setup__grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 1px minmax(0, 1fr);
  gap: var(--space-xl);
  align-items: stretch;
}

.two-factor-setup__left {
  display: flex;
  flex-direction: column;
  gap: var(--space-lg);
}

.two-factor-setup__qr-row {
  display: flex;
  gap: var(--space-lg);
  align-items: flex-start;
}

.two-factor-setup__qr-card {
  padding: var(--space-md);
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-inner);
  background: #ffffff;
  width: fit-content;
  box-shadow: inset 0 0 0 1px rgba(5, 6, 7, 0.05);
}

.two-factor-setup__qr {
  width: 13rem;
}

.two-factor-setup__qr :deep(svg) {
  display: block;
  width: 100%;
  height: auto;
}

.two-factor-setup__secret {
  display: flex;
  flex-direction: column;
  gap: var(--space-xs);
  flex: 1;
  min-width: 0;
}

.two-factor-setup__secret-hint {
  margin-bottom: var(--space-sm);
}

.two-factor-setup__secret-box {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-sm);
  padding: var(--space-sm) var(--space-md);
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-inner);
  background: var(--bg-surface);
}

.two-factor-setup__secret-key {
  color: var(--text-main);
  font-family: var(--font-mono);
  font-size: 0.82rem;
  letter-spacing: 0.05em;
  overflow-wrap: anywhere;
}

.two-factor-setup__copy-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  padding: 0;
  border: var(--border-width) solid var(--border);
  border-radius: 0.6rem;
  background: transparent;
  color: var(--text-muted);
  cursor: pointer;
  flex-shrink: 0;
  transition: color 0.15s ease;
}

.two-factor-setup__copy-icon:hover {
  background: var(--bg-hover);
  color: var(--text-main);
}

.two-factor-setup__copy-icon svg {
  width: 1rem;
  height: 1rem;
}

.two-factor-setup__badge-icon,
.two-factor-setup__button-icon {
  width: 0.95rem;
  height: 0.95rem;
}

.two-factor-setup__refresh {
  width: 100%;
  margin-top: auto;
}

.two-factor-setup__divider {
  background: var(--border);
  align-self: stretch;
}

.two-factor-setup__right {
  display: flex;
  flex-direction: column;
  gap: var(--space-xl);
}

.two-factor-setup__steps {
  list-style: none;
  padding: 0;
  color: var(--text-muted);
  font-size: 0.82rem;
  line-height: 1.75;
}

.two-factor-setup__steps li {
  margin-bottom: var(--space-xs);
}

.two-factor-setup__step-num {
  color: var(--warn);
  margin-right: var(--space-sm);
}

.two-factor-setup__code-group {
  display: flex;
  flex-direction: column;
  gap: var(--space-sm);
}

.two-factor-setup__code-inputs {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
}

.two-factor-setup__digit {
  width: 3.5rem;
  height: 3.5rem;
  padding: 0;
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-inner);
  background: var(--bg-surface);
  color: var(--text-main);
  font-family: var(--font-mono);
  font-size: 1.25rem;
  text-align: center;
  outline: none;
}

.two-factor-setup__digit:focus {
  border-color: var(--accent-border);
  box-shadow: 0 0 0 3px var(--accent-bg);
}

.two-factor-setup__code-sep {
  color: var(--text-muted);
  font-size: 1.25rem;
  font-family: var(--font-mono);
  flex-shrink: 0;
}

.two-factor-setup__field-error {
  margin-top: var(--space-xs);
  color: var(--warn);
  font-size: 0.85rem;
}

.two-factor-setup__cta {
  width: 100%;
  margin-top: auto;
  border-color: var(--accent);
  color: var(--text-inverse);
  background: var(--accent);
  box-shadow: 0 0.75rem 1.8rem rgba(67, 224, 125, 0.16);
}

.two-factor-setup__cta:hover:not(:disabled) {
  border-color: var(--accent-dim);
  background: var(--accent-dim);
}

.two-factor-setup__cta:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.two-factor-setup__hint {
  margin-top: calc(-1 * var(--space-md));
}

.two-factor-setup__footer {
  display: flex;
  align-items: center;
  gap: var(--space-md);
  margin-top: var(--space-xl);
  padding-top: var(--space-lg);
  border-top: var(--border-width) solid var(--border);
}

.two-factor-setup__info-icon {
  width: 1.25rem;
  height: 1.25rem;
  color: var(--accent);
  flex-shrink: 0;
}

.two-factor-setup__actions {
  display: flex;
  flex-wrap: wrap;
  gap: var(--space-md);
  margin-top: var(--space-md);
}

.two-factor-setup__recovery {
  margin-top: var(--space-lg);
}

.two-factor-setup__recovery-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-md);
  margin-bottom: var(--space-md);
}

.two-factor-setup__codes {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(12rem, 1fr));
  gap: var(--space-sm);
  list-style: none;
  padding: 0;
}

.two-factor-setup__codes li {
  padding: var(--space-sm) var(--space-md);
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-inner);
  background: var(--bg-panel);
}

.two-factor-setup__codes code {
  color: var(--text-main);
  font-size: 0.82rem;
  font-family: var(--font-mono);
}

.two-factor-setup__disable {
  margin-top: var(--space-xl);
  padding-top: var(--space-lg);
  border-top: var(--border-width) solid var(--border);
}

.two-factor-setup__disable-btn {
  color: var(--warn);
}

.two-factor-setup__message {
  color: var(--accent-dim);
  font-size: 0.85rem;
}

@media (max-width: 760px) {
  .two-factor-setup__grid {
    grid-template-columns: 1fr;
  }

  .two-factor-setup__divider {
    display: none;
  }

  .two-factor-setup__qr-row {
    flex-direction: column;
  }

  .two-factor-setup__qr-card {
    width: 100%;
    max-width: 16rem;
  }

  .two-factor-setup__qr {
    width: 100%;
  }

  .two-factor-setup__digit {
    width: 3rem;
    height: 3rem;
    font-size: 1rem;
  }
}
</style>
