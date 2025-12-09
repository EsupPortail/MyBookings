<template>
  <q-card-section class="no-padding">
    <div v-if="basketUser.selection === null">
      <q-card-section class="no-padding">
        <catalog-list-available v-if="basketUser.localization !== null || basketUser.subtype !== null" :resources="resources" :events="events" @clickOnCatalog="changeResourceList" @clickOnPrevious="sendPrevious"></catalog-list-available>
      </q-card-section>
      <div class="text-center text-h6" v-if="basketUser.localization === null && basketUser.subtype === null">Aucune localisation ou sous-catégorie sélectionnée</div>
    </div>
    <div v-else>
      <div v-if="$route.name === 'scheduleCatalog' || $route.name === 'scheduleRessource'">
        <q-btn class="xs-hide" color="primary" icon="arrow_left" @click="$router.back()" :label="$t('return')" />
      </div>
      <div v-else>
        <q-btn class="xs-hide" color="primary" icon="arrow_left" @click="clearBasket" :label="$t('return')" />
        <q-btn class="xs q-mt-lg centerButton" color="primary" icon="arrow_left" @click="clearBasket" :label="$t('return')" />
      </div>
      <q-card-section :horizontal="!$q.screen.lt.sm">
          <q-card-section class="xs-hide infoCatalogue">
              <basket-dialog v-if="basketUser.selection.view ==='Lot'" :is-resource="basketUser.resourceId !== null"></basket-dialog>
              <basket-dialog v-if="basketUser.selection.view ==='Collection'" :isResource="true" :showResourceList="true" :resourceList="resourcesList" :idResource="idSelectedResource" @resourceChange="changeResource"></basket-dialog>
          </q-card-section>
          <q-separator class="xs-hide" vertical/>
          <q-card-section class="schedulerForDay">
            <SchedulerForDay v-if="basketUser.selection !== null && events.value !== null" :idResource="idSelectedResource" :resources="resourcesList" :events="events"></SchedulerForDay>
            <div v-else class="q-pa-md q-gutter-sm" style="text-align: center;">
              <q-circular-progress
                  indeterminate
                  rounded
                  size="90px"
                  color="primary"
                  class="center"
              />
              <p>Chargement en cours...</p>
            </div>
          </q-card-section>
          <q-card-section class="xs basketUserSelection">
            <basket-dialog v-if="basketUser.selection.view ==='Lot'" :is-resource="basketUser.resourceId !== null"></basket-dialog>
            <basket-dialog v-if="basketUser.selection.view ==='Collection'" :isResource="true" :showResourceList="true" :resourceList="resourcesList" :idResource="idSelectedResource" @resourceChange="changeResource"></basket-dialog>
          </q-card-section>
      </q-card-section>
    </div>
  </q-card-section>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { basket } from '../../store/basket';
import { user } from '../../store/counter';
import { booking } from '../../store/booking';
import { date } from 'quasar';
import SchedulerForDay from "../scheduler/SchedulerForDay.vue";
import basketDialog from "../dialog/basketDialog.vue";
import {getCatalogFromParameters, getCatalogFromParametersBySubtype} from "../../api/CatalogRessource";
import {loadBookingsFromManyCatalogs, loadBookingsFromUser} from "../../api/Booking";
import {clearRolesWithoutAdmin} from "../../utils/userUtils";
import CatalogListAvailable from "../catalogListAvailable.vue";

const emit = defineEmits(['clickOnPrevious']);
const resources = ref([]);
const events = ref([]);
const resourcesList = ref([]);
const idSelectedResource = ref(null);
const userStore = user();
const basketUser = basket();
const bookingsStore = booking();

const clearResources = () => {
  while (resources.value.length > 0) {
    resources.value.pop();
  }
};

const loadBookingsFromCatalogue = async (catalogues, start) => {
  let dateStart = new Date(start);
  let dateEnd = new Date(dateStart);
  dateEnd.setDate(dateEnd.getDate() + 1);
  events.value = [];
  dateStart = date.formatDate(dateStart, 'YYYY-MM-DDTHH:mm:ss');
  dateEnd = date.formatDate(dateEnd, 'YYYY-MM-DDTHH:mm:ss');
  if (catalogues.length > 0) {
    loadBookingsFromManyCatalogs(idSelectedResource.value !== null ? null : catalogues, dateStart, dateEnd, 'countBookings', idSelectedResource.value).then(response => {
      events.value = Object.values(response.data);
      bookingsStore.events = events.value;
    });
  }
};

const getSelection = computed(() => basketUser.selection);

const loadCatalogueFromType = () => {
  let localizationfilter = "";
  if (basketUser.localization !== null) {
    if(typeof basketUser.localization.childs === 'undefined') {
      localizationfilter = '&localization=' + basketUser.localization.id;
    } else {
      localizationfilter = '&localization=' + getChilds(basketUser.localization);
    }
  }

  let start = date.extractDate(basketUser.start, 'DD/MM/YYYY');
  let end = date.extractDate(basketUser.end, 'DD/MM/YYYY');
  end.setHours(23);
  end.setMinutes(59);
  start = date.formatDate(start, 'YYYY-MM-DDTHH:mm:ss');
  end = date.formatDate(end, 'YYYY-MM-DDTHH:mm:ss');
  let roles = clearRolesWithoutAdmin();
  if(basketUser.selection !== null) {
    loadBookingsFromCatalogue([basketUser.selection], start);
  } else {
    if(basketUser.subtype !== null) {
      clearResources();
      let subTypeID = basketUser.subtype.id;
      getCatalogFromParametersBySubtype(subTypeID, Object.values(roles) + localizationfilter).then(response => {
        response.data.forEach(element => {
          resources.value.push(element);
        });
        if(response.data.length === 1) {
          basketUser.selection = response.data[0];
          changeResourceList(response.data[0]);
        }
        // Requêtage des bookings en fonction des catalogues
        loadBookingsFromCatalogue(response.data, start);
      });
    } else {
      clearResources();
      getCatalogFromParameters(Object.values(roles) + localizationfilter).then(response => {
        response.data.forEach(element => {
          resources.value.push(element);
        });

        if(response.data.length === 1) {
          basketUser.selection = response.data[0];
          changeResourceList(response.data[0]);
        }
        // Requêtage des bookings en fonction des catalogues
        loadBookingsFromCatalogue(response.data, start);
      });
    }
  }


};

const getChilds = function (localization) {
  let childs = localization.id;
  localization.childs.forEach(function (child) {
      if(typeof child.childs !== 'undefined') {
        childs+= ','+getChilds(child);
      } else {
        childs += ','+child.id;
      }
  })
  return childs;
}

const parseResourceFromCatalogue = id => {
  resourcesList.value.forEach(resource => {
    if (resource.id == id) {
      basketUser.selection.resource = [resource];
    }
  });
};

const changeResource = idResource => {
  idSelectedResource.value = idResource;
  parseResourceFromCatalogue(idSelectedResource.value);
  basketUser.resourceId = idResource;
  loadCatalogueFromType();
};

const changeResourceList = selection => {
  if ((basketUser.selection.view === "Collection") || basketUser.selection.view === "Lot" && basketUser.resourceId !== null) {
    resourcesList.value = selection.childs;
    idSelectedResource.value = selection.resource[0].id;
    parseResourceFromCatalogue(idSelectedResource.value);
    basketUser.resourceId = idSelectedResource.value;
  }
};

const sendPrevious = () => {
  emit('clickOnPrevious');
};

const clearBasket = function () {
  basketUser.resourceId= null;
  basketUser.selection = null;
};

//watch(getStart, loadCatalogueFromType);

watch(getSelection, () => {
  if (basketUser.selection === null) {
    idSelectedResource.value = null;
    let start = date.extractDate(basketUser.start, 'DD/MM/YYYY');
    start = date.formatDate(start, 'YYYY-MM-DDTHH:mm:ss');
    loadBookingsFromCatalogue(resources.value, start);
  }
})

watch(basketUser, (value) => {
  if(value.resourceId !== idSelectedResource.value && value.resourceId !== null) {
    changeResource(value.resourceId);
  }
});

onMounted(() => {
  if(basketUser.subtype !== null || basketUser.localization !== null) {
    loadCatalogueFromType();
  }
  if (basketUser.selection !== null) {
    if ((basketUser.selection.view === "Collection" && idSelectedResource.value === null) || (basketUser.selection.view === "Lot" && basketUser.resourceId !== null)) {
      const selection = {
        childs: basketUser.selection.resource,
        resource: basketUser.selection.resource,
        view: basketUser.selection.view,
      };
      changeResourceList(selection);
    }
    let start = date.extractDate(basketUser.start, 'DD/MM/YYYY');
    if(events.value.length === 0) {
      if(basketUser.resourceId !== null) {
        loadBookingsFromCatalogue([basketUser.selection], start.toISOString());
      } else {
        loadBookingsFromCatalogue(resources.value, start.toISOString());
      }
    }
  }
});
</script>

<style>
.q-calendar-resource__head--interval {
  font-size: 14px!important;
}

.infoCatalogue {
  max-width: 35%;
}

.schedulerForDay {
  width: 100%;
}
</style>