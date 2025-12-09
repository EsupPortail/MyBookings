<template>
  <div>
    <h1 class="text-h6">{{ $t('myBookingHistory') }}</h1>
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
        :no-data-label="$t('noBookingFound')"
        role="presentation"
        :aria-label="$t('myBookingHistory')"
    >
      <template v-slot:body-cell-subType="props">
        <q-td :props="props">
          <p v-if="props.row.catalogueResource.subType">
            {{props.row.catalogueResource.subType.title}}
          </p>
        </q-td>
      </template>
      <template v-slot:body-cell-resource="props">
        <q-td :props="props">
          {{getResourceTitles(props.row.Resource)}}
        </q-td>
      </template>
      <template v-slot:body-cell-start="props">
        <q-td :props="props">
          {{formatDatefromAPItoString(props.row.dateStart)}}
        </q-td>
      </template>
      <template v-slot:body-cell-status="props">
        <q-td :props="props">
          <q-item-label><q-chip color="red" text-color="white" v-if="props.row.status === 'pending'">{{ $t('pendingConfirmation') }}</q-chip></q-item-label>
          <q-item-label><q-chip color="positive" text-color="white" v-if="props.row.status === 'accepted'">{{ $t('confirmed') }}</q-chip></q-item-label>
          <q-item-label><q-chip color="primary" text-color="white" v-if="props.row.status === 'progress'">{{ $t('inProgress') }}</q-chip></q-item-label>
          <q-item-label><q-chip color="grey" text-color="white" v-if="props.row.status === 'refused'">{{ $t('refused') }}</q-chip></q-item-label>
          <q-item-label><q-chip color="primary" text-color="white" v-if="props.row.status === 'returned'">{{ $t('closed') }}</q-chip></q-item-label>
        </q-td>
      </template>
      <template v-slot:header="props">
        <q-tr :props="props">
          <q-th
              v-for="col in props.cols"
              :key="col.name"
              :props="props"
              role="rowheader"
          >
            {{ col.label }}
          </q-th>
        </q-tr>
      </template>
      <template v-slot:body-cell-actions="props">
        <q-td :props="props">
          <q-btn round color="primary" icon="visibility" v-on:click="callOpenDrawer(props.row)" :aria-label="$t('viewDetail')">
            <q-tooltip>
              {{ $t('viewDetail') }}
            </q-tooltip>
          </q-btn>
        </q-td>
      </template>
      <template v-slot:top-right>
        <q-input dense debounce="300" v-model="filter" :placeholder="$t('search')">
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
import BookingDetailDrawer from "../../components/drawer/bookingDetailDrawer.vue";
import { booking } from '../../store/booking';
import { user } from '../../store/counter';
import SelectionBookingList from "../../components/selectionBookingList.vue";
import {loadBookingByUsername} from "../../api/Booking";
import {formatDatefromAPItoString} from "../../utils/dateUtils";
const bookingStore = booking();
const userStore = user();
const bookings = ref([]);
export default {
  name: "MyBookings",
  components: {SelectionBookingList, BookingDetailDrawer},
  data() {
    return {
      userStore,
      bookingStore,
      bookings,
      filter: ref(''),
      columns: [
        { name: 'subType', align: 'left', label: this.$t('subType'), field: row => row.catalogueResource.subType.title, sortable: true },
        { name: 'resource', align: 'left', label: this.$t('Resource'), field: row => row.Resource, sortable: true },
        { name: 'number', align: 'left', label: this.$t('Number'), field: 'number', sortable: true },
        { name: 'start', align: 'center', label: this.$t('bookingStart'), field: 'dateStart', sortable: true },
        { name: 'status', align: 'center', label: this.$t('status'), field:'status', sortable: true, filter_type:'select' },
        { name: 'actions', align: 'center', label: this.$t('actions'), field: 'actions'},
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
    formatDatefromAPItoString,
    getResourceTitles(Resources) {
      let arrayResources = [];
      Resources.forEach(function (resource) {
        arrayResources.push(resource.title);
      })
      return arrayResources.map(nom => nom.split(' ').map(part => part.charAt(0).toUpperCase() + part.slice(1).toLowerCase()).join(' ')).join(', ');
    },
    loadBookings() {
      if(this.userStore.username !== null) {
        loadBookingByUsername(this.userStore.username).then(function (response) {
          bookings.value = response.data;
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