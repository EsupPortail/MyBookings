<template>
  <provision-delete-confirm-dialog :open-dialog="confirmDelete" :catalogue="catalogue" :current-planning="currentPlanning" @close="confirmDelete=false" @deleted="closeAndRefreshConfirmDelete"></provision-delete-confirm-dialog>
  <q-dialog v-model="openSecondDialog" persistent>
    <q-card style="width: 800px; max-width: 80vw;">
      <q-card-section>
        <div class="row items-center">
          <div class="col-1">
            <q-btn round color="negative" icon="delete" @click="OpenConfirmDelete" v-if="this.currentPlanning.id !== null">
              <q-tooltip>
                Supprimer le planning
              </q-tooltip>
            </q-btn>
          </div>
          <div class="col-10">
            <div class="text-h6 text-center">Définition du planning de réservation</div>
          </div>
          <div class="col-1 text-right">
            <q-btn round color="dark" icon="close" @click="closeDialog">
              <q-tooltip>
                Fermer la fenêtre
              </q-tooltip>
            </q-btn>
          </div>
        </div>
      </q-card-section>
      <q-separator/>
      <q-stepper
          v-model="step"
          header-nav
          ref="stepper"
          color="primary"
          animated
      >
        <q-step
            :name="1"
            title="Sélection d'une période"
            icon="calendar"
            :done="step > 1"
            :header-nav="step > 1"
        >
          <p style="text-align: center">Sélection d'une période</p>
          <q-card-section>
            <q-select
              filled
              v-model="selectedPeriodBracket"
              :options="periodBrackets"
              option-value="id"
              option-label="title"
              label="Sélectionner un groupe de périodes"
              clearable
              :loading="loadingPeriodBrackets"
              @update:model-value="onPeriodBracketChange"
            >
              <template v-slot:no-option>
                <q-item>
                  <q-item-section class="text-grey">
                    Aucun groupe de périodes disponible
                  </q-item-section>
                </q-item>
              </template>

              <template v-slot:option="scope">
                <q-item v-bind="scope.itemProps">
                  <q-item-section>
                    <q-item-label>{{ scope.opt.title }}</q-item-label>
                  </q-item-section>
                </q-item>
              </template>
            </q-select>
            <p class="text-caption">
              Pour créer des groupes de périodes, rendez-vous dans l'onglet
              <a target="_blank" :href="'/administration/site/'+route.params.id+'/periods'" class="text-primary cursor-pointer">
                <span class="text-bold">périodes de réservation</span></a>
              <div class="text-caption">Nous vous encourageons à utiliser les groupes de périodes car ils remplaceront prochainement les dates définies dans l'étape de mise à disposition.</div>
            </p>
          </q-card-section>
          <q-stepper-navigation class="text-primary" style="display: flex; justify-content: space-between">
            <q-btn flat @click="closeDialog" color="primary" label="Annuler" class="q-ml-sm" />
            <q-btn @click="() => { done1 = true; step = 2 }" color="primary" label="Continuer" />
          </q-stepper-navigation>
        </q-step>
        <q-step
            :name="2"
            title="Période de réservation"
            icon="schedule"
            :done="step > 2"
            :header-nav="step > 2"
        >
          <p style="text-align: center">Périodes de mise à disposition</p>
          <q-card-section v-if="!selectedPeriodBracket" horizontal>
            <q-card-section style="width: 100%">
              <q-input filled v-model="currentPlanning.dateStart" label="Ouverture" mask="##/##/####" placeholder="DD/MM/YYYY">
                <template v-slot:append>
                  <q-icon name="event" class="cursor-pointer">
                    <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                      <q-date v-model="currentPlanning.dateStart" mask="DD/MM/YYYY">
                        <div class="row items-center justify-end">
                          <q-btn v-close-popup label="Fermer" color="primary" flat />
                        </div>
                      </q-date>
                    </q-popup-proxy>
                  </q-icon>
                </template>
              </q-input>
            </q-card-section>
            <q-card-section style="width: 100%">
              <q-input filled v-model="currentPlanning.dateEnd" label="Clôture" mask="##/##/####" placeholder="DD/MM/YYYY">
                <template v-slot:append>
                  <q-icon name="event" class="cursor-pointer">
                    <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                      <q-date v-model="currentPlanning.dateEnd" mask="DD/MM/YYYY">
                        <div class="row items-center justify-end">
                          <q-btn v-close-popup label="Fermer" color="primary" flat />
                        </div>
                      </q-date>
                    </q-popup-proxy>
                  </q-icon>
                </template>
              </q-input>
            </q-card-section>
          </q-card-section>
              <q-card-section horizontal v-if="!selectedPeriodBracket">
            <div class="q-pa-md">
              <div class="q-gutter-sm">
                <q-checkbox v-model="currentPlanning.days" val="monday" label="Lundi" color="primary" />
                <q-checkbox v-model="currentPlanning.days" val="tuesday" label="Mardi" color="primary" />
                <q-checkbox v-model="currentPlanning.days" val="wednesday" label="Mercredi" color="primary" />
                <q-checkbox v-model="currentPlanning.days" val="thursday" label="Jeudi" color="primary" />
                <q-checkbox v-model="currentPlanning.days" val="friday" label="Vendredi" color="primary" />
                <q-checkbox v-model="currentPlanning.days" val="saturday" label="Samedi" color="primary" />
                <q-checkbox v-model="currentPlanning.days" val="sunday" label="Dimanche" color="primary" />
              </div>
            </div>
          </q-card-section>
          <q-card-section>
            <q-checkbox v-model="currentPlanning.allowMultipleDay" val="true" label="Autoriser la réservation sur plusieurs jours" color="primary" />
          </q-card-section>
          <q-card-section horizontal v-if="!selectedPeriodBracket">
            <q-card-section style="width: 100%">
              <q-input filled v-model="currentPlanning.minBookingTime" mask="time" label="Heure de début de la première réservation" :rules="['time']">
                <template v-slot:append>
                  <q-icon name="access_time" class="cursor-pointer">
                    <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                      <q-time v-model="currentPlanning.minBookingTime">
                        <div class="row items-center justify-end">
                          <q-btn v-close-popup label="Fermer" color="primary" flat />
                        </div>
                      </q-time>
                    </q-popup-proxy>
                  </q-icon>
                </template>
              </q-input>
            </q-card-section>
            <q-card-section style="width: 100%">
              <q-input filled v-model="currentPlanning.maxBookingTime" mask="time" label="Heure de fin de la dernière réservation" :rules="['time']">
                <template v-slot:append>
                  <q-icon name="access_time" class="cursor-pointer">
                    <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                      <q-time v-model="currentPlanning.maxBookingTime">
                        <div class="row items-center justify-end">
                          <q-btn v-close-popup label="Fermer" color="primary" flat />
                        </div>
                      </q-time>
                    </q-popup-proxy>
                  </q-icon>
                </template>
              </q-input>
            </q-card-section>
          </q-card-section>
          <q-card-section horizontal>
            <q-card-section style="width: 100%">
                <div class="row justify-between">
                  <div class="col buttonMaxDuration">
                    <q-btn size="lg" color="primary" label="-30" @click="changeMaxBookingDuration('sub')" />
                  </div>
                  <div class="col-8">
                    <q-input type="number" filled v-model="currentPlanning.maxBookingDuration" label="Durée minimale de la réservation (minutes)" disable/>
                  </div>
                  <div class="col buttonMaxDuration">
                    <q-btn size="lg" color="primary" label="+30" @click="changeMaxBookingDuration('add')"/>
                  </div>
                </div>
            </q-card-section>
            <q-card-section style="width: 100%">
              <q-input filled v-model="currentPlanning.bookingInterval" type="number" label="Intervalle entre chaque réservation (minutes)">
              </q-input>
            </q-card-section>
          </q-card-section>
          <q-card-section>
            <q-expansion-item
                dense-toggle
                expand-separator
                icon="schedule"
                label="Simulation des horaires de réservations"
            >
              <ul v-for="i in getNumbers(0, maxBooking)" :key="i">
                <li>{{getRestOfTime(i, 'hour')}}:{{getRestOfTime(i, 'min')}} - {{getRestOfTime(i+1, 'hour')}}:{{getRestOfTime(i+1, 'min')}}</li>
              </ul>
            </q-expansion-item>
          </q-card-section>
          <q-stepper-navigation class="text-primary" style="display: flex; justify-content: space-between">
            <q-btn flat @click="step = 1" color="primary" label="Précédent" class="q-ml-sm" />
            <q-btn @click="() => { done2 = true; step = 3 }" color="primary" label="Continuer" />
          </q-stepper-navigation>
        </q-step>
        <q-step
            :name="3"
            title="Conditions de réservation"
            icon="comment"
            :header-nav="step > 3"
        >
          <p style="text-align: center">Conditions de la réservation</p>
          <q-card-section style="width: 100%">
            <q-select filled v-model="currentPlanning.allGroups" use-input :options="mode" option-label="title" option-value="id" multiple label="Quels utilisateurs peuvent réserver le catalogue ?" @filter="filterFn">
              <template v-slot:option="{ itemProps, opt, selected, toggleOption }">
                <q-item v-bind="itemProps">
                  <q-item-section>
                    <q-item-label>{{opt.title}}</q-item-label>
                  </q-item-section>
                  <q-item-section side>
                    <q-toggle :model-value="selected" @update:model-value="toggleOption(opt)" />
                  </q-item-section>
                </q-item>
              </template>
            </q-select>
          </q-card-section>
          <q-card-section horizontal class="">
            <q-card-section class="q-py-none" style="width: 100%">
              <q-input type="number" filled v-model="currentPlanning.maxBookingByWeek" label="Réservations max par personne par semaine" />
            </q-card-section>
            <q-card-section class="q-py-none" style="width: 100%">
              <q-input type="number" filled v-model="currentPlanning.maxBookingByDay" label="Réservations max par personne par jour" />
            </q-card-section>
          </q-card-section>
          <q-card-section class="q-pt-none">
            <caption class="text-caption text-left" style="width: 100%">Réservations illimités = 0</caption>
          </q-card-section>
          <q-card-section horizontal>
            <q-avatar text-color="primary" icon="help">
              <q-tooltip>
                Le processus de validation permettra de définir si les étapes de la réservation sont automatiquement validées ou non.
              </q-tooltip>
            </q-avatar>
            <q-select
                filled
                v-model="currentPlanning.attachedWorkflow"
                :options="workflows"
                option-value="id"
                option-label="title"
                label="Choisir un processus de validation"
                 @change="console.log(currentPlanning.attachedWorkflow)"
                style="width: 92%"
            />
          </q-card-section>
          <q-stepper-navigation class="text-primary" style="display: flex; justify-content: space-between">
            <q-btn flat label="Précédent" @click="step = 2" />
            <q-btn flat label="Enregistrer" @click="saveAndClose" />
          </q-stepper-navigation>
        </q-step>
      </q-stepper>
    </q-card>
  </q-dialog>
</template>

<script>
import ProvisionDeleteConfirmDialog from "./provisionDeleteConfirmDialog.vue";
import {ref} from "vue";
import {useRouter, useRoute} from "vue-router";
import {getNumbers} from "../../../utils/bookingUtils";
import {addProvision, updateProvision} from "../../../api/Provision";
import {getAllGroupsNoService, getGroupByService} from "../../../api/Group";
import {getCatalogueById} from "../../../api/CatalogRessource";
import {getPeriodBracket} from "../../../api/PeriodBracket";
import {date} from "quasar";
import {convertDateForServer, convertTimeForServer} from "../../../utils/dateUtils";

const step = ref(1);
const mode = ref([]);
const allGroup = ref(null);

export default {
  name: "provisionDefinitionDialog",
  emits: ["close"],
  components: {ProvisionDeleteConfirmDialog},
  props: {
    openDialog:Boolean,
    currentPlanning:Object,
    catalogue:String,
    workflows:Array,
  },
  setup() {
    const router = useRouter();
    const route = useRoute();
    return { router, route };
  },
  data () {
    return {
      step,
      openSecondDialog: false,
      confirmDelete: false,
      mode,
      minBooking: null,
      maxBooking: null,
      interval: null,
      allGroup,
      // Nouvelles données pour les brackets de périodes
      periodBrackets: [],
      selectedPeriodBracket: null,
      loadingPeriodBrackets: false
    }
  },
  mounted() {
    this.openSecondDialog = this.openDialog;
    this.getGroups();
    this.loadPeriodBrackets();
  },
  methods: {
    getNumbers,
    filterFn (val, update, abort) {
      update(() => {
        const needle = val.toLowerCase()
        mode.value = allGroup.value.filter(v => v.title.toLowerCase().indexOf(needle) > -1)
      })
    },
    async saveAndClose() {
      // Si un bracket de période est sélectionné, envoyer les dates et heures comme null
      if (this.selectedPeriodBracket) {
        this.currentPlanning.dateStart = null;
        this.currentPlanning.dateEnd = null;
        this.currentPlanning.minBookingTime = null;
        this.currentPlanning.maxBookingTime = null;
        this.currentPlanning.days = null;
      } else {
        // Sinon, convertir les dates et heures normalement
        this.currentPlanning.dateStart = convertDateForServer(this.currentPlanning.dateStart);
        this.currentPlanning.dateEnd = convertDateForServer(this.currentPlanning.dateEnd);
        this.currentPlanning.minBookingTime = convertTimeForServer(this.currentPlanning.minBookingTime);
        this.currentPlanning.maxBookingTime = convertTimeForServer(this.currentPlanning.maxBookingTime);
      }

      this.currentPlanning.maxBookingByWeek = parseInt(this.currentPlanning.maxBookingByWeek);
      this.currentPlanning.maxBookingByDay = parseInt(this.currentPlanning.maxBookingByDay);

      this.currentPlanning.catalogueResource = '/api/catalogue_resources/' + this.catalogue;
      this.currentPlanning.workflow = this.currentPlanning.attachedWorkflow ? '/api/workflows/' + this.currentPlanning.attachedWorkflow.id : null;
      this.currentPlanning.groups = this.currentPlanning.allGroups.map(group => {
        return '/api/groups/' + group.id;
      });
      this.currentPlanning.periodBracket = this.selectedPeriodBracket ? '/api/period_brackets/' + this.selectedPeriodBracket.id : null;
      if(this.currentPlanning.id !== null) {
        await updateProvision(this.currentPlanning.id, this.currentPlanning)
      } else {
        await addProvision(this.currentPlanning)
      }

      step.value = 1;
      this.$emit('close', false);
    },
    getGroups() {
      allGroup.value = [];
      mode.value = [];
      let serviceId = null;
      getCatalogueById(this.catalogue).then(function (response) {
        serviceId = response.service.id;
        getGroupByService(serviceId).then(function (response) {
          allGroup.value = allGroup.value.concat(response.data);
          mode.value = mode.value.concat(response.data);
        })
        getAllGroupsNoService().then(function (response) {
          allGroup.value = allGroup.value.concat(response.data);
          mode.value = mode.value.concat(response.data);
        })
      });

    },
    OpenConfirmDelete () {
      this.confirmDelete = true;
    },
    closeAndRefreshConfirmDelete() {
      this.confirmDelete = false;
      this.closeDialog();
    },
    closeDialog() {
      this.$emit('close', false);
    },
    getRestOfTime(n, type) {
      const restOfMin =  Math.round(((60*24) / ((60/this.interval) * 24))*(this.minBooking+n));
      if(type === 'hour') {
        const hour = Math.trunc(restOfMin / 60);
        return hour >= 10 ? hour : '0'+hour;
      } else {
        const min = 60 * ((restOfMin / 60) % 1);
        return min > 10 ? min : min + '0';
      }
    },
    changeMaxBookingDuration(mode) {
      if(mode === 'add') {
        if(this.currentPlanning.maxBookingDuration !== 240) {
          this.currentPlanning.maxBookingDuration = this.currentPlanning.maxBookingDuration + 30;
        }
      } else {
        if(this.currentPlanning.maxBookingDuration !== 30) {
          this.currentPlanning.maxBookingDuration = this.currentPlanning.maxBookingDuration-30;
        }
      }
      this.changeMinAndMaxBookingPlanningDate();
    },
    changeMinAndMaxBookingPlanningDate(){
      this.interval = this.currentPlanning.maxBookingDuration;
      let minBookingTime = this.currentPlanning.minBookingTime.split(':');
      let maxBookingTime = this.currentPlanning.maxBookingTime.split(':');
      this.minBooking = (parseInt(minBookingTime[0])*60+parseInt(minBookingTime[1])) / this.interval;
      this.maxBooking = (parseInt(maxBookingTime[0])*60+parseInt(maxBookingTime[1])) / this.interval;
      this.maxBooking = Math.floor(this.maxBooking - this.minBooking);
    },
    // Nouvelle méthode pour charger les brackets de périodes
    async loadPeriodBrackets() {
      try {
        this.loadingPeriodBrackets = true;

        // Récupérer l'ID du service depuis le catalogue
        const catalogueResponse = await getCatalogueById(this.catalogue);
        const serviceId = catalogueResponse.service.id;

        // Récupérer les brackets de périodes pour ce service
        const bracketsResponse = await getPeriodBracket(serviceId);
        this.periodBrackets = bracketsResponse.data;

        // Si le planning actuel a déjà un bracket de période assigné, le pré-sélectionner
        if (this.currentPlanning.periodBracket) {
          this.selectedPeriodBracket = this.periodBrackets.find(
            bracket => bracket.id === this.currentPlanning.periodBracket.id
          );
        }
      } catch (error) {
        console.error('Erreur lors du chargement des brackets de périodes:', error);
        this.$q.notify({
          type: 'negative',
          message: 'Erreur lors du chargement des groupes de périodes',
          position: 'top'
        });
      } finally {
        this.loadingPeriodBrackets = false;
      }
    },

    // Méthode appelée lors du changement de sélection du bracket
    onPeriodBracketChange(selectedBracket) {
      this.selectedPeriodBracket = selectedBracket;

      // Mettre à jour le planning avec le bracket sélectionné
      if (selectedBracket) {
        this.currentPlanning.periodBracket = selectedBracket;
      } else {
        this.currentPlanning.periodBracket = null;
      }
    },
  },
  watch: {
    currentPlanning: function () {
      if(this.currentPlanning.minBookingTime !== undefined) {
        this.changeMinAndMaxBookingPlanningDate();
      }
    },
    openDialog: function () {
      this.openSecondDialog = this.openDialog === true;
      this.loadPeriodBrackets();
    }
  }
}
</script>
<style scoped>

</style>