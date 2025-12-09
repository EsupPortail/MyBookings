<template>
  <q-dialog v-model="dialog" persistent>
    <q-card style="width: 700px; max-width: 80vw;">
      <q-card-section>
        <div class="text-h5">{{ catalog.title }} <div style="display: inline; font-size: 1rem; color: #6d6d6c; line-height: 1rem;">{{ catalog.description }}</div></div>
      </q-card-section>
      
      <q-separator/>
      
      <CustomTableBookEffects :catalog="catalog" @selectedRessources="onUpdatedSelectedRessources"></CustomTableBookEffects>
      
      <q-separator/>
      
      <q-card-actions class="row justify-between">
        <div class="row q-gutter-x-lg q-ml-none items-center q-pb-md">
          <div class="text-bold">{{ catalog.service.title }}</div>
          <span style="font-size: 1.5rem;">&raquo;</span>
          <q-icon name="local_shipping" size="sm" style=""></q-icon>
          <span style="font-size: 1.5rem;">&raquo;</span>
          <q-select style="width: 150px;" :options="sitesOptions" v-model="selectedSite" option-value="id" option-label="title" label="Destination" dense :rules="[val => !!val || 'Sélectionnez une destination']"></q-select>
        </div>
        
        <div>
          <q-btn flat label="Fermer" color="dark" @click="sendClose" />
          <q-btn flat label="Réserver" color="primary" @click="book" :disabled="selectedRessources.length == 0 || selectedSite == null"><q-tooltip v-if="selectedRessources.length == 0 || selectedSite == null">Cocher au moins une ressource et une destination</q-tooltip></q-btn>
        </div>
      </q-card-actions>
      
    </q-card>
  </q-dialog>
</template>

<script>
import { ref } from 'vue'
import { getServiceByType } from '../../../api/Service'
import CustomTableBookEffects from '../../table/ressourcerie/customTableBookEffects.vue'
import { bookEffects } from '../../../api/ressourcerie/Effects'

const sitesOptions = ref([])
const selectedRessources = ref([])
const selectedSite = ref(null)

export default {
  name: "bookingEffectsDialog",
  props: {
    openEffectsDialog:Boolean,
    catalog: Object,
  },
  components: {
    CustomTableBookEffects
  },
  data() {
    return {
      dialog: false,
      sitesOptions,
      selectedSite,
      selectedRessources,
    }
  },
  mounted() {
    this.dialog  = this.openEffectsDialog;
  },
  methods: {
    sendClose() {
      this.$emit('close', false)
    },
    book() {
      let ressourceIds = selectedRessources.value.map((ressource) => {
        return ressource.id
      })
      bookEffects(ressourceIds, selectedSite.value.id).then((response) => {
        this.$emit('update')
      })
    },
    onUpdatedSelectedRessources(ressources) {
      selectedRessources.value = ressources
    }
  },
  watch: {
    openEffectsDialog: function () {
      this.dialog = this.openEffectsDialog;
      if(this.dialog === true) {
        getServiceByType('ressourcerie').then((response) => {
          sitesOptions.value = response.data;
          selectedRessources.value = [];
        })
      }
    }
  }
}
</script>

<style scoped>

</style>