import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { activeBookChoice as defaultActiveBookChoice } from '@/data/club/bookChoiceEvent'
import type { BookChoiceEvent } from '@/types/club'

export const useClubStore = defineStore('club', () => {
  const name = ref('Читальный клуб умничек')
  const shortName = ref('ЧКУ')
  const displayName = computed(() => `${name.value} (${shortName.value})`)
  const activeBookChoice = ref<BookChoiceEvent | null>(defaultActiveBookChoice)

  return { name, shortName, displayName, activeBookChoice }
})
