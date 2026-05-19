<script setup lang="ts">
import { RouterView } from 'vue-router'
import AppFooter from '@/components/AppFooter.vue'
import AppHeader from '@/components/AppHeader.vue'
import BannerSection from '@/components/layouts/BannerSection.vue'
</script>

<template lang="pug">
.app-shell
  .container
    AppHeader
  BannerSection
  main.app-main
    RouterView
  #modal-portal
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
