<template>
  <q-drawer show-if-above v-model="leftDrawerOpen" side="left" bordered>
    <div style="text-align: center; margin-top: 10px; margin-bottom: 5px">
      <div class="text-h5">
        {{ $t('bookingsAwait') }}
        <q-badge color="red" align="top">{{ bookings.length }}</q-badge>
      </div>
    </div>
    <q-separator/>
    <q-expansion-item
        expand-separator
        :default-opened="true"
        icon="pending_actions"
        :label="$t('bookingsToConfirm')"
    >
    <q-card v-if="bookings.length > 0" v-for="booking in bookings">
      <q-item :to="'/administration/site/'+idSite+'/booking'" v-ripple>
        <q-item-section style="margin-left: 10px">
          {{ booking.number }} | {{ booking.catalogueResource.title }} | {{ formatDatefromAPItoString(booking.dateStart) }}
        </q-item-section>
      </q-item>
    </q-card>
    <q-card v-else>
      <q-item v-ripple>
        <q-item-section style="text-align: center">
          {{ $t('noBookingsPending') }}
        </q-item-section>
      </q-item>
    </q-card>
    </q-expansion-item>
  </q-drawer>
</template>

<script>
import {ref} from 'vue';
import {loadBookingsByCatalogByStatus} from "../../api/Booking";
import {formatDatefromAPItoString} from "../../utils/dateUtils";

const bookings = ref([]);
export default {
  name: "catalogueSidebar",
  setup () {
    const leftDrawerOpen = ref(false)
    return {
      tab: ref('tab1'),
      leftDrawerOpen,
      toggleLeftDrawer () {
        leftDrawerOpen.value = !leftDrawerOpen.value
      }
    }
  },
  data() {
    return {
      idSite : null,
      bookings,
    }
  },
  mounted() {
    this.idSite = this.$router.currentRoute.value.params.id;
    this.getPendingBookings(this.idSite);
  },
  methods:{
    formatDatefromAPItoString,
    getPendingBookings(id) {
      loadBookingsByCatalogByStatus(id, 'pending').then(function (response) {
        bookings.value = response.data;
      })
    }
  },
}
</script>
<style scoped>

</style>