<template>
  <confirm-booking-effects-dialog v-if="admin" :openConfirmDialog="confirmDialog" @close="confirmDialog=false" ></confirm-booking-effects-dialog>

  <edit-booking-dialog v-if="admin" :openStartDialog="editResourceBookingDialog" :type="editType" @close="editResourceBookingDialog=false"></edit-booking-dialog>

  <RemoveDialog
        v-model="refuseBookingDialog"
        message="Voulez-vous refuser la demande ?<br><small>@todo L'utilisateur sera informé de la décision par mail.</small>"
        :ok-label="'Refuser'"
        :ok-action="refuseBooking"
    />

  <RemoveDialog
      v-model="closeBookingDialog"
      message="Confirmer la livraison du bien ?<br><small>@todo L'utilisateur sera informé de la clôture de la demande.</small>"
      :ok-label="'Clôturer'"
      :ok-color="'primary'"
      :ok-action="closeBooking"
  />

  <RemoveDialog
      v-model="cancelBookingDialog"
      message="Voulez-vous annuler la demande ?<br><small>@todo L'utilisateur sera informé de la décision par mail.</small>"
      :ok-label="'Annuler'"
      :ok-action="cancelBooking"
  />

  <q-drawer bordered v-model="rightDrawerOpen" side="right" style="padding-top: 50px" :width="350" class="column" v-if="bookingStore.getSelection?.id">
    
    <q-img :src="getUrlImage()" style="height: 150px;">
      <template v-slot:error>
        <div class="absolute-full flex flex-center bg-grey text-white">
          <q-icon size="md" name="no_photography"/>
        </div>
      </template>
    </q-img>
    <q-btn round outline icon="close" color="primary" title="Fermer" @click="closeDrawer" class="absolute-top-right" style="margin-top: 3.5rem; margin-right: .5rem;" />
    
    <q-card-section>

      <p>
        <q-btn v-if="admin && bookingStore.getSelection.status !== 're_delivered'" icon="edit" round size="xs" outline color="primary" class="q-mr-xs" @click="editElement('users')"/>
        Demandeur(s) :
      </p>
      <q-chip color="blue-2" text-color="primary" v-for="user in bookingStore.getSelection.user" :key="user.id">{{ user.displayUserName }}</q-chip>
      
      <div v-if="bookingStore.getSelection.userComment" class="q-pt-md">
        <p>Commentaire :</p>
        <i>« {{bookingStore.getSelection.userComment}} »</i>
      </div>
      
      <div v-if="bookingStore.getSelection.confirmComment" class="q-pt-md">
        <p>Commentaire de la modération :</p>
        <i>« {{bookingStore.getSelection.confirmComment}} »</i>
      </div>

      <div v-if="bookingStore.getSelection.catalogueResource?.service" class="q-pt-md">
        <p>Source :</p>
        <i>« {{ bookingStore.getSelection.catalogueResource.service.title }} »</i> <router-link :to="`/ressourcerie/administration/site/${bookingStore.getSelection.catalogueResource.service.id}/users`" target="_blank">voir les modérateurs</router-link>
      </div>

      <div v-if="destination" class="q-pt-md">
        <p>Destination :</p>
        <i>« {{ destination.title }} »</i> <router-link :to="`/ressourcerie/administration/site/${destination.id}/users`" target="_blank">voir les modérateurs</router-link>
      </div>
      
      <div class="q-pt-md">
        <p>
          <q-btn v-if="admin && bookingStore.getSelection.status !== 're_delivered'" icon="edit" round size="xs" outline color="primary" class="q-mr-xs" @click="editElement('resource')" />
          Exemplaires ({{bookingStore.getSelection.number}}) :
        </p>
        <ul>
          <li v-for="resource in bookingStore.getSelection.Resource" :key="resource.id">
            <span>{{resource.title}}</span>
            <span v-if="resource.inventoryNumber !== null && resource.inventoryNumber !== '' && resource.inventoryNumber !== 'null'"> - #{{resource.inventoryNumber}}</span>
          </li>
        </ul>
      </div>
      
    </q-card-section>
    
    <q-list class="q-mt-auto">
      <q-item clickable v-if="(admin && bookingStore.getSelection.status === 're_progress')" @click="cancelBookingDialog = true">
        <q-item-section avatar>
          <q-icon color="amber-10" name="block" />
        </q-item-section>
        <q-item-section class="text-amber-10">
          <q-item-label>Annuler la demande</q-item-label>
        </q-item-section>
      </q-item>

      <q-item clickable v-if="admin && bookingStore.getSelection.status === 're_requested'" @click="refuseBookingDialog = true">
        <q-item-section avatar>
          <q-icon color="negative" name="cancel" />
        </q-item-section>
        <q-item-section class="text-negative">
          <q-item-label>Refuser la demande</q-item-label>
        </q-item-section>
      </q-item>

      <q-item clickable v-if="admin && bookingStore.getSelection.status === 're_requested'" @click="confirmBooking">
        <q-item-section avatar>
          <q-icon color="green" name="check" />
        </q-item-section>
        <q-item-section class="text-green">
          <q-item-label>Accepter la demande</q-item-label>
        </q-item-section>
      </q-item>

      <q-item clickable v-if="admin && bookingStore.getSelection.status === 're_progress'" @click="closeBookingDialog = true">
        <q-item-section avatar>
          <q-icon color="green" name="check" />
        </q-item-section>
        <q-item-section class="text-green">
          <q-item-label>Clôturer la demande</q-item-label>
        </q-item-section>
      </q-item>
  

    </q-list>

  </q-drawer>
</template>

<script>
import RemoveDialog from "../../dialog/RemoveDialog.vue";
import ConfirmBookingEffectsDialog from "../../dialog/ressourcerie/confirmBookingEffectsDialog.vue";
import { storeToRefs } from "pinia";
import { booking } from "../../../store/booking";
import { ref } from "vue";
import { formatDatefromAPItoString } from "../../../utils/dateUtils";
import { closeBookingEffects, refuseBookingEffects, cancelBookingEffects } from "../../../api/ressourcerie/Effects";
import EditBookingDialog from "../../dialog/Administration/BookingDrawer/editBookingDialog.vue";
import { getService } from "../../../api/Service";

const destination = ref('');
const bookingStore = booking();

export default {
  name: 'bookingDetailDrawer',
  components: {
    EditBookingDialog, ConfirmBookingEffectsDialog, RemoveDialog
  },
  props: {
    admin: Boolean,
  },
  data() {
    const { selection } = storeToRefs(bookingStore);
    return {
      selection,
      bookingStore ,
      rightDrawerOpen: false,
      confirmDialog: false,
      openHistory: false,
      userDeleteDialog: false,
      cancelBookingDialog: false,
      refuseBookingDialog: false,
      editResourceBookingDialog: false,
      editType: '',
      closeBookingDialog: false,
      destination: '',
    }
  },
  mounted() {
  },
  methods: {
    getUrlImage() {
      let url= '/uploads/' + this.bookingStore.getSelection.catalogueResource?.image
      if(this.bookingStore.getSelection.Resource?.length === 1) {
        if(this.bookingStore.getSelection.Resource[0].image !== null) {
          url= '/uploads/' + this.bookingStore.getSelection.Resource[0].image;
        }
      }
      return url;
    },
    formatDatefromAPItoString,
    editElement(type) {
      this.editResourceBookingDialog = true;
      this.editType = type;
    },
    confirmBooking() {
      this.confirmDialog = true;
    },
    closeBooking() {
      closeBookingEffects(this.bookingStore.getSelection.id).then(() => {
        this.bookingStore.clearSelection()
      }).finally(() => { 
        this.closeBookingDialog = false 
      })
    },
    refuseBooking() {
      refuseBookingEffects(this.bookingStore.getSelection.id).then(() => {
        this.bookingStore.clearSelection()
      }).finally(() => {
        this.refuseBookingDialog = false
      })
    },
    cancelBooking() {
      cancelBookingEffects(this.bookingStore.getSelection.id).then(() => {
        this.bookingStore.clearSelection()
      }).finally(() => {
        this.cancelBookingDialog = false
      })
    },
    closeDrawer() {
      this.bookingStore.clearSelection()
    }
  },
  watch: {
    selection(newSelection) {
      console.log('Watching selection', newSelection)
      if (newSelection !== null) {
        this.rightDrawerOpen = true;
        this.destination = '';
        if (newSelection.title) {
          getService(newSelection.title).then((response) => {
            this.destination = response;
          });
        }
      } else {
        this.rightDrawerOpen = false;
      }
    },
    // admin: function () {
    //   this.isAdmin = this.admin;
    // }
  }
}
</script>

<style scoped>

</style>