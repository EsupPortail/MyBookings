<template>
  <q-dialog v-model="dialog" persistent>
    <q-card style="width: 700px; max-width: 80vw;">
      <q-card-section>
        <div class="text-h5">{{ $t('bookingHistory.title') }}</div>
      </q-card-section>
      <q-separator/>
      <q-card-section>
          <q-table
              :rows="history"
              :columns="columns"
              row-key="status"
              hide-bottom
              flat
          >
            <template v-slot:body-cell-status="props">
              <q-td :props="props" v-if="props.row.status === 'init_booking'">
                {{ $t('bookingHistory.initBooking') }}
              </q-td>
              <q-td :props="props" v-if="props.row.status === 'waiting_moderation'">
                {{ $t('bookingHistory.waitingModeration') }}
              </q-td>
              <q-td :props="props" v-if="props.row.status === 'accepted_auto'">
                {{ $t('bookingHistory.acceptedAuto') }}
              </q-td>
              <q-td :props="props" v-if="props.row.status === 'accepted_moderation'">
                {{ $t('bookingHistory.acceptedModeration') }}
              </q-td>
              <q-td :props="props" v-if="props.row.status === 'start_booking'">
                {{ $t('bookingHistory.startBooking') }}
              </q-td>
              <q-td :props="props" v-if="props.row.status === 'end_booking'">
                {{ $t('bookingHistory.endBooking') }}
              </q-td>
              <q-td :props="props" v-if="props.row.status === 'refused'">
                {{ $t('bookingHistory.refused') }}
              </q-td>
              <q-td :props="props" v-if="props.row.status === 'checkActuator'">
                {{ $t('bookingHistory.checkActuator') }}
              </q-td>
              <q-td :props="props" v-if="props.row.status === 'accepted_auto_silent'">
                {{ $t('bookingHistory.reloaded') }}
              </q-td>
            </template>
            <template v-slot:body-cell-date="props">
              <q-td :props="props">
                {{ formatDateAPI(props.row.date.date) }}
              </q-td>
            </template>
            <template v-slot:body-cell-comment="props">
              <q-td :props="props" v-if="props.row.comment !== null">
                {{props.row.comment}}
              </q-td>
              <q-td :props="props" v-if="props.row.comment === null">
                <i>{{ $t('bookingHistory.noComment') }}</i>
              </q-td>
            </template>
          </q-table>
      </q-card-section>
      <q-card-actions align="right" class="text-primary">
        <q-btn flat :label="$t('bookingHistory.close')" @click="sendClose" />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script>
import { booking } from '../../../../store/booking';
import {getBookingHistory} from "../../../../api/Booking";
import {ref} from "vue";
import {formatDateAPI} from "../../../../utils/dateUtils";
const bookingStore = booking();

const history = ref([]);
export default {
  name: "bookingHistoryDialog",
  props: {
    openHistory:Boolean,
  },
  data() {
    return {
      bookingStore,
      dialog: false,
      history,
      columns: []
    }
  },
  mounted() {
    this.initializeColumns();
    this.dialog  = this.openHistory;
  },
  methods: {
    initializeColumns() {
      this.columns = [
          {name: 'status', label: this.$t('bookingHistory.status'), field: 'status', align: 'left'},
          {name: 'date', label: this.$t('bookingHistory.date'), field: row=>row.date.date, align: 'left', 'sortable': true},
          {name: 'comment', label: this.$t('bookingHistory.comment'), field: 'comment', align: 'left'},
      ];
    },
    formatDateAPI,
    sendClose() {
      this.$emit('close', false)
    },
    getHistoryFromBooking() {
      getBookingHistory(this.bookingStore.getSelection.id).then(function (response) {
        history.value = response.data;
      })
    },
  },
  watch: {
    openHistory: function () {
      this.dialog = this.openHistory;
      if(this.dialog === true)
        this.getHistoryFromBooking();
    }
  }
}
</script>

<style scoped>

</style>