<template>
  <q-card-section class="q-pa-none xs-hide">
    <div class="parentCenter">
      <div class="leftItem">
        <q-btn class="q-mt-sm" align="center"  v-if="this.basketUser.selection === null" color="primary" icon="arrow_left" @click="sendPrevious" :label="$t('return')" />
      </div>
      <h1 class="text-h4 ">{{$t('wantBook')}}</h1>
    </div>
  </q-card-section>
  <q-card-section class="q-pa-none xs">
    <q-btn class="q-mt-md centerButton" align="center"  v-if="this.basketUser.selection === null" color="primary" icon="arrow_left" @click="sendPrevious" :label="$t('return')" />
  </q-card-section>
  <q-card-section class="q-pa-none" v-if="localResources.length > 0">
    <h5 v-if="basketUser.type !== null" class="text-center titleMobile q-ma-none">{{localResources[0].type.title}} / {{localResources[0].subType.title}}</h5>
    <h5 v-if="basketUser.localization !== null" class="text-center titleMobile q-ma-none">{{ $t('location') }} : {{basketUser.localization.title}}</h5>
  </q-card-section>
  <q-card-section class="q-pa-none">
    <h5 class="text-center q-ma-none titleMobile">{{ basketUser.getStartDay }} {{ basketUser.getStartMonth }} {{ basketUser.getStartYear }}</h5>
  </q-card-section>
  <q-card-section class="q-pa-none" v-if="localResources.length > 0">
    <q-list class="q-pa-xs row q-gutter-md flex-center">
      <q-card v-for="resource in resources" class="shadow-3" style="cursor: pointer;" @click="selectCatalog(resource)" @keydown.enter="selectCatalog(resource)" role="listitem" :key="resource.id" :aria-label="resource.title" tabindex="0">
        <q-img class="xs-hide" :src="'/uploads/' + resource.image" :alt="resource.title" width="250px" :ratio="16/9">
            <template v-slot:error>
              <div class="absolute-full flex flex-center bg-grey text-white" role="img" :aria-label="$t('noPicture')">
                <q-icon size="md" name="no_photography"/>
              </div>
            </template>
        </q-img>
        <q-img class="xs" :src="'/uploads/' + resource.image" width="100px" :alt="resource.title" :ratio="16/9">
          <template v-slot:error>
            <div class="absolute-full flex flex-center bg-grey text-white" role="img" :aria-label="$t('noPicture')">
              <q-icon size="md" name="no_photography"/>
            </div>
          </template>
        </q-img>
        <div class="text-caption mobileSubtitleText"><q-icon name="pin_drop"></q-icon>{{resource.localization.title}}</div>
        <q-card-section class="q-pa-sm q-mb-md">
          <div class="text-bold mobileSubtitleText">{{ resource.title }}</div>
          <div class="text-subtitle3 xs-hide">{{resource.type.title}}</div>
        </q-card-section>
        <q-linear-progress class="absolute-bottom" size="10px" :value="getEvents(resource)" :color="getColor(resource)" :aria-label="$t('bookingRate') + ' (' +Math.round(getEvents(resource)*100) + ')%'">
          <q-tooltip>
            {{ $t('bookingRate') }} ({{Math.round(getEvents(resource)*100)}}%)
          </q-tooltip>
        </q-linear-progress>
      </q-card>
    </q-list>
  </q-card-section>
  <q-card-section v-else>
    <div class="text-h6 text-center">{{ $t('noResourceAvailable') }}</div>
  </q-card-section>
</template>

<script>
import {basket} from "../store/basket";
import {date} from "quasar";
import {getActualParameterFromProvisions} from "../utils/basketUtils";
import {VueFlow} from "@vue-flow/core";

const basketUser = basket();
export default {
  name: "catalogListAvailable",
  components: {VueFlow},
  props: {
    resources: Array,
    events: Array,
  },
  emits :{
    clickOnCatalog: null,
    clickOnPrevious: null,
  },
  data() {
    return {
      basketUser: basketUser,
      localResources: [],
      getActualParameterFromProvisions,
    }
  },
  mounted() {
    this.localResources = this.resources;
  },
  methods: {
    sendPrevious()  {
      this.$emit('clickOnPrevious');
    },
    selectCatalog(catalog) {
      let dateStart = date.extractDate(this.basketUser.start, 'DD/MM/YYYY');
      dateStart.setHours(12)
      this.basketUser.resourceId=null;
      this.basketUser.selection = catalog;
      this.$emit('clickOnCatalog', this.basketUser.selection);
    },
    getEvents (scope) {
      let scopeDate = date.extractDate(this.basketUser.start, 'DD/MM/YYYY');
      let formatedDate = date.formatDate(scopeDate, 'YYYY-MM-DD')
      let occupancyRate = 0;
      this.events.forEach(function (element){
        if(element.id == scope.id && element.schedule[formatedDate] !== undefined) {
          occupancyRate =  element.schedule[formatedDate]['occupancyRate'] / 100;
        }
      })
      return occupancyRate;
    },
    getColor(scope) {
      let number = this.getEvents(scope);
      let color = 'warning';
      if(number <= 0.4) {
        color = 'positive';
      } else if (number > 0.6) {
        color =  'negative'
      } else {
        color = 'warning'
      }
      return color;
    },
  },
  watch: {
    resources: function () {
      this.localResources = this.resources;
    },
  }
}
</script>



<style scoped>
@media (max-width: 700px) {
  .mobileSubtitleText {
    font-size: 10px;
    max-width: 100px;
  }
  .titleMobile {
    font-size:20px;
  }
}

</style>