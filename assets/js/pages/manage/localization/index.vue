<template>

  <q-dialog v-model="confirmDelete" persistent>
    <q-card>
      <q-card-section class="q-pt-lg">
        <div class="text-h6">{{ $t('localizations.deleteConfirmation') }} <b>{{ selectedTree.title }}</b> ?</div>
      </q-card-section>
      <q-card-actions align="right">
        <q-btn flat :label="$t('common.cancel')" color="dark" v-close-popup />
        <q-btn flat :label="$t('common.delete')" color="negative" @click="deleteDialog" />
      </q-card-actions>
    </q-card>
  </q-dialog>

  <create-localization-dialog :open="openDialog" :localization="tree" @close="openDialog = false" @closeAndRefresh="closeDialogAndRefresh"></create-localization-dialog>

  <q-card bordered class="my-card">
    <q-card-section>
      <div class="row">
        <div class="col">
          <div class="text-h4">{{ $t('localizations.management') }}</div>
        </div>
        <div class="col-auto">
          <q-btn round color="primary" icon="add" @click="openDialog = true">
            <q-tooltip>
              {{ $t('localizations.add') }}
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
                children-key="childs"
                node-key="id"
                label-key="title"
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
                  <div class="text-h4">{{ $t('localizations.selection') }}</div>
                </div>
                <div class="col-auto">
                  <q-btn round color="negative" icon="delete" @click="confirmDelete = true">
                    <q-tooltip>
                      {{ $t('localizations.delete') }}
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
                  {{ $t('localizations.edit') }}
                  <q-popup-edit v-model="selectedTree.title" v-slot="scope" style="display: inline-block" ref="popupRef">
                    <q-input v-model="scope.value" dense autofocus style="width: 92%; display: inline-block"/>
                    <q-btn color="primary" icon="save" size="0.7rem" class="absolute-right" @click="save(scope.value, 'title')"></q-btn>
                  </q-popup-edit>
                </div>
              </div>
              <p>{{ selectedTree.title }}</p>
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
              {{ $t('localizations.selectLocalization') }}
            </div>
          </div>
        </template>
      </q-splitter>
    </q-card-section>
  </q-card>
</template>
œ
<script setup>
import { ref, onMounted } from 'vue';
import CreateLocalizationDialog from "../../../components/dialog/createLocalizationDialog.vue";
import {getParentLocalizations, getLocalization, removeLocalization, updateLocalization} from "../../../api/Localization";

const tree = ref([]);
const selectedTree = ref({
  parent: {
    title: ''
  }
});
const selected = ref(null);
const splitterModel = ref(50);
const openDialog = ref(false);
const confirmDelete = ref(false);

const getTree = () => {
  getParentLocalizations().then((response) => {
    tree.value = response.data;
  });
};

const getNode = (selected) => {
  if (selected !== null) {
    getLocalization(selected).then((response) => {
      selectedTree.value = response.data;
    });
  } else {
    selectedTree.value = {
      parent: {
        title: ''
      }
    };
  }
};

const closeDialogAndRefresh = () => {
  openDialog.value = false;
  getTree();
};

const deleteDialog = () => {
  removeLocalization(selectedTree.value.id).then(() => {
    confirmDelete.value = false;
    getTree();
    selectedTree.value = {
      parent: {
        title: ''
      }
    };
  });
};

const save = (value, field) => {
  changeValue(value, field);
  delete selectedTree.value.childs;
  updateLocalization(selectedTree.value).then(() => {
    getTree();
  });
};

const changeValue = (value, field) => {
  switch (field) {
    case 'title':
      selectedTree.value.title = value;
      break;
  }
};

onMounted(() => {
  getTree();
});
</script>

<style scoped>

</style>