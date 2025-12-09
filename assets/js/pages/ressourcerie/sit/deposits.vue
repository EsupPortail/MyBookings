<template>
  <q-card bordered class="my-card">
    <q-card-section>
      <div class="row justify-between">
        <div class="col-3">
          <q-select square outlined clearable label="Rechercher par status" v-model="statusFilter" option-label="label" option-value="value" :options="optionsStatus"></q-select>
        </div>
      </div>
      <custom-table-catalog :catalogues />
    </q-card-section>
  </q-card>
</template>

<script setup>
import CustomTableCatalog from "../../../components/table/ressourcerie/customTableCatalog.vue";
import {onMounted, ref, watch} from "vue";
import {getEffectsByStatus} from "../../../api/CatalogRessource";

const catalogues = ref([]);
const statusFilter = ref({label: 'Publiés', value: 'rc_published'});
const optionsStatus= [
  {label: 'Publiés', value: 'rc_published'},
  {label: 'En attente de modération par l\'entité', value: 'rc_pending'},
  {label: 'A valider SIT', value: 'rc_validated'},
  {label: 'Refusés', value: 'rc_disabled'},
];

async function getCatalogues(status) {
  getEffectsByStatus(status?.value).then((response) => {
    catalogues.value = response.data;
  });
}

onMounted(() => {
  getCatalogues(statusFilter.value);
})

watch(() => statusFilter.value, (newStatus) => {
  getCatalogues(newStatus);
});

</script>