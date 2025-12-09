<script setup>

import {ref} from "vue";
import {useRouter} from "vue-router";

const router = useRouter();
const props = defineProps({
  catalogues: {
    type: Array,
    required: true
  }
})

const columns = [
  {name: 'image', align: 'center', label: '', field: 'image', sortable: true},
  {name: 'title', align: 'center', label: 'Nom', field: 'title', sortable: true},
  {name: 'type', align: 'center', label: 'Type', field: row => row.type.title, sortable: true},
  {name: 'nb', align: 'center', label: 'Nombre de biens', field: row => row.resource.length, sortable: true},
  {name: 'action', align: 'center', label: 'Actions', field: 'action'}
]

const filter = ref('');

function redirectToCatalog(idCatalogue, idService) {
  router.push({
    name: 'editCatalogueRessourcerie',
    params: { idCatalogue: idCatalogue, id: idService }
  });
}
</script>

<template>
  <q-table
      :rows="catalogues"
      :columns="columns"
      row-key="title"
      :filter="filter"
      :rows-per-page-options="[20, 50, 100, 0]"
      flat
  >
    <template v-slot:top-right>
      <q-input dense v-model="filter" placeholder="Rechercher">
        <template v-slot:append>
          <q-icon name="search" />
        </template>
      </q-input>
    </template>
    <template v-slot:body="props">
      <q-tr :props="props">
        <q-td key="image" :props="props">
          <q-img :src="'/uploads/'+props.row.image">
            <template v-slot:error>
              <div class="absolute-full flex flex-center bg-grey text-white">
                Aucune image
              </div>
            </template>
          </q-img>
        </q-td>
        <q-td key="title" :props="props">
          {{props.row.title}}
        </q-td>
        <q-td key="type" :props="props">
          {{props.row.type.title}}
        </q-td>
        <q-td key="nb" :props="props">
          {{props.row.resource.length}}
        </q-td>
        <q-td key="action" :props="props">
          <q-btn round color="primary" icon="visibility" @click="redirectToCatalog(props.row.id, props.row.service.id)">
            <q-tooltip>
              Voir le catalogue
            </q-tooltip>
          </q-btn>
        </q-td>
      </q-tr>
    </template>
  </q-table>
</template>

<style scoped>

</style>