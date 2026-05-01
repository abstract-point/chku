<script setup lang="ts">
import { computed } from 'vue'
import { RouterView, useRoute } from 'vue-router'
import AppFooter from '@/components/AppFooter.vue'
import AppHeader from '@/components/AppHeader.vue'
import BookCandidateVerificationBanner from '@/components/dashboard/BookCandidateVerificationBanner.vue'
import TwoFactorRequiredBanner from '@/components/TwoFactorRequiredBanner.vue'
import { mapCandidateToChoice } from '@/mappers/candidateMapper'
import { useAuthSession } from '@/queries/authQueries'
import { useActiveCandidateQuery } from '@/queries/candidateQueries'

const route = useRoute()
const isPublicPage = computed(() => route.meta.public === true)
const { isAdmin, isAuthenticated, twoFactorEnabled, user } = useAuthSession({
  enabled: computed(() => !isPublicPage.value),
})
const activeCandidateQuery = useActiveCandidateQuery({
  enabled: computed(() => !isPublicPage.value && isAuthenticated.value),
})
const activeBookChoice = computed(() => {
  const candidate = activeCandidateQuery.data.value
  if (!candidate || !user.value) return null

  if (candidate.canConfirm) {
    return mapCandidateToChoice(candidate)
  }

  const currentResponse = candidate.responses.find(
    (response) => response.member.id === user.value?.id,
  )

  if (candidate.status === 'pending' && (!currentResponse || currentResponse.response === 'pending')) {
    return mapCandidateToChoice(candidate)
  }

  return null
})
const shouldShowTwoFactorBanner = computed(() => isAdmin.value && !twoFactorEnabled.value)
</script>

<template lang="pug">
.app-shell
  template(v-if="!isPublicPage")
    .container
      AppHeader
    .container(v-if="shouldShowTwoFactorBanner")
      TwoFactorRequiredBanner
    .container(v-if="activeBookChoice")
      BookCandidateVerificationBanner(:choice="activeBookChoice")
  main.app-main
    RouterView
  template(v-if="!isPublicPage")
    AppFooter
</template>

<style scoped>
.app-shell {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  background: transparent;
}

.app-main {
  flex: 1;
  padding-top: var(--space-md);
}
</style>
