<template>
  <div>
    <booking-effects-detail-drawer :admin="true"></booking-effects-detail-drawer>
    <q-card bordered class="my-card">
      <q-card-section>
        <div class="row">
          <div class="col-3">
            <q-select square outlined clearable label="Rechercher par status" v-model="statusFilter" option-label="label" option-value="value" :options="optionsStatus"></q-select>
          </div>
        </div>
        <custom-table-bookings :bookings="bookings" :actualPagination="pagination" @update="onRequest"></custom-table-bookings>
      </q-card-section>
    </q-card>
  </div>
</template>

<script>
import { ref } from "vue";
import { booking } from '../../../store/booking';
import { user } from '../../../store/counter';
import SelectionBookingList from "../../../components/selectionBookingList.vue";
import CustomTableBookings from "../../../components/table/ressourcerie/customTableBookings.vue";
import BookingEffectsDetailDrawer from "../../../components/drawer/ressourcerie/bookingEffectsDetailDrawer.vue";
import { getBookingEffects } from "../../../api/ressourcerie/Effects";
import { fromJsonLd } from "../../../utils/paginationUtils";

const searchBy = ref('');
const filter = ref('');
const rowsPerPage = ref(20);
const descending = ref(true);
const sortBy = ref('dateStart');
const bookings = ref([]);
const statusFilter = ref({label: 'En cours de traitement', value: 're_progress'});
const optionsStatus= [
  {label: 'En cours de traitement', value: 're_progress'},
  {label: 'Clôturé', value: 're_delivered'},
  {label: 'Annulé', value: 're_removed'},
];
const pagination = ref({
  sortBy: 'dateStart',
  descending: true,
  page: 1,
  rowsPerPage: 20,
});

const setPaginationValue = function () {
  pagination.value.rowsPerPage = rowsPerPage.value;
  pagination.value.searchBy = searchBy.value;
  pagination.value.filter = filter.value;
  pagination.value.descending = descending.value;
  pagination.value.sortBy = sortBy.value;
};

export default {
  name: "SITModeration",
  components: { SelectionBookingList, CustomTableBookings, BookingEffectsDetailDrawer },
  data() {
    return {
      userStore: user(),
      bookingStore: booking(),
      filter: ref(''),
      bookings,
      statusFilter,
      optionsStatus,
      pagination,
    }
  },
  mounted() {
    this.getBookings();
  },
  computed: {
    getBookingStoreUpdate() {
      return this.bookingStore.updated;
    }
  },
  methods:{
    getBookings() {
      let status = statusFilter.value
      if (status !== null) {
        status = status.value;
      } else {
        status = ['re_progress', 're_delivered', 're_removed'];
      }
      if (pagination.value.searchBy !== '') {
        searchBy.value = pagination.value.searchBy;
        filter.value = pagination.value.filter;
      }
      rowsPerPage.value = pagination.value.rowsPerPage;
      descending.value = pagination.value.descending;
      sortBy.value = pagination.value.sortBy;

      getBookingEffects(this.id, status, pagination.value).then(function (response) {
        bookings.value = response.data.member;
        pagination.value = fromJsonLd(response.data);
        setPaginationValue();
      })
    },
    onRequest(props) {
      pagination.value = props.pagination;
      if(pagination.value.rowsPerPage === 0) {
        pagination.value.rowsPerPage = pagination.value.rowsNumber;
      }
      this.getBookings();
    }
  },
  watch: {
    getBookingStoreUpdate : function () {
      if(this.bookingStore.updated === true) {
        this.getBookings();
        this.bookingStore.definedUpdated(false);
      }
    },
    statusFilter: function () {
      this.getBookings();
    }
  }
}
</script>