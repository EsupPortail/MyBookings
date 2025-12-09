<template>
  <q-layout view="hHr lpR fFr" :lang="$i18n.locale">
    <q-header v-if="checkEncrypted() && notInRoutes()" reveal elevated class="bg-primary text-white" height-hint="98" style="right: 0!important;" role="banner">
      <q-toolbar>
        <div v-if="customContent.length>0" v-html="customContent" class="q-mr-lg" aria-label="Contenu personnalisé" role="complementary" />
        <q-toolbar-title @click="returnToHomePage" @keydown.enter="returnToHomePage" id="iconBar" aria-label="MyBookings home page" tabindex="0" style="min-width: 10%">
          <q-img src="/images/MyBookings_blanc.png" fit="scale-down" height="40px" class="iconMenu" alt="Mybookings icon" />
        </q-toolbar-title>
        <nav class="full-width row items-center justify-evenly">
          <q-btn flat to="/book" icon="event" :label="getNavLabel('book')" class="text-white" :aria-label="$t('book')"/>
          <q-btn flat to="/me" icon="person" :label="getNavLabel('myBookings')" :aria-label="$t('myBookings')" class="text-white"/>
          <q-btn flat v-if="storedUser.isUserAdminOrModerator()" to="/administration/site" icon="domain" :label="getNavLabel('adminLabel')" :aria-label="$t('adminLabel')" class="text-white"/>
          <q-btn flat v-if="storedUser.hasRoles('ROLE_ADMIN')" to="/manage" icon="settings" :label="getNavLabel('paramLabel')" class="mobile-hide text-white"/>
        </nav>
        <q-btn v-if="storedUser.hasRoles('ROLE_SWITCHED_USER') && !$q.screen.lt.sm" color="red" @click="backToAdmin" icon="redo" label="Rôle principal" no-caps/>
        <q-btn v-if="storedUser.hasRoles('ROLE_SWITCHED_USER') && $q.screen.lt.sm" color="red" @click="backToAdmin" icon="redo" rounded no-caps/>
        <q-space/>

        <navbar-actions :basket-count="basketUser.cart.length" @toggle-basket="toggleLeftDrawer" />

      </q-toolbar>
    </q-header>

    <custom-sidebar></custom-sidebar>

    <q-page-container>
      <q-page padding>
        <router-view :key="$route.fullPath"> </router-view>
      </q-page>
    </q-page-container>

  </q-layout>

</template>

<script setup>
import { counter, user } from './store/counter';
import {onMounted, ref, watch} from 'vue';
import {useQuasar} from "quasar";
import {useI18n} from "vue-i18n";
import CustomSidebar from "./components/customSidebar.vue";
import NavbarActions from "./components/NavbarActions.vue";
import {basket} from "./store/basket";
import {useRoute} from "vue-router";

const store = counter();
const storedUser = user();
const basketUser = basket();
const { locale, t } = useI18n();
const customContent = ref('');
const $route = useRoute();

const getNavLabel = (label) => {
  if(!$q.screen.lt.sm) {
    return t(label)
  }
  return '';
};

const toggleLeftDrawer =  () => {
  const menuEnt = document.getElementById("menuEnt");
  if(menuEnt && menuEnt.classList.contains('active')) {
    menuEnt.dispatchEvent(new Event('click'));
  }
  storedUser.leftDrawer = !storedUser.leftDrawer
};


const encryptedUsername = ref(null);

const $q = useQuasar();

const checkEncrypted = () => {
  return typeof document?.getElementById("app").dataset.encryptedUsername !== 'undefined';
};

const notInRoutes = () => {
  return !$route.path.includes('/planning/') && !$route.path.includes('/about');
};

const backToAdmin = () => {
  window.location.href = '?_switch_user=_exit';
};

const returnToHomePage = () => {
  window.location.href = '/book';
};

const initializeContent = () => {
    const contentDiv = document.getElementById("customContent");
    if(contentDiv) {
      customContent.value = contentDiv.innerHTML;
    }
};

const isMobile = () => {
  return window.innerWidth <= 500;
};

onMounted(() => {
  if(typeof document.getElementById("app").dataset.encryptedUsername !== 'undefined') {
    initializeContent();
    storedUser.getUsername();
  }
  storedUser.isMobile = isMobile();
})

watch(locale, (newLocale) => {
  localStorage.setItem('userLocale', newLocale);
});
</script>

<style>

#imgPC, #iconBar {
  display: block;
}
@media (max-width: 700px) {
  #imgPC, #iconBar {
    display: none;
  }
}

.iconMenu {
  cursor: pointer;
}
</style>