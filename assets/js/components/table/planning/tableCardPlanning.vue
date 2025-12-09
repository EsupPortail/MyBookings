<template>
  <q-table
    :rows="resources"
    v-model:pagination="pagination"
    row-key="id"
    :loading="loading"
    card-container-class="card-container"
    flat
    grid>

    <template v-slot:top>
      <div class="full-width bg-primary text-white text-center">
        <div class="row justify-between items-center">
          <q-toolbar-title id="iconBar" style="max-width: 10%; min-width: 10%">
            <a href="/book"><q-btn id="imgPC" style="width: 100%" flat><q-img src="/images/MyBookings_blanc.png" fit="scale-down" height="40px" class="iconMenu" /></q-btn></a>
          </q-toolbar-title>
          <span class="custom-big-font-size">{{ headerTitle }}</span>
          <span class="custom-big-font-size">{{ currentDateTime }}</span>
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
            style="width: 3%; margin-bottom: 10px; margin-right: 20px">
            
            <template v-slot:selected-item="scope">
              <span style="color: white; font-size: 24px" v-if="scope.opt.value === 'fr'" v-html="scope.opt.title"></span>
              <span style="color: white; font-size: 24px" v-if="scope.opt.value === 'en'" v-html="scope.opt.title"></span>
            </template>
          </q-select>
        </div>
      </div>
    </template>

    <template v-slot:item="props">
      <transition
        :key="props.row['animationKey']"
        appear
        enter-active-class="animated flipInX slower"
      >
        <div class="q-pa-xs q-gutter-xs">
          <q-card style="width: 360px; height: 175px;" :class="`border-${props.row['currentBooking'] ? 'negative' : 'primary'}`">
            <q-card-section horizontal>
              <q-img
                class="col"
                style="max-width: 120px; min-width: 120px; height: 175px;"
                :src="'/uploads/' + (props.row.image ? props.row.image : defaultCatalogImage)"
              >
                <template v-slot:error>
                  <div class="absolute-full flex flex-center bg-grey text-white border-box">
                    <q-icon size="md" name="no_photography"/>
                  </div>
                </template>
              </q-img>

              <q-card-actions style="width: 240px;" vertical class="justify-around">
                <div class="row items-center">
                  <q-icon class="q-pr-sm" flat round :color="props.row['currentBooking'] ? 'negative' : 'primary'" name="inventory" size="md"/>



                  <span v-if="props.row.capacity == undefined" :class="`custom-big-font-size text-${props.row['currentBooking'] ? 'negative' : 'primary'}`">{{ props.row.title }}</span>
                  
                  <div v-else style="max-width: 180px;" class="column">
                    <span :class="`custom-big-font-size text-${props.row['currentBooking'] ? 'negative' : 'primary'}`">{{ props.row.title }}</span>
                    <span :class="`ellipsis custom-small-font-size text-${props.row['currentBooking'] ? 'negative' : 'primary'}`">
                      {{ $t('capacity') }} : {{ props.row.capacity }}                   
                    </span>
                  </div>



                </div>
                <div class="row items-center">
                  <q-icon class="q-py-sm q-pr-sm" flat round :color="props.row['currentBooking'] ? 'negative' : 'primary'" name="schedule" size="md"/>
                  <div style="max-width: 180px;" class="column" v-if="props.row['currentBooking']">
                    <span :class="`custom-big-font-size text-${props.row['currentBooking'] ? 'negative' : 'primary'}`">{{ $t('busy') }}</span>
                    <span :class="`ellipsis custom-small-font-size text-${props.row['currentBooking'] ? 'negative' : 'primary'}`">
                      {{ props.row['currentBooking']['dateStart'] }} - {{ props.row['currentBooking']['dateEnd'] }}
                      <span v-if="showNames">
                        : {{ props.row['currentBooking'].user[0].displayUserName }}
                        <q-tooltip>
                          <ul>
                            <li v-for="user in props.row['currentBooking'].user">{{ user.displayUserName }}</li>
                          </ul>
                        </q-tooltip>
                      </span>
                    </span>
                  </div>
                  <div v-else class="column">
                    <span :class="`custom-big-font-size text-${props.row['currentBooking'] ? 'negative' : 'primary'}`">{{ $t('free') }}</span>
                    <span v-if="props.row['upcomingBooking']" :class="`custom-small-font-size text-${props.row['currentBooking'] ? 'negative' : 'primary'}`">{{ $t('until') + " " + props.row['upcomingBooking']['dateStart'] + " " }}</span>
                    <span v-else :class="`custom-small-font-size text-${props.row['currentBooking'] ? 'negative' : 'primary'}`">{{ $t('allDay') }}</span>
                  </div>
                </div>
                <div class="row items-center">
                  <q-icon class="q-pr-sm" flat round :color="props.row['upcomingBooking'] ? 'negative' : 'primary'" name="double_arrow" size="md"/>
                  <div class="column" style="max-width: 180px;">
                    <span :class="`custom-big-font-size text-${props.row['upcomingBooking'] ? 'negative' : 'primary'}`">{{ $t('upcomingBooking') }}</span>
                    <span v-if="props.row['upcomingBooking']" :class="`ellipsis custom-small-font-size text-${props.row['upcomingBooking'] ? 'negative' : 'primary'}`">
                      {{ props.row['upcomingBooking']['dateStart'] }} - {{ props.row['upcomingBooking']['dateEnd'] }}
                      <span v-if="showNames">
                        : {{ props.row['upcomingBooking'].user[0].displayUserName }}
                        <q-tooltip>
                            <ul>
                            <li v-for="user in props.row['upcomingBooking'].user">{{ user.displayUserName }}</li>
                          </ul>
                        </q-tooltip>
                      </span>
                    </span>
                    <span v-else :class="`custom-small-font-size text-${props.row['upcomingBooking'] ? 'negative' : 'primary'}`">{{ $t('free') }}</span>
                  </div>
                </div>
              </q-card-actions>
            </q-card-section>
          </q-card>
        </div>
      </transition>
    </template>

  </q-table>
</template>

<style lang="sass" scoped>
.border-primary,
.border-negative
  position: relative

  &::after
    content: ""
    position: absolute
    top: 0
    left: 0
    width: 100%
    height: 100%
    pointer-events: none 
    z-index: 10
    border: 2px solid

.border-primary::after
  border-color: var(--q-primary)

.border-negative::after
  border-color: var(--q-negative)
  
.custom-big-font-size
  font-size: 22px

.custom-small-font-size
  font-size: 14px

.ellipsis
  white-space: nowrap
  overflow: hidden
  text-overflow: ellipsis
  max-width: 100%
  display: block 

::v-deep .card-container
  display: flex
  justify-content: space-evenly
</style>

<script>
import { ref } from "vue";
import { date } from 'quasar'
import { loadResourcesAsync, areResourcesIdentical } from "../../../utils/planningCatalogUtils";

// Page data
const loading = ref(true);
const resources = ref([]);

const currentDateTime = ref(null);
const headerTitle = ref("");
const showNames = ref(false);
const defaultCatalogImage = ref("");

// Intervals
let dataInterval = null;
let dateTimeInterval = null;

// Petite clé globale quand j'ai besoin de changer la valeur de plein de choses d'un coup
let globalAnimationKey = 1;

export default {
  name: "tableCardPlanning",
  props: {
    id: {
      type: String,
      requinegative: true
    },
    anonymous: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      pagination: {
        page: 1,
        rowsPerPage: 30
      },
      localeOptions: [
        { value: 'fr', label: '🇫🇷 Français', title: '&#127467;&#127479;' },
        { value: 'en', label: '🇬🇧 English', title: '&#127468;&#127463;' }
      ],
      showNames,
      resources,
      currentDateTime,
      headerTitle,
      loading,
      defaultCatalogImage
    }
  },
  mounted() {
    showNames.value = !this.anonymous;
    this.dateTimeInterval = setInterval(() => {
      const timeStamp = Date.now()
      this.currentDateTime = date.formatDate(timeStamp, 'YYYY/MM/DD HH:mm:ss')
    }, 1000);

    this.loadPageResources();
    dataInterval = setInterval(this.nextPage, 10000);
  },

  methods: {
    loadPageResources() {
      this.loading = true;
      loadResourcesAsync(this.id, this.anonymous).then((value) => {
        this.pagination.page = 1;
        headerTitle.value = value.title;
        defaultCatalogImage.value = value.image;
        globalAnimationKey++;

        const totalPages = Math.ceil(value.resources.length / this.pagination.rowsPerPage);

        // Si on affiche qu'une seule page et qu'on avait déjà des données affichées avant,
        if(totalPages == 1 && resources.value.length !== 0) {
          // Alors on veut une animation que pour les données qui ont changé :
          resources.value = value.resources.map(element => {
            const existingResource = resources.value.find(resource => resource.id === element.id);

            return {
              ...element,
              animationKey: areResourcesIdentical(existingResource, element) ? existingResource.animationKey : globalAnimationKey
            };
          });
        } else {
          // Sinon, on a deux cas :
          // - Premier chargement de la page -> Besoin d'une animation
          // - Les pages changent -> Nouvelles données affichées, tout doit avoir une nouvelle animation
          resources.value = value.resources.map(element => {
            return {
              ...element,
              animationKey: globalAnimationKey
            };
          });
        }
        
        loading.value = false;
      });
    },

    nextPage() {
      if(!this.resources.length) return;

      const totalPages = Math.ceil(this.resources.length / this.pagination.rowsPerPage);
      
      if (this.pagination.page < totalPages) {
        this.pagination.page++;
        globalAnimationKey++;
        resources.value.forEach((resource) => {
          resource.animationKey = globalAnimationKey;
        });
      } else {
        this.loadPageResources();
      }
    }
  },

  beforeUnmount() {
    // Nettoyer l'intervalle quand le composant est détruit
    clearInterval(dataInterval);
    clearInterval(dateTimeInterval);
  }
}
</script>