import { createI18n } from 'vue-i18n'
import ru from './ru'
import { pluralRules } from './pluralRules'

const i18n = createI18n({
  legacy: false,
  locale: 'ru',
  fallbackLocale: 'ru',
  messages: { ru },
  pluralRules,
})

export default i18n
