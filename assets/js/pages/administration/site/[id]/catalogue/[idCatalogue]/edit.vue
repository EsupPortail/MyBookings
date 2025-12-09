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
            <q-btn round color="negative" icon="delete" @click="openRemoveDialogCatalog()">
              <q-tooltip>
                Supprimer le catalogue
              </q-tooltip>
            </q-btn>
          </div>
        </div>
      </q-card-section>
      <q-separator inset />
      <q-card-section horizontal>
        <q-card-section style="min-width: 30%;">
          <div style="display: flex; margin-top: 10px">
            <div class="cursor-pointer">
              <q-btn dense round color="primary" icon="edit" size="sm"/>
              <q-popup-edit v-model="catalogue.title" v-slot="scope" ref="title">
                <q-input v-model="scope.value" dense autofocus counter @keyup.enter="save(scope.value, 'title')" style="width: 400px; display: inline-block"/>
                <q-btn color="primary" icon="save" size="0.7rem" class="absolute-right" @click="save(scope.value, 'title')"></q-btn>
              </q-popup-edit>
            </div>
            <h2 class="text-h6" style="margin: 0 0 0 10px">
              {{ $t('title') }} :
            </h2>
          </div>
          <p>{{catalogue.title}}</p>
          <div style="display: flex; margin-top: 10px">
            <div class="cursor-pointer">
              <q-btn dense round color="primary" icon="edit" size="sm"/>
              <q-popup-edit v-model="catalogue.description" v-slot="scope" style="width: 500px" ref="description">
                <q-editor
                    v-model="scope.value"
                    min-height="4rem"
                    autofocus
                    @keyup.enter.stop
                    style="width: 92%; display: inline-block"
                />
                <q-btn color="primary" icon="save" size="0.7rem" class="absolute-right" @click="save(scope.value, 'description')"></q-btn>
              </q-popup-edit>
            </div>
            <h2 class="text-h6" style="margin: 0 0 0 10px">
              {{ $t('description') }} :
            </h2>
          </div>
          <div style="overflow-wrap: break-word; max-width: 100%">
            <p v-html="catalogue.description"></p>
          </div>
            <div style="display: flex; margin-top: 10px">
              <div class="cursor-pointer">
                <q-btn dense round color="primary" icon="edit" size="sm"/>
                <q-popup-edit v-model="catalogue.type" v-slot="scope" ref="type">
                  <q-select v-model="scope.value" :options="options" style="width: 400px; display: inline-block" option-value="id" option-label="label"></q-select>
                  <q-btn color="primary" icon="save" size="0.7rem" class="absolute-right" @click="save(scope.value, 'type')"></q-btn>
                </q-popup-edit>
              </div>
              <h2 class="text-h6" style="margin: 0 0 0 10px">
                {{ $t('type') }} :
              </h2>
            </div>
            <p>{{catalogue.type.title}}</p>
            <div style="display: flex; margin-top: 10px">
              <div class="cursor-pointer">
                <q-btn dense round color="primary" icon="edit" size="sm"/>
                <q-popup-edit v-model="catalogue.subType" v-slot="scope" ref="subType">
                  <q-select v-model="scope.value" :options="subOptions" style="width: 400px; display: inline-block" option-value="id" option-label="title"></q-select>
                  <q-btn color="primary" icon="save" size="0.7rem" class="absolute-right" @click="save(scope.value, 'subType')"></q-btn>
                </q-popup-edit>
              </div>
              <h2 class="text-h6" style="margin: 0 0 0 10px">
                {{ $t('subType') }} :
              </h2>
            </div>
            <p>{{catalogue.subType?.title}}</p>
            <h2 class="text-h6">Localisation</h2>
            <div style="width: 50%; margin-bottom: 20px;">
              <q-select
                  dense
                  readonly
                  v-model="catalogue.localization"
                  :options="localizationOptions"
                  option-value="id"
                  option-label="title"
                  label-color="primary"
                  label="Cliquez sur l'icône"
              >
                <template #prepend>
                  <q-icon
                      name="explore"
                      class="cursor-pointer"
                      @click="openTreeSelector"
                  >
                  </q-icon>
                </template>
              </q-select>
              <TreeSelector
                  v-model="catalogue.localization"
                  :treeData="localizationOptions"
                  ref="treeSelector"
              />
            </div>
            <add-option-dialog :id="idCatalogue" :open-dialog="addOption" :options="catalogue.customFields" @close="closeOptions(false)" @submitted="closeOptions(true)"></add-option-dialog>
            <h2 class="text-h6"><q-btn dense round color="primary" icon="edit" size="sm" @click="addOption=true"/> Options : </h2>
            <div v-if="catalogue.customFields.length>0">
              <q-select :options="catalogue.customFields" option-label="title" option-value="id" model-value="Voir les options" style="margin: auto; width: 50%"></q-select>
            </div>
            <p v-else>Aucune</p>
            <div v-if="user.hasRoles('ROLE_ADMIN')">
              <div style="display: flex; margin-top: 10px">
                <div class="cursor-pointer">
                  <q-btn dense round color="primary" icon="edit" size="sm"/>
                  <q-popup-edit v-model="catalogue.actuator" v-slot="scope" ref="actuator">
                    <q-select v-model="scope.value" :options="actuatorOptions" style="width: 400px; display: inline-block" option-value="id" option-label="title" clearable></q-select>
                    <q-btn color="primary" icon="save" size="0.7rem" class="absolute-right" @click="save(scope.value, 'actuator')"></q-btn>
                  </q-popup-edit>
                </div>
                <h2 class="text-h6" style="margin: 0 0 0 10px">
                  {{ $t('actionneur') }} :
                </h2>
              </div>
              <p v-if="catalogue.actuator !== null">[{{catalogue.actuator.type}}] {{catalogue.actuator.title}}</p>
              <p v-else>N/R</p>
            </div>
        </q-card-section>
        <q-separator vertical />
        <div>
          <q-popup-edit v-model="catalogue.image" v-slot="scope" ref="image">
            <h2 class="text-h6">{{$t('common.editImage')}}</h2>
            <q-uploader
                label="Téléverser une image"
                @added="fileSelected"
                @removed="fileRemoved"
                accept=".jpg, .png, .jpeg"
                style="width: 500px; margin-top: 10%"
                flat
                bordered
            />
            <q-btn color="primary" icon="save" size="0.7rem" id="saveImg" style="margin-top: 5%; margin-left: 60%; display: none" label="Enregistrer" @click="save(newImage, 'image')"></q-btn>
          </q-popup-edit>
          <q-btn class="absolute-top-right q-ma-xl" round color="primary" icon="edit" size="sm">
            <q-tooltip>
              Modifier l'image
            </q-tooltip>
          </q-btn>
        </div>

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

    <RemoveDialog
      v-model="confirmDeleteCatalog"
      message="Voulez-vous supprimer le catalogue et toutes les ressources associées ?"
      :ok-action="removeCatalog"
    />

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
            <div class="col-auto">
              <q-btn round color="secondary" icon="edit" @click="provision=true;">
                <q-tooltip>
                  Editer le planning
                </q-tooltip>
              </q-btn>
            </div>
          </div>
        </q-card-section>
        <q-separator inset />
        <q-card-section>
          <div style="height: 700px" v-if="calendarOption !== null">
            <SchedulerComponent  class="left-container" :block="blockingDate" :key="isUpdate" :catalogue="idCatalogue" :site="id" :id-resource="calendarOption.id"></SchedulerComponent>
          </div>
          <div style="height: 700px" v-else>
            <SchedulerComponent class="left-container" :block="blockingDate" :key="isUpdate" :catalogue="idCatalogue" :site="id" ></SchedulerComponent>
          </div>
        </q-card-section>
      </q-card>
    </div>
    <provision-dialog :openProvision="provision" :catalogue="idCatalogue" :initial-provisions="catalogue.Provisions" @close="closeProvisionDialog" @planningUpdate="updatePlanning"></provision-dialog>
    <div>
      <q-card bordered class="my-card">
        <q-card-section>
          <h1 class="text-h5">{{ $t('affiliatedResources') }}</h1>
        </q-card-section>
        <q-separator inset />
        <q-card-section>
          <custom-table v-if="catalogue.actuator !== null" :resources="catalogue.resource" :catalogue="idCatalogue" :option-list="catalogue.customFields" :actuator="catalogue.actuator.id" :is-editable="true" @reloadCatalog="reloadCatalog"></custom-table>
          <custom-table v-else :resources="catalogue.resource" :catalogue="idCatalogue" :is-editable="true" :option-list="catalogue.customFields" @reloadCatalog="reloadCatalog"></custom-table>
        </q-card-section>
      </q-card>
    </div>
  </div>
</template>

<route lang="json">
{
  "name": "editCatalogue"
}
</route>

<script>
import axios from 'axios';
import {ref} from 'vue';
import {useQuasar} from 'quasar';
import CustomTable from "../../../../../../components/table/customTable.vue";
import provisionDialog from "../../../../../../components/dialog/provision/provisionDialog.vue";
import {counter, user} from "../../../../../../store/counter";
import SchedulerComponent from "../../../../../../components/scheduler/SchedulerComponent.vue";
import RemoveDialog from '../../../../../../components/dialog/RemoveDialog.vue';
import {editCatalog, removeCatalog} from '../../../../../../api/CatalogRessource'
import AddOptionDialog from "../../../../../../components/dialog/addOptionDialog.vue";
import {getParentLocalizations} from "../../../../../../api/Localization";
import TreeSelector from "../../../../../../components/dialog/TreeSelector.vue";
import ResourceViewDialog from "../../../../../../components/dialog/resourceViewDialog.vue";
import {getProvisionsFromCatalog} from "../../../../../../api/Provision";
import {processLegacyPlanning, processPeriodBracket} from "../../../../../../utils/planningCatalogUtils";
import { useRoute } from 'vue-router/auto';

const localizationOptions = ref([]);
const treeSelector = ref(null);
const calendarOption = ref(null);
const blockingDate = ref([]);
export default {
  components: {
    ResourceViewDialog,
    TreeSelector,
    AddOptionDialog,
    CustomTable,
    provisionDialog,
    SchedulerComponent,
    RemoveDialog
  },
  setup() {
    const nbCounter = counter();
    const route = useRoute();
    const id = route.params.id;
    const idCatalogue = route.params.idCatalogue;

    function openTreeSelector() {
      if (treeSelector) {
        treeSelector.value.dialog = true;
      }
    }

    function incrementCounter() {
      nbCounter.increment();
    }

    window.stores = {incrementCounter}
    return {
      nbCounter,
      incrementCounter,
      treeSelector,
      openTreeSelector,
      confirmDeleteCatalog: ref(false),
      calendarOption,
      id,
      idCatalogue
    }
  },
  data() {
    return {
      localizationOptions,
      treeSelector,
      optionsField: [],
      user: user(),
      isloaded: false,
      catalogue: [],
      $q: useQuasar(),
      selectedFile: false,
      newImage: '',
      provision: false,
      events: [],
      blockingDate,
      isUpdate: 0,
      options: [],
      subOptions: [],
      actuatorOptions: [],
      addOption: false,
      openResourceDialog: false
    }
  },
  created() {
    if (this.user.roles.length === 0) {
      this.user.getRoles()
    }
    this.$q.loading.show();
    this.getCatalogue(this.idCatalogue);
    this.getCatalogueProvisions(this.idCatalogue);
    this.getCategory();
    this.getLocalizations();
  },
  computed: {
    getCatalogLocalization() {
      return this.catalogue.localization;
    }
  },
  methods: {
    openRemoveDialogCatalog() {
      this.confirmDeleteCatalog = true;
    },
    removeCatalog() {
      this.$q.loading.show();
      let self = this;
      removeCatalog(this.idCatalogue)
          .then(function (response) {
            self.isloaded = true;
            self.$q.loading.hide();
            self.$router.replace('/administration/site/' + self.id);
          })
    },
    reloadCatalog() {
      this.getCatalogue(this.idCatalogue);
    },
    getCatalogue(id) {
      let self = this;
      blockingDate.value = [];
      axios({
        method: "get",
        url: "/api/catalogue_resources/" + id,
        headers: {
          'accept': 'application/json'
        },
      })
          .then(function (response) {
            self.catalogue = response.data;
            self.optionsField = self.catalogue.customFields;
            self.getSubCategory(self.catalogue.type.id);
            self.getActuators();
            self.isloaded = true;
            self.$q.loading.hide();
          })
    },
    async getCatalogueProvisions(id) {
      let response = await getProvisionsFromCatalog(id);
      this.setPlanning(response.data.Provisions);
    },
    save(value, field) {
      //Modification du catalogue en front
      this.editCatalogue(value, field);
      let self = this;
      //Construction du body
      let bodyFormData = new FormData();
      if (field !== 'image') {
        bodyFormData.append('image', null);
      } else {
        bodyFormData.append('image', this.newImage);
      }
      bodyFormData.append('title', this.catalogue.title);
      bodyFormData.append('description', this.catalogue.description);
      bodyFormData.append('type', this.catalogue.type.id);
      bodyFormData.append('subType', this.catalogue.subType.id);
      bodyFormData.append('service', this.catalogue.service.id);
      bodyFormData.append('localization', this.catalogue.localization ? this.catalogue.localization.id : null);

      if (this.catalogue.actuator !== null) {
        bodyFormData.append('actuator', this.catalogue.actuator.id);
      } else {
        bodyFormData.append('actuator', null);
      }
      editCatalog(this.idCatalogue, bodyFormData).then(function (response) {
        //Cacher les popup
        if (field !== 'localization') {
          self.$refs[field].hide();
        }
        self.getCatalogue(self.idCatalogue);
        //Notification utilisateur
        self.$q.notify({
          type: 'positive',
          message: 'Le catalogue a été modifié !',
          position: 'top',
        })
        //Rafraichir l'image
        if (field === 'image') {
          self.catalogue.image = self.catalogue.image + '?t=' + new Date().getTime();
        }
      }).catch(function (error) {
        self.$q.notify({
          type: 'negative',
          message: 'Erreur lors de la modification du catalogue',
          position: 'top',
        })
      })
    },
    editCatalogue(value, field) {
      switch (field) {
        case 'title':
          this.catalogue.title = value;
          break;
        case 'description':
          this.catalogue.description = value;
          break;
        case 'image':
          break;
        case 'type':
          this.catalogue.type = value;
          break;
        case 'subType':
          this.catalogue.subType = value;
          break;
        case 'localization':
          this.catalogue.localization = value;
          break;
        case 'actuator':
          this.catalogue.actuator = value;
          break;
        default:
          console.log('Erreur d\'édition');
      }
    },
    fileSelected(files) {
      if (files.length !== 0) {
        this.selectedFile = true
      }
      this.newImage = files[0];
      document.getElementById('saveImg').style.display = 'inline-block';
    },
    fileRemoved() {
      this.selectedFile = false
    },
    setPlanning(planning) {

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
    updatePlanning() {
      this.getCatalogue(this.idCatalogue);
      this.provision = false;
    },
    getCategory() {
      let self = this;
      axios({
        method: "get",
        url: "/api/category",
      })
          .then(function (response) {
            self.options = response.data;
          })
    },
    getSubCategory(parent) {
      let self = this;
      axios({
        method: "get",
        url: "/api/categories/"+parent,
      })
          .then(function (response) {
            self.subOptions = response.data.enfants;
          })
    },
    getActuators() {
      let self = this;
      axios({
        method: "get",
        url: "/api/actuators",
        headers: {
          'accept': 'application/json'
        },
      })
          .then(function (response) {
            self.actuatorOptions = response.data;
          })
    },
    getLocalizations() {
      getParentLocalizations().then(function (response) {
        localizationOptions.value = response.data;
      })
    },
    closeProvisionDialog(provisions) {
      blockingDate.value = [];
      this.provision=false;
      this.catalogue.provision = provisions;
      if(this.catalogue.provision.length > 0) {
        this.getCatalogueProvisions(this.catalogue.id);
      } else {
        this.isUpdate++;
      }
    },
    closeOptions(reloadOptions) {
      if(reloadOptions === true) {
        this.getCatalogue(this.idCatalogue);
      }
      this.addOption = false;
    },
  },
  watch: {
    getCatalogLocalization: function (newValue, oldValue) {
      if(this.catalogue.localization !== null && oldValue !== undefined) {
        if(oldValue === null || (newValue.id !== oldValue.id)) {
          this.save(this.catalogue.localization, 'localization');
        }
      }
    },
  }
}
</script>

<style scoped>
h6 {
  margin-bottom: 10px;
}

.action_button {
  position: absolute;
  margin-left: 0.5%;
  margin-top: 3%;
}

.info .col-6{
  padding: 10px 15px;
  border: 1px solid rgba(86,61,124,.2);
}

.frame {
  display: inline-block;
  position: relative;
}

.my-card {
  margin-bottom: 30px;
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