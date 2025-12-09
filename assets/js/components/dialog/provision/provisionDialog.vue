<template>
  <div>
    <q-dialog v-model="dialog" persistent transition-show="scale" transition-hide="scale">
      <q-card style="width: 600px">
        <q-card-section>
          <div class="row">
            <div class="col">
              <div class="text-h6">Liste des plannings définis</div>
            </div>
            <div class="col-auto">
              <q-btn round color="secondary" icon="add" @click="newPlanning">
                <q-tooltip>
                  Ajouter un planning
                </q-tooltip>
              </q-btn>
            </div>
          </div>
        </q-card-section>
        <q-separator/>
        <q-card-section class="bg-white text-black">
          <q-list bordered separator v-if="provisions.length">
            <q-item clickable v-ripple v-for="(provision, index) in provisions" v-on:click="editPlanning(provision)">
              <q-item-section>
                <q-item-label>Planning {{index+1}} :
                  <span v-if="provision.dateStart && provision.dateEnd">{{ formatDateAPI(provision.dateStart) }} - {{ formatDateAPI(provision.dateEnd) }}</span>
                  <span v-if="provision.periodBracket">
                    {{provision.periodBracket.title}}
                  </span>
                </q-item-label>
                <q-item-label caption v-if="provision.minBookingTime && provision.maxBookingTime">{{formatTimeAPI(provision.minBookingTime)}} - {{formatTimeAPI(provision.maxBookingTime)}}</q-item-label>
                <q-item-label caption v-if="provision.workflow">Workflow : {{provision.workflow.title}}</q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
          <div v-if="!provisions.length" style="text-align: center">Aucun planning défini</div>
        </q-card-section>
        <q-card-actions align="right" class="bg-white text-teal">
          <q-btn flat label="Fermer" @click="sendClose" />
        </q-card-actions>
      </q-card>
    </q-dialog>
    <provision-definition-dialog @close="closeDefinitionDialog" :catalogue="catalogue" :open-dialog="openSecondDialog" :current-planning="currentPlanning" :workflows="workflows"></provision-definition-dialog>
  </div>
</template>

<script>
import { ref } from 'vue';
import {counter} from "../../../store/counter";
import ProvisionDefinitionDialog from "./provisionDefinitionDialog.vue";
import {getProvisionsFromCatalog} from "../../../api/Provision";
import {getWorkflowsByService} from "../../../api/Workflow";

const provisions = ref([]);
const workflows = ref([]);

export default {
  name: "provisionDialog",
  components: {ProvisionDefinitionDialog},
  setup () {
    const nbCounter = counter();
    function incrementCounter() {
      nbCounter.increment();
    }
    return {
      nbCounter,
      incrementCounter
    }
  },
  data () {
    return {
      openSecondDialog: false,
      confirm: ref(false),
      currentPlanning: {},
      defaultPlanning: {
        id: null,
        dateStart: null,
        dateEnd: null,
        minBookingTime: '08:00',
        maxBookingTime: '17:00',
        bookingInterval: 0,
        workflow: null,
        days: ['monday','tuesday','wednesday','thursday','friday'],
        maxBookingDuration: 60,
        maxBookingByWeek: 0,
        maxBookingByDay: 0,
        allowMultipleDay: false,
      },
      provisions,
      dialog: false,
      workflows,
    }
  },
  props: {
    openProvision:Boolean,
    catalogue:String,
    initialProvisions:Array,
  },
  mounted() {
    this.dialog = this.openProvision;
    provisions.value = Object.assign([], this.initialProvisions);
    this.getWorkflows();
  },
  methods: {
    editPlanning(provision) {
      this.currentPlanning = Object.assign({}, provision);
      this.currentPlanning.periodBracket = provision.periodBracket ? provision.periodBracket : null;
      this.currentPlanning.dateStart = this.formatDateAPI(this.currentPlanning.dateStart);
      this.currentPlanning.dateEnd = this.formatDateAPI(this.currentPlanning.dateEnd);
      this.currentPlanning.minBookingTime = this.formatTimeAPI(this.currentPlanning.minBookingTime).slice(0,-3);
      this.currentPlanning.maxBookingTime = this.formatTimeAPI(this.currentPlanning.maxBookingTime).slice(0,-3);
      this.currentPlanning.attachedWorkflow = provision.workflow;
      if(this.currentPlanning.allowMultipleDay === null) {
        this.currentPlanning.allowMultipleDay = false;
      }
      this.openSecondDialog = true;
    },
    newPlanning() {
      this.currentPlanning = Object.assign({}, this.defaultPlanning);
      let dateNow = new Date();
      this.currentPlanning.dateStart = this.formatDateAPI(dateNow);
      dateNow.setDate(dateNow.getDate()+1);
      this.currentPlanning.dateEnd = this.formatDateAPI(dateNow);
      this.openSecondDialog = true;
    },
    formatDateAPI(apiDate) {
      let newDate = new Date(apiDate);
      return newDate.toLocaleDateString();
    },
    formatTimeAPI(apiDate) {
      let newDate = new Date(apiDate);
      return newDate.toLocaleTimeString();
    },
    sendClose() {
      this.$emit('close', provisions.value);
    },
    closeDefinitionDialog() {
      this.getProvisionsFromCatalogue();
      this.openSecondDialog = false;
    },
    getProvisionsFromCatalogue() {
      getProvisionsFromCatalog(this.catalogue).then(function (response) {
        provisions.value = Object.assign([], response.data.Provisions);
      })
    },
    getWorkflows() {
      getWorkflowsByService(this.$router.currentRoute.value.params.id).then(function (response) {
        workflows.value = response.data;
      })
    },
  },
  watch: {
    openProvision: function () {
      this.dialog = this.openProvision === true;
    }
  }
}
</script>

<style scoped>

</style>