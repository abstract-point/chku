import { ref, computed } from 'vue'
import { ApiError } from '@/api/http'

export function useFormErrors() {
  const errors = ref<Record<string, string[]>>({})

  const fieldErrors = computed<Record<string, string>>(() => {
    const result: Record<string, string> = {}
    for (const [key, messages] of Object.entries(errors.value)) {
      if (Array.isArray(messages) && messages.length > 0) {
        result[key] = messages[0]!
      }
    }
    return result
  })

  function setFromApiError(error: unknown) {
    if (error instanceof ApiError && error.validationErrors) {
      errors.value = { ...error.validationErrors }
    } else {
      errors.value = {}
    }
  }

  function setFieldError(field: string, message: string) {
    errors.value = { ...errors.value, [field]: [message] }
  }

  function clearFieldError(field: string) {
    const next = { ...errors.value }
    delete next[field]
    errors.value = next
  }

  function clearAllErrors() {
    errors.value = {}
  }

  function hasError(field: string): boolean {
    return !!errors.value[field]?.length
  }

  function getError(field: string): string | undefined {
    return errors.value[field]?.[0]
  }

  return {
    errors,
    fieldErrors,
    setFromApiError,
    setFieldError,
    clearFieldError,
    clearAllErrors,
    hasError,
    getError,
  }
}
