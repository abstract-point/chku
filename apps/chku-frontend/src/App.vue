<script setup lang="ts">
import { computed } from 'vue'
import { RouterView } from 'vue-router'
import AppFooter from '@/components/AppFooter.vue'
import AppHeader from '@/components/AppHeader.vue'
import BookCandidateVerificationBanner from '@/components/dashboard/BookCandidateVerificationBanner.vue'
import TwoFactorRequiredBanner from '@/components/TwoFactorRequiredBanner.vue'
import { mapCandidateToChoice } from '@/mappers/candidateMapper'
import { useActiveCandidateQuery } from '@/queries/candidateQueries'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const activeCandidateQuery = useActiveCandidateQuery()
const activeBookChoice = computed(() =>
  activeCandidateQuery.data.value ? mapCandidateToChoice(activeCandidateQuery.data.value) : null,
)
const shouldShowTwoFactorBanner = computed(() => auth.isAdmin && !auth.twoFactorEnabled)
</script>

<template lang="pug">
.app-shell
  .container
    AppHeader
  .container(v-if="shouldShowTwoFactorBanner")
    TwoFactorRequiredBanner
  .container(v-if="activeBookChoice")
    BookCandidateVerificationBanner(:choice="activeBookChoice")
  main.app-main
    RouterView
  AppFooter
</template>

<style scoped>
.app-shell {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
  background: var(--bg-base);
}

.app-main {
  flex: 1;
}
</style>
