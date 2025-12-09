<template>
  <q-card>
    <q-card-section>
      <div class="text-h5" v-if="basketUser.selection !== null">{{$t('BookCatalog')}} : <b>{{basketUser.selection.title}}</b></div>
    </q-card-section>
    <q-separator inset/>
    <div class="q-pa-md example-row-equal-width">
      <div class="row" v-if="basketUser.selection !== null">
        <div class="col-3">
          <div class="imageCatalogue">
            <q-img :src="'/uploads/'+imgUrl">
              <template v-slot:error>
                <div class="absolute-full flex flex-center bg-grey text-white">
                  <q-icon size="md" name="no_photography"/>
                </div>
              </template>
            </q-img>
          </div>
        </div>
        <q-card-section class="col-3">
          <p><b>{{ $t('Type') }}</b> : {{basketUser.selection.type.title}} / {{basketUser.selection.subType.title}}</p>
          <p v-if="!userStore.isMobile"><b>{{ $t('Description') }}</b> : <span v-html="basketUser.selection.description"></span></p>
        </q-card-section>
        <q-card-section class="col-4">
          <p><b>{{ $t('service') }}</b> : {{basketUser.selection.service.title}}</p>
          <p v-if="getFromProvision('maxBookingByDay')>0"><b>{{$t('MaxBookingPerDay')}}</b> : {{getFromProvision('maxBookingByDay')}}</p>
        </q-card-section>
      </div>
    </div>
      <q-separator inset/>
      <q-card-section v-if="events !== null" class="q-pa-md q-gutter-sm" style="text-align: center;">
        <q-btn
            v-if="!userStore.isMobile"
            padding="xl"
            size="xl"
            :color="colorNow"
            icon="check"
            :label="$t('Now')"
            :to="urlNow"
        />
        <q-btn
            v-if="userStore.isMobile"
            padding="lg"
            size="lg"
            :color="colorNow"
            :label="$t('Now')"
            :to="urlNow"
        />
        <q-btn
            v-if="!userStore.isMobile"
            padding="xl"
            size="xl"
            icon="schedule"
            color="primary"
            :label="$t('Later')"
            :to="urlSchedule"
        />
        <q-btn
            v-if="userStore.isMobile"
            padding="lg"
            size="lg"
            color="primary"
            :label="$t('Later')"
            :to="urlSchedule"
        />
      </q-card-section>
    <p style="text-align: center">{{informationBooking}}</p>
  </q-card>
</template>

<script>
import {user} from '../../../../store/counter';
import { basket } from '../../../../store/basket';
import basketDialog from "../../../../components/dialog/basketDialog.vue";
import { getActualParameterFromProvisions } from '../../../../utils/basketUtils'
import {date} from "quasar";
import axios from "axios";
import {storeToRefs} from "pinia";
import {loadBookingsFromManyCatalogs} from "../../../../api/Booking";
import {checkEachResourcesFromBooking, getRestOfTime} from "../../../../utils/bookingUtils";
import {dateRangerOverlaps} from "../../../../utils/dateUtils";
import {ref} from "vue";
import { useRoute } from 'vue-router/auto';
const basketUser = basket();
const userStore = user();
const available = ref(true);
export default {
  name: "index.vue",
  components: {basketDialog},
  setup() {
    const route = useRoute();
    const id = route.params.id;
    return { id };
  },
  data() {
    const { getStart, getEnd } = storeToRefs(basketUser);
    return {
      urlNow: null,
      urlSchedule: null,
      userStore,
      basketUser,
      getStart,
      getEnd,
      resource: [],
      colorNow: 'primary',
      events: null,
      informationBooking: '',
      imgUrl: '',
      available,
      getActualParameterFromProvisions
    }
  },
  mounted() {
    this.urlNow = this.id+'/now';
    this.urlSchedule = this.id+'/schedule';
    let dateStart = new Date();
    this.basketUser.start = date.formatDate(dateStart, 'DD/MM/YYYY');
    this.getCatalogueOfResource();
  },
  methods: {
    checkEvents() {
      const dateNow = new Date();
      let self = this;
      let bookedCount = 0;
      this.events.forEach(function (element) {
        let dateStart = new Date(element.dateStart);
        let dateEnd = new Date(element.dateEnd);
        if(dateNow > dateStart && dateNow < dateEnd) {
          bookedCount = bookedCount + element.Resource.length;
        }
      })

      if(bookedCount >= this.basketUser.selection.resource.length) {
        self.urlNow = self.id;
        self.colorNow = 'negative';
        self.informationBooking = self.$t('CatalogOnBook')
      }
    },
    getCatalogueOfResource() {
      let start = date.extractDate(this.basketUser.start, 'DD/MM/YYYY');
      start = date.formatDate(start, 'YYYY-MM-DDTHH:mm:ss');
      let self = this;
      axios({
        method: "get",
        headers: {
          'accept': 'application/json'
        },
        url: "/api/catalogue_resources/"+this.id,
      })
          .then(function (response) {
            self.basketUser.selection = response.data;
            self.basketUser.selection.resource.forEach(function (element) {
              if(element.id == self.id) {
                self.resource = element;
              }
            });
            self.setImgUrl();
            //Requêtage des bookings en fonction des catalogues
            self.loadBookingsFromResource(start);
          })
    },
    loadBookingsFromResource(start) {
      let dateStart = new Date(start);
      dateStart = date.formatDate(dateStart, 'YYYY-MM-DDTHH:mm:ss');
      let dateEnd = new Date(start);
      dateEnd.setDate(dateEnd.getDate()+3);
      dateEnd = date.formatDate(dateEnd, 'YYYY-MM-DDTHH:mm:ss');
      let self = this;
      loadBookingsFromManyCatalogs([this.basketUser.selection], dateStart, dateEnd).then(function (response) {
        self.events = response.data;
        self.checkEvents();
      });
    },
    getFromProvision(parameter) {
      let dateStart = this.getStart.split('/');
      let dateSelected = new Date(dateStart[2], parseInt(dateStart[1])-1, dateStart[0]);
      return this.getActualParameterFromProvisions(dateSelected, this.basketUser.selection.Provisions, parameter);
    },
    setImgUrl() {
      if(this.resource.image) {
        this.imgUrl = this.resource.image;
      } else {
        this.imgUrl = this.basketUser.selection.image
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
}

.secondSectionNoPadding {
  padding: 10px 0 0!important;
}

</style>