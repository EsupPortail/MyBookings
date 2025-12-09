<template>
  <q-dialog v-model="dialog" persistent>
    <q-card style="width: 700px; max-width: 80vw;">
      <q-card-section v-if="type==='start'">
        <div class="text-h5">Démarrer la réservation</div>
      </q-card-section>
      <q-card-section v-if="type==='end'">
        <div class="text-h5">Clôturer la réservation</div>
      </q-card-section>
      <q-separator/>
      <q-card-section v-if="type==='start'">
        <div class="text-h6">Matériel remis à l'utilisateur</div>
        <q-select outlined multiple v-model="resourceSelected" :options="resourceOption" option-label="title" option-value="id" label="Sélectionnez la ressource que vous souhaitez attribuer">
          <template v-slot:option="{itemProps, opt, selected, toggleOption }">
            <q-item v-bind="itemProps">
              <q-item-section>
                <q-item-label>{{ opt.title }}</q-item-label>
                <q-item-label caption v-if="opt.inventoryNumber">Num. inventaire : {{ opt.inventoryNumber }}</q-item-label>
              </q-item-section>
              <q-item-section side>
                <q-toggle :model-value="selected" @update:model-value="toggleOption(opt)" />
              </q-item-section>
            </q-item>
          </template>
        </q-select>
      </q-card-section>
      <q-card-section v-if="type==='end'">
        <div v-if="admin">
          <div class="text-h6">Matériel retourné par l'utilisateur :</div>
          <ul>
            <li v-for="resource in bookingStore.getSelection.Resource"><span>{{resource.title}}</span><span v-if="resource.inventoryNumber"> - #{{resource.inventoryNumber}}</span></li>
          </ul>
          <p>Si l'utilisateur a bien rendu le matériel prêté vous pouvez confirmer la clôture de la réservation</p>
        </div>
        <div v-else>
          <p>La clôture terminera la réservation à l'heure actuelle et libérera la ressource.</p>
        </div>
      </q-card-section>
      <q-card-actions class="text-primary flex justify-between">
        <q-chip square text-color="white" color="dark" clickable @click="sendClose">Annuler</q-chip>
        <q-chip square text-color="white"  color="primary" clickable @click="saveAndClose" v-if="type==='start'" >Confirmer</q-chip>
        <q-chip square text-color="white"  color="primary" clickable @click="saveAndCloseEnd" v-if="type==='end'" >Confirmer</q-chip>
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script>
import {storeToRefs} from "pinia";
import { booking } from '../../../../store/booking';
import {endBooking, startBooking} from "../../../../api/Booking";
import {getCatalogueById} from "../../../../api/CatalogRessource";
import {ref} from "vue";
const bookingStore = booking();
const { getSelection } = storeToRefs(bookingStore);

const resourceOption = ref([]);
export default {
  name: "startBookingDialog",
  props: {
    openStartDialog:Boolean,
    type:String,
    admin:Boolean
  },
  data() {
    return {
      bookingStore,
      getSelection,
      confirm: "",
      resourceSelected: [],
      comment: "",
      resourceOption,
      dialog: false,
    }
  },
  mounted() {
    this.dialog  = this.openStartDialog;
  },
  methods: {
    sendClose() {
      this.$emit('close', false)
    },
    saveAndClose() {
      let id = this.bookingStore.getSelection.id;
      let self = this;
      let bodyFormData = new FormData();
      bodyFormData.append('confirm', true);
      bodyFormData.append('resource', JSON.stringify(this.resourceSelected));
      startBooking(id, bodyFormData).then(function (response) {
        self.bookingStore.defineSelection([]);
        self.bookingStore.definedUpdated(true);
        self.$emit('close', false)
      })
    },
    saveAndCloseEnd() {
      let id = this.bookingStore.getSelection.id;
      let self = this;
      let bodyFormData = new FormData();
      bodyFormData.append('confirm', true);
      endBooking(id, bodyFormData).then(function (response) {
        self.bookingStore.defineSelection([]);
        self.bookingStore.definedUpdated(true);
        self.$emit('close', false)
      })
    },
    getCatalogueResources(id) {
      let self = this;
      getCatalogueById(id).then(function (response) {
        resourceOption.value = response.resource;
      })
    },
  },
  watch: {
    openStartDialog: function () {
      if(this.openStartDialog === true) {
        this.dialog = true;
        this.comment = "";
        this.resourceSelected = [];
        this.getCatalogueResources(this.bookingStore.getSelection.catalogueResource.id)
        this.resourceSelected = this.bookingStore.getSelection.resources;
      } else {
        this.dialog = false;
      }
    }
  }
}
</script>

<style scoped>

</style>