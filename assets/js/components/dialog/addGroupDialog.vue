<template>
  <q-dialog v-model="dialogVisible" persistent>
    <q-card style="width: 700px; max-width: 80vw;">
      <q-card-section>
        <div v-if="toEdit === null" class="text-h5">{{ $t('groups.addGroupDialog') }}</div>
        <div v-else class="text-h5">{{ $t('groups.editGroupDialog') }}</div>
      </q-card-section>
      <q-separator/>
      <q-card-section>
        <q-form ref="form">
          <q-input v-model="input1" :label="$t('title')" :rules="[val => !!val || $t('externalUsers.requiredField')]" />
          <div v-if="serviceId === undefined">
          <q-input v-model="input3" :label="$t('groups.provider')" :placeholder="$t('groups.providerPlaceholder')" :rules="[val => !!val || $t('externalUsers.requiredField')]" />
            <p>{{ $t('groups.userAdditionMode') }}</p>
            <div class="q-gutter-sm">
              <q-radio v-model="request" val="req" :label="$t('groups.query')" />
              <q-radio v-model="request" val="man" :label="$t('common.manual')" />
            </div>
            <q-input v-if="request === 'req' " v-model="input2" :label="$t('groups.query')" :placeholder="$t('groups.queryPlaceholder')" />
          </div>
          <q-select
              v-if="request === 'man'"
              ref="searchParticipant"
              id="searchParticipant"
              v-model="users"
              outlined
              use-input
              fill-input
              use-chips
              multiple
              input-debounce=200
              :label="$t('groups.addUsersToGroup')"
              :options="options"
              @filter="filter"
              new-value-mode="add-unique"
          >
            <template v-slot:no-option>
              <q-item>
                <q-item-section class="text-grey">
                  {{ $t('groups.noResults') }}
                </q-item-section>
              </q-item>
            </template>
          </q-select>
        </q-form>
      </q-card-section>
      <q-card-section v-if="request === 'man'">
        <q-virtual-scroll
            style="max-height: 300px;"
            :items="alreadyAddedUsers"
            separator
            v-slot="{ item, index }"
        >
          <q-item dense :key="index">
            <q-item-section>{{item}}</q-item-section>
            <q-item-section>
              <q-btn dense color="negative" :label="$t('groups.delete')" @click="deleteUserFromGroup(index)" />
            </q-item-section>
          </q-item>
        </q-virtual-scroll>
      </q-card-section>

      <q-card-actions align="right">
        <q-btn v-if="toEdit === null" color="primary" :label="$t('groups.validate')" @click="validate()" />
        <q-btn v-else color="primary" :label="$t('groups.validate')" @click="edit()" />
        <q-btn color="negative" :label="$t('common.cancel')" @click="close" />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script>
import {ref} from "vue";
import {editGroup, getGroup, postGroup} from "../../api/Group";
import {searchInAllUser, searchUserAsAdmin} from "../../api/User";

const input1 = ref('');
const input2 = ref('');
const input3 = ref('');
const request = ref('req');
const users = ref([]);
const options = ref([]);
const alreadyAddedUsers = ref([]);
export default {
  name: "addGroupDialog",
  props: {
    openDialog:Boolean,
    toEdit: Object|null,
    serviceId: String,
    admin: Boolean
  },
  data() {
    return {
      dialogVisible: false,
      input1,
      input2,
      input3,
      request,
      users,
      options,
      alreadyAddedUsers,
      isAdmin: false
    }
  },
  mounted() {
    this.dialogVisible  = this.openDialog;
    if(this.admin) {
      this.isAdmin = true;
    }
  },
  methods: {
    validate() {
      this.$refs.form.validate();
      let self = this;
      if(this.serviceId === undefined) {
        if(input1.value !== "" && input3.value !== "") {
          let body = {
            'title' : input1.value,
            'provider' : input3.value
          }
          if(request.value === 'req') {
            body.query = input2.value;
          } else {
            body.query = "";
            body.users = JSON.stringify(this.parseUserArray());
          }

          postGroup(body, this.isAdmin).then(function (response) {
            input1.value = "";
            input2.value = "";
            input3.value = "";
            self.$emit('validate', false);
          })
        }
      } else {
        this.postGroupService();
      }
    },
    postGroupService() {
      let self = this;
      if(input1.value !== "") {
        let body = {
          'title' : input1.value,
          'provider' : 'db',
          'Service' : 'api/services/'+this.serviceId
        }
        body.query = "";
        body.users = JSON.stringify(this.parseUserArray());
        postGroup(body, false).then(function (response) {
          input1.value = "";
          input2.value = "";
          input3.value = "";
          self.$emit('validate', false);
        })
      }
    },
    edit() {
      this.$refs.form.validate();
      if(this.serviceId === undefined) {
        let self = this;
        if(input1.value !== "" && input3.value !== "") {
          let body = {
            'title' : input1.value,
            'query' : input2.value,
            'provider' : input3.value
          }
          if(request.value === 'req') {
            body.query = input2.value;
          } else {
            body.query = "";
            body.users = JSON.stringify(this.parseUserArray());
          }
          editGroup(this.toEdit.id, body, this.isAdmin).then(function (response) {
            input1.value = "";
            input2.value = "";
            input3.value = "";
            users.value = [];
            request.value = 'req';
            self.$emit('validate', false);
          })
        }
      } else {
        this.editGroupService();
      }
    },
    editGroupService() {
      let self = this;
      if(input1.value !== "" && input3.value !== "") {
        let body = {
          'title': input1.value,
          'Service': 'api/services/'+this.serviceId,
          'provider': 'db'
        }
        body.users = JSON.stringify(this.parseUserArray());
        editGroup(this.toEdit.id, body, false).then(function (response) {
          input1.value = "";
          input2.value = "";
          input3.value = "";
          users.value = [];
          request.value = 'req';
          self.$emit('validate', false);
        })
      }
    },
    close() {
      this.$emit('close', false);
    },
    filter(val, update) {
      update(() => {
        if(val === '') {
          options.value = []
        } else {
          if (val.length > 1) {
            searchUserAsAdmin(val).then(function (response) {
              options.value = []
              response.data.forEach(user => options.value.push(user));
            })
          }
        }
      })
    },
    getUsersFromGroup() {
      getGroup(this.toEdit.id).then(function (response) {
        alreadyAddedUsers.value = JSON.parse(response.data.users);
      })
    },
    parseUserArray() {
      let usersArray = [];
      users.value.forEach(user => usersArray.push(user.uid));
      return usersArray.concat(alreadyAddedUsers.value);
    },
    deleteUserFromGroup(index) {
      let self = this;
      alreadyAddedUsers.value.splice(index, 1);
      let body = {
        'title' : input1.value,
        'query' : input2.value,
        'provider' : input3.value,
        'users' : JSON.stringify(alreadyAddedUsers.value)
      }
      editGroup(this.toEdit.id, body).then(function (response) {
        self.$emit('reload');
      })
    }
  },
  watch: {
    openDialog: function () {
      this.dialogVisible  = this.openDialog;
      alreadyAddedUsers.value = [];
      request.value = 'req';
      users.value = [];
      if(this.toEdit !== null) {
        input1.value = this.toEdit.title;
        input2.value = this.toEdit.query;
        input3.value = this.toEdit.provider;
        this.getUsersFromGroup();
      } else {
        input1.value = "";
        input2.value = "";
        input3.value = "";
      }
      if(this.serviceId !== undefined) {
        request.value = 'man';
      }
    }
  }
}
</script>

<style scoped>

</style>