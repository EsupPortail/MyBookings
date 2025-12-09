
<template>
  <div class="row">
    <div class="col">
      <div class="text-h6">{{ $t('rules.management') }}</div>
    </div>
    <div class="col-auto">
      <q-btn round color="secondary" icon="add">
        <q-tooltip>
          {{ $t('rules.add') }}
        </q-tooltip>
      </q-btn>
    </div>
  </div>
  <div class="q-pa-md">
    <q-table
        :rows="rows"
        :columns="columns"
        row-key="name"
        :filter="filter"
    >
      <template v-slot:body-cell-arguments="props">
        <q-td :props="props">
          <p>{{props.row.Arguments.value.label}}</p>
        </q-td>
      </template>
      <template v-slot:body-cell-ressources="props">
        <q-td :props="props">
          <p>{{Rules[props.row.id]}}</p>
        </q-td>
      </template>
      <template v-slot:body-cell-actions="props">
        <q-td :props="props">
          <q-btn round color="primary" icon="edit">
            <q-tooltip>
              {{ $t('rules.edit') }}
            </q-tooltip>
          </q-btn>
        </q-td>
      </template>
      <template v-slot:top-right>
        <q-input dense debounce="300" v-model="filter" :placeholder="$t('rules.search')">
          <template v-slot:append>
            <q-icon name="search" />
          </template>
        </q-input>
      </template>
    </q-table>
  </div>
</template>

<script>
import {getRuleResources, getRules} from "../../api/Rules";
import {ref} from "vue";

const rows = ref([]);
const Rules = ref([]);
export default {

  name: "rules.vue",

  data() {
    return {
      filter: ref(''),
      Rules,
      rows,
      columns: []
    }
  },
  mounted() {
    this.initializeColumns();
    this.loadRules();
  },
  methods: {
    initializeColumns() {
      this.columns = [
        { name: 'id', align: 'left', label: 'ID', field: 'id', sortable: true },
        { name: 'title', align: 'left', label: this.$t('title'), field: 'Title', sortable: true },
        { name: 'description', align: 'left', label: this.$t('description'), field: 'Description', sortable: true },
        { name: 'arguments', align: 'center', label: this.$t('rules.arguments'), field: row => row.Arguments.operand, sortable: true },
        { name: 'ressources', align: 'center', label: this.$t('rules.resources'), field: row => row.id, sortable: true },
        { name: 'actions', align: 'center', label: this.$t('actions'), field: 'actions', sortable: false },
      ];
    },
    loadRules() {
      getRules().then(function (response) {
        rows.value = response.data;
        rows.value.forEach(function (rules) {
          getRuleResources(rules.id).then(function (response) {
            Rules.value[rules.id] = response.data.length;
          })
        })
      });
    },
  }
}
</script>

<style scoped>

</style>