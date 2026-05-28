<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { ArrowUpDown, Users, ArrowUp, ArrowDown, Lock } from '@lucide/vue'
import { useI18n } from 'vue-i18n'
import UserAvatar from '@/components/UserAvatar.vue'
import { useReorderTurnOrderMutation } from '@/queries/memberQueries'
import type { TurnOrderMember } from '@/types/dashboard'

const { t } = useI18n()
const props = defineProps<{
  members: TurnOrderMember[]
  cycleStatus?: string | null
  isAdmin?: boolean
}>()

const isEditing = ref(false)
const editOrder = ref<TurnOrderMember[]>([])
const { mutate: saveOrder, isPending: isSaving } = useReorderTurnOrderMutation()

function startEdit() {
  editOrder.value = props.members.map((m) => ({ ...m }))
  isEditing.value = true
}

function cancelEdit() {
  isEditing.value = false
  editOrder.value = []
}

function moveUp(index: number) {
  const arr = editOrder.value
  const [item] = arr.splice(index, 1)
  arr.splice(index - 1, 0, item!)
}

function moveDown(index: number) {
  const arr = editOrder.value
  const [item] = arr.splice(index, 1)
  arr.splice(index + 1, 0, item!)
}

const canMoveUp = (index: number) => index > 1
const canMoveDown = (index: number) => index < editOrder.value.length - 1

function handleSave() {
  saveOrder(editOrder.value.map((m) => m.memberId), {
    onSuccess: () => {
      isEditing.value = false
    },
  })
}

const isCurrentTurn = (member: TurnOrderMember) => member.status === 'Текущий'
const isNextTurn = (member: TurnOrderMember) => member.status === 'Выбирает следующую'

const currentProcessMember = computed(() => {
  const proposer = props.members.find((m) => m.isCurrentCycleProposer)
  if (proposer) return proposer
  return props.members.find((m) => m.isChoosingNow || isCurrentTurn(m) || m.active)
})

const queueMembers = computed(() => props.members.filter((m) => m !== currentProcessMember.value))

const currentProcessBadge = computed(() => {
  if (!currentProcessMember.value) return null
  if (currentProcessMember.value.isChoosingNow) return t('dash.choosingBook')
  if (currentProcessMember.value.isCurrentCycleProposer) return t('dash.choseBook')
  if (isCurrentTurn(currentProcessMember.value)) return t('dash.choosingBook')
  if (currentProcessMember.value.active) return t('dash.choosingBook')
  return null
})

const allMembers = computed(() => {
  const result: { member: TurnOrderMember; isActive: boolean; isNext: boolean }[] = []
  if (currentProcessMember.value) {
    result.push({ member: currentProcessMember.value, isActive: true, isNext: false })
  }
  queueMembers.value.forEach((m) => {
    result.push({
      member: m,
      isActive: false,
      isNext: !!m.isNextSelector || isNextTurn(m) || !!m.active,
    })
  })
  return result
})

watch(
  () => props.members,
  () => {
    if (isEditing.value) {
      cancelEdit()
    }
  },
)
</script>

<template lang="pug">
section.panel.dashboard-card(aria-labelledby="turn-order-title")
  .section-header.section-header--compact
    .section-header__icon(aria-hidden="true")
      Users(:size="18")
    .section-header__content
      span#turn-order-title.label-text.section-header__title {{ $t('dash.turnOrder') }}
      p.section-header__description {{ $t('dash.turnOrderDesc') }}
    button.section-header__action.icon-button(
      v-if="isAdmin"
      @click="startEdit"
      :title="$t('common.edit')"
      aria-label="$t('common.edit')"
    )
      ArrowUpDown(:size="16")

  template(v-if="isEditing")
    .turn-order__list
      .turn-order__card.turn-order__card--editable(
        v-for="(item, index) in editOrder"
        :key="item.memberId"
        :class="{ 'turn-order__card--head': index === 0 }"
      )
        .turn-order__card-body
          .turn-order__person
            UserAvatar.turn-order__avatar(
              :name="item.name"
              :avatar-url="item.avatarUrl"
              size="sm"
            )
            .turn-order__info
              span.turn-order__name {{ item.name }}
              span.turn-order__badge-text.turn-order__badge-text--head(v-if="index === 0") {{ $t('dash.turnOrderCurrent') }}
          .turn-order__actions
            template(v-if="index === 0")
              Lock.turn-order__lock-icon(:size="14")
            template(v-else)
              button.turn-order__move-btn(
                @click="moveUp(index)"
                :disabled="!canMoveUp(index)"
                :aria-label="$t('common.moveUp')"
              )
                ArrowUp(:size="14")
              button.turn-order__move-btn(
                @click="moveDown(index)"
                :disabled="!canMoveDown(index)"
                :aria-label="$t('common.moveDown')"
              )
                ArrowDown(:size="14")
    .turn-order__actions-bar
      button.button.button--primary(
        @click="handleSave"
        :disabled="isSaving"
      ) {{ $t('common.save') }}
      button.button(
        @click="cancelEdit"
        :disabled="isSaving"
      ) {{ $t('common.cancel') }}

  template(v-else)
    .turn-order__list
      .turn-order__card(
        v-for="item in allMembers"
        :key="item.member.memberId"
        :class=`{
          'turn-order__card--active': item.isActive,
          'turn-order__card--next': item.isNext
        }`
      )
        template(v-if="item.isActive")
          .turn-order__card-header {{ $t('dash.turnOrderCurrent') }}
        .turn-order__card-body
          .turn-order__person
            UserAvatar.turn-order__avatar(
              :name="item.member.name"
              :avatar-url="item.member.avatarUrl"
              size="md"
            )
            .turn-order__info
              span.turn-order__name {{ item.member.name }}
              template(v-if="item.isActive")
                span.turn-order__badge-text {{ currentProcessBadge }}
              span.turn-order__badge-text.turn-order__badge-text--muted(v-else-if="item.isNext") {{ $t('dash.choosingNext') }}

    .turn-order__empty(v-if="!allMembers.length")
      p.body-text {{ $t('dash.turnOrderError') }}
</template>

<style scoped>
.turn-order__list {
  display: flex;
  flex-direction: column;
  gap: var(--space-sm);
}

.turn-order__card {
  padding: var(--space-sm) var(--space-md);
  border-radius: var(--radius-inner);
  border: var(--border-width) solid transparent;
  background: transparent;
}

.turn-order__card--active {
  border-color: var(--accent-border);
  background: linear-gradient(135deg, rgba(67, 224, 125, 0.06), rgba(67, 224, 125, 0.015));
  box-shadow: 0 0 16px rgba(67, 224, 125, 0.06);
}

.turn-order__card--next {
  border-color: var(--border-strong);
  background: var(--bg-hover);
}

.turn-order__card--head {
  opacity: 0.7;
}

.turn-order__card-header {
  color: var(--accent);
  font-size: 0.6rem;
  font-weight: 600;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  margin-bottom: var(--space-xs);
  opacity: 0.9;
}

.turn-order__card-body {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: var(--space-sm);
}

.turn-order__person {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
}

.turn-order__avatar {
  flex-shrink: 0;
}

.turn-order__info {
  display: flex;
  flex-direction: column;
  gap: 2px;
}

.turn-order__name {
  font-size: 0.95rem;
  font-weight: 600;
  color: var(--text-main);
}

.turn-order__badge-text {
  font-size: 0.65rem;
  font-weight: 500;
  color: var(--accent);
  letter-spacing: 0.02em;
}

.turn-order__badge-text--muted {
  color: var(--text-muted);
}

.turn-order__badge-text--head {
  color: var(--text-muted);
}

.turn-order__empty {
  padding: var(--space-md) var(--space-lg);
  text-align: center;
  color: var(--text-muted);
}

.turn-order__actions {
  display: flex;
  align-items: center;
  gap: 2px;
  flex-shrink: 0;
}

.turn-order__move-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 28px;
  height: 28px;
  border-radius: var(--radius-inner);
  border: var(--border-width) solid var(--border-strong);
  background: transparent;
  color: var(--text-muted);
  cursor: pointer;
  transition: all 0.15s ease;
}

.turn-order__move-btn:hover:not(:disabled) {
  background: var(--bg-hover);
  color: var(--text-main);
  border-color: var(--text-muted);
}

.turn-order__move-btn:disabled {
  opacity: 0.25;
  cursor: not-allowed;
}

.turn-order__lock-icon {
  color: var(--text-muted);
  opacity: 0.5;
}

.turn-order__actions-bar {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  padding: var(--space-sm) var(--space-md);
  justify-content: flex-end;
}

.section-header {
  display: flex;
  align-items: flex-start;
  gap: var(--space-sm);
  margin-bottom: var(--space-md);
}

.section-header__icon {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: var(--accent-bg);
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--accent);
  flex-shrink: 0;
}

.section-header__content {
  flex: 1;
}

.section-header__title {
  display: block;
  font-size: 0.95rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  color: var(--text-main);
  margin-bottom: 4px;
}

.section-header__description {
  font-size: 0.78rem;
  color: var(--text-muted);
  line-height: 1.5;
  margin: 0;
}

.section-header__action {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: var(--radius-inner);
  border: var(--border-width) solid var(--border-strong);
  background: transparent;
  color: var(--text-muted);
  cursor: pointer;
  transition: all 0.15s ease;
  flex-shrink: 0;
  margin-top: 2px;
}

.section-header__action:hover {
  background: var(--bg-hover);
  color: var(--text-main);
  border-color: var(--text-muted);
}
</style>
