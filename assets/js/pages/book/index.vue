<template>
    <q-card style="position: absolute; height: 95%; width: 97%">
      <q-card-section v-if="basketUser.selection === null" class="q-pt-lg q-pb-none">
        <div class="row text-center">
          <div class="col">
            <h1 v-if="step === 1" class="text-h4 titleResponsive">{{$t('wantBook')}}</h1>
          </div>
        </div>
        <q-btn v-if="step===1 && (showLocalization || showResourceType)" round color="primary" icon="arrow_left" style="z-index: 10" class="absolute-top-left q-mt-xl q-ml-md" @click="resetForm(false,false)"/>

      </q-card-section>
      <!--<q-separator inset />-->
      <q-stepper
            :contracted="contractedStepper"
            v-model="step"
            ref="stepper"
            color="secondary"
            header-class="filAriane"
            animated
        >
          <q-step
              :name="1"
              :title="$t('selectType')"
              icon="none"
              :done="step > 1"
          >
              <div  class="row justify-center q-gutter-lg">
                <div v-if="showLocalization === false" class="col-4 col-sm-4 col-md-3 text-center" @click="showResourceType = true" @keydown.enter="showResourceType = true" role="button" :aria-label="$t('searchByResourceType')" tabindex="0">
                  <q-card class="shadow-3 searchButton">
                    <img src="/images/resource.jpg"  :alt="$t('searchByResourceTypeImage')"/>
                    <q-card-section>
                      <div class="text-h6 fontSearchButton text-uppercase">{{$t('searchByResourceType')}}</div>
                    </q-card-section>
                  </q-card>
                </div>
                <div v-if="showResourceType === false" class="col-4 col-sm-4 col-md-3 text-center" @click="showLocalization = true" @keydown.enter="showLocalization = true" role="button" :aria-label="$t('searchByLocalization')" tabindex="0">
                  <q-card class="shadow-3 searchButton">
                    <img src="/images/localization.jpg" :alt="$t('searchByLocalizationImage')"/>
                    <q-card-section>
                      <div class="text-h6 fontSearchButton text-uppercase">{{$t('searchByLocalization')}}</div>
                    </q-card-section>
                  </q-card>
                </div>
              </div>

            <!-- Resource Type section -->
            <q-card-section v-if="showResourceType">
              <div class="text-h6 fontSearchButton">{{$t('resourceType')}}*</div>
              <q-select outlined v-model="basketUser.type" :options="categoryOptions" label-color="primary" option-label="label" option-value="id" :label="$t('SelectTypeYouWould')"/>
            </q-card-section>
            <q-card-section v-if="showResourceType && basketUser.type !== null">
              <div class="text-h6 fontSearchButton">{{$t('resourceSubType')}}*</div>
              <q-select outlined v-model="basketUser.subtype" :options="subCategoryOptions" label-color="primary" option-label="title" option-value="id" :label="$t('SelectSubTypeYouWould')"/>
            </q-card-section>
            <q-card-section v-if="showResourceType && basketUser.type !== null && basketUser.subtype !== null">

                <div class="row justify-between">
                  <div class="col-12 col-md-5">
                    <div class="text-h6 fontSearchButton">{{$t("watchAvailableResources")}}*</div>
                    <CalendarDate></CalendarDate>
                  </div>
                  <div class="col-12 col-md-5">
                    <q-input outlined v-model="basketUser.end" :label="$t('toDate')" style="display: none"/>
                    <div class="text-h6 fontSearchButton">{{$t('localizationFilter')}}</div>
                    <localization-selector :selected="basketUser.localization" :options="options" @selected="localizationChange"></localization-selector>
                  </div>
                </div>
                <div v-if="triggered && (basketUser.start === null)" style="margin-top: 10px; margin-left: 1%"><p style="color: red">{{$t('Required')}}</p></div>
            </q-card-section>

            <div v-if="showResourceType">
              <q-btn class="q-ml-sm float-right buttonAnimate btnNavigate" v-if="basketUser.type !== null && basketUser.subtype !== null && (basketUser.start !== null && basketUser.end !== null) && checkDateFormat(basketUser.start)" @click="$refs.stepper.next()" color="primary" :label="$t('continue')"  />
            </div>

            <!-- Localization section -->
            <div v-if="showLocalization">
              <div class="text-h6">{{$t("localizationSearch")}}*</div>
              <q-card-section>
                <localization-selector :selected="basketUser.localization" :options="options" @selected="localizationChange"></localization-selector>
              </q-card-section>
            </div>

          </q-step>

          <q-step
              :name="2"
              :title="$t('ResourceChoice')"
              icon="none"
              :done="step > 2"
          >
            <custom-table-catalogue @clickOnPrevious="$refs.stepper.previous();"></custom-table-catalogue>
          </q-step>
        </q-stepper>
    </q-card>
</template>

<style src="@quasar/quasar-ui-qcalendar/dist/QCalendarDay.min.css"></style>

<script>
import {ref} from 'vue'
import CustomTableCatalogue from "../../components/table/customTableCatalogue.vue";
import CalendarDate from "../../components/CalendarDate.vue";
import {user} from '../../store/counter';
import {basket} from '../../store/basket';
import axios from "axios";
import {storeToRefs} from "pinia";
import {getParentLocalizations} from "../../api/Localization";
import LocalizationSelector from "../../components/LocalizationSelector.vue";
import {checkDateFormat} from "../../utils/dateUtils";
import {getCategory} from "../../api/Category";

const basketUser = basket();
const storedUser = user();
const { getSelection } = storeToRefs(basketUser);
const showResourceType = ref(false);
const showLocalization = ref(false);


export default {
  name: "index.vue",
  components: {LocalizationSelector, CustomTableCatalogue, CalendarDate},
  setup () {
    return {
      basketUser,
      getSelection,
      step: ref(1),
      categoryOptions: ref([]),
      subCategoryOptions: ref([]),
      triggered:ref(false),
      continueColor: ref('primary'),
      comment: ref(''),
      widthMobile: window.innerWidth,
      showLocalization,
      showResourceType,
    }
  },
  data() {
    return {
      users: [],
      options: [],
      storedUser,
    }
  },
  computed: {
    dateSelected() {
      return this.basketUser.start;
    },
    selection () {
      return this.basketUser.selection;
    },
    contractedStepper() {
      return window.innerWidth < 700;
    },
    categorySelected() {
      return this.basketUser.type;
    },
    localizationSelected() {
      return this.basketUser.localization;
    }
  },
  mounted() {
    this.getCategory();
    this.getServices();
    //Reset basket
    this.basketUser.resourceId=null;
    this.basketUser.selection=null;
    this.basketUser.start=this.getToday();
    this.basketUser.end=this.getToday();
    this.basketUser.localization = null;
    this.basketUser.type = null;
    this.basketUser.subtype = null;
  },
  methods:{
    checkDateFormat,
    resetForm(resourceType, localizationType) {
      this.basketUser.localization = null;
      this.basketUser.type = null;
      this.basketUser.subtype = null;
      this.showLocalization = localizationType;
      this.showResourceType = resourceType;
    },
    localizationChange(value) {
      basketUser.localization = value;
    },
    getToday() {
      const date = new Date();

      // Get the components of the date (month, day, year)
      const month = (date.getMonth() + 1).toString().padStart(2, '0'); // Months are zero-indexed, so add 1
      const day = date.getDate().toString().padStart(2, '0');
      const year = date.getFullYear();

      return day+'/'+month+'/'+year
    },
    getCategory() {
      let self = this;
      axios({
        method: "get",
        url: "/api/category",
      })
          .then(function (response) {
            self.categoryOptions = response.data;
          })
    },
    getSubCategory(parent) {
      let self = this;
      getCategory(parent.id).then(function (response) {
        self.basketUser.subtype = null;
        self.subCategoryOptions = response.data.enfants;
      });
    },
    manageLocalizations(data) {
      let self = this;
      this.options = [];
      data.forEach(function (localization) {
        localization.level = 0;
        self.options.push(localization);
        if(localization.childs !== null) {
          self.options = self.options.concat(self.getChilds(localization, 1));
        }
      })
    },
    getChilds(localization, level) {
      let self = this;
      let childs = [];
      localization.childs.forEach(function (child) {
        child.level = level;
        childs.push(child);
        if(child.childs !== null) {
          childs = childs.concat(self.getChilds(child, level+1));
        }
      });
      return childs;
    },
    getServices() {
      let self = this;
      getParentLocalizations().then(function (response) {
        self.manageLocalizations(response.data);
      });
    },
    moveObject(id) {
      this.triggered = true;
      this.continueColor = "negative";
      /*if (document.getElementById(id).classList.contains('left')) {
        document.getElementById(id).classList.remove("left");
      } else {
        document.getElementById(id).classList.add("left");
      }*/

      setTimeout(() => {
        this.continueColor = "primary";
      }, 500)
    },
    formatDateAPI(apiDate) {
      let newDate = new Date(apiDate.date+' '+apiDate.time);
      return newDate.toLocaleDateString()+' '+newDate.toLocaleTimeString();
    },
    clearBasket() {
      this.basketUser.selection = null;
    },
  },
  watch: {
    dateSelected: function (newVal) {
      if(newVal === null) {
        this.basketUser.type = null;
        this.basketUser.subtype = null;
        this.basketUser.start = null;
        this.basketUser.end = null;
        this.step = 1;
      }
    },
    localizationSelected: function () {
      if(this.showLocalization && basketUser.localization !== null) {
        this.step++;
      }
    },
    categorySelected: function (newValue) {
      if(newValue !== null) {
        this.getSubCategory(newValue);
      }
    }
  }
}
</script>

<style>

.filAriane {
  display: none!important;
}

@media (max-width: 700px) {
  .buttonsBot {
    right: 20%!important;
  }
  .fontSearchButton {
    font-size: 14px!important;
  }
}

.buttonAnimate {
  margin-left: 50%;
  transition: margin-left 0.1s ease-in-out;
}

.btnNavigate {
  margin: 10px;
}

.buttonAnimate.left {
  margin-left: 0;
}

.sectionBook {
  padding-bottom: 0;
}

.searchButton {
  height: 100%;
  cursor: pointer;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  transition: transform 0.6s cubic-bezier(0.25, 0.8, 0.25, 1), box-shadow 0.6s cubic-bezier(0.25, 0.8, 0.25, 1);
  img {
    padding: 5%;
  }
}
.searchButton:hover {
  transform: scale(1.01);
  box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
}

</style>