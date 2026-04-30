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
const { isAdmin, twoFactorEnabled } = useAuthSession({
  enabled: computed(() => !isPublicPage.value),
})
const activeCandidateQuery = useActiveCandidateQuery()
const activeBookChoice = computed(() =>
  activeCandidateQuery.data.value ? mapCandidateToChoice(activeCandidateQuery.data.value) : null,
)
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
