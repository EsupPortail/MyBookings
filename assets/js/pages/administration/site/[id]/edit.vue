<template>
  <div v-if="storedUser.isUserAdminSite(id)">
    <q-card bordered class="my-card">
        <q-card-section>
          <div class="row">
            <div class="col-auto">
              <q-btn v-if="storedUser.hasRoles('ROLE_ADMIN')" round color="negative" icon="delete" @click="openRemoveDialogService(service.id)" style="margin-right: 10px">
                <q-tooltip>
                  Supprimer le site
                </q-tooltip>
              </q-btn>
            </div>
            <div class="col">
              <div class="cursor-pointer" style="margin: auto; overflow-wrap: break-word">
                <h1 class="text-h6">{{ $t('detailService') }} : <b>{{service.title}}</b></h1>
                <q-popup-edit v-model="service.title" v-slot="scope" style="display: inline-block" ref="popupRef">
                  <q-input v-model="scope.value" dense autofocus counter @keyup.enter="save(scope.value, 'title')" style="width: 92%; display: inline-block"/>
                  <q-btn round color="primary" icon="save" size="0.7rem" style="margin-top: 4%; margin-left: 3%" @click="save(scope.value, 'title')"></q-btn>
                </q-popup-edit>
              </div>
            </div>
          </div>
        </q-card-section>
      <q-separator inset />
      <q-card-section>
        <q-card-section>
          <h6  style="margin-top: 10px"><q-icon name="people" color="primary" style="margin-right: 5px; margin-bottom: 5px"></q-icon>Utilisateurs :</h6>
          <div class="q-pa-md row items-start q-gutter-md">
            <q-card v-if="isLoaded === false" flat bordered class="my-card" style="width: 200px; height: 156px">
              <q-card-actions align="center" style="height: 154px">
                <q-circular-progress
                    indeterminate
                    size="80px"
                    :thickness="0.2"
                    color="primary"
                    center-color="transparent"
                    track-color="transparent"
                    class="q-ma-md"
                />
                Chargement des utilisateurs

              </q-card-actions>
            </q-card>
          <q-card flat bordered v-for="user in users" class="my-card">

            <q-card-section horizontal>
              <q-card-actions vertical class="justify-around q-px-md">
                <q-btn flat round icon="delete" color="negative" @click="openRemoveDialogUser(user.id)" />
              </q-card-actions>
              <q-separator vertical></q-separator>
              <q-card-section vertical>
                <div class="text-h6">{{ user.username }}</div>
                <div class="text-subtitle2">{{ user.mail }}</div>
                <div class="q-pa-md">
                  <q-chip v-if="user.role === 'ROLE_ADMINSITE'" color="primary" text-color="white">
                    Administrateur
                  </q-chip>
                  <q-chip v-if="user.role === 'ROLE_MODERATOR'" color="primary" text-color="white">
                    Modérateur
                  </q-chip>
                </div>
              </q-card-section>
            </q-card-section>
          </q-card>
          <q-card flat bordered class="my-card" style="width: 180px; height: 156px">
            <q-card-actions align="center" style="height: 156px">
              <q-btn round color="primary" icon="add" @click="userDialog = true">
                <q-tooltip>
                  Ajouter un utilisateur
                </q-tooltip>
              </q-btn>
            </q-card-actions>
          </q-card>
          </div>
        </q-card-section>
      </q-card-section>
    </q-card>
    <div>
    </div>

    <RemoveDialog
      v-model="confirmDeleteService"
      message="Voulez-vous vraiment supprimer ce site ?<br><small>Attention tous les catalogues et réservations associées seront supprimées !</small>"
      :ok-action="deleteService"
    />

    <RemoveDialog
      v-model="confirmDeleteUser"
      message="Voulez-vous supprimer l'utilisateur du service ?"
      :ok-action="removeUserRole"
    />

    <q-dialog
        v-model="userDialog"
        persistent
    >
      <q-card style="width: 700px; max-width: 80vw;">
        <q-card-section>
          <div class="text-h6">Ajouter un utilisateur au site</div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-select
              filled
              v-model="addUser"
              use-input
              use-chips
              input-debounce="0"
              label="Administrateur(s)"
              :options="options"
              @filter="filter"
              new-value-mode="add-unique"
              hint="Rechercher et sélectionner un utilisateur"
          >
          </q-select>
          <q-select
              v-model="addUserMode"
              label="Rôle"
              :options="optionsModes"
          />
        </q-card-section>

        <q-card-actions align="right" class="bg-white text-teal">
          <q-btn flat label="Annuler" v-close-popup/>
          <q-btn flat label="Envoyer" @click="addNewUser" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </div>
  <div v-else>
    <p style="text-align: center">Vous n'avez pas les droits requis pour modifier le site.</p>
  </div>
</template>

<route lang="json">
{
"name": "editSite",
"meta": {
"requiresAuth": false,
"dynamic": true
}
}
</route>

<script>
import {counter, user} from "../../../../store/counter";
import {ref} from "vue";
import {addUserToService, deleteService, editService, getService, getUserService} from "../../../../api/Service";
import {removeUserRole, searchInAllUser, searchUser} from "../../../../api/User";
import RemoveDialog from "../../../../components/dialog/RemoveDialog.vue";
import { useRoute } from 'vue-router/auto';

const service = ref([]);
const options = ref([]);
const users = ref([]);
const isLoaded = ref(false);
const addUser = ref(null);
export default {
  setup() {
    const storedUser = user();
    const nbCounter = counter();
    const route = useRoute();
    const id = route.params.id;
    function incrementCounter() {
      nbCounter.increment();
    }
    window.stores = { incrementCounter }
    return {
      storedUser,
      incrementCounter,
      userDialog: ref(false),
      confirmDeleteUser: ref(false),
      confirmDeleteService: ref(false),
      id
    }
  },
  components: { RemoveDialog },
  data() {
    return {
      isLoaded,
      service,
      users,
      selectedFile: false,
      newImage: '',
      options,
      optionsModes: ['Administrateur', 'Modérateur'],
      addUserMode: 'Administrateur',
      addUser,
      serviceId: null,
      userId: null
    }
  },
  created() {
    this.getService(this.id);
    this.getUserFromService(this.id);
  },
  methods: {
    getService(id) {
      getService(id).then(function (response) {
        service.value = response
      });
    },
    getUserFromService(id) {
      getUserService(id).then(function (response) {
        users.value = response;
        isLoaded.value = true;
      })
    },
    openRemoveDialogService(id) {
      this.serviceId = id;
      this.confirmDeleteService = true;
    },
    deleteService() {
      let self = this
      deleteService(this.serviceId).then(function (response) {
        self.$router.push('/book');
      })

      this.confirmDeleteService = false
    },
    addNewUser() {
      this.userDialog = false;
      let self = this;
      addUserToService(addUser.value.value, this.addUserMode, this.id).then(function (response){
        self.getUserFromService(self.id);
        addUser.value = null;
      });
    },
    openRemoveDialogUser(id) {
      this.userId = id;
      this.confirmDeleteUser = true;
    },
    removeUserRole() {
      let id = this.userId;
      removeUserRole(this.userId).then(function (response) {
        users.value = users.value.filter(function (value) {
          return value.id !== id;
        })
      })

      this.confirmDeleteUser = false
    },
    filter(val, update, abort) {
      update(() => {
        let self = this;
        if(val === '') {
          options.value = [];
        } else {
          if (val.length > 2) {
            searchInAllUser(val).then(function (response) {
              options.value = []
              response.data.forEach(user => options.value.push(user));
            })
          }
        }
      })
    },
    save(value) {
      let body = {
        'title': value
      }
      let self = this;
      editService(this.id, body).then(function (response) {
        service.value.title = value;
        //Cacher les popup
        Object.keys(self.$refs).forEach(el => self.$refs[el].hide());
      });
    }
  },
}
</script>

<style scoped>
h6 {
  margin-bottom: 10px;
}

.action_button {
  position: absolute;
  margin-left: 0.5%;
  margin-top: 3%;
  z-index: 10;
}

.info .col-6{
  padding: 10px 15px;
  border: 1px solid rgba(86,61,124,.2);
}

img {
  max-height: 500px;
  max-width: 98%;
  width: auto;
  height: auto;
  top: 0;
  bottom: 0;
  left: 0;
  right: 0;
  margin: auto;
}

.my-card {
  margin-bottom: 30px;
}

.frame {
  display: inline-block;
  position: relative;
}

</style>