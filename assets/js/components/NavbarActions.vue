<template>
    <q-separator vertical inset color="white" class="q-mx-sm" />
    <q-btn-dropdown round flat dense text-color="white" dropdown-icon="more_vert" :aria-label="$t('optionsMenu')">
      <q-list>
        <q-item clickable v-close-popup href="/help">
          <q-item-section avatar>
            <q-icon name="help" />
          </q-item-section>
          <q-item-section>
            <q-item-label>{{ $t('help') }}</q-item-label>
          </q-item-section>
        </q-item>

        <q-separator />

        <q-item-label header>{{ $t('languageSelection') }}</q-item-label>
        <q-item
          class="q-mb-sm"
          v-for="opt in localeOptions"
          :key="opt.value"
          clickable
          v-close-popup
          @click="$i18n.locale = opt.value"
          :active="$i18n.locale === opt.value"
        >
          <q-item-section avatar>
            <span v-html="opt.title" style="font-size: 16px"></span>
          </q-item-section>
          <q-item-section>
            <q-item-label>{{ opt.label }}</q-item-label>
          </q-item-section>
        </q-item>

        <q-separator />

        <q-item clickable v-close-popup @click="$emit('toggle-basket')">
          <q-item-section avatar>
            <q-avatar>
              <q-img src="/images/icon_basket_user_black.png" width="20px" alt="Basket icon"/>
            </q-avatar>
          </q-item-section>
          <q-item-section>
            <q-item-label>{{ $t('basket') }}</q-item-label>
          </q-item-section>
          <q-item-section side v-if="basketCount > 0">
            <q-badge color="red" rounded>{{ basketCount }}</q-badge>
          </q-item-section>
        </q-item>
      </q-list>
    </q-btn-dropdown>
</template>

<script setup>
import { useQuasar } from 'quasar';

defineProps({
  basketCount: {
    type: Number,
    default: 0
  }
});

defineEmits(['toggle-basket']);

const $q = useQuasar();

const localeOptions = [
  { value: 'fr', label: 'Français', title: '&#127467;&#127479;' },
  { value: 'en', label: 'English', title: '&#127468;&#127463;' }
];
</script>

<style scoped>
.floatingBadgeBasket {
  position: absolute;
  bottom: 6px;
  left: 0;
  cursor: inherit;
  padding: 1px 4px!important;
}

.langItem {
  transition: background-color 0.2s ease;
}

.langItem:focus,
.langItem:focus-within {
  background-color: rgba(0, 0, 0, 0.15);
  outline-offset: -2px;
}

:deep(.q-manual-focusable--focused > .q-focus-helper) {
  background: none!important;
}

:deep(.q-item--active.langItem) {
  font-weight: bold;
}

.langItem.q-manual-focusable--focused {
  background-color: rgba(230, 230, 230, 0.55);
}
</style>

