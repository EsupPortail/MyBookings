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
            <p v-if="!userStore.isMobile"><b>{{ $t('Description') }}</b> : <div v-html="basketUser.selection.description"></div></p>
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
import { useRoute } from 'vue-router/auto';
const basketUser = basket();
const userStore = user();
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
      this.events.forEach(function (element) {
        element.Resource.forEach(function (resource) {
          if(resource.id == self.id) {
            let dateStart = new Date(element.dateStart);
            let dateEnd = new Date(element.dateEnd);
            if(dateNow > dateStart && dateNow < dateEnd) {
              self.urlNow = self.id;
              self.colorNow = 'negative';
              self.informationBooking = self.$t('ResourceOnBook')
            }
          }
        })
      })
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
        url: "/api/catalogue_resources?resource.id="+this.id,
      })
          .then(function (response) {
            self.resources = [];
            response.data.forEach(function(element) {
              self.resources.push(element)
            });
            self.basketUser.selection = self.resources[0];
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
      let date = new Date(start);
      let dateEnd = new Date(start);
      dateEnd.setDate(dateEnd.getDate()+3);
      let url = '/api/bookings?dateStart='+date.toISOString()+'&dateEnd='+dateEnd.toISOString()+'&Resource.id='+this.id;
      let self = this;
      if(url.length < 8000) {
        axios({
          method: "get",
          headers: {
            'accept': 'application/json'
          },
          url: url,
        })
            .then(function (response) {
              self.events = response.data;
              self.checkEvents();
            })
      } else {
        self.$q.notify({
          type: 'negative',
          message: "Erreur : Request too long",
          position: 'top',
        })
      }
    },
    getFromProvision(parameter) {
      let dateStart = this.getStart.split('/');
      let dateSelected = new Date(dateStart[2], parseInt(dateStart[1])-1, dateStart[0]);
      return this.getActualParameterFromProvisions(dateSelected, this.basketUser.selection.Provisions, parameter);
    },
    setImgUrl() {
      if(this.resource.image !== null) {
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