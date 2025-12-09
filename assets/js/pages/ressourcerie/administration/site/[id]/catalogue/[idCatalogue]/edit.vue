<template>
  <div v-if="isloaded">
    <q-card bordered class="my-card">
      <q-card-section>
        <div class="q-flex q-gutter-lg row">
          

          
          <div style="max-width: 300px;">
              <q-btn-dropdown color="primary" :label="optionsStatus[catalogue.status]" ref="dropdownStatus">
                <q-list>
                  <template v-for="(action, status) in actionStatus">
                  <q-item v-if="status !== catalogue.status"  clickable v-close-popup @click="updateStatus(status)">
                    <q-item-section>
                      <q-item-label>{{action}}</q-item-label>
                    </q-item-section>
                  </q-item>
                  </template>
                </q-list>
              </q-btn-dropdown>
          </div>
          <div class="q-ml-auto">
            <q-btn v-if="user.isUserAdminSite(id, true)" round color="negative" icon="delete" @click="deleteCatalogue(idCatalogue)">
              <q-tooltip>
                Supprimer le catalogue
              </q-tooltip>
            </q-btn>
          </div>
        </div>
      </q-card-section>
      <q-separator inset />
      <q-card-section horizontal>
        <q-card-section>
          <div class="col-6">
            <div style="text-align: center">
              <h6 style="margin-top: 10px">{{ $t('title') }} :</h6>
              <div class="cursor-pointer" style="margin: auto; overflow-wrap: break-word">
                <p>{{catalogue.title}}</p>
                <q-popup-edit v-if="user.isUserAdminSite(id, true)" v-model="catalogue.title" v-slot="scope" style="display: inline-block" ref="popupRef">
                  <q-input v-model="scope.value" dense autofocus counter @keyup.enter="save(scope.value, 'title')" style="width: 92%; display: inline-block"/>
                  <q-btn color="primary" icon="save" size="0.7rem" class="absolute-right" @click="save(scope.value, 'title')"></q-btn>
                </q-popup-edit>
              </div>
              <h6>{{ $t('description') }} :</h6>
              <div class="cursor-pointer" style="margin: auto; overflow-wrap: break-word; max-width: 100%">
                <p v-html="catalogue.description"></p>
                <q-popup-edit v-if="user.isUserAdminSite(id, true)" v-model="catalogue.description" v-slot="scope" style="display: inline-block; width: 500px" ref="popupRef2">
                  <q-editor
                      v-model="scope.value"
                      min-height="4rem"
                      autofocus
                      @keyup.enter.stop
                      style="width: 92%; display: inline-block"
                  />
                  <q-btn color="primary" icon="save" size="0.7rem" class="absolute-right" @click="save(scope.value, 'description')"></q-btn>
                </q-popup-edit>
              </div>
              <div>
                <h6>{{ $t('type') }} :</h6>
                <div class="cursor-pointer" style="margin: auto; overflow-wrap: break-word; width: 500px;">
                  <p>{{catalogue.type.title}}</p>
                  <q-popup-edit v-if="user.isUserAdminSite(id, true)" v-model="catalogue.type" v-slot="scope" style="display: inline-block" ref="popupRef3">
                    <q-select v-model="scope.value" :options="options" style="width: 90%; display: inline-block" option-value="id" option-label="title"></q-select>
                    <q-btn color="primary" icon="save" size="0.7rem" class="absolute-right" @click="save(scope.value, 'type')"></q-btn>
                  </q-popup-edit>
                </div>
              </div>
            </div>
          </div>
        </q-card-section>
        <q-separator vertical />
        <q-card-section style="margin: auto; overflow-wrap: break-word; width: 500px">
            <q-img :src="'/uploads/'+catalogue.image" height="300px" fit="contain">
              <template v-slot:error>
                <div class="absolute-full flex flex-center bg-grey text-white">
                    <q-icon size="md" name="no_photography"/>
                  </div>
              </template>
              <q-tooltip
                  class="bg-primary text-body2"
                  anchor="center middle"
              >Cliquez sur l'image pour l'éditer</q-tooltip>
            </q-img>
            <q-popup-edit v-if="user.isUserAdminSite(id, true)" v-model="catalogue.image" v-slot="scope" ref="popupRef4">
              <q-uploader
                  label="Téléverser une image"
                  @added="fileSelected"
                  @removed="fileRemoved"
                  accept=".jpg, .png, .jpeg"
                  style="width: 100%; margin-top: 10%"
                  flat
                  bordered
              />
              <q-btn color="primary" icon="save" size="0.7rem" id="saveImg" style="margin-top: 5%; margin-left: 60%; display: none" label="Enregistrer" @click="save(newImage, 'image')"></q-btn>
            </q-popup-edit>
        </q-card-section>
      </q-card-section>
    </q-card>

    <div>
      <q-card bordered class="my-card">
        <q-card-section>
          <custom-table-admin-catalog-effects :resources="catalogue.resource" :id-service="id" :catalogue="idCatalogue" :is-editable="true"></custom-table-admin-catalog-effects>
        </q-card-section>
      </q-card>
    </div>
  </div>
</template>

<route lang="json">
{
  "name": "editCatalogueRessourcerie",
  "meta": {
    "requiresAuth": false,
    "dynamic": true
  }
}
</route>

<script>
import axios from 'axios';
import {useQuasar} from 'quasar';
import customTableAdminCatalogEffects from '../../../../../../../components/table/ressourcerie/customTableAdminCatalogEffects.vue';
import {counter, user} from "../../../../../../../store/counter";
import { getCategoryByType } from '../../../../../../../api/Category';
import { updateCatalogResource } from '../../../../../../../api/CatalogRessource';
import { ref, computed } from 'vue';
import { useRoute } from 'vue-router';

const defaultOptions = {
  'rc_pending': 'En attente de modération',
  'rc_disabled': 'Refusé'
}

const defaultAction = {
  'rc_pending': 'Remettre en attente',
  'rc_disabled': 'Refuser'
}

const optionsStatus = ref([]);
const actionStatus = ref([]);

export default {
  components: {customTableAdminCatalogEffects},
  setup() {
    const nbCounter = counter();
    const route = useRoute();
    const id = computed(() => route.params.id);
    const idCatalogue = computed(() => route.params.idCatalogue);
    function incrementCounter() {
      nbCounter.increment();
    }
    window.stores = { incrementCounter }
    return {
      nbCounter,
      incrementCounter,
      getCategoryByType,
      id,
      idCatalogue,
    }
  },
  data() {
    return {
      user: user(),
      isloaded: false,
      catalogue: [],
      $q: useQuasar(),
      selectedFile: false,
      newImage: '',
      provision: false,
      events: [],
      blockingDate:[],
      isUpdate: 0,
      options: [],
      subOptions: [],
      optionsStatus,
      actionStatus,
    }
  },
  async mounted() {
    if (this.user.roles.length === 0) {
      await this.user.getRoles()
    }

    this.getOptionsStatusByRoles();
    this.$q.loading.show();
    this.getCatalogue(this.idCatalogue);
    this.getCategory();
  },
  methods: {
    updateStatus(status) {
      this.catalogue.status = status;
      this.$refs.dropdownStatus.hide();
    },
    getOptionsStatusByRoles() {
      let options = defaultOptions;
      let actions = defaultAction;
      if(this.user.isUserAdminSite(this.id, true)) {
        options['rc_validated'] = 'En attente de modération SIT';
        actions['rc_validated'] = 'Valider le catalogue';
      }
      if(this.user.hasRoles('ROLE_ADMIN_RESSOURCERIE')) {
        options['rc_published'] = 'Publié';
        actions['rc_published'] = 'Publier le catalogue';
      }

      optionsStatus.value = options;
      actionStatus.value = actions;
    },
    deleteCatalogue(id) {
      if (confirm("Voulez-vous supprimer le catalogue et toutes les ressources associées ?") == true) {
        this.$q.loading.show();
        let self = this;
        axios({
          method: "delete",
          url: "/api/catalogue/"+id,
        })
            .then(function (response) {
              self.isloaded = true;
              self.$q.loading.hide();
              self.$router.replace('/ressourcerie/administration/site/'+self.id+'/catalogue');
            })
      }
    },
    getCatalogue(id) {
      let self = this;
      this.blockingDate = [];
      axios({
        method: "get",
        url: "/api/catalogue_resources/"+id,
        headers: {
          'accept': 'application/json'
        },
      })
        .then(function (response) {
          self.catalogue = response.data;
          self.isloaded = true;
          self.$q.loading.hide();
        })
    },
    save(value, field) {
      //Modification du catalogue en front
      this.editCatalogue(value, field);
      let self = this;
      //Construction du body
      let bodyFormData = new FormData();
      if(field !== 'image') {
        bodyFormData.append('image', null);
      } else {
        bodyFormData.append('image', this.newImage);
      }
      bodyFormData.append('title', this.catalogue.title);
      bodyFormData.append('description', this.catalogue.description);
      bodyFormData.append('type', this.catalogue.type.id);
      bodyFormData.append('status', this.catalogue.status);
      bodyFormData.append('service', this.catalogue.service.id);
      if(this.catalogue.actuator !== null) {
        bodyFormData.append('actuator', this.catalogue.actuator.id);
      } else {
        bodyFormData.append('actuator', null);
      }
      axios({
        method: "post",
        url: "/api/catalogue/"+this.idCatalogue,
        data: bodyFormData,
        headers: { "Content-Type": "multipart/form-data" },
      })
        .then(function (response) {
          //Mise à jour de la barre latérale
          self.incrementCounter();
          //Cacher les popup
          Object.keys(self.$refs).forEach(el => self.$refs[el].hide());
          self.getCatalogue(self.idCatalogue);
          //Notification utilisateur
          self.$q.notify({
            type: 'positive',
            message: 'Le catalogue a été modifié !',
            position: 'top',
          })
          //Rafraichir l'image
          if(field === 'image') {
            self.catalogue.image= self.catalogue.image + '?t='+new Date().getTime();
          }
        })
        .catch(function (response) {
          //Notification utilisateur
          self.$q.notify({
            type: 'negative',
            message: 'Erreur : impossible de modifier le catalogue',
            position: 'top',
          })
        });
    },
    editCatalogue(value, field) {
      switch (field) {
        case 'title':
          this.catalogue.title = value;
          break;
        case 'description':
          this.catalogue.description = value;
          break;
        case 'image':
          break;
        case 'type':
          this.catalogue.type = value;
          break;
        default:
          console.log('Erreur d\'édition');
      }
    },
    fileSelected (files) {
      if (files.length !== 0) {
        this.selectedFile = true
      }
      this.newImage=files[0];
      document.getElementById('saveImg').style.display = 'inline-block';
    },
    fileRemoved () {
      this.selectedFile = false
    },
    getCategory() {
      let self = this;
      getCategoryByType('ressourcerie')
        .then(function (response) {
          self.options = response.data;
        })
    }
  },
  watch: {
    // watch catalogue status and reload if it changes
    'catalogue.status': function (newStatus, oldStatus) {
      if (oldStatus !== undefined && newStatus !== oldStatus) {
        console.log('Status changed from', oldStatus, 'to', newStatus);
        updateCatalogResource(this.idCatalogue, { status: newStatus })
          .then(() => {
            this.$q.notify({
              type: 'positive',
              message: 'Le statut du catalogue a été modifié !',
              position: 'top',
            });
          })
          .catch(() => {
            this.$q.notify({
              type: 'negative',
              message: 'Erreur : impossible de modifier le statut du catalogue',
              position: 'top',
            });
          });
      }
    }
  }
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
}

.info .col-6{
  padding: 10px 15px;
  border: 1px solid rgba(86,61,124,.2);
}

.frame {
  display: inline-block;
  position: relative;
}

.my-card {
  margin-bottom: 30px;
}

.left-container {
  overflow: hidden;
  position: relative;
  height: 80vh;
  display: inline-block;
  width: 100%;
  margin-top: -15px;
}


</style>