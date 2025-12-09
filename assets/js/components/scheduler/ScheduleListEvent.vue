<script setup>
import {basket} from "../../store/basket";
import {onMounted, ref, watch} from "vue";
import {date, Notify} from "quasar";
import {user} from "../../store/counter";
import {booking} from "../../store/booking";
import {getBookingRest, getRestOfTime, mergeBookings} from "../../utils/bookingUtils";
import {dateRangerOverlaps, dateRangerOverlapsEqual, stringToDateTime} from "../../utils/dateUtils";
import {getBookingRestrictionForUser} from "../../api/Booking";
import BookInformationDialog from "../dialog/bookInformationDialog.vue";
import {isUserAdminOrModerator} from "../../utils/userUtils";

const props = defineProps({
  events: {
    type: Array,
    required: true
  },
  idResource: {
    type: Number,
    default: null
  }
})

const basketUser = basket();
const bookingHours = ref([]);
const storeUser = user();
const bookingStore = booking();
const dialogInfo = ref(false);
function getColNumberBook () {
  if(basketUser.selection.resource.length<=1) {
    if(storeUser.isMobile) {
      return 'col-6'
    } else {
      return 'col-8'
    }
  } else {
    if(storeUser.isMobile) {
      return 'col-3'
    } else {
      return 'col-5'
    }
  }
}

function getEventsFromSelectedDate() {
  const start = date.extractDate(basketUser.start, 'DD/MM/YYYY');
  const formattedDate = date.formatDate(start, 'YYYY-MM-DD');
  bookingHours.value = []; // Réinitialiser les heures de réservation

  props.events.forEach((element, index) => {
    // Accéder aux données pour la date spécifique
    if (element.schedule[formattedDate]) {
      const dateData = element.schedule[formattedDate];
      // Accéder aux réservations

      if (dateData.bookings) {
        // Pour accéder à chaque heure de réservation
        Object.entries(dateData.bookings).forEach(([hour, count]) => {
          bookingHours.value.push({
            hour: hour,
            count: count,
            occupancyRate: dateData.occupancyRate,
            interval: dateData.interval,
          });
        });
      }
    }
  });

  return bookingHours.value;
}

function getColor(n) {
  let number = getBookingRest(n, props.idResource);
  let color = 'warning';
  if(number <= 0.4) {
    color = 'positive';
  } else if (number > 0.6) {
    color =  'negative'
  } else {
    color = 'warning'
  }
  return color;
}

function isBookInCart(n) {
  if(n === undefined) {
    return false;
  }
  let bookInCart = false;
  let startBooking = date.extractDate(basketUser.start+' '+n.hour, 'DD/MM/YYYY HH:mm:ss');
  let endBooking = date.addToDate(startBooking, {minutes: n.interval});
  basketUser.cart.forEach(function (booking) {
    if(basketUser.selection.id === booking.catalogue.id) {
      let bookingDateStart = date.extractDate(booking.dateStart, 'YYYY-M-D HH:mm:ss');
      let bookingDateEnd = date.extractDate(booking.dateEnd, 'YYYY-M-D HH:mm:ss')
      if(booking.resourceId !== null) {
        if(booking.resourceId === basketUser.resourceId) {
          if(dateRangerOverlaps(bookingDateStart, bookingDateEnd, startBooking, endBooking)) {
            bookInCart = true;
          }
        }
      } else {
        if(dateRangerOverlaps(bookingDateStart, bookingDateEnd, startBooking, endBooking)) {
          bookInCart = true;
        }
      }
    }
  })
  return bookInCart;
}

function getSize() {
  if(storeUser.isMobile) {
    return 'sm'
  }
}

function showTimeInterval(time, interval) {
  let extractTime = date.extractDate(time, 'HH:mm:ss');
  return date.formatDate(extractTime, 'HH:mm') + ' - ' + date.formatDate(date.addToDate(extractTime, {minutes: interval}), 'HH:mm');
}

function isDatePast(time) {
  let dateStart = date.extractDate(basketUser.getStartDate+' '+time, 'YYYY-M-D HH:mm:ss');
  const dateNow = new Date();
  return dateStart < dateNow;
}


function onSelectBook (n, time, interval) {
  basketUser.selectionNStart= n;
  basketUser.selectionNEnd = n + 1;
  basketUser.bookingConditions.allowUsersToBookWithRules = true;
  basketUser.bookingConditions.allowUsersToBook = true;
  let timeSelected = date.extractDate(time, 'HH:mm');
  //add interval to timeSelected
  bookingStore.configuration.interval = interval;
  let endTimeSelected = date.formatDate(date.addToDate(timeSelected, {minutes: interval}), 'HH:mm:ss');
  let dateStart = date.extractDate(basketUser.getStartDate+' '+time, 'YYYY-M-D HH:mm:ss');
  const dateNow = new Date();
  if((dateStart > dateNow) || storeUser.isUserAdminOrModeratorSite(basketUser.selection.service.id)) {
    getBookingRestrictionForUser('available', storeUser.username, basketUser.selection.id, props.idResource, basketUser.getStartDate+' '+time, basketUser.getStartDate+' '+endTimeSelected).then(function (response) {
      basketUser.startBooking = basketUser.getStartDate+' '+time;
      basketUser.endBooking = basketUser.getStartDate+' '+endTimeSelected;
      basketUser.resourceId = props.idResource;
      dialogInfo.value = true;
    }).catch((error) => {
      let message = error.response.data.details;
      if(error.response.data.restrictions !== undefined) {
        message = error.response.data.restrictions;
        Notify.create({
          type: 'negative',
          message: message,
          position: 'top',
        });
      } else {
        if(error.response.data.rules !== undefined) {
          basketUser.bookingConditions.allowUsersToBookWithRules = false;
          basketUser.startBooking = basketUser.getStartDate+' '+time;
          basketUser.endBooking = basketUser.getStartDate+' '+endTimeSelected;
          dialogInfo.value = true;
        }
      }


    })
  } else {
    Notify.create({
      type: 'negative',
      message: this.$t('cannotBookThisSlot'),
      position: 'top',
    });
  }
}

function unselectBook(n) {
  let timeSelected = n.hour
  let dateStart = date.extractDate(basketUser.start+' '+n.hour, 'DD/MM/YYYY HH:mm:ss');
  let dateEnd = date.addToDate(dateStart, {minutes: n.interval});
  let endTimeSelected = date.formatDate(dateEnd, 'HH:mm:ss');
  let lengthCart = basketUser.cart.length;
  basketUser.cart.forEach(function(booking, index) {
    let bookingDateStart = date.extractDate(booking.dateStart, 'YYYY-M-D HH:mm:ss');
    let bookingDateEnd = date.extractDate(booking.dateEnd, 'YYYY-M-D HH:mm:ss');
    let dateRangeEqual = dateRangerOverlapsEqual(bookingDateStart, bookingDateEnd, dateStart, dateEnd);
    if(index < lengthCart) {
      if(dateRangeEqual === 3) {
        if(booking.resourceId !==null) {
          if(basketUser.resourceId === booking.resourceId) {
            basketUser.cart.splice(index, 1);
          }
        } else {
          basketUser.cart.splice(index, 1);
        }
      } else if(dateRangeEqual === 2) {
        basketUser.cart[index].dateEnd = basketUser.getStartDate+' '+timeSelected;
      } else if (dateRangeEqual === 1) {
        basketUser.cart[index].dateStart = basketUser.getStartDate+' '+endTimeSelected;
      } else if(dateRangeEqual === 4) {
        basketUser.cart[lengthCart] = {...basketUser.cart[index]};
        basketUser.cart[lengthCart].dateStart = basketUser.getStartDate+' '+endTimeSelected;
        basketUser.cart[lengthCart].dateEnd = booking.dateEnd;
        basketUser.cart[index].dateEnd = basketUser.getStartDate+' '+timeSelected;
      }
    }
  });
}


onMounted(() => {
  if(props.events.length > 0) {
    getEventsFromSelectedDate();
  }
});

watch(() => props.events, () => {
  getEventsFromSelectedDate();

});

</script>

<template>
  <div style="width: 100%">
    <q-list bordered class="rounded-borders responsive-list" style="position: relative;" :aria-label="$t('availableSlots')">
      <q-item>
        <q-item-section>
          {{ $t('scheduleLabel') }}
        </q-item-section>
        <q-item-section v-if="basketUser.selection.resource.length>1" class="col-3">
          {{ $t('numberOfResources') }}
        </q-item-section>
        <q-item-section :class="getColNumberBook()">
          {{ $t('bookingRate') }}
        </q-item-section>
        <q-item-section>
        </q-item-section>
      </q-item>
      <div v-for="(booking, i) in bookingHours" >
        <q-item v-if="!isDatePast(booking.hour) || isUserAdminOrModerator()" :key="i">
          <q-item-section>
            {{ showTimeInterval(booking.hour, booking.interval) }}
          </q-item-section>
          <q-item-section v-if="basketUser.selection.resource.length>1" class="col-3">
            {{basketUser.selection.resource.length}}
          </q-item-section>
          <q-item-section :class="getColNumberBook()">
            <q-linear-progress rounded animation-speed="150" size="15px" :value="getBookingRest(booking, props.idResource)" :color="getColor(booking)" :aria-label="$t('bookingRate')">
            </q-linear-progress>
          </q-item-section>
          <q-item-section v-if="bookingStore.events.length > 0"  class="col-2">
            <!-- + -->
            <q-btn v-if="getBookingRest(booking, props.idResource)<1 && !isBookInCart(booking)" icon="add" color="primary" :padding="getSize()" :size="getSize()" @click="onSelectBook(i,booking.hour, booking.interval)" :aria-label="$t('selectBooking') + ' ' + showTimeInterval(booking.hour, booking.interval)">
              <q-tooltip>
                {{$t('selectBooking')}}
              </q-tooltip>
            </q-btn>
            <!-- chevron up -->
            <q-btn v-if="(((getBookingRest(booking, props.idResource)>0 && (getBookingRest(bookingHours[i-1], props.idResource)<1 && !isBookInCart(bookingHours[i-1]))) && isBookInCart(booking)))" icon="expand_less" outline color="primary" padding="xs" size="xs" @click="mergeBookings(bookingHours[i-1],i-1, 'before')" :aria-label="$t('expendBooking')">
              <q-tooltip>
                {{$t('expendBooking')}}
              </q-tooltip>
            </q-btn>
            <!-- - -->
            <q-btn v-if="getBookingRest(booking, props.idResource)>0 && isBookInCart(booking)" icon="remove" color="dark" :padding="getSize()" :size="getSize()" @click="unselectBook(booking)" :aria-label="$t('unselectBooking')">
              <q-tooltip>{{$t('unselectBooking')}}</q-tooltip>
            </q-btn>

            <!-- disabled button -->
            <q-btn v-if="getBookingRest(booking, props.idResource)===1 && !isBookInCart(booking)" color="dark" icon="event_busy" :padding="getSize()" :size="getSize()" :aria-label="$t('AlreadyBook') + ' ' + showTimeInterval(booking.hour, booking.interval)" disabled>
              <q-tooltip>{{$t('AlreadyBook')}}</q-tooltip>
            </q-btn>

            <!-- chevron down -->
            <q-btn v-if="(((getBookingRest(booking, props.idResource)>0 && (getBookingRest(bookingHours[i+1], props.idResource)<1 && !isBookInCart(bookingHours[i+1]))) && isBookInCart(booking)))" icon="expand_more" outline color="primary" padding="xs" size="xs" @click="mergeBookings(bookingHours[i+1],i+1, 'after')" :aria-label="$t('expendBooking')">
              <q-tooltip>
                {{$t('expendBooking')}}
              </q-tooltip>
            </q-btn>
          </q-item-section>
        </q-item>
      </div>
      <q-item v-if="!storeUser.isLoading && bookingHours.length === 0">
        <q-item-section>
          <q-item-label class="centerObj">{{ $t('noSlotAvailable') }}</q-item-label>
        </q-item-section>
      </q-item>
      <div v-if="storeUser.isLoading" class="centerObj">
        <q-spinner size="100px" color="primary"/>
      </div>
    </q-list>
  </div>
  <book-information-dialog :open-dialog="dialogInfo" :max-number="basketUser.selection.resource.length" @close="dialogInfo=false"></book-information-dialog>
</template>

<style scoped>
.centerObj {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100%;
}

.responsive-list {
  max-height: 60vh;
  overflow-y: auto;
}
</style>