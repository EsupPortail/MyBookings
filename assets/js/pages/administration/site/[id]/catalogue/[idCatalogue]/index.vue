<template>
  <div v-if="isloaded">
    <resource-view-dialog :openResourceDialog="openResourceDialog" @close="openResourceDialog=false" :id="idCatalogue" :linkName="'catalog'"></resource-view-dialog>
    <q-card bordered class="my-card">
        <q-card-section>
          <div class="row">
            <div class="col-auto">
              <q-btn round style="margin-right: 10px" color="secondary" icon="qr_code" @click="openResourceDialog = true">
                <q-tooltip>
                  Lien de réservation direct
                </q-tooltip>
              </q-btn>
            </div>
            <div class="col">
              <h1 class="text-h5" style="margin-top: 5px">{{ $t('detail') }}</h1>
            </div>
            <div class="col-auto">
              <q-btn v-if="user.isUserAdminSite(id)" round color="secondary" icon="edit" :to="{ name: 'editCatalogue' }">
                <q-tooltip>
                  Editer le catalogue
                </q-tooltip>
              </q-btn>
            </div>
          </div>
        </q-card-section>
      <q-separator inset />
      <q-card-section horizontal>
        <q-card-section>
              <div>
                <h2 class="text-h6" style="margin-top: 10px">{{ $t('title') }} :</h2>
                <div style="margin: auto; overflow-wrap: break-word">
                  <p>{{catalogue.title}}</p>
                </div>
                <h2 class="text-h6">{{ $t('description') }} :</h2>
                <div style="margin: auto; overflow-wrap: break-word; max-width: 100%">
                  <p v-html="catalogue.description"></p>
                </div>
                <h2 class="text-h6">{{ $t('type') }} :</h2>
                <div style="margin: auto; overflow-wrap: break-word; width: 500px;">
                  <p>{{catalogue.type.title}}</p>
                </div>
                <h2 class="text-h6">{{ $t('subType') }} :</h2>
                <div style="margin: auto; overflow-wrap: break-word; width: 500px;">
                  <p>{{catalogue.subType?.title }}</p>
                </div>
                <h2 class="text-h6">Localisation</h2>
                <div style="width: 50%; margin-bottom: 20px">
                  <q-select
                      readonly
                      v-model="catalogue.localization"
                      :options="localizationOptions"
                      option-value="id"
                      option-label="title"
                      label-color="primary"
                  >
                  </q-select>
                </div>
                <h2 class="text-h6">Options : </h2>
                <div v-if="catalogue.customFields.length>0">
                  <q-select :options="catalogue.customFields" option-label="title" option-value="id" model-value="Voir les options" style="margin: auto; width: 50%"></q-select>
                </div>
                <p v-else>Aucune</p>
                <div v-if="user.hasRoles('ROLE_ADMIN')">
                  <h2 class="text-h6">{{ $t('actionneur') }} :</h2>
                  <p v-if="catalogue.actuator !== null">[{{catalogue.actuator.type}}] {{catalogue.actuator.title}}</p>
                  <p v-else>N/R</p>
                </div>
              </div>
        </q-card-section>
        <q-separator vertical />
        <q-card-section style="margin: auto; overflow-wrap: break-word; width: 500px">
          <q-img :src="'/uploads/'+catalogue.image" :alt="catalogue.title">
            <template v-slot:error>
              <div class="absolute-full flex flex-center bg-grey text-white">
                <q-icon size="md" name="no_photography"/>
              </div>
            </template>
          </q-img>
        </q-card-section>
      </q-card-section>
    </q-card>
    <div>
      <q-card bordered class="my-card">
        <q-card-section>
          <div class="row justify-between">
            <h1 class="text-h5 col-auto">
              {{ $t('planing') }}
            </h1>
            <div class="col-4">
              <q-select borderless label="Filtrer par ressource" v-model="calendarOption" option-label="title" option-value="id" :options="catalogue.resource" clearable></q-select>
            </div>
            <div class="col-auto q-gutter-sm">
              <q-btn round color="primary" size="sm" icon="list_alt" :href='"/planning/catalog/"+idCatalogue+"/list/"' target="_blank">
                <q-tooltip>
                  Lien vers les réservations en liste
                </q-tooltip>
              </q-btn>
              <q-btn round color="primary" size="sm" icon="calendar_view_month" :href='"/planning/catalog/"+idCatalogue+"/card/"' target="_blank">
                <q-tooltip>
                  Lien vers les réservations en carte
                </q-tooltip>
              </q-btn>
              <q-btn round size="sm" icon="today" :href='"/generate/site/"+id+"/catalogue/"+idCatalogue+".ics"'>
                <q-tooltip>
                  Lien vers le calendrier externe (ICS)
                </q-tooltip>
              </q-btn>
            </div>
          </div>
        </q-card-section>
        <q-separator inset />
        <q-card-section v-if="!isLoading">
          <div style="height: 700px" v-if="calendarOption !== null">
            <SchedulerComponent  class="left-container" :block="blockingDate" :key="isUpdate" :catalogue="idCatalogue" :site="id" :id-resource="calendarOption.id"></SchedulerComponent>
          </div>
          <div style="height: 700px" v-else>
            <SchedulerComponent class="left-container" :block="blockingDate" :key="isUpdate" :catalogue="idCatalogue" :site="id" ></SchedulerComponent>
          </div>
        </q-card-section>
      </q-card>
    </div>
    <div>
      <q-card bordered class="my-card">
        <q-card-section>
          <h1 class="text-h5">{{ $t('affiliatedResources') }}</h1>
        </q-card-section>
        <q-separator inset />
        <q-card-section>
          <custom-table :resources="catalogue.resource" :is-editable="false"></custom-table>
        </q-card-section>
      </q-card>
    </div>
  </div>
</template>

<route lang="json">
{
  "name": "catalogueDetail",
  "meta": {
  "requiresAuth": false,
  "dynamic": true
  }
}
</route>

<script>
import axios from 'axios';
import {useQuasar} from 'quasar';
import {user} from "../../../../../../store/counter";
import CustomTable from "../../../../../../components/table/customTable.vue";
import SchedulerComponent from "../../../../../../components/scheduler/SchedulerComponent.vue";
import {getParentLocalizations} from "../../../../../../api/Localization";
import {ref} from "vue";
import ResourceViewDialog from "../../../../../../components/dialog/resourceViewDialog.vue";
import {processLegacyPlanning, processPeriodBracket} from "../../../../../../utils/planningCatalogUtils";
import {getProvisionsFromCatalog} from "../../../../../../api/Provision";
import { useRoute } from 'vue-router/auto';

const localizationOptions = ref([]);
const calendarOption = ref(null);
const blockingDate = ref([]);
const isLoading = ref(false);

export default {
  components: {
    ResourceViewDialog,
    CustomTable,
    SchedulerComponent
  },
  setup() {
    const route = useRoute();
    const id = route.params.id;
    const idCatalogue = route.params.idCatalogue;
    return {
      id,
      idCatalogue
    }
  },
  data() {
    return {
      user: user(),
      isloaded: false,
      catalogue: [],
      $q: useQuasar(),
      selectedFile: false,
      newImage: '',
      events: [],
      blockingDate,
      isUpdate: 0,
      openResourceDialog: false,
      localizationOptions,
      calendarOption,
      isLoading
    }
  },
  mounted() {
    this.$q.loading.show();
    this.getCatalogue(this.idCatalogue);
    this.getLocalizations();
    this.getCatalogueProvisions(this.idCatalogue);
  },
  methods: {
    getCatalogue(id) {
      let self = this;
      blockingDate.value = [];
      axios({
        method: "get",
        url: "/api/catalogue_resources/"+id,
        headers: {
          'accept': 'application/json'
        },
      })
          .then(function (response) {
            self.catalogue = response.data;
            self.isloaded = true;
            self.$q.loading.hide();
          })
    },
    async setPlanning(planning) {
      let coveredDays = new Set(); // Utiliser un Set pour collecter tous les jours couverts
      planning.forEach(function (element) {
        // Si un periodBracket est défini, utiliser le nouveau système
        if (element.periodBracket && element.periodBracket.periods) {
          let process = processPeriodBracket(element.periodBracket, blockingDate.value);
          let bracketCoveredDays = process.coveredDays; // Jours couverts par le bracket
          blockingDate.value = process.blockingDate; // Zones de blocage
          // Ajouter tous les jours du bracket au Set global
          bracketCoveredDays.forEach(day => coveredDays.add(day));
        }
        // Sinon, utiliser l'ancien système si les données nécessaires sont présentes
        else if (element.dateEnd && element.days && element.minBookingTime && element.maxBookingTime) {
          let process = processLegacyPlanning(element, blockingDate.value);
          let legacyCoveredDays = process.coveredDays; // Jours couverts par le planning legacy
          blockingDate.value = process.blockingDate; // Zones de blocage
          // Ajouter tous les jours du planning legacy au Set global
          legacyCoveredDays.forEach(day => coveredDays.add(day));
        }
      });

      // Détection des jours non couverts
      let arrayOfDay = [0, 1, 2, 3, 4, 5, 6];

      // Supprimer les jours déjà couverts
      let uncoveredDays = arrayOfDay.filter(day => !coveredDays.has(day));

      // Bloquer les jours non couverts
      uncoveredDays.forEach(function (day) {
        blockingDate.value.push({
          days: day,
          zones: 'fullday',
          type: "dhx_time_block",
          css: "blue_section"
        })
      });
      this.isUpdate++;
    },
    async getCatalogueProvisions(id) {
      isLoading.value = true;
      let response = await getProvisionsFromCatalog(id);
      await this.setPlanning(response.data.Provisions);
      isLoading.value = false;
    },
    getLocalizations() {
      getParentLocalizations().then(function (response) {
        localizationOptions.value = response.data;
      })
    },
  },
}
</script>

<style>
h6 {
  margin-bottom: 10px;
}

.info .col-6{
  padding: 10px 15px;
  border: 1px solid rgba(86,61,124,.2);
}

img {
  max-height: 500px;
  max-width: 98%;
  width: auto;
  height: auto;
  top: 0;
  bottom: 0;
  left: 0;
  right: 0;
  margin: auto;
}

.my-card {
  margin-bottom: 30px;
}

.frame {
  display: inline-block;
  position: relative;
}

.container {
  height: 100%;
  width: 100%;
}

.left-container {
  overflow: hidden;
  position: relative;
  height: 80vh;
  display: inline-block;
  width: 100%;
  margin-top: -15px;
}

</style>