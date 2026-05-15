<script setup lang="ts">
import { computed } from 'vue'
import { useDashboardQuery } from '@/queries/dashboardQueries'
import BannerChooseBook from '@/components/banners/BannerChooseBook.vue'

const dashboardQuery = useDashboardQuery()

interface Banner {
  type: 'choose_book'
}

const banners = computed<Banner[]>(() => {
  const data = dashboardQuery.data.value
  if (!data) return []

  const result: Banner[] = []

  if (data.lifecycle?.shouldShowChooseBookBanner) {
    result.push({ type: 'choose_book' })
  }

  return result
})
</script>

<template lang="pug">
template(v-if="banners.length")
  .container(v-for="banner in banners" :key="banner.type")
    BannerChooseBook(v-if="banner.type === 'choose_book'")
</template>
