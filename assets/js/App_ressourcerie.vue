<template>
  <q-layout view="hHr lpR fFr">
    <q-header reveal elevated class="bg-primary text-white" height-hint="98" style="right: 0!important;">
      <q-toolbar>

        <q-toolbar-title style="max-width: 13%; min-width: 10%">
          <q-btn style="height: 45px; width: 100%" flat @click="returnToHomePage">Ressourcerie</q-btn>
        </q-toolbar-title>
          <!--
          <q-select
              v-model="$i18n.locale"
              :options="localeOptions"
              :label="null"
              dense
              borderless
              emit-value
              map-options
              options-dense
              label-color="white"
              hide-dropdown-icon
              style="width: 3%; margin-bottom: 10px; margin-right: 20px"
          >
            <template v-slot:selected-item="scope">
              <span style="color: white; font-size: 24px" v-if="scope.opt.value === 'fr'">{{ scope.opt.title }}</span>
              <span style="color: white; font-size: 24px" v-if="scope.opt.value === 'en'">{{ scope.opt.title }}</span>
            </template>
          </q-select>
          -->
        <q-tabs align="justify" inline-label style="width: 68%">
          <q-route-tab to="/ressourcerie" icon="event" :label="bookingLabel" />
          <q-route-tab to="/ressourcerie/me" icon="person" :label="myBookingsLabel" />
          <q-route-tab to="/ressourcerie/submission" icon="unarchive" :label="mySubmissionsLabel" />
          <q-route-tab v-if="storedUser.isUserAdminOrModerator(true)" to="/ressourcerie/administration/site" icon="domain" :label="adminLabel" />
          <q-route-tab v-if="storedUser.hasRoles('ROLE_ADMIN_RESSOURCERIE')" to="/ressourcerie/sit" icon="domain" :label="menuSITLabel" />
          <q-route-tab v-if="storedUser.hasRoles('ROLE_ADMIN')" to="/manage" icon="settings" :label="paramLabel" />
        </q-tabs>
        <!--
        <q-btn class="absolute-right" flat @click="toggleLeftDrawer" dense style="margin-right: 10px">
          <q-img src="/images/icon_basket_user_white.png" width="24px">
          </q-img>
          <q-badge v-if="basketUser.cart.length>0" color="red" rounded class="floatingBadgeBasket">{{basketUser.cart.length}}</q-badge>
        </q-btn>
        -->
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

<script>
import { counter, user } from './store/counter';
import {ref} from 'vue';
import {useQuasar} from "quasar";
import CustomSidebar from "./components/customSidebar.vue";
import {basket} from "./store/basket";

export default {
  name: "appRessourcerie",
  components: {CustomSidebar},
  setup () {
    const store = counter();
    const storedUser = user();
    const basketUser = basket();
    return {
      store,
      storedUser,
      basketUser,
      tab: ref('tab1'),
      toggleLeftDrawer () {
        storedUser.leftDrawer = !storedUser.leftDrawer
      },
      localeOptions: [
        { value: 'fr', label: '🇫🇷 Français', title: '🇫🇷' },
        { value: 'en', label: '🇬🇧 English', title: '🇬🇧' }
      ],
    }
  },
  data() {
    return {
      $q: useQuasar(),
      bookingLabel:"",
      myBookingsLabel: "",
      mySubmissionsLabel: "",
      adminLabel: "",
      menuSITLabel: "",
      paramLabel: ""
    }
  },
  mounted() {
    this.getLabelMenu();
    this.storedUser.getUsername();
    this.storedUser.isMobile = this.isMobile();
  },
  methods: {
    isMobile() {
      return window.innerWidth <= 500;
    },
    returnToHomePage() {
      this.$router.push('/ressourcerie');
      this.basketUser.selection = null;
      this.basketUser.start = null;
      this.basketUser.end = null;
    },
    getLabelMenu(){

      if(window.innerWidth < 700) {
        this.bookingLabel = null;
        this.myBookingsLabel = null;
        this.mySubmissionsLabel = null;
        this.adminLabel = null;
        this.menuSITLabel = null;
        this.paramLabel = null;
      } else {

        this.bookingLabel = this.$t('book');
        this.myBookingsLabel = this.$t('ressourcerie.mes_demandes_de_bien');
        this.mySubmissionsLabel = this.$t('ressourcerie.mes_soumissions');
        this.adminLabel = this.$t('adminLabel');
        this.menuSITLabel = this.$t('ressourcerie.menu_sit');
        this.paramLabel = this.$t('paramLabel');
      }
    }
  }
}
</script>

<style>

#imgPC {
  display: block;
}
#imgMobile {
  display: none;
}
@media (max-width: 700px) {
  #imgPC {
    display: none;
  }
  #imgMobile {
    display: block;
  }
}
.floatingBadgeBasket {
  position: absolute;
  bottom: 6px;
  left: 0;
  cursor: inherit;
  padding: 1px 4px!important;
}

.iconMenu {
  cursor: pointer;
}

</style>