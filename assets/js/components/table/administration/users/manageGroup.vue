<template>
  <add-group-dialog :open-dialog="openDialog" @close="openDialog=false; groupToEdit=null" :toEdit="groupToEdit" :serviceId="id" @validate="closeAndReload" @updated="getQuery"></add-group-dialog>
  <q-dialog v-model="confirmDelete" persistent>
    <q-card>
      <q-card-section>
        <div class="row  items-center">
          <div class="col">
            <h3 class="text-h6">{{ $t('groups.deleteTitle') }}</h3>
          </div>
          <div class="col-auto">
            <q-avatar color="red" text-color="white" icon="delete"/>
          </div>
        </div>
      </q-card-section>
      <q-separator/>
      <q-card-section class="row items-center">
        <span class="q-ml-sm">{{ $t('groups.deleteConfirmation') }}</span>
      </q-card-section>
      <q-card-section style="text-align: center">
        <span>{{deleteRow.id}} | {{deleteRow.title}} | {{deleteRow.query}}</span>
      </q-card-section>
      <q-card-actions align="right">
        <q-btn flat :label="$t('groups.confirm')" color="primary" @click="deleteQuery(deleteRow.id)" />
        <q-btn flat :label="$t('common.cancel')" color="primary" v-close-popup />
      </q-card-actions>
    </q-card>
  </q-dialog>
  <q-card-section>
    <div class="row">
      <div class="col">
        <h2 class="text-h6">{{ $t('groups.management') }}</h2>
        <div>
          {{ $t('groups.description') }}
        </div>
      </div>
      <div class="col-auto">
        <q-btn v-if="userStore.isUserAdminSite(id)" round color="secondary" icon="add" @click="openDialog = true">
          <q-tooltip>
            {{ $t('groups.add') }}
          </q-tooltip>
        </q-btn>
      </div>
    </div>
  </q-card-section>
  <q-table
      :rows="rows"
      :columns="columns"
      row-key="name"
      :aria-label="$t('groups.management')"
  >
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
    <template v-slot:body-cell-actions="props">
      <q-td :props="props" style="max-width: 100px">
        <div class="row items-center justify-around" >
          <q-btn v-if="userStore.isUserAdminSite(id)" rounded color="primary" :label="$t('groups.edit')" icon="edit" @click="groupToEdit=props.row; openDialog=true;">
            <q-tooltip>
              {{ $t('groups.edit') }}
            </q-tooltip>
          </q-btn>
          <q-btn v-if="userStore.isUserAdminSite(id)" rounded :label="$t('groups.delete')" color="red" icon="delete" @click="deleteRow = props.row; confirmDelete = true;" >
            <q-tooltip>
              {{ $t('groups.delete') }}
            </q-tooltip>
          </q-btn>
        </div>
      </q-td>
    </template>
  </q-table>
</template>

<script>
import AddGroupDialog from "../../../dialog/addGroupDialog.vue";
import {ref} from "vue";
import {deleteGroup, getAllGroupByService, getGroupByService, loadUserFromGroup} from "../../../../api/Group";
import {user} from "../../../../store/counter";

const confirmDelete = ref(false);
const rows = ref([]);
const groupToEdit = ref(null);
const serviceId = ref(null);
const getQuery = function () {
  getAllGroupByService(serviceId.value).then(function (response) {
    rows.value = response.data;
  })
};

const userStore = user();
export default {
  name: "manageGroup",
  components: {AddGroupDialog},
  props: {
    id: String
  },
  data() {
    return {
      openDialog: false,
      confirmDelete,
      deleteRow: null,
      rows,
      groupToEdit,
      getQuery,
      serviceId,
      userStore,
      columns: [
        {name: 'id', align: 'left', label: 'ID', field: 'id', sortable: true},
        {name: 'title', align: 'left', label: this.$t('title'), field: 'title', sortable: true},
        {name: 'numberOfUser', align: 'center', label: this.$t('groups.numberOfUsers'), field: 'numberOfUser', sortable: true},
        {name: 'actions', align: 'center', label: this.$t('actions'), field: 'actions', sortable: false},
      ]
    }
  },
  mounted() {
    serviceId.value = this.id;
    getQuery();
  },
  methods: {

    deleteQuery(id) {
      deleteGroup(id).then(function () {
        confirmDelete.value = false;
        getQuery();
      })
    },
    reloadUserQuery(id) {
      loadUserFromGroup(id).then(function (){
        getQuery();
        confirmDelete.value = false;
      })
    },
    closeAndReload(){
      this.openDialog = false;
      groupToEdit.value = null;
      getQuery();
    },
  }
}
</script>

<style scoped>

</style>