<template>
  <q-card bordered class="my-card">
    <q-card-section v-if="userStore.isUserAdminSite(idSite, true)">
      <div class="row justify-between">
          <div class="col-3">
            <q-select square outlined clearable label="Rechercher par status" v-model="statusFilter" option-label="label" option-value="value" :options="optionsStatus"></q-select>
          </div>
          <q-chip clickable square color="primary" text-color="white" icon="add_to_photos" @click="$router.push({ name: 'createCatalogueRessourcerie'})">
            Ajouter
            <q-tooltip>Ajouter un bien</q-tooltip>
          </q-chip>
      </div>
    </q-card-section>
    <custom-table-catalog :catalogues />
  </q-card>
</template>

<route lang="json">
{
  "name": "catalogueListAdminRessourcerie",
  "meta": {
  "requiresAuth": false,
  "dynamic": true
  }
}
</route>

<script>
import {ref} from 'vue';
import Catalogue from "../catalogue.vue";
import { user } from "../../../../../../store/counter";
import { getEffects } from '../../../../../../api/ressourcerie/Effects';
import CustomTableCatalog from "../../../../../../components/table/ressourcerie/customTableCatalog.vue";

const userStore = user();
const statusFilter = ref({label: 'Publiés', value: 'rc_published'});
const optionsStatus= [
  {label: 'Publiés', value: 'rc_published'},
  {label: 'À valider', value: 'rc_pending'},
  {label: 'En attente de modération SIT', value: 'rc_validated'},
  {label: 'Refusés', value: 'rc_disabled'},
];

export default {
  name: "index.vue",
  components: {CustomTableCatalog, Catalogue},
  data() {
    return {
      catalogues: [],
      idSite: null,
      columns: [
        {name: 'image', align: 'center', label: '', field: 'image', sortable: true},
        {name: 'title', align: 'center', label: 'Nom', field: 'title', sortable: true},
        {name: 'type', align: 'center', label: 'Type', field: row => row.type.title, sortable: true},
        {name: 'nb', align: 'center', label: 'Nombre de biens', field: row => row.resource.length, sortable: true},
        {name: 'action', align: 'center', label: 'Actions', field: 'action'}
      ],
      filter: '',
      userStore,
      statusFilter,
      optionsStatus,
    }
  },
  mounted() {
    this.idSite = this.$router.currentRoute.value.params.id;
    if(this.getUrlParameter('status') !== null) {
      statusFilter.value = optionsStatus.find(option => option.value === this.getUrlParameter('status'));
    }
    this.loadCatalogue();
  },
  methods: {
    loadCatalogue() {
      let self = this;
      this.$q.loading.show();
      getEffects(statusFilter.value?.value, this.idSite)
      .then(function (response) {
        self.catalogues = response.data.member;
        self.$q.loading.hide();
      })
    },
    getUrlParameter(param) {
      const queryString = window.location.search;
      const urlParams = new URLSearchParams(queryString);
      return urlParams.get(param);
    },
  },
  watch: {
    statusFilter() {
      this.loadCatalogue();
    }
  }
}
</script>

<style scoped>
h3 {
  text-align: center;
}
</style>