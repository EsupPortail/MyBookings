<template>
  <q-dialog v-model="dialog">
    <q-card style="min-width: 500px" class="q-pa-sm">
      <p class="text-h6 q-pa-sm">{{ $t('localizations.browse') }}</p>
      <q-separator/>
      <q-card-section class="q-pa-sm">
        <q-tree
            :nodes="treeData"
            v-model:selected="selectedNode"
            node-key="id"
            label-key="title"
            children-key="childs"
            control-color="primary"
        />
      </q-card-section>
      <q-card-actions align="right">
        <q-btn flat dense :label="$t('common.cancel')" color="primary" @click="dialog = false" />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script>
import {defineComponent, ref, watch} from 'vue';
import {getLocalization} from "../../api/Localization";

export default defineComponent({
  name: 'TreeSelector',
  props: {
    modelValue: Object,
    treeData: Array
  },
  emits: ['update:modelValue'],
  setup(props, { emit }) {
    const dialog = ref(false);
    const selectedNode = ref(null);

    const selectNode = () => {
      dialog.value = false;
      emit('update:modelValue', selectedNode.value);
    };

    watch(() => selectedNode.value, (newVal) => {
      if(typeof selectedNode.value === 'number') {
        getLocalization(selectedNode.value).then((response) => {
          selectedNode.value = response.data;
          selectNode();
        })
      }
    });

    return {
      dialog,
      selectedNode,
      selectNode
    };
  }
});
</script>
