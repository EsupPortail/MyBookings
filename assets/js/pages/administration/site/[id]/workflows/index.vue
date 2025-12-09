<template>
    <q-dialog v-model="confirmDelete" persistent>
      <q-card>
        <q-card-section class="row items-center">
          <span class="q-ml-sm">{{ $t('workflows.deleteConfirmation') }} <b>{{workflowToDelete.title}}</b> ?</span>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat :label="$t('common.cancel')" color="primary" v-close-popup />
          <q-btn flat :label="$t('groups.confirm')" color="primary" @click="deleteWorkflow(workflowToDelete.id)" />
        </q-card-actions>
      </q-card>
    </q-dialog>
      <q-card bordered class="my-card">
        <q-card-section>
          <div class="row">
            <div class="col">
              <h1 class="text-h6">{{ $t('workflows.management') }}</h1>
            </div>
            <div class="col-auto">
              <q-btn v-if="storedUser.isUserAdminSite(id)" round color="secondary" icon="add" :to="{ name: 'createWorkflow' }">
                <q-tooltip>
                  {{ $t('workflows.add') }}
                </q-tooltip>
              </q-btn>
            </div>
          </div>
        </q-card-section>
        <q-table
            :rows="workflows"
            :columns="columns"
            row-key="title"
            :aria-label="$t('workflows.management')"
        >
          <template v-slot:header="props">
            <q-tr :props="props">
              <q-th
                  v-for="col in props.cols"
                  :key="col.name"
                  :props="props"
                  role="rowheader"
              >
                <b>{{ col.label }}</b>
              </q-th>
            </q-tr>
          </template>
          <template v-slot:body="props">
            <q-tr :props="props">
              <q-td key="title" :props="props">
                {{props.row.title}}
              </q-td>
              <q-td key="autoValid" :props="props">
                <div v-if="props.row.autoValidation === true">{{ $t('workflows.yes') }}</div>
                <div v-else>{{ $t('workflows.no') }}</div>
              </q-td>
              <q-td key="autoStart" :props="props">
                <div v-if="props.row.auto_start === true">{{ $t('workflows.yes') }}</div>
                <div v-else>{{ $t('workflows.no') }}</div>
              </q-td>
              <q-td key="autoEnd" :props="props">
                <div v-if="props.row.auto_end === true">{{ $t('workflows.yes') }}</div>
                <div v-else>{{ $t('workflows.no') }}</div>
              </q-td>
              <q-td key="action" :props="props" class="justify-evenly">
                <q-btn style="margin-right: 10px" v-if="storedUser.isUserAdminSite(id)" round color="primary" icon="edit" @click="editWorkflow(props.row.id)">
                  <q-tooltip>
                    {{ $t('workflows.edit') }}
                  </q-tooltip>
                </q-btn>
                <q-btn v-if="storedUser.isUserAdminSite(id)" round color="red" icon="delete" v-on:click="confirmDelete = true; workflowToDelete = props.row">
                  <q-tooltip>
                    {{ $t('workflows.delete') }}
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
"name": "workflowListAdmin",
  "meta": {
  "requiresAuth": false,
  "dynamic": true
  }
}
</route>

<script>
import axios from "axios";
import {user} from "../../../../../store/counter";
import {getProvisionsFromWorkflow} from "../../../../../api/Provision";
import { useRoute } from 'vue-router/auto';

export default {
  name: "index.vue",
  setup() {
    const storedUser = user();
    const route = useRoute();
    const id = route.params.id;
    return {
      storedUser,
      id
    }
  },
  data() {
    return {
      workflows: [],
      confirmDelete: false,
      workflowToDelete:null,
      columns: []
    }
  },
  mounted() {
    this.initializeColumns();
    this.getWorkflowsFromService();
  },
  methods: {
    initializeColumns() {
      this.columns = [
        {name: 'title', align: 'center', label: this.$t('title'), field: 'title', sortable: true},
        {name: 'autoValid', align: 'center', label: this.$t('workflows.autoValidation'), field: 'autoValidation', sortable: true},
        {name: 'autoStart', align: 'center', label: this.$t('workflows.autoStart'), field: 'auto_start', sortable: true},
        {name: 'autoEnd', align: 'center', label: this.$t('workflows.autoEnd'), field: 'auto_end', sortable: true},
        {name: 'action', align: 'center', label: this.$t('actions'), field: 'action'}
      ];
    },
    deleteWorkflow(id) {
      let self = this;
      getProvisionsFromWorkflow(id).then(function (response) {
        if(response.data.length > 0){
          self.$q.notify({
            color: 'negative',
            position: 'top',
            message: self.$t('workflows.cannotDelete'),
            timeout: 2500
          });
          self.confirmDelete = false;
        }else{
          self.deleteWorkflowFromService(id);
        }
      })
    },
    deleteWorkflowFromService(id) {
      let self = this;
      axios({
        method: "delete",
        url: "/api/workflows/"+id,
        headers: {
          'accept': 'application/json'
        },
      }).then(function (response) {
        self.confirmDelete = false;
        self.getWorkflowsFromService();
      })
    },
    getWorkflowsFromService() {
      let id = this.$router.currentRoute.value.params.id;
      let self = this;
      axios({
        method: "get",
        url: "/api/workflows?ServiceId="+id,
        headers: {
          'accept': 'application/json'
        },
      })
          .then(function (response) {
            self.workflows = response.data
          })
    },
    editWorkflow(id) {
      this.$router.push({name: 'editWorkflow', params: {workflowId: id}});
    },
  }
}
</script>

<style scoped>
h3 {
  text-align: center;
}
</style>