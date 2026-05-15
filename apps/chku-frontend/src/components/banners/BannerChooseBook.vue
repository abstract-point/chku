<script setup lang="ts">
import { ListPlus } from '@lucide/vue'
import { RouterLink } from 'vue-router'
import AppBanner from '@/components/ui/AppBanner.vue'
</script>

<template lang="pug">
AppBanner
  template(#icon)
    ListPlus(:size="22" aria-hidden="true")
  template(#content)
    span.label-text Требуется действие
    h2 Выберите книгу
    p.body-text Твоя очередь подошла, но в личной очереди нет книг для проверки.
  template(#actions)
    RouterLink.button.button--primary.label-text(to="/propose-selection") Управлять очередью
</template>
