<template>
  <add-users :open-dialog="addUserDialog" @close="addUserDialog= false" @reload="reload" :id="id"></add-users>
  <q-dialog v-model="removeUser" persistent>
    <q-card>
      <q-card-section class="q-pt-lg">
        <h2 class="text-h6">{{ $t('externalUsers.deleteConfirmation') }}</h2>
        <div class="text-caption text-grey-7 q-mt-sm">{{ $t('externalUsers.deleteConfirmationSubtext') }}</div>
      </q-card-section>
      <q-card-actions align="right">
        <q-btn flat :label="$t('common.cancel')" color="dark" v-close-popup />
        <q-btn flat :label="$t('externalUsers.delete')" color="negative" @click="removeBookingByUser" />
      </q-card-actions>
    </q-card>
  </q-dialog>
  <q-card-section>
    <div class="row">
      <div class="col">
        <h2 class="text-h6">{{ $t('externalUsers.management') }}</h2>
        <div>
          {{ $t('externalUsers.description') }}
        </div>
      </div>
      <div class="col-auto">
        <q-btn v-if="userStore.isUserAdminSite(id)"  round color="secondary" icon="add" @click="addUserDialog= true">
          <q-tooltip>
            {{ $t('externalUsers.add') }}
          </q-tooltip>
        </q-btn>
      </div>
    </div>
  </q-card-section>
  <q-table
      :rows="rows"
      :columns="columns"
      row-key="name"
      :aria-label="$t('externalUsers.management')"
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
          <q-btn v-if="userStore.isUserAdminSite(id)" rounded :label="$t('externalUsers.delete')" color="red" icon="delete" @click="openRemoveDialog(props.row.id)">
            <q-tooltip>
              {{ $t('externalUsers.delete') }}
            </q-tooltip>
          </q-btn>
        </div>
      </q-td>
    </template>
  </q-table>
</template>

<script>
import AddUsers from "../../../dialog/Administration/users/addUsers.vue";
import {getExternalUserFromService} from "../../../../api/User";
import {deleteExternalUserFromService} from "../../../../api/Service";
import {ref} from "vue";
import {user} from "../../../../store/counter";

const userToRemove = ref(null);
const removeUser = ref(false);
const userStore = user();
export default {
  name: "manageUsers",
  components: {AddUsers},
  props: {
    id: String
  },
  data() {
    return {
      columns: [
        {name: 'Nom Prénom', align: 'left', label: this.$t('externalUsers.fullName'), field: 'displayUserName', sortable: true},
        {name: 'username', align: 'center', label: this.$t('externalUsers.username'), field: 'username', sortable: true},
        {name: 'Email', align: 'center', label: this.$t('externalUsers.email'), field: 'email', sortable: true},
        {name: 'actions', align: 'center', label: this.$t('actions'), field: 'actions', sortable: false},
      ],
      rows: [],
      addUserDialog: false,
      removeUser,
      userToRemove,
      userStore
    }
  },
  mounted() {
    this.getExternalUsers();
  },
  methods: {
    getExternalUsers() {
      getExternalUserFromService(this.id).then((response) => {
        this.rows = response.data;
      })
    },
    reload() {
      this.getExternalUsers();
      this.addUserDialog = false;
    },
    openRemoveDialog(id) {
      userToRemove.value = id;
      removeUser.value = true;
    },
    removeBookingByUser() {
      let self = this;
      deleteExternalUserFromService(this.id, userToRemove.value).then(function (response) {
        userToRemove.value = null;
        removeUser.value = false;
        self.getExternalUsers();
      })
    },
  }
}
</script>

<style scoped>

</style>