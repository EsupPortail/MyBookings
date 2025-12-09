<template>
  <div class="row justify-between">
    <div class="text-h6 text-weight-bold q-px-md row">

      <span class="q-mr-md">{{ service.title }}</span>

      <div v-if="service && isAdmin" class="q-mx-xs">
        <q-btn round color="secondary" icon="edit" size="xs">
          <q-tooltip>Renommer le site</q-tooltip>
        </q-btn>
        <q-popup-edit v-model="service.title" v-slot="scope" ref="popupRef">
          <q-input v-model="scope.value" dense autofocus counter @keyup.enter="save(scope.value, 'title')"/>
        </q-popup-edit>
      </div>

      <div v-if="service && isSuperAdmin" class="q-mx-xs">
        <q-btn round color="negative" icon="delete" size="xs" @click="deleteService">
          <q-tooltip>Supprimer le site</q-tooltip>
        </q-btn>
      </div>

    </div>
    <q-tabs
      v-model="tab"
      dense
      align="justify"
      active-color="primary"
      indicator-color="primary"
      class="text-primary"
    >
      <q-route-tab :ripple="false" name="users" label="Modérateurs" :to="'/ressourcerie/administration/site/' + id + '/users'" />
      <q-route-tab :ripple="false" name="catalogue" label="Biens" :to="'/ressourcerie/administration/site/' + id + '/catalogue'" />
      <q-route-tab :ripple="false" name="bookings" label="Demandes" :to="'/ressourcerie/administration/site/' + id + '/bookings'" />
    </q-tabs>
  </div>
  <router-view />
</template>

<route lang="json">
{
  "meta": {
    "requiresAuth": false,
    "dynamic": true
  }
}
</route>

<script>

import { ref, computed } from 'vue'
import { useRoute } from 'vue-router';
import axios from 'axios';
import { user } from "../../../../store/counter";

export default {
  setup () {
    const storedUser = user();
    const route = useRoute();
    const id = computed(() => route.params.id);
    return {
      service: ref(''),
      tab: ref('users'),
      storedUser,
      id,
    }
  },
  data() {
    return {
      isAdmin: false,
      isSuperAdmin: false,
    }
  },
  computed: {
    roles() {
      return this.storedUser.roles;
    }
  },
  mounted() {
    let self = this;
    axios({
      method: "get",
      url: "/api/services/" + this.id,
      headers: {
        'accept': 'application/json'
      },
    }).then(function (response) {
      self.service = response.data
    })
    this.checkIfUserIsAdmin()
  },
  methods: {
    checkIfUserIsAdmin() {
      let self = this;
      if(this.storedUser.isUserAdminSite(this.id)) {
        self.isAdmin = true;
      }
      if(this.storedUser.hasRoles('ROLE_ADMIN')) {
        self.isSuperAdmin = true;
      }
    },
    save(value) {
      let body = {
        'title': value
      }
      let self = this;
      axios({
        method: "patch",
        url: "/api/services/"+this.id,
        data: body,
        headers: { "Content-Type": "application/merge-patch+json" },
      })
      .then(function (response) {
        self.service.title = value;
        Object.keys(self.$refs).forEach(el => self.$refs[el].hide());
        self.$q.notify({type: 'positive', position: 'top', message: 'Le service a été modifié !'})
      })
      .catch(function (response) {
        self.$q.notify({type: 'negative', position: 'top', message: 'Erreur : impossible de modifier le service'})
      });
    },
    deleteService() {
      if (window.confirm("Voulez-vous vraiment supprimer ce site ? Attention tous les catalogues et réservations associées seront supprimées !")) {
        let self = this;
        axios({
          method: "DELETE",
          url: "/api/service/"+this.id,
        }).then(function (response) {
          self.$router.push('/ressourcerie');
        })
      }
    }
  },
  watch:{
    roles(newRoles, oldRoles) {
      this.checkIfUserIsAdmin(newRoles);
    }
  }
}

</script>