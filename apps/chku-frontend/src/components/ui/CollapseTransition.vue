<script setup lang="ts">
import { Transition } from 'vue'

withDefaults(
  defineProps<{
    mode?: 'out-in' | 'in-out' | 'default'
  }>(),
  { mode: 'out-in' },
)

const fadeMs = 200
const expandMs = 320
let lastHeight = 0

function onLeave(el: Element, done: () => void) {
  const node = el as HTMLElement
  lastHeight = node.offsetHeight
  node.style.transition = `opacity ${fadeMs}ms ease`
  node.style.opacity = '0'
  window.setTimeout(done, fadeMs)
}

function onEnter(el: Element, done: () => void) {
  const node = el as HTMLElement
  const target = node.scrollHeight
  const start = Math.min(lastHeight, target)
  node.style.height = `${start}px`
  node.style.overflow = 'hidden'
  node.style.opacity = '0'
  void node.offsetHeight
  node.style.transition = `height ${expandMs}ms ease, opacity ${expandMs}ms ease`
  node.style.height = `${target}px`
  node.style.opacity = '1'
  window.setTimeout(() => {
    node.style.height = ''
    node.style.overflow = ''
    node.style.transition = ''
    node.style.opacity = ''
    done()
  }, expandMs)
}
</script>

<template lang="pug">
Transition(
  :mode="mode"
  :css="false"
  @leave="onLeave"
  @enter="onEnter"
)
  slot
</template>
