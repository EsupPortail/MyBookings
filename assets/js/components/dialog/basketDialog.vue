<template>
  <q-btn v-if="$q.screen.lt.sm && ((getFromProvision('maxBookingByDay')>0 || getFromProvision('maxBookingByWeek')>0))" color="primary" class="absolute-top-right z-max" @click="showRulesDialog = true" icon="info" flat no-caps>{{$t('seeTheRules')}}</q-btn>
  <q-card-section :horizontal="$q.screen.lt.sm" class="sectionNoPadding">
    <q-card-section class="sectionNoPadding imageCatalogue" style="text-align: center;">
        <q-img v-if="!$q.screen.lt.sm" :src="'/uploads/'+imgUrl" :alt="basketUser.selection.title" height="200px" fit="contain">
          <template v-slot:error>
            <div class="absolute-full flex flex-center bg-grey text-white border-box">
              <q-icon size="md" name="no_photography" alt="No picture"/>
            </div>
          </template>
        </q-img>
        <q-img v-else :src="'/uploads/'+imgUrl" :alt="basketUser.selection.title" height="80px" fit="cover">
          <template v-slot:error>
            <div class="absolute-full flex flex-center bg-grey text-white">
              <q-icon size="sm" name="no_photography" alt="No picture"/>
            </div>
          </template>
        </q-img>
    </q-card-section>
    <q-card-section v-if="isResource === false" class="sectionNoPadding textMobileAdapting">
      <div v-if="$q.screen.lt.sm" class="text-h6">{{ $t('Catalog') }}</div>
      <ul>
        <li><b>{{ $t('title') }}</b> : {{basketUser.selection.title}}</li>
        <li><b>{{ $t('Type') }}</b> : {{basketUser.selection.type.title}} / {{basketUser.selection.subType.title}}</li>
        <li><b>{{ $t('service') }}</b> : {{basketUser.selection.service.title}}</li>
        <li v-if="!$q.screen.lt.sm"><b>{{ $t('Number') }}</b> : {{basketUser.selection.resource.length}}</li>
        <li v-if="!$q.screen.lt.sm"><b>{{ $t('Description') }}</b> : <div v-html="basketUser.selection.description"></div></li>
      </ul>
    </q-card-section>
    <q-card-section v-if="isResource === true && resource !== null" class="sectionNoPadding textMobileAdapting">
      <div v-if="$q.screen.lt.sm" class="text-h6">{{ $t('Resource') }}</div>
      <ul>
        <li><b>{{ $t('title') }}</b> : {{resource.title}}</li>
        <li><b>{{ $t('Type') }}</b> : {{basketUser.selection.type.title}} / {{basketUser.selection.subType.title}}</li>
        <li><b>{{ $t('service') }}</b> : {{basketUser.selection.service.title}}</li>
        <li v-if="!$q.screen.lt.sm"><b>{{ $t('Description') }}</b> :
          <span v-if="resource.AdditionalInformations !== null && resource.AdditionalInformations !== ''">{{resource.AdditionalInformations}}</span>
          <span v-else-if="basketUser.selection.description !== null && basketUser.selection.description !== ''">
            <div v-html="basketUser.selection.description"></div>
          </span>
          <span v-else>
            <i>N/R</i>
          </span>
        </li>
      </ul>
    </q-card-section>
    <q-separator v-if="!$q.screen.lt.sm" />
    <q-card-section v-if="!$q.screen.lt.sm && (getFromProvision('maxBookingByDay')>0 || getFromProvision('maxBookingByWeek')>0)" class="secondSectionNoPadding">
      <div class="rounded-borders">
        <div class="text-primary">{{$t('nbMaxBookingBy')}}&nbsp;:</div>
        <div v-if="getFromProvision('maxBookingByWeek')>0" class="q-ml-md q-my-xs">
          <span class="text-weight-bold">{{$t('Semaine')}} :</span>
          <span class="text-bold q-ml-sm">{{ (getFromProvision('maxBookingByWeek') * getFromProvision('maxBookingDuration') / 60).toFixed(1) }} h</span>
        </div>
        <div v-if="getFromProvision('maxBookingByDay')>0" class="q-ml-md">
          <span class="text-weight-bold">{{$t('Jour')}} :</span>
          <span class="text-bold q-ml-sm">{{ (getFromProvision('maxBookingByDay') * getFromProvision('maxBookingDuration') / 60).toFixed(1) }} h</span>
        </div>
      </div>
    </q-card-section>
    <q-separator />
    <q-select v-if="showResourceList && !$q.screen.lt.sm" label="Selectionner une ressource" v-model="resourceChoice" :options="resourceList" option-value="id" option-label="title" @update:model-value="changeResourceChoice"></q-select>
  </q-card-section>
  <div v-if="$q.screen.lt.sm">
    <div v-if="isResource === false" class="textMobileSize" v-html="basketUser.selection.description"></div>
    <div v-if="isResource === true && resource !== null">
      <span v-if="resource.AdditionalInformations !== null && resource.AdditionalInformations !== ''">{{resource.AdditionalInformations}}</span>
      <span v-else-if="basketUser.selection.description !== null && basketUser.selection.description !== ''">
            <div v-html="basketUser.selection.description"></div>
          </span>
      <span v-else>
            <i>N/R</i>
      </span>
    </div>
    <q-dialog v-model="showRulesDialog">
      <q-card class="q-pa-md">
        <div class="text-h6 text-primary">{{$t('nbMaxBookingBy')}}&nbsp;:</div>
        <div v-if="getFromProvision('maxBookingByWeek')>0" class="q-my-xs">
          <span class="text-weight-bold">{{$t('Semaine')}} :</span>
          <span class="text-bold q-ml-sm">{{ (getFromProvision('maxBookingByWeek') * getFromProvision('maxBookingDuration') / 60).toFixed(1) }} h</span>
        </div>
        <div v-if="getFromProvision('maxBookingByDay')>0">
          <span class="text-weight-bold">{{$t('Jour')}} :</span>
          <span class="text-bold q-ml-sm">{{ (getFromProvision('maxBookingByDay') * getFromProvision('maxBookingDuration') / 60).toFixed(1) }} h</span>
        </div>
        <q-card-actions align="right" class="q-pa-none">
          <q-btn color="primary" class="q-mt-md" @click="showRulesDialog = false" flat>{{$t('Fermer')}}</q-btn>
        </q-card-actions>
      </q-card>
    </q-dialog>
  </div>
  <q-select v-if="$q.screen.lt.sm && showResourceList" label="Selectionner une ressource" dense v-model="resourceChoice" :options="resourceList" option-value="id" option-label="title" @update:model-value="changeResourceChoice"></q-select>
</template>

<script>
import { getActualParameterFromProvisions } from '../../utils/basketUtils.js';
import { basket } from '../../store/basket';
import { user } from '../../store/counter';
import {storeToRefs} from "pinia";
import { ref } from 'vue'
import {date} from "quasar";

export default {
  name: "basketDialog",
  props: {
    isResource: Boolean,
    idResource: Number,
    showResourceList: Boolean,
    resourceList: Array
  },
  emits :{
    resourceChange: null,
  },
  data() {
    const basketUser = basket();
    const userStore = user();
    const { getStart, getEnd } = storeToRefs(basketUser);
    return {
      getActualParameterFromProvisions,
      basketUser,
      userStore,
      getStart,
      getEnd,
      imgUrl: '',
      showResourceInformation: false,
      resource: null,
      resourceChoice: ref({
        title: '',
        id: ''
      }),
      showRulesDialog: false,
    }
  },
  mounted() {
    if(this.isResource === true) {
      let self = this;
      let view = self.basketUser.selection.view;
      this.basketUser.selection.resource.forEach(function (element) {
        if(element.id == self.idResource || view === 'Lot') {
          self.resource = element;
          self.resourceChoice.id = element.id;
          self.resourceChoice.title = element.title;
        }
      });
    }
    this.setImgUrl();
  },
  methods: {
    clearBasket() {
      this.basketUser.startBooking = null;
      this.basketUser.endBooking = null;
      this.basketUser.selection = [];
    },
    formatDateAPI(apiDate) {
      if(apiDate !== null) {
        let newDate = new Date(apiDate.date+' '+apiDate.time);
        return newDate.toLocaleDateString()+' '+newDate.toLocaleTimeString();
      }
    },
    onClosePopup() {
        this.showResourceInformation = false;
    },
    getFromProvision(parameter) {
        let dateStart = this.getStart.split('/');
        let dateSelected = new Date(dateStart[2], parseInt(dateStart[1])-1, dateStart[0]);
        return this.getActualParameterFromProvisions(dateSelected, this.basketUser.selection.Provisions, parameter);
    },
    setImgUrl() {
      if(this.resource !== null && this.resource.image !== null) {
          this.imgUrl = this.resource.image;
      } else {
        this.imgUrl = this.basketUser.selection.image
      }
    },
    changeResourceChoice() {
      this.$emit('resourceChange', this.resourceChoice.id)
    }
  },
  watch:{
    idResource: function () {
      let self = this;
      this.resourceList.forEach(function (element) {
        if(element.id == self.idResource) {
          self.resource = element;
          self.resourceChoice.id = element.id;
          self.resourceChoice.title = element.title;
        }
      });
      this.setImgUrl();
    }
  }
}
</script>

<style scoped>
ul {
  padding-left: 0;
  list-style: none;
}
.sectionNoPadding {
  padding: 0!important;
}

.secondSectionNoPadding {
  padding: 10px 0 0!important;
}

@media (max-width: 700px) {
  ul {
    margin-top: 0;
  }
  .imageCatalogue {
    width: 30%;
  }
  .textMobileAdapting {
    margin-left: 2%;
  }
  .textMobileSize {
    font-size: 11px;
  }
}
</style>