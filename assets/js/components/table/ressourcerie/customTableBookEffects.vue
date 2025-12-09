<template>
    <q-table
        :rows="rows"
        :columns="columns"
        row-key="id"
        :loading="loading"
        :rows-per-page-options="[10, 50, 100]"
        flat
        selection="multiple"
        v-model:selected="selectedRessources"
    >
      <template v-slot:header-selection="props"><q-checkbox v-model="props.selected" /></template>
      <template v-slot:body="props">
        <q-tr :props="props">
          <q-td>
            <q-checkbox :model-value="props.selected" @update:model-value="(val, evt) => { Object.getOwnPropertyDescriptor(props, 'selected').set(val, evt) }" />
          </q-td>
          <q-td key="image" :props="props">
            <div>
              <q-img :src="'/uploads/' + (props.row.image ?? catalog.image)">
                <template v-slot:error>
                  <div class="absolute-full flex flex-center bg-grey text-white">
                    <q-icon size="xs" name="no_photography"/>
                  </div>
                </template>
              </q-img>
            </div>
          </q-td>
          <q-td key="title" :props="props">
            {{ props.row.title }}
          </q-td>
          <q-td key="AdditionalInformations" :props="props">
            <div v-if="props.row.AdditionalInformations !== null && props.row.AdditionalInformations !== ''">{{ props.row.AdditionalInformations }}</div>
            <div v-else>N/R</div>
          </q-td>
        </q-tr>
      </template>
    </q-table>  

</template>

<script>
import { ref } from "vue";

const columns = [
  { name: 'image', align: 'center', label: 'Image', field: 'image'},
  { name: 'title', required: true, label: 'Nom du bien', align: 'left', field: 'title'},
  { name: 'AdditionalInformations', align: 'center', label: 'Informations complémentaires', field: 'AdditionalInformations'},
]
const rows = ref([]);
const loading = ref(false);

export default {
  name: "customTableBookEffects",
  props: {
    catalog: Object,
  },
  data() {
    return {
      columns,
      rows,
      loading,
      rowCount: 0,
      pagination : {
        page: 1,
        rowsPerPage: 1000
      },
      selectedRessources: []
    }
  },
  mounted() {
    this.rows = this.catalog.remainingEffects
  },
  watch: {
    selectedRessources: function () {
      this.$emit('selectedRessources', this.selectedRessources)
    }
  },
  methods: {
    
  }
}
</script>

<style scoped>

</style>