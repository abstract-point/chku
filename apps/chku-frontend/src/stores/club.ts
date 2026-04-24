import { computed, ref } from 'vue'
import { defineStore } from 'pinia'
import { activeBookChoice as defaultActiveBookChoice } from '@/data/club/bookChoiceEvent'
import type { BookChoiceEvent, BookProposalForm, MemberProfile } from '@/types/club'

export const useClubStore = defineStore('club', () => {
  const name = ref('Читальный клуб умничек')
  const shortName = ref('ЧКУ')
  const displayName = computed(() => `${name.value} (${shortName.value})`)
  const activeBookChoice = ref<BookChoiceEvent | null>(defaultActiveBookChoice)
  const currentUserName = ref('Елена')
  const currentSelectorName = ref('Елена')
  const currentCycleLabel = ref('Цикл #43')
  const currentMember = ref<MemberProfile>({
    name: 'Елена',
    initials: 'ЕЛ',
    memberSince: '2021',
    email: 'elena@example.com',
    favoriteGenre: 'Современная проза',
    stats: {
      read: 14,
      proposed: 3,
      meetings: 8,
    },
    readingHistory: [
      {
        title: 'Тайная история',
        coverTitle: 'Тайная\nистория',
        author: 'Донна Тартт',
        completedLabel: 'сентябрь 2023',
        proposedBy: 'Елена',
      },
      {
        title: 'Дюна',
        coverTitle: 'Дюна',
        author: 'Фрэнк Герберт',
        completedLabel: 'август 2023',
        proposedBy: 'Михаил',
        coverVariant: 'accent',
      },
      {
        title: '1984',
        coverTitle: '1984',
        author: 'Джордж Оруэлл',
        completedLabel: 'июль 2023',
        proposedBy: 'Анна',
        coverVariant: 'blue',
      },
    ],
  })

  const canProposeNextBook = computed(() => currentUserName.value === currentSelectorName.value)

  function submitBookProposal(form: BookProposalForm) {
    activeBookChoice.value = {
      type: 'book-choice',
      proposerName: currentMember.value.name,
      bookTitle: form.title.trim(),
      author: form.author.trim(),
      description: form.description.trim(),
      reason: form.reason.trim(),
    }
  }

  return {
    name,
    shortName,
    displayName,
    activeBookChoice,
    currentUserName,
    currentSelectorName,
    currentCycleLabel,
    currentMember,
    canProposeNextBook,
    submitBookProposal,
  }
})
