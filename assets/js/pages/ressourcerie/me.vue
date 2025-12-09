<template>
  <div>
    <div class="text-h6">Historique de mes réservations</div>
    <q-separator/>
    <booking-detail-drawer :admin="false" :booking-list="bookingStore"></booking-detail-drawer>
    <selection-booking-list v-if="userStore.isMobile" :basket-user="bookings" :deleteObject="false" @openDrawer="callOpenDrawer"></selection-booking-list>
    <q-table
        v-if="!userStore.isMobile"
        :rows="bookings"
        :columns="columns"
        row-key="id"
        :filter="filter"
        :rows-per-page-options="[10, 20, 30]"
    >
      <template v-slot:body-cell-type="props">
        <q-td :props="props">
          {{props.row.catalogueResource.type.title}}
        </q-td>
      </template>
      <template v-slot:body-cell-start="props">
        <q-td :props="props">
          {{formatDateAPI(props.row.dateStart)}}
        </q-td>
      </template>
      <template v-slot:body-cell-status="props">
        <q-td :props="props">
          <q-item-label><q-chip color="red-5" text-color="white" v-if="props.row.status === 're_requested'">Attente de validation</q-chip></q-item-label>
          <q-item-label><q-chip color="blue-5" text-color="white" v-if="props.row.status === 're_progress'">Livraison en cours</q-chip></q-item-label>
          <q-item-label><q-chip color="positive" text-color="white" v-if="props.row.status === 're_delivered'">Livraison terminée</q-chip></q-item-label>
        </q-td>
      </template>
      <template v-slot:body-cell-actions="props">
        <q-td :props="props">
          <q-btn round color="primary" icon="visibility" v-on:click="callOpenDrawer(props.row)">
            <q-tooltip>
              Voir le détail
            </q-tooltip>
          </q-btn>
        </q-td>
      </template>
      <template v-slot:top-right>
        <q-input dense debounce="300" v-model="filter" placeholder="Rechercher">
          <template v-slot:append>
            <q-icon name="search" />
          </template>
        </q-input>
      </template>
    </q-table>
  </div>
</template>

<script>
import axios from "axios";
import {ref} from "vue";
import BookingDetailDrawer from "../../components/drawer/ressourcerie/bookingEffectsDetailDrawer.vue";
import { booking } from '../../store/booking';
import { user } from '../../store/counter';
import SelectionBookingList from "../../components/selectionBookingList.vue";
const bookingStore = booking();
const userStore = user();

export default {
  name: "MyBookings",
  components: {SelectionBookingList, BookingDetailDrawer},
  data() {
    return {
      userStore,
      bookingStore,
      filter: ref(''),
      bookings: [],
      columns: [
        { name: 'type', align: 'left', label: 'Type', field: 'catalogueResource.type.title', sortable: true },
        { name: 'number', align: 'left', label: 'Nombre', field: 'number', sortable: true },
        { name: 'start', align: 'center', label: 'Date de la demande', field: 'dateStart', sortable: true },
        { name: 'status', align: 'center', label: 'Statut', field: 'status', sortable: true },
        { name: 'actions', align: 'center', label: 'Actions', field: 'actions', sortable: false },
      ]
    }
  },
  mounted() {
    this.loadBookings();
  },
  computed: {
    getBookingStoreUpdate() {
      return this.bookingStore.updated;
    }
  },
  methods:{
    loadBookings() {
      if(this.userStore.username !== null) {
        let self = this;
        axios({
          method: "get",
          url: "/api/bookings?user.username="+this.userStore.username,
          headers: {
            'accept': 'application/json'
          },
        })
          .then(function (response) {
            self.bookings = response.data;
          })
      } else {
        setTimeout(function () {
          this.loadBookings();
        }.bind(this), 50);
      }
    },
    callOpenDrawer(row) {
      this.bookingStore.defineSelection(row);
    },
    formatDateAPI(apiDate) {
      let newDate = new Date(apiDate);
      return newDate.toLocaleDateString() + ' ' + newDate.toLocaleTimeString();
    },
  },
  watch: {
    getBookingStoreUpdate : function () {
      if(this.bookingStore.updated === true) {
        this.loadBookings();
        this.bookingStore.definedUpdated(false);
      }
    }
  }
}
</script>

<style scoped>

</style>