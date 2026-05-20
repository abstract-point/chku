<script setup lang="ts">
import { RouterLink } from 'vue-router'
import { ShieldAlert } from '@lucide/vue'
import { useI18n } from 'vue-i18n'
import AppBanner from '@/components/ui/AppBanner.vue'

const { t } = useI18n()
</script>

<template lang="pug">
AppBanner
  template(#icon)
    ShieldAlert(:size="22" aria-hidden="true")
  template(#content)
    span.label-text {{ $t('dash.bannerAction') }}
    h2 {{ $t('dash.bannerEnable2fa') }}
    p.body-text {{ $t('dash.bannerEnable2faText') }}
  template(#actions)
    RouterLink.button.button--primary.label-text(to="/profile/settings") {{ $t('dash.bannerSetup2fa') }}
</template>
