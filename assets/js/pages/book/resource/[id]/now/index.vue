<template>

  <q-card>
    <q-card-section>
      <div class="text-h5">{{$t('BookRessource')}} : <b>{{resource.title}}</b></div>
    </q-card-section>
    <q-separator inset/>
    <q-card-section horizontal v-if="basketUser.selection !== null">
      <q-card-section horizontal style="width: 100%">
        <q-card-section class="imageCatalogue" style="width: 20%">
          <q-img :src="'/uploads/'+imgUrl" width="100%">
          </q-img>
        </q-card-section>
        <q-card-section>
          <p><b>{{ $t('Type') }}</b> : {{basketUser.selection.type.title}} / {{basketUser.selection.subType.title}}</p>
          <p v-if="!userStore.isMobile"><b>{{ $t('Description') }}</b> : {{basketUser.selection.description}}</p>
        </q-card-section>
      </q-card-section>
      <q-card-section horizontal>
        <q-card-section>
          <p><b>{{ $t('service') }}</b> : {{basketUser.selection.service.title}}</p>
          <p><b>{{$t('MaxBookingPerDay')}}</b> : {{getFromProvision('maxBookingByDay')}}</p>
        </q-card-section>
      </q-card-section>
    </q-card-section>
    <q-separator inset/>
    <q-card-section v-if="events !== null && !outOfRange">
      <q-card-section v-if="available">
        <div class="text-center">
          <div class="text-h5">{{ $t('free') }}</div>
          <div class="text-h6">
            {{date.formatDate(basketUser.startBooking, 'HH:mm')}}
            -
            {{date.formatDate(basketUser.endBooking, 'HH:mm')}}
          </div>
          <q-btn
              padding="lg"
              size="m"
              color="primary"
              icon="schedule"
              :label="$t('book')"
              @click="bookNow"
          />
          </div>
      </q-card-section>
      <q-card-section v-else>
        <div class="text-center">
        <div class="text-h5">{{ $t('currentlyBooked') }}</div>
          <div class="text-h6">
            {{date.formatDate(basketUser.startBooking, 'HH:mm')}}
            -
            {{date.formatDate(basketUser.endBooking, 'HH:mm')}}
          </div>
          <q-btn
              padding="md"
              size="md"
              color="negative"
              icon="lock"
              :label="$t('booked')"
          />
        </div>
      </q-card-section>
    </q-card-section>
    <q-card-section v-else>
      <div class="text-h5 text-center" v-if="outOfRange">
        {{ $t('noSlotAvailableNow') }}

        <div v-if="nextAvailableSlot" class="q-mt-md text-subtitle1">
          {{ $t('nextSlotAvailableAt') }} {{ date.formatDate(nextAvailableSlot, 'HH:mm') }}
        </div>

        <div class="q-mt-md">
          <q-btn
              padding="md"
              color="primary"
              icon="schedule"
              :label="$t('Later')"
              @click="redirectToSchedule"
          />
        </div>
      </div>
      <div class="text-h5 text-center" v-else>{{ $t('loadingAvailability') }}</div>
    </q-card-section>
  </q-card>
  <q-dialog v-model="confirm">
    <q-card>
      <q-card-section>
        <div class="text-h6">{{$t('BookRessource')}}</div>
      </q-card-section>
      <q-separator/>

      <q-card-section class="q-pt-none">
        {{ $t('confirmBookingMessage') }}
      </q-card-section>

      <q-card-actions class="row justify-between">
        <q-btn class="btnDialogConfirm" :label="$t('confirm')" color="primary" @click="confirmBooking" />
        <q-btn class="btnDialogConfirm" :label="$t('bookAnotherSlot')" color="primary" @click="redirectToSchedule" />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script>
import {basket} from "../../../../../store/basket";
import {date} from "quasar";
import {user} from "../../../../../store/counter";
import { getActualParameterFromProvisions } from '../../../../../utils/basketUtils'
import {getCatalogueOfResourceById} from "../../../../../api/CatalogRessource";
import {loadBookingsFromManyCatalogs} from "../../../../../api/Booking";
import {ref} from "vue";
import { useRoute } from 'vue-router/auto';
const basketUser = basket();
const userStore = user();
const available = ref(true);
const events = ref(null);
const outOfRange = ref(false);
const nextAvailableSlot = ref(null);
const interval = ref(60); // Intervalle par défaut de 60 minutes
export default {
  name: "index.vue",
  setup() {
    const route = useRoute();
    const id = route.params.id;
    return { id };
  },
  data() {
    return {
      resource: [],
      events,
      start:null,
      hourPerDay: 60*24,
      interval,
      calendarInterval: null,
      imgUrl: null,
      minBooking: null,
      maxBooking: null,
      available,
      getActualParameterFromProvisions,
      confirm: false,
      basketUser,
      userStore,
      outOfRange,
      nextAvailableSlot,
    }
  },
  mounted() {
    basketUser.cart = [];
    this.getCatalogueOfResource();
  },
  computed: {
    date() {
      return date
    },
    getCart() {
      return basketUser.cart;
    }
  },
  methods: {
    getCatalogueOfResource() {
      let self = this;
        getCatalogueOfResourceById(this.id).then(function (response) {
          self.resources = [];
          response.forEach(function(element) {
            self.resources.push(element)
          });
          basketUser.selection = self.resources[0];
          basketUser.resourceId=self.id;
          basketUser.selection.resource.forEach(function (element) {
            if (element.id == self.id) {
              self.resource = element;
            }
          })
          self.setImgUrl();
          //Requêtage des bookings en fonction de la ressource
          self.loadBookingsFromResource();
      });
    },
    loadBookingsFromResource() {
      let self = this;
      let dateStart = new Date();
      let dateEnd = new Date(dateStart);
      dateEnd.setDate(dateEnd.getDate() + 1);
      dateStart = date.formatDate(dateStart, 'YYYY-MM-DDTHH:mm:ss');
      dateEnd = date.formatDate(dateEnd, 'YYYY-MM-DDTHH:mm:ss');
      loadBookingsFromManyCatalogs(null, dateStart, dateEnd, 'countBookings', this.id).then(response => {
        events.value = response.data;
        self.checkIfResourceAvailable();
      })
    },
    getFromProvision(parameter) {
      let dateSelected = new Date();
      return this.getActualParameterFromProvisions(dateSelected, basketUser.selection.Provisions, parameter);
    },
    setImgUrl() {
      if(this.resource.image !== null) {
        this.imgUrl = this.resource.image;
      } else {
        this.imgUrl = basketUser.selection.image
      }
    },
    checkIfResourceAvailable() {
      // Définir l'heure de début en fonction de l'intervalle disponible
      basketUser.startBooking = new Date();
      basketUser.endBooking = new Date();

      // Date actuelle
      const currentDate = new Date();
      const currentHour = currentDate.getHours();
      const currentMinute = currentDate.getMinutes();

      // Valeur par défaut pour l'intervalle (en minutes)

      // Parcourir le tableau events.value pour trouver l'intervalle approprié
      if (Array.isArray(events.value)) {
        for (let i = 0; i < events.value.length; i++) {
          const event = events.value[i];

          // Vérifier si l'événement contient un intervalle de réservation
          if (event.schedule) {
            // Récupérer la date actuelle au format YYYY-MM-DD
            const today = date.formatDate(new Date(), 'YYYY-MM-DD');

            // Vérifier si la date existe dans le planning
            if (event.schedule[today]) {
              // Récupérer l'intervalle défini pour aujourd'hui
              interval.value = event.schedule[today].interval;
              break; // Nous avons trouvé l'intervalle, sortir de la boucle
            }
          }
        }
      }

      // Calculer les minutes totales depuis minuit
      const currentTotalMinutes = currentHour * 60 + currentMinute;

      // Calculer le nombre de créneaux déjà passés aujourd'hui
      const slotsElapsed = Math.floor(currentTotalMinutes / interval.value);

      // Calculer l'heure de début du créneau actuel
      const startHour = Math.floor((slotsElapsed * interval.value) / 60);
      const startMinute = (slotsElapsed * interval.value) % 60;

      // Calculer l'heure de fin du créneau actuel
      const endSlotMinutes = (slotsElapsed + 1) * interval.value;
      const endHour = Math.floor(endSlotMinutes / 60);
      const endMinute = endSlotMinutes % 60;

      // Mettre à jour basketUser.startBooking avec l'heure de début du créneau
      basketUser.startBooking.setHours(startHour);
      basketUser.startBooking.setMinutes(startMinute);
      basketUser.startBooking.setSeconds(0);

      // Mettre à jour basketUser.endBooking avec l'heure de fin du créneau
      basketUser.endBooking.setHours(endHour);
      basketUser.endBooking.setMinutes(endMinute);
      basketUser.endBooking.setSeconds(0);
      // Vérifier les chevauchements avec les réservations existantes
      if (events.value) {
        let self = this;
        events.value.forEach(function (event){
          let dateStart = date.formatDate(currentDate, 'YYYY-MM-DD');
          let timeStart = date.formatDate(basketUser.startBooking, 'HH:mm:ss');
          if(event.schedule[dateStart]?.bookings[timeStart] === undefined) {
            outOfRange.value = true;
            // Si hors plage, chercher le prochain créneau disponible
            self.findNextAvailableSlot(event, interval.value, dateStart);
          } else {
            available.value = event.schedule[dateStart].bookings[timeStart] < 1;
          }

        });
      }
    },
    findNextAvailableSlot(event, interval, dateStart) {
      nextAvailableSlot.value = null;
      if (!event.schedule || !event.schedule[dateStart]) {
        return;
      }

      const bookings = event.schedule[dateStart].bookings;
      const currentDate = new Date();
      const currentHour = currentDate.getHours();
      const currentMinute = currentDate.getMinutes();

      // Définir l'heure de début de recherche (créneau actuel)
      const startSearchMinutes = currentHour * 60 + currentMinute;

      // Parcourir tous les créneaux restants de la journée
      for (let minutesFromMidnight = startSearchMinutes; minutesFromMidnight < 24 * 60; minutesFromMidnight += interval) {
        // Arrondir au créneau supérieur en fonction de l'intervalle
        const slotMinutes = Math.ceil(minutesFromMidnight / interval) * interval;

        // Calculer l'heure et les minutes du créneau
        const slotHour = Math.floor(slotMinutes / 60);
        const slotMinute = slotMinutes % 60;

        // Si on dépasse minuit, on s'arrête
        if (slotHour >= 24) {
          break;
        }

        // Formater l'heure du créneau pour la recherche dans les bookings
        const slotTime = `${slotHour.toString().padStart(2, '0')}:${slotMinute.toString().padStart(2, '0')}:00`;
        // Vérifier si ce créneau est disponible
        if (bookings[slotTime] !== undefined && bookings[slotTime] < 1) {
          // Créer un objet Date pour ce créneau
          const availableSlotDate = new Date();
          availableSlotDate.setHours(slotHour);
          availableSlotDate.setMinutes(slotMinute);
          availableSlotDate.setSeconds(0);

          nextAvailableSlot.value = availableSlotDate;
          break;
        }
      }
    },
    bookNow() {
      basketUser.cart = [];
      basketUser.cart.push({
        catalogue: basketUser.selection,
        dateStart: date.formatDate(basketUser.startBooking, 'YYYY-MM-DD HH:mm:ss'),
        dateEnd: date.formatDate(basketUser.endBooking, 'YYYY-MM-DD HH:mm:ss'),
        comment: '',
        title: null,
        number: 1,
        resourceId: this.id,
        moderatorBooking: false,
        users: JSON.stringify(basketUser.users),
      });
      basketUser.comment = null;
      basketUser.users = [];
      basketUser.number = 1;
      this.confirm = true;
    },
    confirmBooking() {
      setTimeout(function () {
        document.getElementById('submitBookings').click();
      }, 200);
      this.confirm = false;
    },
    redirectToSchedule() {
      this.$router.push('schedule', this.id);
    }
  },
  watch:{
    getCart: function () {
      if(basketUser.cart.length === 0) {
        this.loadBookingsFromResource();
      }
    }
  }
}
</script>

<style scoped>
@media (max-width: 700px) {
  .imageCatalogue {
    width: 100%!important;
  }
  .btnDialogConfirm {
    font-size: 12px;
  }
}
</style>