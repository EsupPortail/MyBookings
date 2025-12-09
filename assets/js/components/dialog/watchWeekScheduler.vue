<template>
  <q-dialog full-width v-model="dialog" @hide="sendClose">
    <q-card>
      <q-card-section class="q-pb-none q-pt-xs q-px-xs">
        <q-select outlined v-model="selectedResource" :options="props.resources" option-value="id" option-label="title" label="Ressource sélectionnée"/>
      </q-card-section>
      <q-card-section style="height: 700px">
        <SchedulerComponent class="left-container" :block="blockingDate" :key="isUpdate" :idResource="props.idResource" :site="props.idSite" />
      </q-card-section>
      <q-card-actions class="justify-end">
        <q-btn color="dark" @click="sendClose">Fermer</q-btn>
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { ref, watch, onMounted } from "vue";
import SchedulerComponent from "../scheduler/SchedulerComponent.vue";
import { basket } from "../../store/basket";

const props = defineProps({
  openDialog: Boolean,
  idResource: Number,
  idSite: Number,
  resources: Array
});
const emit = defineEmits(["close"]);

const dialog = ref(false);
const blockingDate = ref([]);
const isUpdate = ref(0);
const basketUser = basket();
const selectedResource = ref(null);

// Synchronisation du dialog avec la prop
watch(() => props.openDialog, (val) => {
  dialog.value = val;
});

// Synchronisation de la ressource sélectionnée avec le panier
watch(selectedResource, (val) => {
  basketUser.resourceId = val ? val.id : null;
});

watch(basketUser, (val) => {
  if (val.resourceId !== null && props.resources) {
    selectedResource.value = props.resources.find(r => r.id === basketUser.resourceId);
  }
});

// Initialisation de la ressource sélectionnée au montage
onMounted(() => {
  if (basketUser.resourceId !== null && props.resources) {
    selectedResource.value = props.resources.find(r => r.id === basketUser.resourceId);
  }
});

function sendClose() {
  dialog.value = false;
  emit("close", dialog.value);
}
</script>

<style scoped>

</style>