<template>
  <booking-confirmation-dialog :is-loading="isLoading" :open-dialog="getConfirmDialog()" @close="confirmDialog=false" :bookings="getBookingsValue()" :errorMsg="getErrorMsg()"></booking-confirmation-dialog>
  <q-dialog v-model="confirm" persistent>
    <q-card>
      <q-card-section class="row items-center">
        <span class="q-ml-sm">{{ $t('bookSidebar.confirmBookings') }}</span>
      </q-card-section>

      <q-card-actions align="right">
        <q-btn flat :label="$t('common.cancel')" color="primary" v-close-popup />
        <q-btn flat :label="$t('workflows.send')" color="primary" @click="book" />
      </q-card-actions>
    </q-card>
  </q-dialog>
  <q-drawer class="selectionDrawer" v-model="storedUser.leftDrawer" :width="400" side="right" bordered>
    <div style="text-align: center; margin-top: 10px; margin-bottom: 5px">
      <div class="text-h5">
          {{ $t('bookSidebar.selection') }}
      </div>
    </div>
    <q-separator/>
    <div v-if="basketUser.cart.length > 0">
        <selection-booking-list :basket-user="basketUser.cart" :delete-object="true"></selection-booking-list>
        <div class="absolute-bottom" style="margin-bottom: 10px; margin-left: 10px; margin-right: 10px">
            <q-btn id="submitBookings" color="primary" text-color="white" @click="confirm = true" no-caps style="width: 100%">
              {{ $t('bookSidebar.submitRequests') }}
              <q-badge v-if="basketUser.cart.length > 0" color="red" floating rounded :label="basketUser.cart.length" />
            </q-btn>
        </div>
    </div>
    <div v-else>
      <q-card-section style="text-align: center">
        {{ $t('bookSidebar.selectResource') }}
      </q-card-section>
    </div>
  </q-drawer>
</template>

<script setup>
import {onMounted, ref, watch} from "vue";
import {sendBookings} from "../../api/Booking";
import {basket} from "../../store/basket";
import SelectionBookingList from "../selectionBookingList.vue";
import BookingConfirmationDialog from "../dialog/bookingConfirmationDialog.vue";
import {user} from "../../store/counter";
import {booking} from "../../store/booking";

const bookingsAfterSend = ref([]);
const errorResponse = ref('');
const confirm = ref(false);
const confirmDialog = ref(false);
const isLoading = ref(false);
const basketUser = basket();
const storedUser = user();
const bookingUser = booking();
const bookingsToSend = ref(false);

function resetBasket() {
  basketUser.cart = [];
  basketUser.startBooking = null;
  basketUser.endBooking = null;
  basketUser.sendBookings = false;
  basketUser.selectionNStart=null;
  basketUser.selectionNEnd=null;
}

function book() {
  let setBookings = basketUser.cart;
  let bodyFormData = new FormData();
  setBookings.forEach(function (booking) {
    booking.catalogue = booking.catalogue.id;
  })
  bodyFormData.append('book', JSON.stringify(setBookings));
  bookingsAfterSend.value = [];
  errorResponse.value = "";
  confirm.value = false;
  confirmDialog.value = true;
  isLoading.value = true;

  sendBookings(bodyFormData).then(function (response) {
    if(response instanceof Array) {
      bookingsAfterSend.value = response;
    } else {
      bookingUser.updated = true;
      errorResponse.value = response;
    }
    isLoading.value = false;
  })
  resetBasket();
}

function getBookingsValue() {
  return bookingsAfterSend.value;
}
function getErrorMsg() {
  return errorResponse.value;
}
function getConfirmDialog() {
  return confirmDialog.value
}

function getBasketUserSendBookings() {
  return basketUser.sendBookings;
}

onMounted(() => {
  confirmDialog.value = false;
  isLoading.value = false;
  bookingsAfterSend.value = [];
})

watch(getBasketUserSendBookings, () => {
  if(basketUser.sendBookings === true) {
    book();
  }
})

</script>
<style>

@media (min-width: 950px) {
  .selectionDrawer {
    margin-top: 55px;
  }
}

</style>