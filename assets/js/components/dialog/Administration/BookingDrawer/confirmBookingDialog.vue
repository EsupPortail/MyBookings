<template>
  <q-dialog v-model="dialog" persistent>
    <q-card style="width: 700px; max-width: 80vw;">
      <q-card-section>
        <div class="text-h5">Confirmer la réservation</div>
      </q-card-section>
      <q-separator/>
      <q-card-section>
        <div class="text-h6">Voulez-vous confirmer la réservation ?</div>
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
        <q-input outlined v-model="comment" type="text" label="Commentaire (optionnel)" style="margin-top: 10px" />
      </q-card-section>
      <q-card-section>
        <p>L'utilisateur recevra un email suite à votre confirmation.</p>
      </q-card-section>
      <div class="absolute-bottom-left text-primary" style="margin-bottom: 10px; margin-left: 10px">
        <q-btn flat label="Annuler" @click="sendClose" />
      </div>
      <q-card-actions align="right" class="text-primary">
        <q-btn flat label="Confirmer" @click="saveAndClose" />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script>
import {storeToRefs} from "pinia";
import { booking } from '../../../../store/booking';
import {getCatalogueById} from "../../../../api/CatalogRessource";
import {ref} from "vue";
import {confirmBooking} from "../../../../api/Booking";
const bookingStore = booking();
const { getSelection } = storeToRefs(bookingStore);

const resourceOption = ref([]);
export default {
  name: "confirmBookingDialog",
  props: {
    openConfirmDialog:Boolean,
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
    this.dialog = this.openConfirmDialog;
  },
  methods: {
    sendClose() {
      this.$emit('close', false)
    },
    getCatalogueResources(id) {
      getCatalogueById(id).then(function (response) {
        resourceOption.value = response.resource
      })
    },
    saveAndClose() {
      let id = this.bookingStore.getSelection.id;
      let self = this;
      let bodyFormData = new FormData();
      bodyFormData.append('comment', this.comment);
      bodyFormData.append('resource', JSON.stringify(this.resourceSelected));
      bodyFormData.append('confirm', true);
      confirmBooking(id, bodyFormData).then(function () {
        self.bookingStore.defineSelection([]);
        self.bookingStore.definedUpdated(true);
        self.$emit('close', false)
      })
    },
  },
  watch: {
    openConfirmDialog: function () {
      if(this.openConfirmDialog === true) {
        this.dialog = true;
        this.comment = "";
        this.resourceSelected = [];
        this.getCatalogueResources(this.bookingStore.getSelection.catalogueResource.id)
      } else {
        this.dialog = false;
      }
    }
  }
}
</script>

<style scoped>

</style>