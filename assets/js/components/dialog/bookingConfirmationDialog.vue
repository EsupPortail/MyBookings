<template>
  <q-dialog v-model="confirmationDialog" persistent>
    <q-card class="confirmationDialog">
      <q-card-section>
        <div v-if="dataLoading" class="text-h6">Réservation en cours d'envoi...</div>
        <div v-else>
          <div v-if="bookingsInformations.length > 0" class="text-h6">{{isMultipleBookings() === true? 'Réservations envoyées': 'Réservation envoyée'}}<q-icon color="positive" name="check"/></div>
          <div v-else class="text-h6">Votre réservation n'a pas pu être effectuée <q-icon color="negative" name="error"/></div>
        </div>
      </q-card-section>
      <q-separator inset />
      <q-card-section>
        <div v-if="dataLoading" class="text-center">
          <q-circular-progress
              indeterminate
              rounded
              size="100px"
              color="primary"
              class="q-ma-md"
          />
        </div>
        <div v-else>
          <q-list v-if="bookingsInformations.length > 0" bordered separator>
            <q-item v-for="(booking, key) in bookingsInformations">
              <q-item-section>
                <q-item-label>
                  <span  v-if="booking.status=== 'pending' " style="color: red">
                    Réservation en attente de validation
                  </span>
                  <span v-else>
                    Réservation
                  </span>
                  #{{key+1}} :
                  <q-chip dense color="primary" text-color="white" v-for="resource in booking.Resource">{{resource.title}}</q-chip>
                </q-item-label>
                <q-item-label caption>
                  {{ shortFormatDatefromAPItoString(booking.dateStart) }}
                  -
                  {{dateSameDay(booking.dateStart, booking.dateEnd, 'ISO')}}
                </q-item-label>
                <q-item-label v-if="booking.status === 'pending'" caption>
                  Votre demande de réservation a bien été prise en compte. Un mail vous sera envoyé lorsque votre demande aura été examinée.
                  Le processus n'étant pas automatique, les demandes de dernière minute peuvent donc ne pas être prises en compte dans les temps.
                </q-item-label>
                <q-item-label caption v-if="booking.catalogueResource.actuator !== null">Accès à la ressource : votre badge sera accrédité au moment de la réservation</q-item-label>
              </q-item-section>
            </q-item>
          </q-list>
          <div v-else>
            <div v-if="errorMsg === 'disconnected'">
              <div class="text-center q-ma-md">
                <q-btn color="primary" @click="reload()">Se reconnecter</q-btn>
              </div>
              <div>Authentification expirée ! Veuillez vous reconnecter à l'application.</div>
            </div>
            <div v-else>
              <div><p>Votre réservation n'a pas pu être effectué car les critères ne correspondent pas aux règles de gestion en vigueur.</p></div>
              <q-list padding bordered class="rounded-borders">
                <q-expansion-item dense icon="info" label="Plus de détail">
                  <q-card-section bordered>
                    <div v-html="getErrorMsg()"></div>
                  </q-card-section>
                </q-expansion-item>
              </q-list>
            </div>

          </div>
        </div>
      </q-card-section>
      <q-card-actions v-if="!dataLoading" class="text-primary flex justify-between">
        <q-chip square text-color="white"  color="dark" clickable @click="close">Rester sur cette page</q-chip>
        <q-chip v-if="bookingsInformations.length > 0" square text-color="white"  color="primary" clickable @click="goMe">Voir mes réservations</q-chip>
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>


<script>
import { basket } from '../../store/basket';
import {loadBookingById} from "../../api/Booking";
import {dateSameDay, formatDatefromAPItoString, shortFormatDatefromAPItoString} from "../../utils/dateUtils";
import {ref} from "vue";

const dataLoading = ref(true);
const bookingsInformations = ref([]);
export default {
  name: "bookingConfirmationDialog",
  props: {
    openDialog: Boolean,
    bookings: Array,
    errorMsg: String|Object,
    isLoading: Boolean
  },
  data() {
    return {
      confirmationDialog: false,
      localBookings: [],
      bookingsInformations,
      basketUser: basket(),
      dataLoading,
    }
  },
  methods: {
    shortFormatDatefromAPItoString,
    formatDatefromAPItoString,
    dateSameDay,
    close() {
      this.$emit('close');
    },
    goMe() {
      this.$router.push('/me');
    },
    isMultipleBookings() {
      return this.localBookings.length>1;
    },
    loadBookings() {
      this.localBookings = this.bookings;
      bookingsInformations.value = [];
      this.localBookings.forEach(function (booking) {
        loadBookingById(booking).then(function (response) {
          bookingsInformations.value.push(response);
          dataLoading.value = false;
        })
      })
    },
    reload() {
      window.location.reload()
    },
    getErrorMsg() {
      if(this.errorMsg instanceof Object) {
        if(this.errorMsg.restrictions !== undefined) {
          return this.errorMsg.restrictions;
        }
      }
      return this.errorMsg;
    },
  },
  watch: {
    openDialog: function () {
      dataLoading.value = true;
      this.confirmationDialog = this.openDialog;
    },
    bookings: function () {
      this.loadBookings();
    },
    errorMsg: function () {
      if(this.errorMsg !== '') {
        dataLoading.value = false;
      }
    }
  },
}

</script>


<style scoped>
.confirmationDialog {
  min-width: 50%;
}
</style>