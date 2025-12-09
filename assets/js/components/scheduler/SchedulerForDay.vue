<template>
  <!-- Booking confirmation dialog -->
  <!-- Week scheduler -->
  <watch-week-scheduler :open-dialog="schedulerWeekDialog" @close="schedulerWeekDialog=false" :id-resource="props.idResource" :id-site="basketUser.selection.service.id" :resources="resources"></watch-week-scheduler>
  <!-- Date selector -->
  <q-card-section class="dateButtonSection">
    <q-btn
        no-caps
        class="button"
        style="margin: 2px;"
        :aria-label="$t('previousDay')"
        @click="onPrev"
    >
      &lt;
    </q-btn>
    <q-btn
        no-caps
        class="button"
        style="margin: 2px;"
        :aria-label="$t('selectDate') + ' ' + localizedDateLabel"
    >
      <q-popup-proxy transition-show="scale" transition-hide="scale">
        <q-date v-model="basketUser.start" mask="DD/MM/YYYY" @update:model-value="onUpdate" no-unset/>
      </q-popup-proxy>
     {{ localizedDateLabel }}
    </q-btn>
    <q-btn
        no-caps
        class="button"
        style="margin: 2px;"
        @click="onNext"
        :aria-label="$t('nextDay')"
    >
      >
    </q-btn>
  </q-card-section>
  <q-card-section v-if="props.idResource !== null" class="no-padding"  style="text-align: center">
    <q-btn class="button" icon="calendar_month" color="primary" @click="schedulerWeekDialog=true">{{ $t('viewWeeklySchedule') }}</q-btn>
  </q-card-section>
  <q-card-section style="display: flex; margin: auto;">
    <schedule-list-event :events="bookingStore.events" :id-resource="props.idResource"/>
  </q-card-section>
</template>

<script setup>
import { basket } from '../../store/basket';
import { user } from '../../store/counter';
import {
  getDayMonthYear,
  stringToDate,
} from "../../utils/dateUtils.js";
import WatchWeekScheduler from "../dialog/watchWeekScheduler.vue";
import {computed, onMounted, ref, watch} from "vue";
import {booking} from "../../store/booking";
import {date} from "quasar";
import {loadBookingsFromManyCatalogs} from "../../api/Booking";
import ScheduleListEvent from "./ScheduleListEvent.vue";
import { useI18n } from 'vue-i18n';
const { locale } = useI18n();
const basketUser = basket();
const storeUser = user();
const bookingStore = booking();
const props = defineProps({
  resources: Array,
  events: Array,
  idResource: Number
})

const selectedDate = ref('');
const actualDay= ref();
const schedulerWeekDialog= ref(false);


const getCart = computed(() => basketUser.cart);

const localizedDateLabel = computed(() => {
  try {
    const d = date.extractDate(basketUser.start || basketUser.getStartDate, 'DD/MM/YYYY');
    if (!d || isNaN(d.getTime())) return '';
    return new Intl.DateTimeFormat(locale.value || 'fr', {
      weekday: 'long',
      day: '2-digit',
      month: 'long',
      year: 'numeric'
    }).format(d);
  } catch (e) {
    return `${basketUser.getStartFullDay} ${basketUser.getStartDay} ${basketUser.getStartMonth} ${basketUser.getStartYear}`;
  }
});

function onPrev () {
  let date = stringToDate(basketUser.getStartDate);
  date.setDate(date.getDate()-1)
  basketUser.start = getDayMonthYear(date);
  basketUser.end = getDayMonthYear(date);
  selectedDate.value = basketUser.getStartDate;
  loadBookings();

}
function onNext () {
  let date = stringToDate(basketUser.getStartDate);
  date.setDate(date.getDate()+1)
  basketUser.start = getDayMonthYear(date);
  basketUser.end = getDayMonthYear(date);
  selectedDate.value = basketUser.getStartDate;
  loadBookings();
}

function onUpdate() {
  selectedDate.value = basketUser.getStartDate;
  basketUser.end = basketUser.start;
  loadBookings();

}

const loadBookings = async () => {
  storeUser.isLoading = true;
  const dateStart = date.extractDate(basketUser.start, 'DD/MM/YYYY');
  const dateEnd = date.extractDate(basketUser.end, 'DD/MM/YYYY');
  dateEnd.setDate(dateEnd.getDate() + 1); // Add one day to dateEnd
  let formatedStart = date.formatDate(dateStart, 'YYYY-MM-DDTHH:mm:ss');
  let formatedEnd = date.formatDate(dateEnd, 'YYYY-MM-DDTHH:mm:ss');
  let events = await loadBookingsFromManyCatalogs(props.idResource !== null ? null : [basketUser.selection], formatedStart, formatedEnd,'countBookings', props.idResource);
  bookingStore.events = Object.values(events.data);
  storeUser.isLoading = false;
}

function checkUserRoles() {
  storeUser.getRoles();
}

onMounted(async () => {
  actualDay.value = new Date();
  selectedDate.value = basketUser.getStartDate;
  await loadBookings();
  checkUserRoles();
})

watch(getCart, async () => {
  if (basketUser.cart.length === 0) {
    await loadBookings();
  }
});

</script>

<style>

.dateButtonSection {
  text-align: center;
  padding: 8px;
}

.title {
  position: relative;
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100%;
}

.scrollScheduler {
  height: 500px;
  width: 100%
}

.listSlot {
  min-height: 500px;
}

@media (max-width: 700px) {
  .q-item {
    padding: 4px 8px;
  }

  .q-item__section {
    font-size: 11px;
  }

  .scrollScheduler {
    height: 400px;
  }

  .listSlot {
    min-height: 400px;
  }
}


</style>