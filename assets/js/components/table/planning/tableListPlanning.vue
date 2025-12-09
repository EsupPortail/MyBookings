<template>
  <div class="absolute-bottom-left q-ma-xl" style="z-index: 10">
    <a :href="url" target="_blank" style="text-decoration: none">
    <div class="column items-center bg-white">
      <span class="text-primary" style="font-size: 18px;">{{ $t('bookNow') }}</span>
        <QRCodeVue3
          width="150"
          height="150"
          :value="url"
          :cornersSquareOptions="{ type: 'square', color: '#000' }"
          :cornersDotOptions="{ type: 'square', color: '#000' }"
          :imageOptions="{ hideBackgroundDots: true, imageSize: 0.4, margin: 0 }"
          :dotsOptions="{
              type: 'square',
              color: '#000',
              gradient: {
                type: 'linear',
                rotation: 0,
                colorStops: [
                  { offset: 0, color: '#006D82' },
                  { offset: 1, color: '#006D82' },
                ],
              },
            }"
          fileExt="png"
         :download="false"
        />
      </div>
    </a>
  </div>

  <q-table
    ref="myTable"
    :rows="resources"
    row-key="id"
    v-model:pagination="pagination"
    :columns="filteredColumns"
    style="width: 100%;"
    >
    <template v-slot:header="props">
      <q-tr>
        <q-th colspan="4" class="bg-primary text-white text-center no-padding-th">
          <div class="row justify-between items-center">
            <q-toolbar-title id="iconBar" style="max-width: 10%; min-width: 10%">
              <a href="/book">
                <q-btn id="imgPC" style="width: 100%" flat>
                  <q-img src="/images/MyBookings_blanc.png" fit="scale-down" height="40px" class="iconMenu" />
                </q-btn>
              </a>
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
        </q-th>
      </q-tr>

      <q-tr :props="props">
        <q-th key="title" :props="props">
          <div class="row items-center justify-center">
            <span class="custom-font-size text-primary">{{ $t('roomName') }}</span>
            <q-icon class="q-px-sm" flat round color="primary" name="edit" size="sm"/>
          </div>
        </q-th>
        <q-th key="capacity" :props="props">
          <div class="row items-center justify-center">
            <span class="custom-font-size text-primary">{{ $t('capacity') }}</span>
            <q-icon class="q-px-sm" flat round color="primary" name="groups" size="sm"/>
          </div>
        </q-th>
        <q-th key="status" :props="props">
          <div class="row items-center justify-center">
            <span class="custom-font-size text-primary">{{ $t('status') }}</span>
            <q-icon class="q-px-sm" flat round color="primary" name="checklist" size="sm"/>
          </div>
        </q-th>
        <q-th key="upcoming" :props="props">
          <div class="row items-center justify-center">
            <span class="custom-font-size text-primary">{{ $t('upcomingBooking') }}</span>
            <q-icon class="q-px-sm" flat round color="primary" name="timelapse" size="sm"/>
          </div>
        </q-th>
      </q-tr>
    </template>

    <template v-slot:body="props">
      <transition
        :key="props.row['animationKey']"
        appear
        enter-active-class="animated flipInX slower"
      >
        <q-tr :props="props">
          <q-td style="width: 35%;" key="title" :props="props">
            <span :class="`custom-font-size text-${props.row['currentBooking'] ? 'negative' : 'primary'}`">{{ props.row.title }}</span>
          </q-td>

          <q-td style="width: 10%;" key="capacity" :props="props">
            <span :class="`custom-font-size text-${props.row['currentBooking'] ? 'negative' : 'primary'}`">{{ props.row.capacity }}</span>
          </q-td>

          <q-td style="width: 30%;" key="status" :props="props">
            <div v-if="props.row['currentBooking']">
              <span :class="`custom-font-size text-${props.row['currentBooking'] ? 'negative' : 'primary'}`">
                {{ $t('busyFrom') + " " + props.row['currentBooking']['dateStart'] }} - {{ props.row['currentBooking']['dateEnd'] }}
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
            <div v-else>
              <span :class="`custom-font-size text-${props.row['currentBooking'] ? 'negative' : 'primary'}`">{{ $t('free') + " " }}</span>
              <span :class="`custom-font-size text-${props.row['currentBooking'] ? 'negative' : 'primary'}`" v-if="props.row['upcomingBooking']"> {{ $t('until') + " " + props.row['upcomingBooking']['dateStart'] }}</span>
              <span :class="`custom-font-size text-${props.row['currentBooking'] ? 'negative' : 'primary'}`" v-else>{{ $t('allDay') }}</span>
            </div>
          </q-td>

          <q-td :style="`width: ${capacityColumn ? 25 : 30}%;`" key="upcoming" :props="props">
            <div v-if="props.row['upcomingBooking']">
              <span :class="`custom-font-size text-negative`">
                {{ $t('busyFrom') }} {{ props.row['upcomingBooking']['dateStart'] }} - {{ props.row['upcomingBooking']['dateEnd'] }}
                <span v-if="showNames">
                  : {{ props.row['upcomingBooking'].user[0].displayUserName }}
                  <q-tooltip>
                    <ul>
                      <li v-for="user in props.row['upcomingBooking'].user">{{ user.displayUserName }}</li>
                    </ul>
                  </q-tooltip>
                </span>
              </span>
            </div>
            <div v-else>
              <span :class="`custom-font-size text-${props.row['currentBooking'] ? 'negative' : 'primary'}`">{{ $t('free') + " " }}</span>
              <span :class="`custom-font-size text-${props.row['currentBooking'] ? 'negative' : 'primary'}`">{{ $t('allDay') }}</span>
            </div>
          </q-td>
        </q-tr>
      </transition>
    </template>
  </q-table>
</template>

<script>
import { loadResourcesAsync, areResourcesIdentical } from "../../../utils/planningCatalogUtils";
import { ref } from "vue";
import { date } from 'quasar'
import QRCodeVue3 from "qrcode-vue3";

// Page data variables
const loading = ref(true);
const resources = ref([]);

const currentDateTime = ref(null);
const headerTitle = ref("");
const showNames = ref(false);
const capacityColumn = ref(false);
const url = ref("");

// Intervals
let dataInterval = null;
let dateTimeInterval = null;

// Petite clé globale quand j'ai besoin de changer la valeur de plein de choses d'un coup
let globalAnimationKey = 1;

export default {
  name: "tableListPlanning",
  components: {
    QRCodeVue3
  },
  props: {
    id: {
      type: String,
      required: true
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
        rowsPerPage: 25,
      },
      localeOptions: [
        { value: 'fr', label: '🇫🇷 Français', title: '&#127467;&#127479;' },
        { value: 'en', label: '🇬🇧 English', title: '&#127468;&#127463;' }
      ],
      url,
      showNames,
      resources,
      currentDateTime,
      headerTitle,
      loading,
      capacityColumn
    }
  },
  beforeCreate() {
    url.value = "https://" + window.location.hostname + "/book/catalog/" + this.id;
  },
  mounted() {
    showNames.value = !this.anonymous;
    this.dateTimeInterval = setInterval(() => {
      const timeStamp = Date.now()
      this.currentDateTime = date.formatDate(timeStamp, 'DD/MM/YYYY  HH:mm:ss')
    }, 1000);
    this.loadPageResources();
    dataInterval = setInterval(this.nextPage, 10000);
  },
  methods: {
    loadPageResources() {
      loading.value = true;

      loadResourcesAsync(this.id, this.anonymous).then((value) => {
        this.pagination.page = 1;
        headerTitle.value = value.title;
        globalAnimationKey++;

        const totalPages = Math.ceil(value.resources.length / this.pagination.rowsPerPage);

        // Si on affiche qu'une seule page et qu'on avait déjà des données affichées avant,
        if(totalPages == 1 && resources.value.length !== 0) {
          // Alors on veut une animation que pour les données qui ont changé :

          value.resources.forEach(element => {
            const existingResourceIndex = resources.value.findIndex(resource => resource.id === element.id);

            // Pour comparer : temporaire ?
            element['animationKey'] = resources.value[existingResourceIndex].animationKey;

            if(!areResourcesIdentical(resources.value[existingResourceIndex], element)){
              resources.value[existingResourceIndex] = {
                ...element,
                animationKey: globalAnimationKey
              }
            }
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
  },
  computed: {
    filteredColumns() {
      // Si on a pas de capacité, alors on n'affiche pas la colonne
      if(resources.value.some(obj => obj.capacity !== undefined && obj.capacity !== null)){
        capacityColumn.value = true;
        return [
          {name: 'title', align: 'center'},
          {name: 'capacity', align: 'center'},
          {name: 'status', align: 'center'},
          {name: 'upcoming', align: 'center'}
        ];
      } else {
        capacityColumn.value = false;
        return [
          {name: 'title', align: 'center'},
          {name: 'status', align: 'center'},
          {name: 'upcoming', align: 'center'}
        ];
      }
    }
  }
}
</script>

<style lang="sass" scoped>
.q-table .q-td
  height: 5px
  line-height: 1

.no-padding-th
  padding: 0 !important

.custom-big-font-size
  font-size: 22px

.custom-font-size
  font-size: 20px
</style>