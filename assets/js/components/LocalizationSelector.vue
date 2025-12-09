<script setup>
import {onMounted, ref, watch} from "vue";

const localizationSelected = ref(null);
const localizationOptions = ref([]);

const props = defineProps({
  selected: Array|null,
  options: Array
})

const emit = defineEmits(['selected'])

function filterFn (val, update) {
  if (val === '') {
    update(() => {
      localizationOptions.value = props.options;
      // here you have access to "ref" which
      // is the Vue reference of the QSelect
    })
    return
  }
  update(() => {
    const needle = val.toLowerCase()
    localizationOptions.value = props.options.filter(v => v.title.toLowerCase().indexOf(needle) > -1)
  })
}

function getMarginFromLevel(level) {
  return 'margin-left:' + 20 * level + 'px;';
}

onMounted(() => {
  localizationSelected.value = props.selected;
  localizationOptions.value = props.options;
})

watch(localizationSelected, () => {
  emit('selected', localizationSelected.value);
})


</script>

<template>
  <q-select outlined v-model="localizationSelected" :options="localizationOptions" use-input use-chips input-debounce="0" @filter="filterFn" label-color="primary" option-label="title" option-value="id" :label="$t('selectFilterByService')">
    <template v-slot:option="scope">
      <q-item v-bind="scope.itemProps">
        <q-item-section>
          <q-item-label><q-icon name="subdirectory_arrow_right" :style="getMarginFromLevel(scope.opt.level)" />&nbsp;{{ scope.opt.title }}</q-item-label>
        </q-item-section>
      </q-item>
    </template>
    <template v-slot:no-option>
      <q-item>
        <q-item-section class="text-grey">
          No results
        </q-item-section>
      </q-item>
    </template>
  </q-select>
</template>

<style scoped>

</style>