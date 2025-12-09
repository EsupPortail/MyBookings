<template>
  <div>
    <h3>Administrer les entités</h3>
    <div class="q-pa-md q-gutter-sm" style="text-align: center">
      <q-btn
          v-if="userStore.hasRoles('ROLE_ADMIN_RESSOURCERIE')"
          padding="lg"
          color="primary"
          label="Créer une entité"
          icon="add"
          @click="openEntityDialog = true"
      />
    </div>
    <q-dialog v-model="openEntityDialog" persistent>
      <q-card>
        <q-card-section>
          <div class="text-h6">Créer une nouvelle entité</div>
        </q-card-section>

        <q-card-section class="q-pt-none">
          <q-input
              v-model="entityTitle"
              label="Titre"
          />
        </q-card-section>

        <q-card-actions align="right" class="bg-white text-teal">
          <q-btn flat color="dark" label="Annuler" v-close-popup/>
          <q-btn flat color="primary" label="Créer" @click="createEntity" />
        </q-card-actions>
      </q-card>
    </q-dialog>

    <div class="q-pa-md row items-start q-gutter-md">
      <div v-if="catalogueOfServices.length === 0" style="width: 100%; text-align: center">
        <p>Vous n'avez aucun service à administrer</p>
      </div>
      <div v-for="element in catalogueOfServices">
        <q-card v-ripple @click="push(element.id)" class="cursor-pointer">
          <q-card-section>
            <div class="text-h6" style="text-align: center">{{ element.title }}</div>
            <q-badge v-if="element.waiting > 0" color="red" floating class="q-mr-lg"><b>{{element.waiting}}</b>
              <q-tooltip anchor="top middle" self="bottom middle" class="bg-red" :offset="[10, 10]">
                <b>{{ element.waiting }} réservation(s) en attente</b>
              </q-tooltip>
            </q-badge>
            <q-space></q-space>
            <q-badge v-if="element.deposits > 0" color="blue" floating><b>{{element.deposits}}</b>
              <q-tooltip anchor="top middle" self="bottom middle" class="bg-blue" :offset="[10, 10]">
                <b>{{ element.deposits }} dépôt(s) en attente</b>
              </q-tooltip>
            </q-badge>
          </q-card-section>

          <q-separator  />

          <q-card-section>
            <div class="q-pa-sm">
              <q-chip class="q-pa-md" square color="primary" text-color="white" icon="verified_user">
                {{ element.role }}
              </q-chip>
            </div>
          </q-card-section>
        </q-card>
      </div>
    </div>
  </div>
</template>

<script>
import axios from 'axios';
import { useQuasar } from "quasar";
import { ref } from 'vue';
import { createService } from '../../../../api/Service'
import { user } from '../../../../store/counter'

const userStore = user()

export default {
  name: "index.vue",
  setup() {
    return {
      openEntityDialog: ref(false),
      userStore
    }
  },
  data() {
    return {
      catalogueOfServices: [],
      $q: useQuasar(),
      entityTitle: ''
    }
  },
  mounted() {
    this.reload()
  },
  methods: {
    push(serviceId) {
      this.$router.push('/ressourcerie/administration/site/' + serviceId + '/users');
    },
    createEntity() {
      let self = this
      let bodyFormData = new FormData()
      bodyFormData.append('title', this.entityTitle)
      bodyFormData.append('type', 'Ressourcerie')
      bodyFormData.append('admin', JSON.stringify({}))
      createService(bodyFormData).then(function (serviceId) {
        self.reload()
        self.openEntityDialog = false;
      })
    },
    reload() {
      this.$q.loading.show();
      let isAdminRessourcerie = this.userStore.hasRoles('ROLE_ADMIN_RESSOURCERIE')
      let self = this;
      axios({
        method: "get",
        url: '/api/services?type=Ressourcerie'
      }).then(function (response) {
        if (response.data.length > 0) {
          self.catalogueOfServices = []
          let servicesToFormat = response.data;
          servicesToFormat.forEach(elt => {
            self.catalogueOfServices.push({
              'id'      : isAdminRessourcerie ? elt.id : elt.id,
              'title'   : isAdminRessourcerie ? elt.title : elt.title,
              'role'    : isAdminRessourcerie ? 'Adminitrateur Ressourcerie' : (elt.acls.find(acl => acl.username === 'specificUsername')?.type === 'ROLE_ADMINSITE' ? 'Administrateur' : 'Modérateur'),
              'waiting' : elt.requestedBookingEffects,
              'deposits': elt.pendingDeposits,
            })
          });
        }
      }).finally(() => {
        self.$q.loading.hide();
      })
    }
  },
}
</script>

<style scoped>
h3 {
  text-align: center;
}
</style>