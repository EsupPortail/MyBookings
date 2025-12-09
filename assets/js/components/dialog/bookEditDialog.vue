<template>
    <q-dialog v-model="prompt" persistent>
        <q-card class="addBookingDialog">
            <q-card-section>
              <div class="text-h6">Modifier la réservation :
                <span class="text-h6 text-primary">{{formatDateAPI(bookEdit.dateStart)}}</span> - <span class="text-h6 text-primary">{{dateSameDay(bookEdit.dateStart, bookEdit.dateEnd)}}</span>
              </div>
            </q-card-section>
            <q-card-section class="q-pt-none">
                <q-input
                    v-if="!basketUser.selection.type.title.includes('Salle')"
                    outlined
                    v-model="bookEdit.number"
                    type="number"
                    min="1"
                    :max="maxNumber"
                    :rules="[
                        val => !!val || 'Champ obligatoire',
                        val => val<=maxNumber || 'La quantité est trop élevée (maximum : '+maxNumber+')',
                        val => val >= 1 || 'La quantité doit être supérieure à 1',
                        val => val % 1 === 0 || 'Le nombre doit être un entier' ]"
                    label="Quantité"
                    :style="getStyle"
                />
                <q-select
                    ref="searchParticipant"
                    id="searchParticipant"
                    v-model="bookEdit.users"
                    outlined
                    use-input
                    use-chips
                    multiple
                    input-debounce=200
                    label="Ajouter des participants à la réservation"
                    :options="options"
                    @filter="filter"
                    @add="addUser"
                    new-value-mode="add-unique"
                >
                    <template v-slot:no-option>
                        <q-item>
                            <q-item-section class="text-grey">
                                Aucun résultat
                            </q-item-section>
                        </q-item>
                    </template>
                </q-select>
                <p class="text-primary" v-if="getResourceRule('capacity')">{{$t('capacityRule')}}</p>
                <div v-if="catalogOptions.length>0" class="q-pa-xs rounded-borders">
                  Options du catalogue:
                  <q-option-group
                      name="catalogOption"
                      v-model="bookEdit.optionsSelected"
                      :options="catalogOptions"
                      type="checkbox"
                      color="primary"
                      inline
                  />
                </div>
                <q-input v-model="bookEdit.comment" outlined type="text" label="Commentaire (optionnel)" style="margin-top: 10px"/>
            </q-card-section>
          <q-card-actions align="right" class="text-primary flex justify-between">
            <q-chip square text-color="white"  color="dark" clickable @click="close">Annuler</q-chip>
            <q-chip square text-color="white" color="primary" clickable label="Modifier" @click="saveBook" />
          </q-card-actions>
        </q-card>
    </q-dialog>
</template>

<script>
import {user} from "../../store/counter";
import { basket } from '../../store/basket';
import {dateSameDay, formatDateAPI} from "../../utils/dateUtils";
import {searchInAllUser} from "../../api/User";
import {ref} from "vue";


const options = ref([]);
const catalogOptions = ref([]);
const selectedOptions = ref([]);
export default {
    name: "bookInformationDialog",
    props: {
        openDialog:Boolean,
        book: Object,
        index: Number
    },
    data() {
        const storedUser = user();
        const basketUser = basket();
        return {
            bookEdit: {
              catalogue: null,
              number: null,
              comment: null,
              dateStart: null,
              dateEnd: null,
              users: null,
              resourceId: null,
            },
            maxNumber: 1,
            storedUser,
            basketUser,
            options,
            catalogOptions,
            selectedOptions,
            prompt: false,
        }
    },
    mounted() {
        this.prompt = this.openDialog;
        this.getCatalogOptions();
    },
    computed: {
      getStyle() {
        let style = '';
        if(this.bookEdit.catalogue.view === 'Collection') {
          style = 'display: none';
        } else {
          style = 'display: block';
        }
        return style;
      },
    },
    methods : {
      dateSameDay,
      formatDateAPI,
        addUser() {
          this.$refs.searchParticipant.updateInputValue('')
        },
        saveBook() {
          this.basketUser.cart[this.index].catalogue = this.bookEdit.catalogue;
          this.basketUser.cart[this.index].comment = this.bookEdit.comment;
          this.basketUser.cart[this.index].dateEnd = this.bookEdit.dateEnd;
          this.basketUser.cart[this.index].dateStart = this.bookEdit.dateStart
          this.basketUser.cart[this.index].number = this.bookEdit.number;
          this.basketUser.cart[this.index].resourceId = this.bookEdit.resourceId;
          this.basketUser.cart[this.index].users = JSON.stringify(this.bookEdit.users);
          this.basketUser.cart[this.index].optionsSelected = this.bookEdit.optionsSelected;
          this.close();
        },
        close() {
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
        if(this.basketUser.selection !== null) {
          this.basketUser.selection.customFields.forEach(function (field) {
            catalogOptions.value.push({
              'value':field.id,
              'label': field.title
            })
          })
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
    },
    watch: {
        openDialog: function () {
            this.prompt = this.openDialog;
            if(this.prompt === true) {
              this.bookEdit.catalogue = this.book.catalogue;
              this.bookEdit.comment = this.book.comment;
              this.bookEdit.dateEnd = this.book.dateEnd;
              this.bookEdit.dateStart = this.book.dateStart
              this.bookEdit.number = this.book.number;
              this.bookEdit.resourceId = this.book.resourceId;
              this.bookEdit.users = JSON.parse(this.book.users);
              this.bookEdit.optionsSelected = this.book.optionsSelected;
            }
        }
    }
}
</script>

<style scoped>

  .addBookingDialog {
    min-width: 50%;
  }

  @media (max-width: 500px) {
    width: 100%;
  }

</style>