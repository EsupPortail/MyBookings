<template>
  <div>
    <q-card bordered> <!-- Use to allow left panel ? -->
      <q-card-section class="q-pa-md"><!-- Use for tabs navigation ? -->

        <div class="q-pa-sm row q-gutter-xs" style="min-height: 200px;">

          <!-- LOADER -->
          <div v-if="isloaded == false" class="column items-center absolute-center">
            <q-circular-progress
              indeterminate
              size="90px"
              :thickness="0.2"
              color="primary"
              center-color="transparent"
              track-color="transparent"
              class="q-ma-md"
            />
            <span>Chargement des modérateurs</span>
          </div>

          <!-- CONTENT -->
          <div v-else class="column full-width">
            <div>
              <q-chip v-if="storedUser.isUserAdminSite(id, true)" square color="primary" text-color="white" icon="person_add" clickable @click="addUserDialog = true">
                Ajouter
                <q-tooltip>Ajouter un modérateur</q-tooltip>
              </q-chip>
            </div>

            <!-- USERS LIST -->
            <div v-for="acl in users" class="row justify-between items-center full-width">
              <div class="text-h6 col">{{ acl.user.displayUserName }}</div>
              <div class="text-subtitle2 col">{{ acl.user.email }}</div>
              <div class="q-pa-sm col-auto row justify-end" style="width: 300px;">
                <!-- REMOVE USER BUTTON -->
                <q-chip v-if="storedUser.isUserAdminSite(id, true) && acl.user.username != storedUser.username" square color="negative" text-color="white" icon="delete" clickable @click="openRemoveUserDialog(acl.id)">
                  Supprimer
                  <q-tooltip>Supprimer le modérateur</q-tooltip>
                </q-chip>
              </div>
            </div>

          </div>

        </div>
      </q-card-section>

    </q-card>

    <!-- ADD USER DIALOG -->
    <q-dialog v-model="addUserDialog" persistent>
      <q-card style="width: 700px; max-width: 80vw;">
        <q-card-section>
          <div class="text-h6">Ajouter un modérateur au site <b>{{ service.title }}</b></div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-select
              filled
              v-model="userToAdd"
              use-input
              use-chips
              input-debounce="0"
              label="Rechercher et sélectionner un modérateur"
              :options="usersOptions"
              @filter="filter"
              new-value-mode="add-unique"
          >
          </q-select>
        </q-card-section>

        <q-card-actions align="right" class="bg-white text-teal">
          <q-btn flat color="dark" label="Annuler" v-close-popup/>
          <q-btn flat color="primary" label="Ajouter" @click="addUser" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <!-- REMOVE USER DIALOG -->
    <q-dialog v-model="removeUserDialog" persistent>
      <q-card>
        <q-card-section class="q-pt-lg">
          <span class="text-h6">Voulez-vous supprimer ce modérateur du service <b>{{ service.title }}</b> ?</span>
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Annuler" color="dark" v-close-popup />
          <q-btn flat label="Supprimer" color="negative" @click="removeUser" />
        </q-card-actions>
      </q-card>
    </q-dialog>

  </div>
</template>

<route lang="json">
{
  "name": "serviceDetailAdminRessourcerie",
  "meta": {
  "requiresAuth": false,
  "dynamic": true
  }
}
</route>

<script>
import axios from 'axios';
import { useQuasar } from 'quasar';
import { user } from "../../../../../store/counter";
import { computed, ref } from "vue";
import { addUserToService } from '../../../../../api/Service';
import { useRoute } from 'vue-router';

export default {
  setup() {
    const storedUser = user();
    const route = useRoute();
    const id = route.params.id;
    return {
      storedUser,
      route,
      id,
      addUserDialog: ref(false),
      removeUserDialog: ref(false),
    }
  },

  data() {
    return {
      isloaded: false,
      $q: useQuasar(),
      service: [],
      users: [],
      usersOptions: [],
      userToAdd: null,
      rolesOptions: ['Modérateur','Administrateur'],
      roleSelected: 'Modérateur',
      aclId: null,
    }
  },

  computed: {
    roles() {
      return this.storedUser.roles;
    }
  },

  mounted() {
    this.getService(this.id);
    this.getUsersFromService(this.id);
  },

  methods: {
    getService(id) {
      let self = this;
      axios({
        method: "GET",
        url: "/api/services/"+id,
        headers: {
          'accept': 'application/json'
        },
      }).then(function (response) {
        self.service = response.data
      })
    },
    deleteService() {
      if (window.confirm("Voulez-vous vraiment supprimer ce site ? Attention tous les catalogues et réservations associées seront supprimées !")) {
        let self = this;
        axios({
          method: "DELETE",
          url: "/api/service/"+this.id,
        }).then(function (response) {
          self.$router.push('/book');
        })
      }
    },
    getUsersFromService(id) {
      let self = this;
      axios({
        method: "GET",
        url: "/api/services/" + id + '/users',
      }).then(function (response) {
        self.users = response.data.acls;
      }).finally(function () {
        self.isloaded = true;
      })
    },
    addUser() {
      this.addUserDialog = false;
      let self = this;
      addUserToService(this.userToAdd.value, 'Administrateur', this.id)
        .then(function (response) {
          self.getUsersFromService(self.id);
          self.userToAdd = null;
        });
    },
    filter(val, update, abort) {
      update(() => {
        let self = this;
        if(val === '') {
          this.usersOptions = []
        } else {
          if (val.length > 2) {
            axios({
              method: "GET",
              url: "/api/user/search?query="+val
            }).then(function (response) {
              self.usersOptions = []
              response.data.forEach(user => self.usersOptions.push(user));
            })
          }
        }
      })
    },
    openRemoveUserDialog(id) {
      this.aclId = id;
      this.removeUserDialog = true;
    },
    removeUser() {
      let id = this.aclId;
      this.removeUserDialog = false
      let self = this;
      axios({
        method: "DELETE",
        url: "/api/user/roles/"+id
      }).then(function (response) {
        self.users = self.users.filter(function (value) {
          return value.id !== id;
        })
        self.$q.notify({type: 'positive', position: 'top', message: "Le modérateur a été supprimé du service"})
      }).catch(function (response) {
        self.$q.notify({type: 'negative', position: 'top', message: "Erreur : impossible de supprimer le modérateur du service"})
      });
    },
  },
}
</script>