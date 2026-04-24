import { computed, ref } from 'vue'
import { defineStore } from 'pinia'

export const useClubStore = defineStore('club', () => {
  const name = ref('Читальный клуб умничек')
  const shortName = ref('ЧКУ')
  const displayName = computed(() => `${name.value} (${shortName.value})`)

  return { name, shortName, displayName }
})
