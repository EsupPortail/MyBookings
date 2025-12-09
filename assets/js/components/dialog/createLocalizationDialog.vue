<template>
  <q-dialog v-model="openDialog" persistent>
    <q-card style="width: 600px;">
      <q-card-section>
        <div class="text-h5">{{ $t('localizations.createNew') }}</div>
      </q-card-section>
      <q-separator/>
        <q-card-section>
        <q-input
            outlined
            v-model="newLocalization.title"
            :label="$t('title') + '*'"
            lazy-rules
            :rules="[ val => val && val.length > 0 || $t('externalUsers.requiredField')]"
        />
          <q-select
              v-model="newLocalization.parent"
              :options="options"
              option-value="id"
              option-label="title"
              label-color="primary"
              :label="$t('localizations.selectParent')"
          >
            <template #prepend>
              <q-icon
                  name="explore"
                  class="cursor-pointer"
                  @click="openTreeSelector"
              >
              </q-icon>
            </template>
          </q-select>
          <TreeSelector
              v-model="newLocalization.parent"
              :treeData="options"
              ref="treeSelector"
          />
      </q-card-section>
      <q-card-actions align="right" class="bg-white text-teal">
        <q-btn flat :label="$t('common.cancel')" @click="sendClose" />
        <q-btn :label="$t('workflows.send')" type="submit" color="primary" @click="onSubmit"/>
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import { createLocalization } from '../../api/Localization';
import TreeSelector from './TreeSelector.vue';
const options = ref([]);
const treeSelector = ref(null);
const openDialog = ref(false);
const newLocalization = ref({
  title: '',
  parent: []
});
const openTreeSelector = () => {
  if (treeSelector) {
    treeSelector.value.dialog = true;
  }
};
const props = defineProps({
  open: Boolean,
  localization: Array
});

const emit = defineEmits(['close', 'closeAndRefresh']);

const onSubmit = () => {
  let bodyFormData = new FormData();
  bodyFormData.append('title', newLocalization.value.title);
  if (newLocalization.value.parent.id !== undefined) {
    bodyFormData.append('parent', '/api/localizations/' + newLocalization.value.parent.id);
  }

  createLocalization(JSON.stringify(Object.fromEntries(bodyFormData))).then(() => {
    // On vide le formulaire et on reset les règles des champs
    resetForm();
    sendCloseAndRefreshList();
  });
};

const sendClose = () => {
  emit('close');
};

const sendCloseAndRefreshList = () => {
  emit('closeAndRefresh');
};

const resetForm = () => {
  newLocalization.value = {
    title: '',
    parent: []
  };
};

watch(() => props.open, (newVal) => {
  resetForm();
  openDialog.value = newVal;
});

watch(() => props.localization, (newVal) => {
  options.value = [...newVal];
});

onMounted(() => {
  openDialog.value = props.open;
});
</script>


<style scoped>

</style>