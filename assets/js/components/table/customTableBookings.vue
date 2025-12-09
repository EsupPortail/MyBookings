<template>
  <div class="q-pa-md">
    <q-table
        :rows="rows"
        :columns="columns"
        row-key="id"
        :loading="loading"
        :filter="filter"
        :rows-per-page-options="[20, 50, 100]"
        flat
        binary-state-sort
        aria-label="Bookings Table"
        v-model:pagination="pagination"
        @request="onRequest"
    >
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
      <template v-slot:top-left>
        <transition
            appear
            enter-active-class="animated flipInX"
        >
          <div v-if="!pagination.filterByDate">
            <q-btn icon="edit_calendar" color="primary" :label="$t('booking.filterByDate')" no-caps @click="pagination.filterByDate = true"/>
          </div>
        </transition>

        <transition
            appear
            enter-active-class="animated flipInX slower"
        >
          <div class="row q-gutter-md" v-if="pagination.filterByDate" >
            <div class="col">
              <label>{{ $t('booking.startDate') }}</label>
              <date-time-picker :dateValue="pagination.filterStart" @update="updateFilterStart"/>
            </div>
            <div class="col">
              <label>{{ $t('booking.endDate') }}</label>
              <date-time-picker :dateValue="pagination.filterEnd" @update="updateFilterEnd"/>
            </div>
            <div class="col-1">
              <q-btn icon="close" dense round class="q-mt-lg" color="primary" @click="pagination.filterByDate = false; sendPagination(pagination)"/>
            </div>
          </div>
        </transition>
      </template>

      <template v-slot:top-right>

        <div class="row q-gutter-md">
          <q-select v-model="pagination.searchBy" :options="searchOptions" :label="$t('booking.searchBy')" dense style="width: 200px" clearable @clear="sendPagination(pagination)" />
          <q-input dense v-model="pagination.filter" :placeholder="$t('booking.filter')" @keydown.enter.prevent="sendPagination(pagination)"/>
          <q-btn icon="search" round color="primary" @click="sendPagination(pagination)" />
        </div>
      </template>

      <template v-slot:body="props">
        <q-tr :props="props">
          <q-td key="users" :props="props">
            <div v-if="props.row.user.length <= 2">
              <q-chip square color="primary" text-color="white" v-for="user in props.row.user">{{ user.displayUserName }}</q-chip>
            </div>
            <div v-if="props.row.user.length > 2">
              <q-chip square color="primary" text-color="white">{{ props.row.user[0].displayUserName }}</q-chip>
              <q-chip square color="primary" text-color="white">{{ props.row.user[1].displayUserName }}</q-chip>
              <q-chip square color="primary" text-color="white">+ {{ props.row.user.length-2 }}</q-chip>
            </div>
          </q-td>
          <q-td key="number" :props="props">
            {{ props.row.number }}
          </q-td>
          <q-td key="catalogueResource" :props="props">
            {{ props.row.catalogueResource.title }}
          </q-td>
          <q-td key="resources" :props="props">
            <q-chip color="white" text-color="primary" v-for="resource in props.row.Resource">{{ resource.title }}</q-chip>
          </q-td>
          <q-td key="dateStart" :props="props">
            {{ formatDatefromAPItoString(props.row.dateStart) }}
          </q-td>
          <q-td key="dateEnd" :props="props">
            {{ formatDatefromAPItoString(props.row.dateEnd) }}
          </q-td>
          <q-td key="status" :props="props">
            <q-btn v-if="props.row.status === 'pending'" color="red" text-color="white" v-on:click="callOpenDrawer(props.row)" no-caps>{{ $t('booking.confirmationRequired') }}</q-btn>
            <q-btn v-if="props.row.status === 'accepted'" color="positive" text-color="white" v-on:click="callOpenDrawer(props.row)" no-caps>{{ $t('booking.confirmed') }}</q-btn>
            <q-btn v-if="props.row.status === 'progress'" color="primary" text-color="white" v-on:click="callOpenDrawer(props.row)" no-caps>{{ $t('booking.statusProgress') }}</q-btn>
            <q-btn v-if="props.row.status === 'returned'" color="primary" text-color="white" v-on:click="callOpenDrawer(props.row)" no-caps>{{ $t('booking.closed') }}</q-btn>
            <q-btn v-if="props.row.status === 'refused'" color="red" text-color="white" v-on:click="callOpenDrawer(props.row)" no-caps>{{ $t('booking.statusRefused') }}</q-btn>
          </q-td>
        </q-tr>
      </template>
    </q-table>
  </div>
</template>
<script>

import dateTimePicker from "../dateTimePicker.vue";
import {storeToRefs} from "pinia";
import { booking } from '../../store/booking';
import {checkDateTimeFormat, formatDatefromAPItoString} from "../../utils/dateUtils";
import {ref} from "vue";
const bookingStore = booking();
const { getSelection } = storeToRefs(booking);

const getList = function (obj, param) {
  let list = '';
  obj.forEach(function (element) {
    list+=element[param] + ' '
  })
  return list
}

const columns = ref([]);

const searchSelection = ref('');
const searchOptions = ref([]);

export default {
  name: "customTableBookings",
  components: {dateTimePicker},
  props: {
    bookings: Array,
    actualPagination: Object
  },
  data() {
    return {
      bookingStore,
      getSelection,
      columns,
      rows : [],
      loading: true,
      filter: ref(''),
      rowCount: 0,
      pagination : {
        page: 1,
        rowsPerPage: 20,
      },
      searchSelection,
      searchOptions,
    }
  },
  mounted() {
    this.initializeColumnsAndOptions();
    this.rows = this.bookings;
    this.rowCount = this.bookings.length;
  },
  methods: {
    initializeColumnsAndOptions() {
      columns.value = [
        {
          name: 'users',
          required: true,
          label: this.$t('booking.users'),
          align: 'center',
          field: row => getList(row.user, 'displayUserName'),
        },
        { name: 'catalogueResource', align: 'center', label: this.$t('booking.catalog'), field: row => row.catalogueResource.title},
        { name: 'resources', align: 'center', label: this.$t('booking.resources'), field: row => getList(row.Resource, 'title')},
        { name: 'dateStart', align: 'center', label: this.$t('booking.startDate'), field: row => formatDatefromAPItoString(row.dateStart), sortable: true},
        { name: 'dateEnd', align: 'center', label: this.$t('booking.endDate'), field: row => formatDatefromAPItoString(row.dateEnd)},
        { name: 'status', align: 'center', label: this.$t('status'), field: 'status' },
      ];

      searchOptions.value = [
        {label: this.$t('booking.searchByUser'), value: 'user.displayUserName'},
        {label: this.$t('booking.searchByTitle'), value: 'title'},
      ];
    },
    checkDateTimeFormat,
    formatDatefromAPItoString,
    callOpenDrawer(row) {
      this.bookingStore.defineSelection(row);
    },
    onRequest(pagination) {
      this.loading = true;
      this.$emit('update', pagination);
    },
    sendPagination(pagination) {
      this.loading = true;
      let props = {
        pagination : pagination
      };
      this.$emit('update', props);
    },
    updateFilterStart(val) {
      this.pagination.filterStart = val;
    },
    updateFilterEnd(val) {
      this.pagination.filterEnd = val;
    }
  },
  watch: {
    bookings() {
      this.pagination = this.actualPagination;
      this.rows = this.bookings;
      this.rowCount = this.bookings.length;
      this.loading = false;
    },
  }
}
</script>

<style scoped>

</style>