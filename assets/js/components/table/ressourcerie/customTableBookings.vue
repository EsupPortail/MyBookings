<template>
  <div>
    <q-table
        :rows="rows"
        :columns="columns"
        row-key="id"
        :loading="loading"
        :filter="filter"
        :rows-per-page-options="[20, 50, 100]"
        flat
        binary-state-sort
        v-model:pagination="pagination"
        @request="onRequest"
        :visible-columns="['users', 'catalogueResource', 'resources', 'dateStart', 'source', $route.name === 'ressourcerie-sit' ? 'destination' : null, 'status']"
    >

      <template v-slot:top-right>

        <div class="row q-gutter-md">
          <q-select v-model="pagination.searchBy" :options="searchOptions" label="Rechercher par" dense style="width: 200px" clearable @clear="sendPagination(pagination)" />
          <q-input dense v-model="pagination.filter" placeholder="Filtrer" @keydown.enter.prevent="sendPagination(pagination)"/>
          <q-btn icon="search" round color="primary" @click="sendPagination(pagination)" />
        </div>
      </template>

      <template v-slot:body="props">
        <q-tr :props="props" @click="callOpenDrawer(props.row)" :class="{selected: this.bookingStore.selection?.id === props.row.id}" style="cursor: pointer;">
          <q-td key="users" :props="props">
            <div>
              <q-chip color="blue-2" text-color="primary">{{ props.row.user[0].displayUserName }}</q-chip>
              <q-chip v-if="props.row.user.length > 1" color="blue-2" text-color="primary">+ {{ props.row.user.length-1 }}</q-chip>
            </div>
          </q-td>
          <q-td key="number" :props="props">
            {{ props.row.number }}
          </q-td>
          <q-td key="catalogueResource" :props="props">
            {{ props.row.catalogueResource.title }}
          </q-td>
          <q-td key="resources" :props="props">
            <div>
              <q-chip color="grey-3" text-color="primary">{{ props.row.Resource[0].title }}</q-chip>
              <q-chip v-if="props.row.Resource.length > 1" color="grey-3" text-color="primary">+ {{ props.row.Resource.length-1 }}</q-chip>
            </div>
          </q-td>
          <q-td key="dateStart" :props="props">
            {{ formatDatefromAPItoString(props.row.dateStart) }}
          </q-td>
          <q-td key="source" :props="props">
            {{ props.row.catalogueResource.service.title }}
          </q-td>
          <q-td key="destination" :props="props" v-show="$route.name === 'ressourcerie-sit'">
            {{ getServiceById(props.row.title)?.title }}
          </q-td>
          <q-td key="status" :props="props">
            <q-chip v-if="props.row.status === 're_requested'" color="red-5" text-color="white">Confirmation nécessaire</q-chip>
            <!-- <q-chip v-if="props.row.status === 'accepted'" color="positive" text-color="white">Confirmé</q-chip> -->
            <q-chip v-if="props.row.status === 're_progress'" color="blue-5" text-color="white">En cours</q-chip>
            <q-chip v-if="props.row.status === 're_delivered'" color="grey-5" text-color="white">Clôturé</q-chip>
          </q-td>
        </q-tr>
      </template>
    </q-table>
  </div>
</template>
<script>

import { storeToRefs } from "pinia"
import { booking } from '../../../store/booking'
import { checkDateTimeFormat, formatDatefromAPItoString } from "../../../utils/dateUtils"
import { ref } from "vue"
import { getServices } from "../../../api/Service"

const bookingStore = booking()
// const { getSelection } = storeToRefs(booking)

const getList = function (obj, param) {
  let list = '';
  obj.forEach(function (element) {
    list+=element[param] + ' '
  })
  return list
}

const columns = [
  {
    name: 'users',
    required: true,
    label: 'Utilisateur(s)',
    align: 'center',
    field: row => getList(rowapp/assets/js/components/table/customTableBookings.vue.user, 'displayUserName'),
  },
  { name: 'catalogueResource', align: 'left', label: 'Bien'},
  { name: 'resources', align: 'left', label: 'Exemplaire'},
  { name: 'dateStart', align: 'center', label: 'Date de la demande', sortable: true},
  { name: 'source', align: 'center', label: 'Source', sortable: true },
  { name: 'destination', align: 'center', label: 'Destination', sortable: true},
  { name: 'status', align: 'center', label: 'Statut' },
]

const searchSelection = ref('')
const searchOptions = [
  {label: 'Utilisateur', value: 'user.displayUserName'}
]

const services = ref([])

export default {
  name: "customTableBookings",
  props: {
    bookings: Array,
    actualPagination: Object
  },
  data() {
    return {
      bookingStore,
      // getSelection,
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
    this.rows = this.bookings
    this.rowCount = this.bookings.length
  },
  methods: {
    checkDateTimeFormat,
    formatDatefromAPItoString,
    callOpenDrawer(row) {
      this.bookingStore.defineSelection(row)
    },
    onRequest(pagination) {
      this.loading = true
      this.$emit('update', pagination)
    },
    sendPagination(pagination) {
      this.loading = true
      let props = {
        pagination: pagination
      }
      this.$emit('update', props)
    },
    updateFilterStart(val) {
      this.pagination.filterStart = val
    },
    updateFilterEnd(val) {
      this.pagination.filterEnd = val
    },
    getServiceById(id) {
      return services.value.find(service => service.id == id)
    },
  },
  watch: {
    bookings() {
      this.pagination = this.actualPagination
      this.rows = this.bookings
      this.rowCount = this.bookings.length
      this.loading = false

      // Create an array of unique titles from bookings
      const uniqueTitles = [...new Set(this.bookings.map(booking => booking.title))]
      // Call the API services with the unique titles
      getServices(uniqueTitles).then(response => {
        services.value = response.data
      }).catch(error => {
        console.error('Error fetching services:', error)
      })
    },
  }
}
</script>

<style scoped>

</style>