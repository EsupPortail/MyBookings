<template>
  <add-group-dialog :open-dialog="openDialog" @close="openDialog=false; groupToEdit=null" :toEdit="groupToEdit" :admin='true' @validate="closeAndReload" @updated="getQuery"></add-group-dialog>
  <q-dialog v-model="confirmDelete" persistent>
    <q-card>
      <q-card-section>
        <div class="row  items-center">
          <div class="col">
            <div class="text-h6">{{ $t('groups.deleteTitle') }}</div>
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
  <div class="row">
    <div class="col">
      <div class="text-h6">{{ $t('GroupManage') }}</div>
    </div>
    <div class="col-auto">
      <q-btn round color="secondary" icon="add" @click="openDialog = true">
        <q-tooltip>
          {{ $t('groups.add') }}
        </q-tooltip>
      </q-btn>
    </div>
  </div>
  <div class="q-pa-md">
    <q-table
        :title="$t('disponibles')"
        :rows="rows"
        :columns="columns"
        row-key="name"
        :filter="filter"
    >
      <template v-slot:body-cell-actions="props">
        <q-td :props="props">
          <div class="row items-center justify-evenly">
            <q-btn round outline color="primary" icon="cached" @click="reloadUserQuery(props.row.id)" >
              <q-tooltip>
                {{ $t('adminGroups.reload') }}
              </q-tooltip>
            </q-btn>
            <q-btn round color="primary" icon="edit" @click="groupToEdit=props.row; openDialog=true;">
              <q-tooltip>
                {{ $t('groups.edit') }}
              </q-tooltip>
            </q-btn>
            <q-btn round color="red" icon="delete" @click="deleteRow = props.row; confirmDelete = true;" >
              <q-tooltip>
                {{ $t('groups.delete') }}
              </q-tooltip>
            </q-btn>
          </div>
        </q-td>
      </template>
      <template v-slot:top-right>
        <q-input dense debounce="300" v-model="filter" :placeholder="$t('adminGroups.search')">
          <template v-slot:append>
            <q-icon name="search" />
          </template>
        </q-input>
      </template>
    </q-table>
  </div>
</template>

<script>
import {ref} from "vue";
import {deleteGroup, getAdminGroups, getAllGroups, loadUserFromGroup} from "../../../api/Group";
import AddGroupDialog from "../../../components/dialog/addGroupDialog.vue";

const confirmDelete = ref(false);
const rows = ref([]);
const groupToEdit = ref(null);
const getQuery = function () {
  getAdminGroups().then(function (response) {
    rows.value = response.data;
  })
};
export default {
  name: "Group",
  components: {AddGroupDialog},
  data() {
    return {
      openDialog: false,
      confirmDelete,
      deleteRow: null,
      filter: ref(''),
      rows,
      groupToEdit,
      getQuery,
      columns: []
    }
  },
  mounted() {
    this.initializeColumns();
    getQuery();
  },
  methods: {
    initializeColumns() {
      this.columns = [
        { name: 'id', align: 'left', label: 'ID', field: 'id', sortable: true },
        { name: 'title', align: 'left', label: this.$t('title'), field: 'title', sortable: true },
        { name: 'query', align: 'left', label: this.$t('adminGroups.query'), field: 'query', sortable: true, style: 'max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap' },
        { name: 'provider', align: 'left', label: this.$t('adminGroups.provider'), field: 'provider', sortable: true },
        { name: 'numberOfUser', align: 'center', label: this.$t('groups.numberOfUsers'), field: 'numberOfUser', sortable: true },
        { name: 'actions', align: 'center', label: this.$t('actions'), field: 'actions', sortable: false },
      ];
    },
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