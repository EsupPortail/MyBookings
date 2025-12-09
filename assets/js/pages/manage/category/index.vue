<template>

  <q-dialog v-model="confirmDelete" persistent>
    <q-card>
      <q-card-section class="q-pt-lg">
        <div class="text-h6">{{ $t('categories.deleteConfirmation') }} <b>{{ selectedTree.title }}</b> ?</div>
      </q-card-section>
      <q-card-actions align="right">
        <q-btn flat :label="$t('common.cancel')" color="dark" v-close-popup />
        <q-btn flat :label="$t('common.delete')" color="negative" @click="deleteDialog" />
      </q-card-actions>
    </q-card>
  </q-dialog>

  <create-category-dialog :open="openDialog" :category="tree" @close="openDialog = false" @closeAndRefreesh="closeDialogAndRefresh"></create-category-dialog>

  <q-card bordered class="my-card">
    <q-card-section>
      <div class="row">
        <div class="col">
          <div class="text-h4">{{ $t('categories.management') }}</div>
        </div>
        <div class="col-auto">
          <q-btn round color="primary" icon="add" @click="openDialog = true">
            <q-tooltip>
              {{ $t('categories.add') }}
            </q-tooltip>
          </q-btn>
        </div>
      </div>
    </q-card-section>
    <q-separator inset />
    <q-card-section>
      <q-splitter
          v-model="splitterModel"
          style="height: 400px"
      >
        <template v-slot:before>
          <div class="q-pa-md q-gutter-sm">
            <q-tree
                :nodes="tree"
                node-key="id"
                label-key="label"
                v-model:selected="selected"
                @update:selected="getNode"
                default-expand-all
            />
          </div>
        </template>
        <template v-slot:after>
          <div v-if="selectedTree.parent !== undefined && selectedTree.title !== undefined">
            <q-card-section>
              <div class="row">
                <div class="col">
                  <div class="text-h4">{{ $t('categories.selection') }}</div>
                </div>
                <div class="col-auto">
                  <q-btn round color="negative" icon="delete" @click="confirmDelete = true">
                    <q-tooltip>
                      {{ $t('categories.delete') }}
                    </q-tooltip>
                  </q-btn>
                </div>
              </div>
            </q-card-section>
            <q-separator/>
            <q-card-section>
              <div class="text-h5 q-mb-md">
                <div class="cursor-pointer" style="margin: auto; overflow-wrap: break-word">
                  <q-btn
                      icon="edit"
                      size="sm"
                      round
                      color="primary"
                  />
                  {{ $t('title') }}
                  <q-popup-edit v-model="selectedTree.title" v-slot="scope" style="display: inline-block" ref="popupRef" @hide="hidePopup">
                    <q-input v-model="scope.value" dense autofocus style="width: 92%; display: inline-block"/>
                    <q-btn color="primary" icon="save" size="0.7rem" class="absolute-right" @click="save(scope.value, 'title')"></q-btn>
                  </q-popup-edit>
                </div>
              </div>
              <p>{{ selectedTree.title }}</p>
              <div class="text-h5 q-mb-md">
                <div class="cursor-pointer" style="margin: auto; overflow-wrap: break-word">
                  <q-btn
                      icon="edit"
                      size="sm"
                      round
                      color="primary"
                  />
                    {{ $t('categories.view') }}
                  <q-popup-edit v-model="selectedTree.view" v-slot="scope" style="display: inline-block" ref="popupRefView" @hide="hidePopup">
                    <q-select
                        v-model="scope.value"
                        :label="$t('categories.catalogView')"
                        :options="viewOptions"
                    >
                    </q-select>
                    <q-btn color="primary" icon="save" size="0.7rem" class="absolute-right" @click="save(scope.value, 'view')"></q-btn>
                  </q-popup-edit>
                </div>
              </div>
              <p>{{ selectedTree.view }}</p>
              <div>
                <div class="text-h5 q-mb-md">
                  {{ $t('localizations.parent') }}
                </div>
                <div v-if="selectedTree.parent !== null">
                  <p>
                    {{selectedTree.parent.title}}
                  </p>
                </div>
                <div v-else>{{ $t('localizations.none') }}</div>
              </div>
            </q-card-section>
          </div>
          <div v-else>
            <div class="absolute-full flex flex-center">
              {{ $t('categories.selectCategory') }}
            </div>
          </div>
        </template>
      </q-splitter>
    </q-card-section>
  </q-card>
</template>

<script>
import { ref } from 'vue';
import CreateCategoryDialog from "../../../components/dialog/createCategoryDialog.vue";
import { getCategory, getAllCategories, updateCategory, removeCategory } from "../../../api/Category";

const tree = ref([])
const selectedTree = ref({
  parent: {
    title: ''
  }
})

export default {
  name: "index.vue",
  components: {
    CreateCategoryDialog
  },
  data() {
    return {
      tree,
      selectedTree,
      selected: null,
      splitterModel: ref(50),
      openDialog: false,
      confirmDelete: false,
      viewOptions: [],
      hidePopup: false
    }
  },
  mounted() {
    this.initializeViewOptions();
    this.getTree();
  },
  methods: {
    initializeViewOptions() {
      this.viewOptions = [
        this.$t('categories.lot'),
        this.$t('categories.collection')
      ];
    },
    getTree() {
      getAllCategories().then(function (response) {
        tree.value = response.data;
      });
    },

    getNode(selected) {
      if(selected !== null) {
        getCategory(selected).then(function (response) {
          selectedTree.value = response.data;
        });
      } else {
        selectedTree.value = [];
      }
    },

    closeDialogAndRefresh() {
      this.openDialog = false;
      this.getTree();
    },

    deleteDialog() {
      removeCategory(selectedTree.value.id).then(() => {
        this.confirmDelete = false
        this.getTree()
        selectedTree.value =  {
          parent: {
            title: ''
          }
        }
      })
    },

    save(value, field) {
      this.changeValue(value, field);
      let self = this;
      delete selectedTree.value.enfants;
      delete selectedTree.value.parent;
      this.hidePopup = false
      updateCategory(selectedTree.value).then(() => {
        self.hidePopup = true;
        self.getTree();
      })
    },

    changeValue(value, field) {
      switch (field) {
        case 'view':
          selectedTree.value.view = value;
          break;
        case 'title':
          selectedTree.value.title = value;
          break;
      }
    },
  }
}
</script>

<style scoped>

</style>