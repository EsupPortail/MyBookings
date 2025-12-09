<template>
  <div class="q-pa-md">
    <div class="q-gutter-md row justify-center">
      <q-select clearable filled v-model="typeSelector" :options="typeOptions" option-label="title" option-value="id" label="Filtrer par type" class="col-2" @update:model-value="getCatalogues()" />
      <q-input v-model="search" filled type="search" label="Rechercher" class="col-3" @update:model-value="getCatalogues()" debounce="300">
        <!-- <template v-slot:append>
          <q-icon name="search" />
        </template> -->
      </q-input>
    </div>
  </div>

  <div class="q-pa-md row q-gutter-md flex-center">

    <q-card v-if="catalogues.length > 0" v-for="c in catalogues" class="shadow-3" style="cursor: pointer;" @click="openEffectsDialog(c)">
      <q-img :src="'/uploads/' + c.image" width="350px" :ratio="16/9">
        <template v-slot:error>
          <div class="absolute-full flex flex-center bg-grey text-white">
            <q-icon size="md" name="no_photography"/>
          </div>
          <div class="absolute-bottom">
            <div class="text-h6">{{ c.title }} <small><i>({{ c.remainingEffects.length }})</i></small></div>
            <div class="text-subtitle2">{{ c.type.title }}</div>
          </div>
        </template>
        <div class="absolute-bottom">
          <div class="text-h6">{{ c.title }} <small><i>({{ c.remainingEffects.length }})</i></small></div>
          <div class="text-subtitle2">{{ c.type.title }}</div>
        </div>
      </q-img>
    </q-card>

    <q-card v-else flat bordered class="bg-teal-1 q-pa-md">
      <div class="text-h6">Aucun bien trouvé. Veuillez modifier votre recherche.</div>
    </q-card>
  </div>

  <bookingEffectsDialog :openEffectsDialog="isOpenEffectsDialog" @close="isOpenEffectsDialog = false" :catalog="selectedCatalog" @update="reloadCatalog"/>

</template>

<script>
import { ref } from 'vue';
import { getCategoryByType } from '../../api/Category';
import {getEffectsByTypeCategoryAndKeywords} from '../../api/CatalogRessource';
import bookingEffectsDialog from '../../components/dialog/ressourcerie/bookingEffectsDialog.vue';

const typeOptions = ref([])
const catalogues = ref([])

export default {
  components: {
    bookingEffectsDialog
  },
  data() {
    return {
      catalogues,
      typeOptions,
      typeSelector: '',
      search: '',
      isOpenEffectsDialog: false,
      selectedCatalog: {}
    }
  },
  mounted() {
    this.reloadSelector()
    this.getCatalogues()
  },
  methods: {
    reloadSelector() {
      getCategoryByType('ressourcerie').then((response) => typeOptions.value = response.data)
    },
    getCatalogues() {
      getEffectsByTypeCategoryAndKeywords('ressourcerie', this.typeSelector ? this.typeSelector.id : '', this.search ?? '').then((response) => catalogues.value = response.data)
    },
    reloadCatalog() {
      this.isOpenEffectsDialog = false
      this.getCatalogues()
    },  
    openEffectsDialog(catalog) {
      this.selectedCatalog = catalog
      this.isOpenEffectsDialog = true
    }
  }
}
</script>


<style scoped>

</style>