<template>
  <div>
    <booking-detail-drawer :admin="true" :booking-list="bookingStore"></booking-detail-drawer>
    <q-card bordered class="my-card">
      <q-card-section>
        <div class="row">
          <div class="col">
            <h1 class="text-h5">{{ $t('booking.management') }} : <b v-on:click="$router.push({ name: 'serviceDetailAdmin'})" style="cursor: pointer">{{service?.title}}</b></h1>
          </div>
        </div>
      </q-card-section>
      <q-card-section>
        <div class="row">
          <div class="col-3">
            <q-select square outlined clearable :label="$t('booking.searchByStatus')" v-model="statusFilter" option-label="label" option-value="value" :options="optionsStatus"></q-select>
          </div>
          <div class="col-5">
            <q-select square outlined clearable :label="$t('booking.searchByCatalog')" v-model="catalogFilter" option-label="title" option-value="id" :options="optionsCatalog"></q-select>
          </div>
          <div class="col-4">
            <q-select square outlined clearable :label="$t('booking.searchByResource')" v-model="resourceFilter" option-label="title" option-value="id" :options="optionResource"></q-select>
          </div>
        </div>
        <custom-table-bookings :bookings="bookings" :actualPagination="pagination" @update="onRequest"></custom-table-bookings>
      </q-card-section>
    </q-card>
  </div>

</template>

<route lang="json">
{
  "name": "manageBookingsFromService",
  "meta": {
  "requiresAuth": false,
  "dynamic": true
  }
}
</route>

<script>
import {ref} from "vue";
import {useQuasar} from "quasar";
import {useRoute} from "vue-router/auto";
import CustomTableBookings from "../../../../components/table/customTableBookings.vue";
import {storeToRefs} from "pinia";
import { booking } from '../../../../store/booking';
import BookingHistoryDialog from "../../../../components/dialog/Administration/BookingDrawer/bookingHistoryDialog.vue";
import BookingDetailDrawer from "../../../../components/drawer/bookingDetailDrawer.vue";
import {
  getBookingsFromService,
  getCatalogFromParametersByService, getCatalogueByServiceId,
} from "../../../../api/CatalogRessource";
import {getService} from "../../../../api/Service";
import {
  loadBookingsByCatalogIdAndStatus,
  loadBookingsByResourceByStatus
} from "../../../../api/Booking";
import {fromJsonLd} from "../../../../utils/paginationUtils";
const bookingStore = booking();
const { getSelection, getUpdated } = storeToRefs(bookingStore);

const searchBy = ref('');
const filter = ref('');
const rowsPerPage = ref(20);
const filterStart = ref('');
const filterEnd = ref('');
const filterByDate = ref(false);
const descending = ref(true);
const sortBy = ref('dateStart');
const bookings = ref([]);
const pendingBookings = ref([]);
const progressBookings = ref([]);
const closeBookings = ref([]);
const service = ref(null);
const catalogFilter = ref(null);
const resourceFilter = ref(null);
const statusFilter = ref(null);
const optionsStatus = ref([]);
const optionsCatalog = ref([]);
const optionResource = ref([]);
const pagination = ref({
  sortBy: 'dateStart',
  descending: true,
  page: 1,
  rowsPerPage: 20,
});

const getResources = function () {
  optionResource.value = [];
  optionsCatalog.value.forEach(function (catalog) {
    catalog.resource.forEach(function (resource) {
      optionResource.value.push(resource);
    })
  })
};

const setPaginationValue = function () {
  pagination.value.rowsPerPage = rowsPerPage.value;
  pagination.value.searchBy = searchBy.value;
  pagination.value.filter = filter.value;
  pagination.value.filterStart = filterStart.value;
  pagination.value.filterEnd = filterEnd.value;
  pagination.value.filterByDate = filterByDate.value;
  pagination.value.descending = descending.value;
  pagination.value.sortBy = sortBy.value;
};

export default {
  components: {BookingDetailDrawer, BookingHistoryDialog, CustomTableBookings},
  setup() {
    const route = useRoute();
    const id = route.params.id;
    return {
      id
    }
  },
  data() {
    return {
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
      catalogFilter,
      optionsCatalog,
      resourceFilter,
      optionResource,
      pagination,
      searchBy,
      filter,
      rowsPerPage,
      filterStart,
      filterEnd,
      filterByDate,
      descending,
      sortBy
    }
  },
  mounted() {
    this.initializeStatusOptions();
    resourceFilter.value = null;
    catalogFilter.value = null;
    this.getService();
    this.getCatalogs();
    this.getBookings();
    if(this.getUrlParameter('status') !== null) {
      statusFilter.value = optionsStatus.value.find(option => option.value === this.getUrlParameter('status'));
    }
  },
  computed: {
    isBookingUpdated() {
      return this.getUpdated;
    },
  },
  methods: {
    initializeStatusOptions() {
      optionsStatus.value = [
        {label: this.$t('booking.statusPending'), value: 'pending'},
        {label: this.$t('booking.statusAccepted'), value: 'accepted'},
        {label: this.$t('booking.statusProgress'), value: 'progress'},
        {label: this.$t('booking.statusReturned'), value: 'returned'},
        {label: this.$t('booking.statusRefused'), value: 'refused'}
      ];
      statusFilter.value = optionsStatus.value.find(option => option.value === 'progress');
    },
    getService() {
      getService(this.id).then(function (response) {
        service.value = response;
      })
    },
    getCatalogs() {
      let self = this;
      getCatalogFromParametersByService(this.id).then(function (response) {
        optionsCatalog.value = response.data;
        if(self.getUrlParameter('catalog') !== null) {
          const catalogId = Number(self.getUrlParameter('catalog'));
          catalogFilter.value = optionsCatalog.value.find(option => option.id === catalogId);
        }
        getResources();
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
      filterStart.value = pagination.value.filterStart;
      filterEnd.value = pagination.value.filterEnd;
      filterByDate.value = pagination.value.filterByDate;
      descending.value = pagination.value.descending;
      sortBy.value = pagination.value.sortBy;
      const handleResponse = (response) => {
        // On passe la pagination courante pour garder rowsPerPage si l'API ne le fournit pas
        const newPagination = fromJsonLd(response.data, pagination.value);
        const totalPages = Math.max(1, Math.ceil(newPagination.rowsNumber / newPagination.rowsPerPage));
        // Si la page demandée est trop grande, on la corrige sans rappeler getBookings en boucle
        if (newPagination.page > totalPages) {
          newPagination.page = totalPages;
          pagination.value = newPagination;
          // On relance la requête uniquement si la page a changé
          getCatalogueByServiceId(this.id, newPagination).then(handleResponse);
          return;
        }
        pagination.value = newPagination;
        bookings.value = response.data.member;
        setPaginationValue();
      };
      if(status === null && catalogFilter.value === null && resourceFilter.value === null) {
        getCatalogueByServiceId(this.id, pagination.value).then(handleResponse)
      } else if(catalogFilter.value === null && resourceFilter.value === null && status !== null) {
        getBookingsFromService(this.id, status, pagination.value).then(handleResponse)
      } else if(resourceFilter.value === null) {
        loadBookingsByCatalogIdAndStatus(catalogFilter.value.id, pagination.value, status).then(handleResponse)
      } else {
        loadBookingsByResourceByStatus(resourceFilter.value.id, status, pagination.value).then(handleResponse)
      }

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
    },
    catalogFilter: function () {
      this.getBookings();
    },
    resourceFilter: function () {
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