<script setup lang="ts">
import { RouterLink } from 'vue-router'
import { Star } from '@lucide/vue'
import AppBanner from '@/components/ui/AppBanner.vue'

defineProps<{
  meetingId: string
}>()
</script>

<template lang="pug">
AppBanner(variant="warn")
  template(#icon)
    Star(:size="22" aria-hidden="true")
  template(#content)
    span.label-text {{ $t('dash.bannerAction') }}
    h2 {{ $t('dash.bannerRateBook') }}
    p.body-text {{ $t('dash.bannerRateBookText') }}
  template(#actions)
    RouterLink.button.button--primary.label-text(:to="`/meetings/${meetingId}`") {{ $t('dash.bannerGoToRate') }}
</template>
