<template>
  <book-information-dialog
    :open-dialog="openEditDialog"
    :edit-mode="true"
    :book-to-edit="bookToEdit"
    :index-to-edit="indexToEdit"
    :max-number="getMaxNumber()"
    @close="openEditDialog = false"
  ></book-information-dialog>
  <q-scroll-area style="height: 800px">
    <q-intersection
        v-for="(book, index) in listOfBookings"
        :key="index"
        once
        transition="fade"
    >
        <q-item v-if="book.catalogue.type !== undefined" clickable v-ripple class="section_book q-mb-xs q-pa-none" tabindex="0" @click="openDrawer(book)">
          <q-item-section v-if="deleteObject" side class="q-pa-none">
            <div class="column q-gutter-xs q-pa-xs">
              <q-btn
                round
                dense
                color="negative"
                icon="delete"
                size="md"
                @click.stop="deleteFromStore(index)"
              >
                <q-tooltip>Supprimer</q-tooltip>
              </q-btn>
              <q-btn
                round
                dense
                color="primary"
                icon="edit"
                size="md"
                @click.stop="editBook(book, index)"
              >
                <q-tooltip>Modifier</q-tooltip>
              </q-btn>
            </div>
          </q-item-section>
          <q-item-section>
            <q-img
                img-class="img-selection"
                loading="lazy"
                :src="'/uploads/'+setImg(book)"
                fit="cover"
                height="100px"
                :alt="book.catalogue.title"
            >
              <template v-slot:error>
                <div class="absolute-full flex flex-center bg-dark-grey text-white">
                  <div class="absolute-full text-subtitle2 flex flex-center">
                    {{ book.catalogue.type.title }} / {{ book.catalogue.subType.title }}
                  </div>
                  <div class="absolute-top text-subtitle2 selection-subtitle">
                    <q-icon name="schedule" style="margin-bottom: 3px"></q-icon>
                    {{ shortFormatDatefromAPItoString(book.dateStart) }}
                    -
                    {{dateSameDay(book.dateStart, book.dateEnd, 'UTC')}}
                  </div>
                  <div v-if="book.resourceId !== null || book.Resource !== undefined" class="absolute-bottom-left text-subtitle2 selection-subtitle backgroundTextBlur">
                    <span style="padding: 3px">{{ getSelectedResource(book) }}</span>
                  </div>
                  <div class="absolute-bottom-right text-subtitle2 selection-subtitle">
                    <q-icon name="pin_drop" style="margin-bottom: 3px"></q-icon>
                    {{ book.catalogue.service.title }}
                  </div>
                </div>
              </template>
              <div class="absolute-full text-subtitle2 flex flex-center">
                {{ book.catalogue.type.title }} / {{ book.catalogue.subType.title }}
              </div>
              <div v-if="deleteObject === false" class="absolute-top-right text-subtitle2 selection-subtitle">
                <q-chip square dense color="red" text-color="white" v-if="book.status === 'pending'">Attente de confirmation</q-chip>
                <q-chip square dense color="positive" text-color="white" v-if="book.status === 'accepted'">Confirmé</q-chip>
                <q-chip square dense color="primary" text-color="white" v-if="book.status === 'progress'">En cours</q-chip>
                <q-chip square dense color="grey" text-color="white" v-if="book.status === 'refused'">Refusée</q-chip>
                <q-chip square dense color="primary" text-color="white" v-if="book.status === 'returned'">Clôturée</q-chip>
              </div>
              <div class="absolute-top-left text-subtitle2 selection-subtitle backgroundTextBlur">
                <q-icon name="schedule" style="margin-bottom: 3px"></q-icon>
                {{ shortFormatDatefromAPItoString(book.dateStart) }}
                -
                {{dateSameDay(book.dateStart, book.dateEnd, 'UTC')}}
              </div>
              <div v-if="book.resourceId !== null || book.Resource !== undefined" class="absolute-bottom-left text-subtitle2 selection-subtitle backgroundTextBlur">
                <span style="padding: 3px">{{ getSelectedResource(book) }}</span>
              </div>
              <div class="absolute-bottom-right text-subtitle2 selection-subtitle backgroundTextBlur">
                <q-icon name="pin_drop" style="margin-bottom: 3px"></q-icon>
                {{ book.catalogue.service.title }}
              </div>
            </q-img>
          </q-item-section>
        </q-item>
    </q-intersection>
  </q-scroll-area>
</template>

<script>
import {
  dateSameDay,
  formatDateAPI,
  formatDatefromAPItoString, shortFormatDateApi,
  shortFormatDatefromAPItoString
} from "../utils/dateUtils";
import bookInformationDialog from "./dialog/bookInformationDialog.vue";
import { basket } from "../store/basket";

export default {
  name: "selectionBookingList",
  emits: ["openDrawer"],
  components: {bookInformationDialog},
  props: {
    basketUser: Object,
    deleteObject: Boolean,
  },
  data() {
    return {
      listOfBookings: [],
      openEditDialog: false,
      bookToEdit: null,
      indexToEdit: null,
      basketStore: basket()
    }
  },
  mounted() {
    this.listOfBookings = this.basketUser;
    if(this.listOfBookings.length>0) {
      this.reorganizeData();
    }
  },
  methods: {
    shortFormatDateApi,
    shortFormatDatefromAPItoString,
    formatDatefromAPItoString,
    formatDateAPI,
    dateSameDay,
    deleteBook(index) {
      this.basketUser.splice(index, 1);
    },
    deleteFromStore(index) {
      this.basketStore.removeFromCart(index);
    },
    editBook(book, index) {
      this.bookToEdit=book;
      this.indexToEdit=index;
      this.openEditDialog = true;
    },
    getMaxNumber() {
      if(this.bookToEdit && this.bookToEdit.catalogue && this.bookToEdit.catalogue.quantity) {
        return this.bookToEdit.catalogue.quantity;
      }
      return 0;
    },
    reorganizeData() {
      this.listOfBookings.forEach(function (element) {
        if(element.catalogue === undefined) {
          element.catalogue = element.catalogueResource;
        }
      })
    },
    openDrawer(book) {
      this.$emit('openDrawer', book);
    },
    setImg(book) {
      let img = book.catalogue.image
      if(book.resourceId !== undefined && book.resourceId !== null) {
        book.catalogue.resource.forEach(function (resource) {
          if(resource.id == book.resourceId) {
            if(resource.image !== null) {
              img = resource.image;
            }
          }
        })
      }
      if(book.Resource !== undefined) {
        book.Resource.forEach(function (resource) {
          if(resource.image !== null) {
            img = resource.image
          }
        });
      }
      return img;
    },
    getSelectedResource(book) {
      let resourceName = "";
      if(book.Resource === undefined) {
        book.catalogue.resource.forEach(function (resource) {
          if(resource.id == book.resourceId) {
            resourceName = resource.title;
          }
        });
      } else {
        book.Resource.forEach(function (resource, index) {
          if (index === book.Resource.length - 1) {
            resourceName += resource.title;
          } else {
            resourceName += resource.title + ',';
          }
        });
      }
      return resourceName;
    }
  },
  watch:{
    basketUser: function () {
      if(this.basketUser.length > 0) {
        this.listOfBookings = this.basketUser;
        this.reorganizeData();
      }
    }
  }
}
</script>

<style scoped>
.selection-subtitle {
  /* .q-chip { z-index: 9; } */
  background: none !important;
  padding: 0 !important;
  font-size: 0.7rem !important;
}

.selection-subtitle .q-chip {
  z-index: 9;
}

.backgroundTextBlur {
  -webkit-backdrop-filter: blur(2px);
  backdrop-filter: blur(2px);
  background-color: rgba(0, 0, 0, 0.26) !important;
}

.img-selection {
  transition: 0.3s;
}

.img-selection:hover {
  transform: scale(1.1);
}

.action_buttons {
  opacity: 0;
  background: rgba(0, 0, 0, 0) !important;
  -webkit-backdrop-filter: blur(2px);
  backdrop-filter: blur(2px);
}

.action_buttons .deleteBookingButton {
  margin-left: 0;
  transition: all 0.5s ease-in-out;
}

.action_buttons .editBookingButton {
  margin-right: 0;
  transition: all 0.5s ease-in-out;
}

.section_book:hover .action_buttons {
  opacity: 100%;
}

.section_book:hover .action_buttons .deleteBookingButton {
  margin-left: 30%;
}

.section_book:hover .action_buttons .editBookingButton {
  margin-right: 30%;
}

@media (max-width: 500px) {
  .q-chip {
    font-size: 0.7rem !important;
  }
}
</style>