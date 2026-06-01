<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, TransitionGroup } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  ArrowUpDown,
  BookMarked,
  MoreHorizontal,
  Plus,
  Search,
  SlidersHorizontal,
  UserRoundCheck,
  UserRoundMinus,
} from '@lucide/vue'
import FilterDropdown from '@/components/ui/FilterDropdown.vue'
import type { FilterOption } from '@/components/ui/FilterDropdown.vue'
import UserAvatar from '@/components/UserAvatar.vue'
import { useAuthSession } from '@/queries/authQueries'
import { useDashboardQuery } from '@/queries/dashboardQueries'
import {
  useActivateMemberMutation,
  useDeactivateMemberMutation,
  useInitReadingProgressMutation,
  useMembersQuery,
} from '@/queries/memberQueries'

type SortMode = 'newest' | 'oldest' | 'name' | 'read' | 'owls'

const { isAdmin, twoFactorEnabled, user } = useAuthSession()
const { t } = useI18n()
const membersQuery = useMembersQuery()
const dashboardQuery = useDashboardQuery()
const deactivateMemberMutation = useDeactivateMemberMutation()
const initReadingProgressMutation = useInitReadingProgressMutation()
const activateMemberMutation = useActivateMemberMutation()
const leaderMemberIds = computed(() => {
  const progress = dashboardQuery.data.value?.memberProgress
  if (!progress) return null
  return new Set(progress.map((m) => m.id))
})

const searchQuery = ref('')
const statusFilter = ref<'all' | 'active' | 'inactive'>('active')
const actionError = ref('')
const openMenuId = ref<number | null>(null)
const canManageMembers = computed(() => isAdmin.value && twoFactorEnabled.value)

function toggleMenu(id: number) {
  openMenuId.value = openMenuId.value === id ? null : id
}

function closeMenu() {
  openMenuId.value = null
}

onMounted(() => {
  document.addEventListener('click', closeMenu)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', closeMenu)
})
const members = computed(() => membersQuery.data.value ?? [])
const activeMemberCount = computed(() => members.value.filter((m) => m.isActive).length)
const statusOptions = computed<FilterOption[]>(() => [
  { value: 'all', label: t('members.all') },
  { value: 'active', label: t('members.active') },
  { value: 'inactive', label: t('members.inactive') },
])

const sortMode = ref<SortMode>('newest')

const sortOptions = computed<FilterOption[]>(() => [
  { value: 'newest', label: t('members.sortNewest') },
  { value: 'oldest', label: t('members.sortOldest') },
  { value: 'name', label: t('members.sortName') },
  { value: 'read', label: t('members.sortRead') },
  { value: 'owls', label: t('members.sortOwls') },
])

const filteredMembers = computed(() => {
  const normalizedQuery = searchQuery.value.trim().toLocaleLowerCase('ru')

  return members.value
    .filter((member) => {
      const matchesQuery =
        !normalizedQuery ||
        [member.name, ...(member.favoriteGenres?.map((g) => g.name) ?? [])].some((value) =>
          value.toLocaleLowerCase('ru').includes(normalizedQuery),
        )
      const matchesStatus =
        statusFilter.value === 'all' ||
        (statusFilter.value === 'active' && member.isActive) ||
        (statusFilter.value === 'inactive' && !member.isActive)

      return matchesQuery && matchesStatus
    })
    .sort((a, b) => {
      if (sortMode.value === 'name') {
        return a.name.localeCompare(b.name, 'ru')
      }
      if (sortMode.value === 'read') {
        return b.stats.read - a.stats.read
      }
      if (sortMode.value === 'owls') {
        const aOwls = a.stats.goldOwls + a.stats.silverOwls + a.stats.bronzeOwls
        const bOwls = b.stats.goldOwls + b.stats.silverOwls + b.stats.bronzeOwls
        return bOwls - aOwls
      }
      if (sortMode.value === 'oldest') {
        return (a.createdAt ?? '').localeCompare(b.createdAt ?? '')
      }
      return (b.createdAt ?? '').localeCompare(a.createdAt ?? '')
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

async function addToLeaders(id: number) {
  actionError.value = ''
  try {
    await initReadingProgressMutation.mutateAsync(id)
  } catch (e: unknown) {
    actionError.value = (e as Error).message || t('dash.initReadingError')
  }
}

async function activateMember(id: number) {
  actionError.value = ''
  try {
    await activateMemberMutation.mutateAsync(id)
  } catch (e: unknown) {
    actionError.value = (e as Error).message || t('members.activateError')
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
      FilterDropdown(
        v-model="statusFilter"
        :options="statusOptions"
        :aria-label="t('members.filterAria')"
        :leading-icon="SlidersHorizontal"
      )
      FilterDropdown(
        v-model="sortMode"
        :options="sortOptions"
        :aria-label="t('members.sortAria')"
        :leading-icon="ArrowUpDown"
      )
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
  TransitionGroup.members__grid(name="list" tag="div" v-else-if="filteredMembers.length")
    RouterLink(
      v-for="member in filteredMembers"
      :key="member.id"
      v-slot="{ navigate }"
      :to="`/members/${member.id}`"
      custom
    )
      .member-card.member-card-wrapper(
        :class="{ 'member-card--inactive': !member.isActive }"
        @click="navigate"
      )
        .member-card__hero
          UserAvatar.member-card__avatar(:name="member.name" :avatar-url="member.avatarUrl")
          .member-card__info
            .member-card__name-row
              h2.member-card__name {{ member.name }}
              span.badge.badge--reading.label-text(v-if="member.id === user?.id") {{ $t('members.you') }}
            span.member-card__status(:class="{ 'member-card__status--inactive badge--muted': !member.isActive }")
              span.member-card__status-dot(aria-hidden="true")
              span {{ member.isActive ? $t('members.statusActive') : $t('members.statusInactive') }}
            span.member-card__joined.label-text {{ $t('members.memberSince', { date: member.memberSince }) }}
          .member-card__menu-wrap(v-if="canManageMembers")
            button.member-card__menu(type="button" :aria-label="t('members.actionsAria')" @click.prevent.stop="toggleMenu(member.id)")
              MoreHorizontal(:size="18")
            .member-card__dropdown(v-if="openMenuId === member.id" @click.stop)
              button.member-card__dropdown-item(
                v-if="canManageMembers && member.isActive && member.id !== user?.id"
                type="button"
                @click.stop="deactivateMember(member.id)"
              )
                UserRoundMinus(:size="14")
                span {{ $t('members.deactivate') }}
              button.member-card__dropdown-item.member-card__dropdown-item--action(
                v-if="canManageMembers && !member.isActive"
                type="button"
                @click.stop="activateMember(member.id)"
              )
                UserRoundCheck(:size="14")
                span {{ $t('members.activate') }}
              button.member-card__dropdown-item.member-card__dropdown-item--action(
                v-if="canManageMembers && member.isActive && leaderMemberIds && !leaderMemberIds.has(member.id)"
                type="button"
                @click.stop="addToLeaders(member.id)"
              )
                BookMarked(:size="14")
                span {{ $t('dash.addToLeaders') }}
        .member-card__owls(:aria-label="t('profile.owlsAria')")
          .member-card__owl
            .member-card__owl-row
              img.member-card__owl-icon.member-card__owl-icon--gold(src="/owl.svg" :alt="t('profile.owlGold')")
              span.member-card__owl-value {{ member.stats.goldOwls }}
          .member-card__owl
            .member-card__owl-row
              img.member-card__owl-icon.member-card__owl-icon--silver(src="/owl.svg" :alt="t('profile.owlSilver')")
              span.member-card__owl-value {{ member.stats.silverOwls }}
          .member-card__owl
            .member-card__owl-row
              img.member-card__owl-icon.member-card__owl-icon--bronze(src="/owl.svg" :alt="t('profile.owlBronze')")
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

  :deep(.filter-dropdown) {
    @include tablet {
      width: auto;
    }
  }
}

.members__search {
  position: relative;
  display: flex;
  align-items: center;
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
  gap: var(--space-sm);
  height: 100%;
  padding: var(--space-md);
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

  @include tablet {
    padding: var(--space-lg);
    gap: var(--space-md);
  }
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
  gap: var(--space-sm);

  @include tablet {
    gap: var(--space-md);
  }
}

.member-card__avatar {
  width: 2.5rem;
  height: 2.5rem;
  font-size: 0.85rem;
  flex-shrink: 0;
  border-color: var(--accent-border);
  color: var(--text-main);

  @include tablet {
    width: 3rem;
    height: 3rem;
    font-size: 1rem;
  }
}

.member-card__info {
  display: flex;
  flex-direction: column;
  gap: var(--space-xs);
  flex: 1;
}

.member-card__joined {
  color: var(--text-subtle);
  font-size: 0.6rem;
}

.member-card__name-row {
  display: flex;
  align-items: center;
  gap: var(--space-xs);
  flex-wrap: wrap;
}

.member-card__name {
  font-size: 1.05rem;
  line-height: 1.3;

  @include tablet {
    font-size: 1.25rem;
  }
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

.member-card__menu-wrap {
  position: relative;
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
  cursor: pointer;
  transition:
    border-color 0.15s ease,
    color 0.15s ease;
}

.member-card__menu:hover {
  border-color: var(--border-strong);
  color: var(--text-main);
}

.member-card__dropdown {
  position: absolute;
  z-index: 10;
  top: 100%;
  right: 0;
  min-width: 14rem;
  padding: var(--space-xs);
  margin-top: var(--space-xs);
  border: var(--border-width) solid var(--border);
  border-radius: var(--radius-inner);
  background: var(--bg-elevated);
  box-shadow: var(--shadow-panel);
}

.member-card__dropdown-item {
  display: flex;
  align-items: center;
  gap: var(--space-sm);
  width: 100%;
  padding: 0.55rem 0.75rem;
  border: 0;
  border-radius: var(--radius-inner);
  background: transparent;
  color: var(--warn);
  font-family: var(--font-mono);
  font-size: 0.72rem;
  font-weight: 500;
  letter-spacing: 0.04em;
  text-align: left;
  text-transform: uppercase;
  cursor: pointer;
  transition:
    background-color 0.15s ease,
    color 0.15s ease;
}

.member-card__dropdown-item:hover {
  background: var(--warn-bg);
  color: var(--warn);
}

.member-card__dropdown-item--action {
  color: var(--text-main);
}

.member-card__dropdown-item--action:hover {
  background: var(--accent-bg);
  color: var(--accent);
}

.member-card__owls {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0;
  padding: var(--space-sm) 0;
}

.member-card__owl {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--space-xs);
  padding: var(--space-xs) 0;
  border-right: var(--border-width) solid var(--border);
}

.member-card__owl:last-child {
  border-right: 0;
}

.member-card__owl-row {
  display: inline-flex;
  align-items: center;
  gap: var(--space-xs);
}

.member-card__owl-icon {
  width: 1rem;
  height: 1rem;
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
  font-variant-numeric: tabular-nums;
}

.member-card__stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 0;
  padding: var(--space-sm) 0;
  border-top: var(--border-width) solid var(--border);
}

.member-card__stat {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: var(--space-xs);
  padding: var(--space-xs) 0;
  border-right: var(--border-width) solid var(--border);
  text-align: center;
}

.member-card__stat:last-child {
  border-right: 0;
}

.member-card__stat-value {
  color: var(--text-main);
  font-family: var(--font-mono);
  font-size: 1rem;
  font-weight: 600;
  line-height: 1;
  font-variant-numeric: tabular-nums;
}

.member-card__stat .label-text {
  font-size: 0.55rem;
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
