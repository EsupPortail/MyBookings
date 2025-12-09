<template>
  <q-dialog v-model="dialog" persistent>
    <q-card style="width: 700px; max-width: 80vw;">
      <q-card-section>
        <div v-if="type === 'resource'" class="text-h5">Modifier la ressource</div>
        <div v-if="type === 'users'" class="text-h5">Modifier les participants</div>
        <div v-if="type === 'date'" class="text-h5">Modifier les dates</div>
      </q-card-section>
      <q-separator/>
      <q-card-section v-if="type === 'date'">
        <p class="text-h6">Date de début</p>
        <q-input outlined ref="startBooking" v-model="startDateBooking" label-color="primary" :label="$t('fromDateTime')"
                 :rules="[
                              val => checkDateTimeFormat(val)|| $t('Required'),
                              val => allowUsersToBookDates === true || 'Aucune ressource disponible sur le créneau demandé',
                              val => allowUsersCheckDates === true || 'La date de début doit être inférieure à la date de fin'
                           ]">
          <template v-slot:prepend>
            <q-icon name="event" class="cursor-pointer">
              <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                <q-date v-model="startDateBooking" mask="DD/MM/YYYY HH:mm">
                  <div class="row items-center justify-end">
                    <q-btn v-close-popup :label="$t('return')" color="primary" flat />
                  </div>
                </q-date>
              </q-popup-proxy>
            </q-icon>
          </template>
        </q-input>
        <p class="text-h6">Date de fin</p>
        <q-input outlined ref="endBooking" v-model="endDateBooking" label-color="primary" :label="$t('fromDateTime')" :rules="[
                              val => checkDateTimeFormat(val)|| $t('Required'),
                              val => allowUsersToBookDates === true || 'Aucune ressource disponible sur le créneau demandé',
                           ]">
          <template v-slot:prepend>
            <q-icon name="event" class="cursor-pointer">
              <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                <q-date v-model="endDateBooking" mask="DD/MM/YYYY HH:mm">
                  <div class="row items-center justify-end">
                    <q-btn v-close-popup :label="$t('return')" color="primary" flat />
                  </div>
                </q-date>
              </q-popup-proxy>
            </q-icon>
          </template>
        </q-input>
      </q-card-section>
      <q-card-section v-if="type === 'resource'">
        <q-select outlined multiple v-model="resourceSelected" :options="resourceOption" option-label="title" option-value="id" label="Sélectionnez les ressources que vous souhaitez attribuer">
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
      <q-card-section v-if="type === 'users'">
        <auto-complete-user-selector @update="updateUsers" :is-admin="true"></auto-complete-user-selector>
        <ul style="list-style: none;" class="q-pl-sm">
          <li v-for="user in usersAdded" class="q-ma-sm"><q-btn icon="delete" color="negative" round size="sm" @click="removeUser(user)"/> {{user.displayUserName}}</li>
        </ul>
      </q-card-section>
      <q-card-actions class="text-primary flex justify-between">
        <q-chip square text-color="white" color="dark" clickable @click="sendClose">Annuler</q-chip>
        <q-chip square text-color="white"  color="primary" clickable @click="saveAndClose">Enregistrer</q-chip>
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script>
import {storeToRefs} from "pinia";
import { booking } from '../../../../store/booking';
import {editBooking, endBooking, startBooking} from "../../../../api/Booking";
import {getCatalogueById} from "../../../../api/CatalogRessource";
import {ref} from "vue";
import AutoCompleteUserSelector from "../../../autoCompleteUserSelector.vue";
import {checkDateTimeFormat, dateStartEndCorrect} from "../../../../utils/dateUtils";
import {date} from "quasar";
import {checkEventDates} from "../../../../utils/bookingUtils";
const bookingStore = booking();
const { getSelection } = storeToRefs(bookingStore);
const allowUsersCheckDates = ref(true);
const allowUsersToBookDates = ref(true);
const resourceOption = ref([]);
const startDateBooking = ref('');
const endDateBooking = ref('');
export default {
  name: "EditBookingDialog",
  components: {AutoCompleteUserSelector},
  props: {
    openStartDialog:Boolean,
    admin:Boolean,
    type: String,
  },
  data() {
    return {
      bookingStore,
      getSelection,
      resourceSelected: [],
      resourceOption,
      dialog: false,
      users: [],
      usersAdded: [],
      allowUsersToBookDates,
      allowUsersCheckDates,
      startDateBooking,
      endDateBooking
    }
  },
  mounted() {
    this.dialog  = this.openStartDialog;
  },
  methods: {
    dateStartEndCorrect,
    checkDateTimeFormat,
    sendClose() {
      this.$emit('close', false)
    },
    mergeUserArray() {
      for(var i = 0; i< this.users.length; i++) {
        this.users[i].username = this.users[i].uid;
        delete this.users[i].uid;
      }
      return this.users.concat(this.usersAdded);
    },
    saveAndClose() {
      let self = this;
      let bodyFormData = new FormData();
      if(this.type === 'users') {
        bodyFormData.append('users', JSON.stringify(this.mergeUserArray()));
      }
      if(this.type === 'resource') {
        bodyFormData.append('resource', JSON.stringify(this.resourceSelected));
      }
      if(this.type === 'date') {
        if(this.$refs.startBooking.validate() && this.$refs.endBooking.validate()) {
          bodyFormData.append('dateStart', date.formatDate(date.extractDate(startDateBooking.value, 'DD/MM/YYYY HH:mm'), 'YYYY-MM-DD HH:mm'));
          bodyFormData.append('dateEnd', date.formatDate(date.extractDate(endDateBooking.value, 'DD/MM/YYYY HH:mm'), 'YYYY-MM-DD HH:mm'));
        }
      }
      editBooking(this.bookingStore.getSelection.id, bodyFormData).then(function (response) {
        self.bookingStore.defineSelection([]);
        self.bookingStore.definedUpdated(true);
        self.users = [];
        self.usersAdded = [];
        self.$emit('close', false)
      })
    },
    getCatalogueResources(id) {
      getCatalogueById(id).then(function (response) {
        resourceOption.value = response.resource;
      })
    },
    updateUsers(users) {
      this.users = users;
    },
    removeUser(user) {
      this.usersAdded = this.usersAdded.filter(function (value) {
        return value.username !== user.username;
      })
    },
    checkInputDates(){
      let dateStart = date.extractDate(startDateBooking.value, 'DD/MM/YYYY HH:mm');
      dateStart = date.formatDate(dateStart, 'YYYY-MM-DD HH:mm:ss');
      let dateEnd = date.extractDate(endDateBooking.value, 'DD/MM/YYYY HH:mm');
      dateEnd = date.formatDate(dateEnd, 'YYYY-MM-DD HH:mm:ss');
      allowUsersCheckDates.value = dateStartEndCorrect(dateStart, dateEnd);
      if(this.$refs.startBooking !== undefined && this.$refs.startBooking !== null) {
        this.$refs.startBooking.validate();
        this.$refs.endBooking.validate();
      }
    },
  },
  watch: {
    openStartDialog: function () {
      if(this.openStartDialog === true) {
        this.dialog = true;
        this.resourceSelected = [];
        this.getCatalogueResources(this.bookingStore.getSelection.catalogueResource.id)
        this.usersAdded = this.bookingStore.selection.user;
        this.resourceSelected = this.bookingStore.selection.Resource;
        startDateBooking.value = date.formatDate(this.bookingStore.selection.dateStart, 'DD/MM/YYYY HH:mm');
        endDateBooking.value = date.formatDate(this.bookingStore.selection.dateEnd, 'DD/MM/YYYY HH:mm');

      } else {
        this.dialog = false;
      }
    },
    startDateBooking: function () {
      this.checkInputDates();

    },
    endDateBooking: function () {
      this.checkInputDates();
    },
  }
}
</script>

<style scoped>

</style>