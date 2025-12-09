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

<route lang="json">
{
  "name": "manageBookingsFromServiceRessourcerie",
  "meta": {
  "requiresAuth": false,
  "dynamic": true
  }
}
</route>

<script>
import { ref, computed } from "vue";
import { useRoute } from "vue-router";
import {useQuasar} from "quasar";
import CustomTableBookings from "../../../../../components/table/ressourcerie/customTableBookings.vue";
import {storeToRefs} from "pinia";
import { booking } from '../../../../../store/booking';
import BookingHistoryDialog from "../../../../../components/dialog/Administration/BookingDrawer/bookingHistoryDialog.vue";
import BookingEffectsDetailDrawer from "../../../../../components/drawer/ressourcerie/bookingEffectsDetailDrawer.vue";
import {getService} from "../../../../../api/Service";
import {fromJsonLd} from "../../../../../utils/paginationUtils";
import { getBookingEffects } from "../../../../../api/ressourcerie/Effects";


const bookingStore = booking();
const { getSelection, getUpdated } = storeToRefs(bookingStore);

const searchBy = ref('');
const filter = ref('');
const rowsPerPage = ref(20);
const descending = ref(true);
const sortBy = ref('dateStart');
const bookings = ref([]);
const pendingBookings = ref([]);
const progressBookings = ref([]);
const closeBookings = ref([]);
const service = ref(null);
const statusFilter = ref({label: 'En attente de Modération', value: 're_requested'});
const optionsStatus= [
  {label: 'En attente de Modération', value: 're_requested'},
  {label: 'En cours', value: 're_progress'},
  {label: 'Terminé', value: 're_delivered'},
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
  components: {BookingEffectsDetailDrawer, BookingHistoryDialog, CustomTableBookings},
  setup() {
    const route = useRoute();
    const id = computed(() => route.params.id);

    return {
      id,
      bookingStore,
      getSelection,
      getUpdated,
      bookings,
      $q: useQuasar(),
      selectedFile: false,
      newImage: '',
      isAdmin: false,
      tab: ref('pending'),
      pendingBookings,
      progressBookings,
      closeBookings,
      service,
      statusFilter,
      optionsStatus,
      pagination,
      searchBy,
      filter,
      rowsPerPage,
      descending,
      sortBy
    }
  },
  mounted() {
    this.getService();
    this.getBookings();
    if(this.getUrlParameter('status') !== null) {
      statusFilter.value = optionsStatus.find(option => option.value === this.getUrlParameter('status'));
    }
  },
  computed: {
    isBookingUpdated() {
      return this.getUpdated;
    },
  },
  methods: {
    getService() {
      getService(this.id).then(function (response) {
        service.value = response;
      })
    },
    getUrlParameter(param) {
      const queryString = window.location.search;
      const urlParams = new URLSearchParams(queryString);
      return urlParams.get(param);
    },
    getBookings() {
      let status = statusFilter.value
      if(status !== null) {
        status = status.value;
      }
      if(pagination.value.searchBy !== '') {
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
    isBookingUpdated: function () {
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

<style scoped>
h6 {
  margin-bottom: 10px;
}


</style>