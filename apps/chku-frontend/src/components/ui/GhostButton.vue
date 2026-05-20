<script setup lang="ts">
import AppButton from './AppButton.vue'
import type { Component } from 'vue'

const props = defineProps<{
  to?: string
  type?: 'button' | 'submit' | 'reset'
  disabled?: boolean
  icon?: Component
  ariaLabel?: string
}>()

const emit = defineEmits<{
  click: [event: MouseEvent]
}>()
</script>

<template lang="pug">
AppButton(variant="ghost" v-bind="props" @click="(e: MouseEvent) => emit('click', e)")
  slot
</template>
