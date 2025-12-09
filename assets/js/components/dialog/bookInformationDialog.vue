<template>
    <q-dialog v-model="prompt" persistent>
        <q-card class="addBookingDialog">
            <q-card-section>
              <div class="row justify-around">
                <div class="col-md-11 col-xs-9">
                  <div class="text-h6 text-left resaTitle">
                    <span v-if="editMode">{{$t('editBooking')}} : </span>
                    <span v-else>{{$t('BookRessource')}} : </span>
                    <span v-if="$q.platform.is.desktop"> {{shortFormatDateApi(basketUser.startBooking)}} : {{dateSameDay(basketUser.startBooking, basketUser.endBooking)}}</span>
                  </div>
                </div>
                <div>
                  <div class="col-auto">
                    <q-btn :size="loadSize" dense round color="dark" icon="close" @click="close">
                      <q-tooltip>
                        {{$t('closeWindow')}}
                      </q-tooltip>
                    </q-btn>
                  </div>
                </div>
              </div>
            </q-card-section>
            <q-card-section class="q-pt-none">
              <div v-if="allowBookingMultipleDays">
                <div class="text-h6 resaTitle">
                  {{$t('startDateTimeLabel')}}
                  <q-input outlined ref="startBooking" v-model="startBooking" label-color="primary" :label="$t('fromDateTime')"
                           :rules="[
                              val => checkDateTimeFormat(val)|| $t('Required'),
                              val => basketUser.bookingConditions.allowUsersCheckDates === true || $t('startBeforeEndError')
                           ]">
                    <template v-slot:prepend>
                      <q-icon name="event" class="cursor-pointer">
                        <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                          <q-date v-model="startBooking" mask="DD/MM/YYYY HH:mm">
                            <div class="row items-center justify-end">
                              <q-btn v-close-popup :label="$t('return')" color="primary" flat />
                            </div>
                          </q-date>
                        </q-popup-proxy>
                      </q-icon>
                    </template>
                  </q-input>
                </div>
                <div class="text-h6 resaTitle">
                  {{$t('endDateTimeLabel')}}
                  <q-input outlined ref="endBooking" v-model="endBooking" label-color="primary" :label="$t('fromDateTime')" :rules="[
                              val => checkDateTimeFormat(val)|| $t('Required'),
                              val => dateStartEndCorrect(basketUser.startBooking, basketUser.endBooking) || $t('endAfterStartError')
                           ]">
                    <template v-slot:prepend>
                      <q-icon name="event" class="cursor-pointer">
                        <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                          <q-date v-model="endBooking" mask="DD/MM/YYYY HH:mm">
                            <div class="row items-center justify-end">
                              <q-btn v-close-popup :label="$t('return')" color="primary" flat />
                            </div>
                          </q-date>
                        </q-popup-proxy>
                      </q-icon>
                    </template>
                  </q-input>
                </div>
              </div>
              <div  v-if="!allowBookingMultipleDays">
                <div class="row justify-center">
                  <div class="col-5">
                    <div class="text-h6 text-center resaTitle">{{$t('startTime')}}</div>
                  </div>
                  <div class="col-5">
                    <div class="text-h6 text-center resaTitle">{{$t('endTime')}}</div>
                  </div>
                </div>
                <div class="row justify-center">
                  <div class="col-5">
                    <div class="row justify-center">
                      <div class="col-2 col-lg-2 col-sm-3">
                        <q-btn :size="loadSize" dense rounded color="primary" icon="remove" @click="merge(basketUser.selectionNStart, 'before', 'start')">
                          <q-tooltip>
                            {{$t('extendBookingBefore', { date: shortFormatDateApi(basketUser.startBooking) })}}
                          </q-tooltip>
                        </q-btn>
                      </div>
                      <div class="col-sm-2 col-xs-5 text-center">
                        <span class="text-h6 text-primary">{{dateSameDay(basketUser.endBooking, basketUser.startBooking)}}</span>
                      </div>
                      <div class="col-2 col-lg-2 col-sm-4 text-right">
                        <q-btn :size="loadSize" dense rounded color="primary" icon="add" @click="merge(basketUser.selectionNStart, 'after', 'start')">
                          <q-tooltip>
                            {{$t('extendBookingAfter', { date: shortFormatDateApi(basketUser.endBooking) })}}
                          </q-tooltip>
                        </q-btn>
                      </div>
                    </div>
                  </div>
                  <div class="col-5">
                    <div class="row justify-center">
                      <div class="col-2 col-lg-2 col-sm-3">
                        <q-btn :size="loadSize" dense rounded color="primary" icon="remove" @click="merge(basketUser.selectionNEnd, 'before', 'end')">
                          <q-tooltip>
                            {{$t('extendBookingBefore', { date: shortFormatDateApi(basketUser.startBooking) })}}
                          </q-tooltip>
                        </q-btn>
                      </div>
                      <div class="col-sm-2 col-xs-5 text-center">
                        <span class="text-h6 text-primary">{{dateSameDay(basketUser.startBooking, basketUser.endBooking)}}</span>
                      </div>
                      <div class="col-2 col-lg-2 col-sm-4 text-right">
                        <q-btn :size="loadSize" dense rounded color="primary" icon="add" @click="merge(basketUser.selectionNEnd, 'after', 'end')">
                          <q-tooltip>
                            {{$t('extendBookingAfter', { date: shortFormatDateApi(basketUser.endBooking) })}}
                          </q-tooltip>
                        </q-btn>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
                <q-input
                    v-if="!basketUser.selection.type.title.includes('Salle')"
                    outlined
                    v-model="basketUser.number"
                    type="number"
                    min="1"
                    :max="maxNumber > 0 ? maxNumber : null"
                    :rules="[
                        val => !!val || $t('Required'),
                        val => (val<=maxNumber || maxNumber === 0) || $t('quantityTooHigh', { max: maxNumber }),
                        val => val >= 1 || $t('quantityMinOne'),
                        val => val % 1 === 0 || $t('integerRequired') ]"
                    :label="$t('quantity')"
                    :style="getStyle"
                />
              <q-select
                    class="q-pt-xs q-px-none"
                    ref="searchParticipant"
                    id="searchParticipant"
                    v-model="basketUser.users"
                    outlined
                    use-input
                    fill-input
                    use-chips
                    multiple
                    input-debounce=200
                    :label="$t('addParticipantsToBooking')"
                    :options="options"
                    @add="addUser"
                    @filter="filter"
                    :rules="[
                        val => basketUser.bookingConditions.allowUsersToBook === true || $t('participantQuotaExceeded'),
                        val => (basketUser.bookingConditions.allowUsersToBookWithRules === true || storedUser.isUserAdminOrModeratorSite(basketUser.selection.service.id)) || $t('bookingConditionsNotMet')
                    ]"
                >
                    <template v-slot:no-option>
                        <q-item>
                            <q-item-section class="text-grey">
                                {{$t('noResultsFound')}}
                            </q-item-section>
                        </q-item>
                    </template>
                </q-select>
              <p class="text-primary q-mb-sm" v-if="getResourceRule('capacity')" style="font-size: 16px">{{$t('capacityRule')}}</p>
              <q-card-section v-if="basketUser.rules" class="q-py-none">
                <div class="row justify-around">
                  <q-btn class="q-ma-none" v-if="basketUser.bookingConditions.allowUsersToBook && basketUser.bookingConditions.allowUsersToBookWithRules && basketUser.bookingConditions.allowUsersCheckDates" icon="check" :label="$t('conditionsMet')" color="positive" @click="checkRulesDialog = true"/>
                  <q-btn v-else icon="warning" :label="$t('seeTheRules')" color="negative" text-color="white" @click="checkRulesDialog = true"/>
                  <booking-rules-informations-dialog :dialog="checkRulesDialog" @closeDialog="checkRulesDialog = false"></booking-rules-informations-dialog>
                </div>
              </q-card-section>
              <q-checkbox v-if="storedUser.isUserAdminOrModeratorSite(basketUser.selection.service.id)" :label="$t('bookForAnotherUserHint')" v-model="moderatorBooking" color="primary" />
              <q-input v-if="workflow.configuration !== undefined && workflow.configuration !== null && workflow.configuration.autoValidation === false" v-model="basketUser.title" dense outlined type="text" :label="$t('bookingTitleRequired')" style="margin-top: 10px" :rules="[val => !!val || $t('titleRequired'), val => (val ? val.length <= 100 : true) || $t('titleMaxLengthExceeded') ]" />
              <q-input v-else v-model="basketUser.title" dense outlined type="text" :label="$t('bookingTitleOptional')" style="margin-top: 10px" :rules="[ val => (val ? val.length <= 100 : true) || $t('titleMaxLengthExceeded') ]" />
              <div v-if="catalogOptions.length>0" class="q-pa-xs rounded-borders">
                  {{$t('catalogOptions')}}
                  <q-option-group
                      name="catalogOption"
                      v-model="selectedOptions"
                      :options="catalogOptions"
                      type="checkbox"
                      color="primary"
                      inline
                  />
                </div>
                <q-input v-model="basketUser.comment" dense outlined type="text" :label="$t('commentOptional')" style="margin-top: 10px"/>
            </q-card-section>
            <q-card-actions align="right" class="text-primary flex justify-between">
              <template v-if="editMode">
                <q-space/>
                <q-chip v-if="checkRules && (!basketUser.title || basketUser.title.length <= 100)" square text-color="white" color="primary" clickable :label="$t('common.save')" @click="updateBook" />
                <q-chip v-else square text-color="white" color="primary" clickable :label="$t('common.save')" disabled/>
              </template>
              <template v-else>
                <q-chip v-if="checkRules && (!basketUser.title || basketUser.title.length <= 100)" square outline text-color="white" color="primary" clickable :label="$t('addToSelection')" @click="saveBook" />
                <q-chip v-else square text-color="white" color="primary" clickable :label="$t('addToSelection')" disabled/>

                <q-chip v-if="checkRules && (!basketUser.title || basketUser.title.length <= 100)" square text-color="white" color="primary" clickable :label="$t('sendBookingRequest')" @click="saveBookAndSend" />
                <q-chip v-else square text-color="white" color="primary" clickable :label="$t('sendBookingRequest')" disabled/>
              </template>
            </q-card-actions>
        </q-card>
    </q-dialog>
</template>

<script>
import { basket } from '../../store/basket';
import {user} from "../../store/counter";
import {booking} from "../../store/booking";
import {
  checkDateTimeFormat,
  dateSameDay, dateStartEndCorrect,
  formatDateAPI,
  shortFormatDateApi,
  stringToDateTime
} from "../../utils/dateUtils";
import {searchInAllUser} from "../../api/User";
import {ref} from "vue";
import {
  checkEventDates, getParamFromEvents,
  mergeBookingsFromDialog
} from "../../utils/bookingUtils";
import {getActualParameterFromProvisions} from "../../utils/basketUtils";
import {date, Notify} from "quasar";
import {getBookingRestrictionForUser} from "../../api/Booking";
import bookingRulesInformationsDialog from "./bookingRulesInformationsDialog.vue";

const options = ref([]);
const catalogOptions = ref([]);
const selectedOptions = ref([]);
const loadSize = ref('md');
const allowBookingMultipleDays = ref(false);
const startBooking = ref(null);
const endBooking = ref(null);
const moderatorBooking = ref(false);
const workflow = ref(null);
const checkRulesDialog = ref(false);
const allowMultipleDay = ref(false);
export default {
    name: "bookInformationDialog",
  components: {bookingRulesInformationsDialog},
    props: {
        openDialog:Boolean,
        maxNumber:Number,
        editMode: {
          type: Boolean,
          default: false
        },
        bookToEdit: {
          type: Object,
          default: null
        },
        indexToEdit: {
          type: Number,
          default: null
        }
    },
    data() {
        const basketUser = basket();
        const storedUser = user();
        const bookingStore = booking();
        return {
          basketUser,
          storedUser,
          bookingStore,
          options,
          selectedOptions,
          catalogOptions,
          loadSize,
          allowBookingMultipleDays,
          startBooking,
          endBooking,
          checkEventDates,
          checkDateTimeFormat,
          moderatorBooking,
          prompt: false,
          workflow,
          checkRulesDialog,
          allowMultipleDay
        }
    },
    mounted() {
        this.basketUser.rules = null;
        this.prompt = this.openDialog;
        this.getCatalogOptions();
        if(this.$q.platform.is.mobile) {
          loadSize.value = 'sm';
        }
        let dateSelected =  date.extractDate(this.basketUser.start, 'DD/MM/YYYY');
        dateSelected.setHours(12);
        allowBookingMultipleDays.value = getParamFromEvents(this.bookingStore.events, date.formatDate(dateSelected, 'YYYY-MM-DD'), 'multipleBookingAllowed');
        workflow.value = getActualParameterFromProvisions(dateSelected, this.basketUser.selection.Provisions, 'workflow');
    },
    computed: {
      getStyle() {
        let style = '';
        if(this.basketUser.selection.view === 'Collection') {
          style = 'display: none';
        } else {
          style = 'display: block';
        }
        return style;
      },
      getUsers() {
        return this.basketUser.users;
      },
      checkRules() {
        if(workflow.value.configuration !== undefined && workflow.value.configuration !== null && workflow.value.configuration.autoValidation === false) {
          return this.basketUser.bookingConditions.allowUsersCheckDates && ((this.basketUser.bookingConditions.allowUsersToBook && this.basketUser.bookingConditions.allowUsersToBookWithRules && this.basketUser.title) || this.storedUser.isUserAdminOrModeratorSite(this.basketUser.selection.service.id));
        } else {
          return this.basketUser.bookingConditions.allowUsersCheckDates && ((this.basketUser.bookingConditions.allowUsersToBook && this.basketUser.bookingConditions.allowUsersToBookWithRules) || this.storedUser.isUserAdminOrModeratorSite(this.basketUser.selection.service.id));
        }
      },
      checkIfModeratorBookingAndUsers() {
        return (moderatorBooking.value === true && this.basketUser.users.length > 0) || moderatorBooking.value === false;
      },
      getConditions() {
        return this.basketUser.bookingConditions;
      }
    },
    methods : {
      dateStartEndCorrect,
      shortFormatDateApi,
      stringToDateTime,
      mergeBookingsFromDialog,
      dateSameDay,
      formatDateAPI,
        addUser() {
          this.$refs.searchParticipant.updateInputValue('')
        },
        saveBook() {
            if(this.maxNumber === 0 || this.basketUser.number <= this.maxNumber) {
              let resourceId = null;
              if(this.basketUser.resourceId !== undefined) {
                resourceId = this.basketUser.resourceId;
              }
              if(this.basketUser.startBooking.length<17) {
                this.basketUser.startBooking+=':00';
                this.basketUser.endBooking+=':00';
              }
              this.basketUser.cart.push({
                catalogue: {...this.basketUser.selection},
                dateStart: this.basketUser.startBooking,
                dateEnd: this.basketUser.endBooking,
                comment: this.basketUser.comment,
                number: this.basketUser.number,
                title: this.basketUser.title,
                optionsSelected: this.selectedOptions,
                actuatorProfile: this.basketUser.selection.actuator,
                moderatorBooking: this.moderatorBooking,
                resourceId: resourceId,
                users: JSON.stringify(this.basketUser.users)
              });
                moderatorBooking.value = false;
                this.basketUser.comment = null;
                this.basketUser.users = [];
                this.basketUser.number = 1;
                this.storedUser.leftDrawer = true;
                this.basketUser.title = null;
                selectedOptions.value = [];
                this.close();
            }
        },
      saveBookAndSend() {
        this.saveBook();
        this.basketUser.sendBookings = true;
      },
      updateBook() {
        if(this.maxNumber === 0 || this.basketUser.number <= this.maxNumber) {
          let resourceId = null;
          if(this.basketUser.resourceId !== undefined) {
            resourceId = this.basketUser.resourceId;
          }
          if(this.basketUser.startBooking.length<17) {
            this.basketUser.startBooking+=':00';
            this.basketUser.endBooking+=':00';
          }
          this.basketUser.cart[this.indexToEdit] = {
            catalogue: {...this.basketUser.selection},
            dateStart: this.basketUser.startBooking,
            dateEnd: this.basketUser.endBooking,
            comment: this.basketUser.comment,
            number: this.basketUser.number,
            title: this.basketUser.title,
            optionsSelected: this.selectedOptions,
            actuatorProfile: this.basketUser.selection.actuator,
            moderatorBooking: this.moderatorBooking,
            resourceId: resourceId,
            users: JSON.stringify(this.basketUser.users)
          };
          moderatorBooking.value = false;
          this.basketUser.comment = null;
          this.basketUser.users = [];
          this.basketUser.number = 1;
          this.basketUser.title = null;
          selectedOptions.value = [];
          this.close();
        }
      },
      getResourceRule(rule) {
        let resourceContainRule = false
        this.basketUser.selection.resource.forEach(function (res) {
          if(res.customFieldResources !== undefined) {
            res.customFieldResources.forEach(function (field) {
              if(field.CustomField.title === rule) {
                resourceContainRule = true;
              }
            })
          }
        })
        return resourceContainRule;
      },
        close() {
            this.basketUser.startBooking = null;
            this.basketUser.endBooking = null;
            this.basketUser.number = 1;
            this.basketUser.title = "";
            selectedOptions.value = [];
            this.$emit('close');
        },
        filter(val, update) {
            update(() => {
                if(val === '') {
                    options.value = []
                } else {
                    if (val.length > 1) {
                        searchInAllUser(val).then(function (response) {
                          options.value = []
                          response.data.forEach(user => options.value.push(user));
                        })
                    }
                }
            })
        },
      getCatalogOptions() {
        catalogOptions.value = [];
        this.basketUser.selection.customFields.forEach(function (field) {
          catalogOptions.value.push({
           'value':field.id,
            'label': field.title
          })
        })
      },
      checkUsersQuota() {
        let self = this;
        if(!this.storedUser.isUserAdminOrModerator()) {
          getBookingRestrictionForUser('check', this.storedUser.username, self.basketUser.selection.id, self.basketUser.resourceId, self.basketUser.startBooking, self.basketUser.endBooking, this.basketUser.users, this.basketUser.number).then(() => {
            self.basketUser.bookingConditions.allowUsersToBookWithRules = true;
            self.basketUser.bookingConditions.allowUsersToBook = true;
            self.$refs.searchParticipant?.validate();
          }).catch((error) => {
            let message = "";
            if(error.response?.data.rules !== undefined) {
              self.basketUser.rules = error.response.data.rules;
              message += self.$t('pleaseRespectBookingRules');
              Notify.create({
                type: 'negative',
                message: message,
                position: 'top',
              });
              self.basketUser.bookingConditions.allowUsersToBookWithRules = false;
            } else {
              self.basketUser.bookingConditions.allowUsersToBookWithRules = true;
            }
            if(error.response?.data.restrictions !== undefined) {
              if(error.response?.data.restrictions instanceof Array) {
                error.response.data.restrictions.forEach(function (restriction) {
                  Notify.create({
                    type: 'negative',
                    message: restriction,
                    position: 'top',
                  });
                });
              } else {
                Notify.create({
                  type: 'negative',
                  message: error.response.data.restrictions,
                  position: 'top',
                });
              }
              self.basketUser.bookingConditions.allowUsersToBook = false;
            } else {
              self.basketUser.bookingConditions.allowUsersToBook = true;
            }
            self.$refs.searchParticipant?.validate();
          })
        }
      },
      merge(n, mode, type) {
        mergeBookingsFromDialog(n, mode, type);
        this.updateRuleSelect();
      },
      updateRuleSelect() {
        this.$refs.searchParticipant?.validate();
      },
      checkInputDates(init = false){
        checkEventDates(this.basketUser.startBooking, this.basketUser.endBooking, null, init).then((res) => {
          if(res === true) {
            this.basketUser.bookingConditions.allowUsersToBook = true;
          } else {
            this.basketUser.bookingConditions.allowUsersToBook = false;
          }
        });
        if(dateStartEndCorrect(this.basketUser.startBooking, this.basketUser.endBooking)) {
          this.basketUser.bookingConditions.allowUsersCheckDates = true;
        } else {
          this.basketUser.bookingConditions.allowUsersCheckDates = false;
        }
        if(this.$refs.startBooking !== undefined && this.$refs.startBooking !== null) {
            this.$refs.startBooking.validate();
            this.$refs.endBooking.validate();
        }

        this.updateRuleSelect();
      },
    },
    watch: {
        openDialog: function () {
          this.basketUser.rules= null;
          this.prompt = this.openDialog;

          // Mode édition : charger les données de la réservation existante
          if(this.openDialog === true && this.editMode && this.bookToEdit) {
            this.basketUser.selection = this.bookToEdit.catalogue;
            this.basketUser.startBooking = this.bookToEdit.dateStart;
            this.basketUser.endBooking = this.bookToEdit.dateEnd;
            this.basketUser.comment = this.bookToEdit.comment;
            this.basketUser.number = this.bookToEdit.number;
            this.basketUser.title = this.bookToEdit.title;
            this.basketUser.resourceId = this.bookToEdit.resourceId;
            this.basketUser.users = this.bookToEdit.users ? JSON.parse(this.bookToEdit.users) : [];
            selectedOptions.value = this.bookToEdit.optionsSelected || [];
            moderatorBooking.value = this.bookToEdit.moderatorBooking || false;
            this.getCatalogOptions();
          }

          let dateSelected =  date.extractDate(this.basketUser.start, 'DD/MM/YYYY');
          allowBookingMultipleDays.value = getParamFromEvents(this.bookingStore.events, date.formatDate(dateSelected, 'YYYY-MM-DD'), 'multipleBookingAllowed');
          if(this.openDialog === true)
             this.checkInputDates(true);
          if(this.openDialog === true && allowBookingMultipleDays.value === true) {
            let dateStart = date.extractDate(this.basketUser.startBooking, 'YYYY-M-D HH:mm:ss');
            startBooking.value = date.formatDate(dateStart, 'DD/MM/YYYY HH:mm');
            dateStart = date.addToDate(dateStart, {minutes: this.bookingStore.configuration.interval})
            endBooking.value = date.formatDate(dateStart, 'DD/MM/YYYY HH:mm');
            workflow.value = getActualParameterFromProvisions(dateStart, this.basketUser.selection.Provisions, 'workflow');
          }
        },
        getUsers: function () {
          if(this.basketUser.startBooking !== null)
            this.checkUsersQuota();
        },
        startBooking: function () {
          let dateStart = date.extractDate(startBooking.value, 'DD/MM/YYYY HH:mm');
          this.basketUser.startBooking = date.formatDate(dateStart, 'YYYY-MM-DD HH:mm:ss');
          this.checkInputDates();
          workflow.value = getActualParameterFromProvisions(dateStart, this.basketUser.selection.Provisions, 'workflow');
        },
        endBooking: function () {
          let dateEnd = date.extractDate(endBooking.value, 'DD/MM/YYYY HH:mm');
          this.basketUser.endBooking = date.formatDate(dateEnd, 'YYYY-MM-DD HH:mm:ss');
          this.checkInputDates();
        },
    }
}
</script>

<style scoped>
  .addBookingDialog {
    min-width: 50%;
  }

  @media (max-width: 700px) {
    .resaTitle {
      font-size: 12px!important;
    }
  }

</style>