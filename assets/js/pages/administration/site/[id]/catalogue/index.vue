<template>
  <q-card bordered class="my-card">
    <q-card-section>
      <div class="row">
        <div class="col">
          <h1 class="text-h6">{{ $t('serviceCatalogues') }}</h1>
        </div>
        <div class="col-auto">
          <q-btn round color="secondary" icon="add" :to="{ name: 'createCatalogue' }">
            <q-tooltip>
              {{ $t('catalogues.add') }}
            </q-tooltip>
          </q-btn>
        </div>
      </div>
    </q-card-section>
      <q-table
          :rows="catalogues"
          :columns="columns"
          row-key="title"
          :filter="filter"
          :rows-per-page-options="[20, 50, 100, 0]"
          role="presentation"
          :aria-label="$t('serviceCatalogues')"
      >
        <template v-slot:top-right>
          <q-input dense v-model="filter" :placeholder="$t('search')">
            <template v-slot:append>
              <q-icon name="search" />
            </template>
          </q-input>
        </template>
        <template v-slot:header="props">
          <q-tr :props="props">
            <q-th
                v-for="col in props.cols"
                :key="col.name"
                :props="props"
                role="rowheader"
            >
              {{ col.label }}
            </q-th>
          </q-tr>
        </template>
        <template v-slot:body="props">
        <q-tr :props="props">
          <q-td key="image" :props="props">
            <q-img :src="'/uploads/'+props.row.image" :alt="props.row.title">
              <template v-slot:error>
                <div class="absolute-full flex flex-center bg-grey text-white">
                  {{ $t('common.noImage') }}
                </div>
              </template>
            </q-img>
          </q-td>
          <q-td key="title" :props="props">
            {{props.row.title}}
          </q-td>
          <q-td key="type" :props="props">
            {{props.row.type.label}}
          </q-td>
          <q-td key="nb" :props="props">
            {{props.row.nb}}
          </q-td>
          <q-td key="action" :props="props">
            <q-btn round color="primary" icon="visibility" :to="'catalogue/'+props.row.id">
              <q-tooltip>
                {{ $t('catalogues.view') }}
              </q-tooltip>
            </q-btn>
          </q-td>
        </q-tr>
        </template>
      </q-table>
  </q-card>
</template>

<route lang="json">
{
  "name": "catalogueListAdmin",
  "meta": {
  "requiresAuth": false,
  "dynamic": true
  }
}
</route>

<script>
import {ref} from 'vue';
import axios from 'axios';
import {useQuasar} from "quasar";
import Catalogue from "../catalogue.vue";
export default {
  name: "index.vue",
  components: {Catalogue},
  data() {
    return {
      catalogues: [],
      idSite: null,
      columns: [
        {name: 'image', align: 'center', label: 'Image', field: 'image', sortable: true},
        {name: 'title', align: 'center', label: this.$t('title'), field: 'title', sortable: true},
        {name: 'type', align: 'center', label: this.$t('type'), field: row => row.type.label, sortable: true},
        {name: 'nb', align: 'center', label: this.$t('catalogues.resourceCount'), field: 'nb', sortable: true},
        {name: 'action', align: 'center', label: this.$t('actions'), field: 'action'}
      ],
      filter: ''
    }
  },
  mounted() {
    this.idSite = this.$router.currentRoute.value.params.id;
    this.loadCatalogue();
  },
  methods: {
    loadCatalogue() {
      let self = this;
      this.$q.loading.show();
      axios({
        method: "get",
        url: "/api/service/"+self.idSite+"/catalogues",
      })
          .then(function (response) {
            self.catalogues = response.data;
            self.$q.loading.hide();
          })
    },
  }
}
</script>

<style scoped>
 h3 {
   text-align: center;
 }
</style>