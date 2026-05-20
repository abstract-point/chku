<script setup lang="ts">
import { ListPlus } from '@lucide/vue'
import { useI18n } from 'vue-i18n'
import { RouterLink } from 'vue-router'
import AppBanner from '@/components/ui/AppBanner.vue'

const { t } = useI18n()
</script>

<template lang="pug">
AppBanner
  template(#icon)
    ListPlus(:size="22" aria-hidden="true")
  template(#content)
    span.label-text {{ $t('dash.bannerAction') }}
    h2 {{ $t('dash.bannerChooseBook') }}
    p.body-text {{ $t('dash.bannerChooseBookText') }}
  template(#actions)
    RouterLink.button.button--primary.label-text(to="/propose-selection") {{ $t('dash.bannerManageQueue') }}
</template>
