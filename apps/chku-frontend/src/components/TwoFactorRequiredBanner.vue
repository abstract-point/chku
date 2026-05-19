<script setup lang="ts">
import { RouterLink } from 'vue-router'
import { ShieldAlert } from '@lucide/vue'
import AppBanner from '@/components/ui/AppBanner.vue'
</script>

<template lang="pug">
AppBanner
  template(#icon)
    ShieldAlert(:size="22" aria-hidden="true")
  template(#content)
    span.label-text Требуется действие
    h2 Включите 2FA
    p.body-text Для управления участниками Админу и Разработчику нужна двухфакторная защита.
  template(#actions)
    RouterLink.button.button--primary.label-text(to="/profile/settings") Настроить 2FA
</template>
