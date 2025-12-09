<template>
  <q-dialog v-model="dialog" persistent>
    <q-card style="width: 700px; max-width: 80vw;">
      <q-card-section>
        <div class="text-h5">Voulez-vous confirmer la réservation ?</div>
      </q-card-section>
      <q-separator/>
      
      <q-card-section>
        <q-input outlined v-model="comment" type="text" label="Commentaire (optionnel)" style="margin-top: 10px" />
      </q-card-section>
      
      <q-card-section>
        <p>@todo Explication du workflow à faire.</p>
      </q-card-section>

      <q-card-actions align="right" class="text-primary">
        <q-btn flat color="dark" label="Annuler" @click="sendClose" />
        <q-btn flat color="primary" label="Confirmer" @click="saveAndClose" />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script>
import {storeToRefs} from "pinia";
import { booking } from '../../../store/booking';
import { confirmBookingEffects } from "../../../api/ressourcerie/Effects";
const bookingStore = booking();
const { getSelection } = storeToRefs(bookingStore);

export default {
  name: "confirmBookingEffectsDialog",
  props: {
    openConfirmDialog: Boolean,
  },
  data() {
    return {
      bookingStore,
      getSelection,
      confirm: "",
      comment: "",
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
    saveAndClose() {
      let id = this.bookingStore.getSelection.id;
      let self = this;
      let bodyFormData = new FormData();
      bodyFormData.append('comment', this.comment);
      bodyFormData.append('confirm', true);
      confirmBookingEffects(id, bodyFormData).then(function () {
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
      } else {
        this.dialog = false;
      }
    }
  }
}
</script>

<style scoped>

</style>