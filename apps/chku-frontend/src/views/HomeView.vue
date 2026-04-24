<script setup lang="ts">
import { useClubStore } from '@/stores/club'

const club = useClubStore()
</script>

<template lang="pug">
main.home-view
  section.hero-section
    p.hero-kicker {{ club.shortName }}
    h1 {{ club.name }}
    p.hero-copy
      | Базовое Vue-приложение для читального клуба: маршрутизация, состояние и Pug-шаблоны готовы к развитию интерфейса.
</template>

<style scoped>
.home-view {
  width: min(100%, 1120px);
  margin: 0 auto;
  padding: 4rem 1.5rem;
}

.hero-section {
  max-width: 44rem;
}

.hero-kicker {
  margin-bottom: 0.75rem;
  color: var(--color-accent);
  font-weight: 700;
  letter-spacing: 0;
}

h1 {
  color: var(--color-heading);
  font-size: clamp(2.5rem, 6vw, 5rem);
  font-weight: 800;
  line-height: 0.95;
}

.hero-copy {
  margin-top: 1.5rem;
  max-width: 38rem;
  color: var(--color-muted);
  font-size: 1.1rem;
}
</style>
