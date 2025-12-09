<template>
  <start-booking-dialog :openStartDialog="startBookingDialog" :type="typeConfirm" :admin="isAdmin" @close="startBookingDialog=false"></start-booking-dialog>
  <confirm-booking-dialog v-if="isAdmin" :openConfirmDialog="confirmDialog" @close="confirmDialog=false" ></confirm-booking-dialog>
  <edit-booking-dialog v-if="isAdmin" :openStartDialog="editResourceBookingDialog" :type="editType" @close="editResourceBookingDialog=false"></edit-booking-dialog>
  <RemoveDialog
    v-model="userDeleteDialog"
    :message="$t('booking.deleteConfirmation')"
    :ok-action="removeBookingByUser"
  />

  <RemoveDialog
      v-model="userRefuseDialog"
      :message="$t('booking.deleteConfirmation')"
      :ok-action="refuseBookingByUser"
  />

  <RemoveDialog
      v-model="adminRefuseDialog"
      :message="$t('booking.refuseConfirmation')"
      :ok-label="$t('booking.refuse')"
      :ok-action="refuseBooking"
      :showComment="true"
  />

  <booking-history-dialog :openHistory="openHistory" @close="openHistory = false"></booking-history-dialog>
  <q-drawer bordered v-model="rightDrawerOpen" side="right" style="margin-top: 50%" :width="350" v-if="bookingStore.getSelection.catalogueResource !== undefined">
    <q-img class="absolute-top" :src="getUrlImage()" style="height: 150px; margin-top: 16%" :alt="bookingStore.getSelection.catalogueResource.title">
      <template v-slot:error>
        <div class="absolute-full flex flex-center bg-dark text-white">
          <q-icon size="md" name="no_photography"/>
        </div>
      </template>
    </q-img>
    <q-card flat style="margin-top: 10%">
      <q-card-section class="q-ma-xs">
        <h2 class="text-h6">{{ $t('booking.requestDetail') }}</h2>
        <q-btn class="absolute-top-right q-ma-sm" round color="primary" icon="schedule" :title="$t('booking.viewHistory')" :aria-label="$t('booking.viewHistory')" @click="openHistory = true;"/>
      </q-card-section>
      <q-card-section class="q-pa-sm">
        <div v-if="isAdmin && bookingStore.getSelection.status === 'accepted'" class="text-center">
          <q-btn color="negative" text-color="white" @click="userRefuseDialog = true" icon="delete" dense no-caps :aria-label="$t('booking.cancelBooking')">{{ $t('booking.cancelBooking') }}</q-btn>
        </div>
        <div v-if="isAdmin && bookingStore.getSelection.status === 'pending'" class="text-center">
          <q-btn color="negative" text-color="white" @click="adminRefuseDialog = true" icon="delete" dense no-caps :aria-label="$t('booking.refuseBooking')">{{ $t('booking.refuseBooking') }}</q-btn>
        </div>
      </q-card-section>
      <q-card-section  style="padding-top: 0">

        <p v-if="isAdmin && bookingStore.getSelection.status !== 'returned'"><q-btn   icon="edit" round size="xs" outline color="primary" class="q-mr-xs" @click="editElement('date')"/> {{ formatDatefromAPItoString(bookingStore.getSelection.dateStart) }}  - {{ formatDatefromAPItoString(bookingStore.getSelection.dateEnd) }}</p>
        <p v-else class="text-center">{{ formatDatefromAPItoString(bookingStore.getSelection.dateStart) }}  - {{ formatDatefromAPItoString(bookingStore.getSelection.dateEnd) }}</p>
        <p>{{ $t('booking.bookingTitle') }} : <span v-if="bookingStore.getSelection.title" class="text-primary text-italic text-bold">{{bookingStore.getSelection.title}}</span><span v-else>{{ $t('common.notSpecified') }}</span></p>
        <p><q-btn v-if="isAdmin && bookingStore.getSelection.status !== 'returned'" icon="edit" round size="xs" outline color="primary" class="q-mr-xs" @click="editElement('users')"/> {{ $t('booking.users') }} :</p>
        <q-chip color="primary" text-color="white" v-for="user in bookingStore.getSelection.user">{{ user.displayUserName }}</q-chip>
        <div style="margin-top: 15px">
          <p>{{ $t('common.comment') }} :</p>
          <i v-if="bookingStore.getSelection.userComment !== ''">« {{bookingStore.getSelection.userComment}} »</i>
          <i v-else>{{ $t('common.noComment') }}</i>
        </div>
        <div class="q-pt-md" v-if="bookingStore.getSelection.state !== 'pending'">
          <p>{{ $t('booking.moderationComment') }} :</p>
          <i v-if="bookingStore.getSelection.confirmComment !== ''">« {{bookingStore.getSelection.confirmComment}} »</i>
          <i v-else>{{ $t('common.noComment') }}</i>
        </div>
        <div class="q-pt-md" v-if="bookingStore.getSelection.state !== 'pending' && bookingStore.getSelection.state !== 'refused'" >
          <p><q-btn v-if="isAdmin && bookingStore.getSelection.status !== 'returned'" icon="edit" round size="xs" outline color="primary" class="q-mr-xs" @click="editElement('resource')" /> {{ $t('booking.assignedResources', { count: bookingStore.getSelection.number }) }} :</p>
          <ul>
            <li v-for="resource in bookingStore.getSelection.Resource"><span>{{resource.title}}</span><span v-if="resource.inventoryNumber !== null && resource.inventoryNumber !== '' "> - #{{resource.inventoryNumber}}</span></li>
          </ul>
        </div>
        <div v-if="bookingStore.getSelection.Options.length>0" class="q-pt-md">
          <p>{{ $t('booking.selectedOptions') }} :</p>
          <ul>
            <li v-for="option in bookingStore.getSelection.Options">{{option.title}}</li>
          </ul>
        </div>

      </q-card-section>
    </q-card>
    <q-btn color="primary" :title="$t('common.close')" @click="closeDrawer" class="absolute-bottom-left q-ma-sm" :aria-label="$t('common.close')">{{ $t('common.close') }}</q-btn>
    <div v-if="isAdmin" class="absolute-bottom-right q-ma-sm">
      <q-btn v-if="bookingStore.getSelection.status === 'pending'" color="primary" text-color="white" @click="confirmBooking" no-caps :aria-label="$t('booking.confirmBooking')">{{ $t('booking.confirmBooking') }}</q-btn>
      <q-btn v-if="bookingStore.getSelection.status === 'accepted'" color="primary" text-color="white" @click="startBookingDialog = true; typeConfirm = 'start'" no-caps :aria-label="$t('booking.startBooking')">{{ $t('booking.startBooking') }}</q-btn>
      <q-btn v-if="bookingStore.getSelection.status === 'progress'" color="primary" text-color="white" @click="startBookingDialog = true; typeConfirm = 'end'" no-caps :aria-label="$t('booking.closeBooking')">{{ $t('booking.closeBooking') }}</q-btn>
      <q-btn v-if="bookingStore.getSelection.status === 'returned'" color="negative" text-color="white" @click="userDeleteDialog = true" no-caps :aria-label="$t('booking.deleteBooking')">{{ $t('booking.deleteBooking') }}</q-btn>
    </div>

    <div v-if="!isAdmin" class="absolute-bottom-right q-ma-sm">
      <q-btn v-if="bookingStore.getSelection.status === 'pending' || bookingStore.getSelection.status === 'accepted'" color="negative" text-color="white" @click="userDeleteDialog = true" no-caps :aria-label="$t('booking.deleteBooking')">{{ $t('booking.deleteBooking') }}</q-btn>
      <q-btn v-if="bookingStore.getSelection.status === 'progress' && bookingStore.getSelection.Workflow.auto_end === true" color="primary" text-color="white" @click="startBookingDialog = true; typeConfirm = 'end'" no-caps :aria-label="$t('booking.closeBooking')">{{ $t('booking.closeBooking') }}</q-btn>
    </div>
  </q-drawer>
</template>

<script>
import RemoveDialog from "../dialog/RemoveDialog.vue";
import StartBookingDialog from "../dialog/Administration/BookingDrawer/startBookingDialog.vue";
import ConfirmBookingDialog from "../dialog/Administration/BookingDrawer/confirmBookingDialog.vue";
import BookingHistoryDialog from "../dialog/Administration/BookingDrawer/bookingHistoryDialog.vue";
import {storeToRefs} from "pinia";
import {booking} from "../../store/booking";
import {ref} from "vue";
import {formatDatefromAPItoString} from "../../utils/dateUtils";
import {deleteBooking, refuseBooking} from "../../api/Booking";
import EditBookingDialog from "../dialog/Administration/BookingDrawer/editBookingDialog.vue";

export default {
  name: 'bookingDetailDrawer',
  components: {
    EditBookingDialog, BookingHistoryDialog, ConfirmBookingDialog, StartBookingDialog, RemoveDialog},
  props: {
    admin: Boolean,
    bookingList: Object,
  },
  data() {
    const bookingStore = booking();
    const { getSelection, getUpdated } = storeToRefs(bookingStore);
    return {
      isAdmin: false,
      getSelection,
      bookingStore,
      rightDrawerOpen: false,
      startBookingDialog: false,
      confirmDialog: false,
      typeConfirm: '',
      openHistory: false,
      userDeleteDialog: false,
      userRefuseDialog: false,
      adminRefuseDialog: false,
      editResourceBookingDialog: false,
      editType: '',
      adminRefuseComment: '', // Ajout pour le commentaire de refus admin
    }
  },
  mounted() {
    this.isAdmin = this.admin;
  },
  methods: {
    getUrlImage() {
      let url= '/uploads/' + this.bookingStore.getSelection.catalogueResource.image
      if(this.bookingStore.getSelection.Resource.length === 1) {
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
    removeBookingByUser() {
      this.userDeleteDialog= false;
      let self = this;
      deleteBooking(this.bookingStore.getSelection.id, this.isAdmin).then(function (response) {
        self.bookingStore.defineSelection([]);
        self.bookingStore.definedUpdated(true);
        self.confirmDialog = false;
      })
    },
    refuseBookingByUser() {
      this.userRefuseDialog= false;
      let self = this;
      refuseBooking(this.bookingStore.getSelection.id).then(function (response) {
        self.bookingStore.defineSelection([]);
        self.bookingStore.definedUpdated(true);
        self.confirmDialog = false;
      })
    },
    refuseBooking(comment) {
      let self = this;
      // Si le commentaire est passé (depuis RemoveDialog), on le stocke
      if (typeof comment === 'string') {
        self.adminRefuseComment = comment;
      }
      refuseBooking(this.bookingStore.getSelection.id, self.adminRefuseComment).then(function (response) {
        self.bookingStore.defineSelection([]);
        self.bookingStore.definedUpdated(true);
        self.adminRefuseDialog = false;
        self.adminRefuseComment = '';
      })
    },
    closeDrawer() {
      this.bookingStore.defineSelection([]);
    }
  },
  watch: {
    getSelection: function () {
      if (this.getSelection !== null || this.getSelection !== []) {
        this.rightDrawerOpen = ref(true);
      }
    },
    admin: function () {
      this.isAdmin = this.admin;
    }
  }
}
</script>

<style scoped>

</style>