<script setup lang="ts">
import { computed, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  MoreHorizontal,
  Plus,
  Search,
  SlidersHorizontal,
  UserRoundCheck,
  UserRoundMinus,
} from '@lucide/vue'
import UserAvatar from '@/components/UserAvatar.vue'
import { useAuthSession } from '@/queries/authQueries'
import { useDeactivateMemberMutation, useMembersQuery } from '@/queries/memberQueries'

const { isAdmin, twoFactorEnabled, user } = useAuthSession()
const { t } = useI18n()
const membersQuery = useMembersQuery()
const deactivateMemberMutation = useDeactivateMemberMutation()
const searchQuery = ref('')
const statusFilter = ref<'all' | 'active' | 'inactive'>('all')
const actionError = ref('')
const canManageMembers = computed(() => isAdmin.value && twoFactorEnabled.value)
const members = computed(() => membersQuery.data.value ?? [])
const activeMemberCount = computed(() => members.value.filter((m) => m.isActive).length)
const filteredMembers = computed(() => {
  const normalizedQuery = searchQuery.value.trim().toLocaleLowerCase('ru')

  return members.value.filter((member) => {
    const matchesQuery =
      !normalizedQuery ||
      [member.name, member.favoriteGenre].some((value) =>
        value.toLocaleLowerCase('ru').includes(normalizedQuery),
      )
    const matchesStatus =
      statusFilter.value === 'all' ||
      (statusFilter.value === 'active' && member.isActive) ||
      (statusFilter.value === 'inactive' && !member.isActive)

    return matchesQuery && matchesStatus
  })
})

async function deactivateMember(id: number) {
  if (!confirm(t('members.deactivateConfirm'))) return
  actionError.value = ''
  try {
    await deactivateMemberMutation.mutateAsync(id)
  } catch (e: unknown) {
    actionError.value = (e as Error).message || t('members.deactivateError')
  }
}
</script>

<template lang="pug">
main.members.container
  .members__header
    .members__heading
      h1.members__title {{ $t('members.title') }}
      span.label-text {{ $t('members.count', { n: activeMemberCount }) }}
    .members__toolbar
      label.members__search
        Search.members__search-icon(:size="18" aria-hidden="true")
        input.field-control(
          v-model="searchQuery"
          type="search"
          :placeholder="t('members.searchPlaceholder')"
          :aria-label="t('members.searchAria')"
        )
      label.members__filter
        SlidersHorizontal(:size="17" aria-hidden="true")
        select.field-control(v-model="statusFilter" :aria-label="t('members.filterAria')")
          option(value="all") {{ $t('members.all') }}
          option(value="active") {{ $t('members.active') }}
          option(value="inactive") {{ $t('members.inactive') }}
      RouterLink.button.button--primary.label-text(v-if="canManageMembers" to="/members/add")
        Plus(:size="16" aria-hidden="true")
        span {{ $t('members.add') }}

  section.panel.members__notice(v-if="isAdmin && !twoFactorEnabled")
    p.body-text {{ $t('members.manageDisabled') }}
    RouterLink.button.button--secondary.label-text(to="/profile/settings") {{ $t('members.setup2fa') }}
  section.panel.members__notice.members__notice--error(v-if="actionError" aria-live="polite")
    p.body-text {{ actionError }}

  section.panel(v-if="membersQuery.isLoading.value" aria-live="polite")
    p.body-text {{ $t('members.loading') }}
  section.panel(v-else-if="membersQuery.error.value" aria-live="polite")
    p.body-text {{ $t('members.error') }}
  .members__grid(v-else-if="filteredMembers.length")
    RouterLink.member-card-wrapper(
      v-for="member in filteredMembers"
      :key="member.id"
      :class="{ 'member-card--inactive': !member.isActive }"
      :to="`/members/${member.id}`"
    )
      .member-card
        .member-card__hero
          UserAvatar.member-card__avatar(:name="member.name" :avatar-url="member.avatarUrl")
          .member-card__info
            .member-card__name-row
              h2.member-card__name {{ member.name }}
              span.badge.badge--reading.label-text(v-if="member.id === user?.id") {{ $t('members.you') }}
            span.member-card__status(:class="{ 'member-card__status--inactive badge--muted': !member.isActive }")
              span.member-card__status-dot(aria-hidden="true")
              span {{ member.isActive ? $t('members.statusActive') : $t('members.statusInactive') }}
          button.member-card__menu(type="button" :aria-label="t('members.actionsAria')" @click.prevent)
            MoreHorizontal(:size="18")
        .member-card__owls(:aria-label="t('profile.owlsAria')")
          .member-card__owl
            img.member-card__owl-icon.member-card__owl-icon--gold(src="/favicon.svg" :alt="t('profile.owlGold')")
            span.member-card__owl-value {{ member.stats.goldOwls }}
          .member-card__owl
            img.member-card__owl-icon.member-card__owl-icon--silver(src="/favicon.svg" :alt="t('profile.owlSilver')")
            span.member-card__owl-value {{ member.stats.silverOwls }}
          .member-card__owl
            img.member-card__owl-icon.member-card__owl-icon--bronze(src="/favicon.svg" :alt="t('profile.owlBronze')")
            span.member-card__owl-value {{ member.stats.bronzeOwls }}
        .member-card__stats
          .member-card__stat
            span.member-card__stat-value {{ member.stats.read }}
            span.label-text {{ $t('members.read') }}
          .member-card__stat
            span.member-card__stat-value {{ member.stats.proposed }}
            span.label-text {{ $t('members.proposed') }}
          .member-card__stat
            span.member-card__stat-value {{ member.stats.meetings }}
            span.label-text {{ $t('members.meetings') }}
        button.member-card__deactivate(
          v-if="canManageMembers && member.isActive && member.id !== user?.id"
          type="button"
          @click.prevent="deactivateMember(member.id)"
        )
          UserRoundMinus(:size="16" aria-hidden="true")
          span {{ $t('members.deactivate') }}
        button.member-card__activate(
          v-else-if="canManageMembers && !member.isActive"
          type="button"
          disabled
          @click.prevent
        )
          UserRoundCheck(:size="16" aria-hidden="true")
          span {{ $t('members.activate') }}
  section.panel.members__empty(v-else aria-live="polite")
    h2 {{ $t('members.noResults') }}
    p.body-text {{ $t('members.noResultsText') }}
</template>

<style scoped lang="scss">
@use '@/styles/breakpoints' as *;

.members__header {
  display: flex;
  flex-direction: column;
  align-items: stretch;
  justify-content: space-between;
  gap: var(--space-lg);
  margin-bottom: var(--space-xl);

  @include desktop {
    flex-direction: row;
    align-items: end;
  }
}

.members__heading {
  display: grid;
  gap: var(--space-sm);
}

.members__title {
  font-size: clamp(2.4rem, 5vw, 4rem);
}

.members__toolbar {
  display: flex;
  flex-direction: column;
  align-items: stretch;
  justify-content: flex-end;
  gap: var(--space-sm);
  min-width: min(100%, 44rem);

  @include tablet {
    flex-direction: row;
    align-items: center;
  }
}

.members__search,
.members__filter {
  position: relative;
  display: flex;
  align-items: center;
}

.members__search {
  flex: 1;
  min-width: 0;

  @include tablet {
    min-width: 17rem;
  }
}

.members__search input {
  width: 100%;
  padding: 0 1rem 0 2.8rem;
}

.members__search-icon {
  position: absolute;
  z-index: 1;
  left: 0.95rem;
  color: var(--text-subtle);
}

.members__filter {
  flex: 1;
  min-width: 0;

  @include tablet {
    flex: 0 0 10rem;
  }
}

.members__filter svg {
  position: absolute;
  z-index: 1;
  left: 0.85rem;
  color: var(--text-subtle);
}

.members__filter select {
  width: 100%;
  padding: 0 2.2rem 0 2.45rem;
  appearance: none;
  color: var(--text-main);
}

.members__toolbar .button {
  width: 100%;

  @include tablet {
    width: auto;
  }
}

.members__grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: var(--space-lg);

  @include tablet {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  @include tablet {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  @include desktop {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

.member-card-wrapper {
  container-type: inline-size;
  height: 100%;
}

.member-card {
  position: relative;
  display: flex;
  flex-direction: column;
  gap: var(--space-md);
  height: 100%;
  padding: var(--space-lg);
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-panel);
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 0.035), rgba(255, 255, 255, 0.014)),
    var(--bg-surface);
  box-shadow: var(--shadow-panel);
  color: inherit;
  transition:
    background-color 0.2s ease,
    border-color 0.2s ease,
    transform 0.2s ease;
}

.member-card:hover {
  border-color: var(--border-strong);
  background:
    linear-gradient(180deg, rgba(255, 255, 255, 0.045), rgba(255, 255, 255, 0.018)), var(--bg-panel);
  transform: translateY(-0.15rem);
}

.member-card--inactive {
  border-color: var(--border);
  background: var(--bg-panel);
  color: var(--text-muted);
  opacity: 0.58;
}

.member-card--inactive:hover {
  border-color: var(--border);
  background: var(--bg-panel);
}

.member-card--inactive .member-card__stat-value {
  color: var(--text-muted);
}

.member-card--inactive .member-card__owl-value {
  color: var(--text-muted);
}

.badge--muted {
  border-color: var(--border);
  background: transparent;
  color: var(--text-muted);
}

.member-card__hero {
  display: flex;
  align-items: flex-start;
  gap: var(--space-md);
}

.member-card__avatar {
  width: 3rem;
  height: 3rem;
  font-size: 1rem;
  flex-shrink: 0;
  border-color: var(--accent-border);
  color: var(--text-main);
}

.member-card__info {
  display: flex;
  flex-direction: column;
  gap: var(--space-xs);
  flex: 1;
}

.member-card__name-row {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  flex-wrap: wrap;
}

.member-card__name {
  font-size: 1.25rem;
  line-height: 1.3;
}

.member-card__status {
  display: inline-flex;
  align-items: center;
  gap: var(--space-sm);
  color: var(--text-muted);
  font-size: 0.9rem;
}

.member-card__status-dot {
  width: 0.42rem;
  height: 0.42rem;
  border-radius: 50%;
  background: var(--accent);
}

.member-card__status--inactive .member-card__status-dot {
  background: var(--text-subtle);
}

.member-card__menu {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 auto;
  width: 2.25rem;
  height: 2.25rem;
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-inner);
  color: var(--text-muted);
}

.member-card__owls {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0;
  padding-top: var(--space-sm);
  padding-bottom: var(--space-sm);
}

.member-card__owl {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: var(--space-xs);
  border-right: var(--border-width) solid var(--border);
}

.member-card__owl:last-child {
  border-right: 0;
}

.member-card__owl-icon {
  width: 1.15rem;
  height: 1.15rem;
  flex-shrink: 0;
}

.member-card__owl-icon--gold {
  filter: invert(78%) sepia(35%) saturate(800%) hue-rotate(355deg) brightness(95%) contrast(90%);
}

.member-card__owl-icon--silver {
  filter: invert(82%) sepia(8%) saturate(200%) hue-rotate(170deg) brightness(95%);
}

.member-card__owl-icon--bronze {
  filter: invert(68%) sepia(40%) saturate(600%) hue-rotate(345deg) brightness(90%);
}

.member-card__owl-value {
  color: var(--text-main);
  font-family: var(--font-mono);
  font-size: 0.95rem;
  font-weight: 600;
}

.member-card__stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0;
  padding-top: var(--space-md);
  padding-bottom: var(--space-md);
  border-top: var(--border-width) solid var(--border);
  border-bottom: var(--border-width) solid var(--border);
}

.member-card__stat {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--space-xs);
  border-right: var(--border-width) solid var(--border);
  text-align: center;

  @container (max-width: 320px) {
    gap: var(--space-sm);
  }
}

.member-card__stat:last-child {
  border-right: 0;
}

.member-card__stat-value {
  color: var(--text-main);
  font-family: var(--font-mono);
  font-size: 1.4rem;
  font-weight: 700;
  line-height: 1;
}

.member-card__deactivate,
.member-card__activate {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: var(--space-sm);
  margin-top: auto;
  min-height: 2.25rem;
  padding: 0.35rem 0.6rem;
  border: 0;
  background: transparent;
  color: var(--warn);
  font-family: var(--font-mono);
  font-size: 0.7rem;
  font-weight: 500;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  cursor: pointer;
  transition:
    border-color 0.15s ease,
    color 0.15s ease,
    background-color 0.15s ease;
}

.member-card__deactivate:hover {
  color: var(--warn);
  background: var(--warn-bg);
}

.member-card__activate {
  color: var(--accent);
}

.member-card__activate:disabled {
  opacity: 0.55;
}

.members__notice {
  display: flex;
  flex-direction: column;
  align-items: stretch;
  justify-content: space-between;
  gap: var(--space-md);
  margin-bottom: var(--space-lg);

  @include tablet {
    flex-direction: row;
    align-items: center;
  }
}

.members__notice--error {
  border-color: var(--danger);
  background: var(--danger-bg);
}
</style>
