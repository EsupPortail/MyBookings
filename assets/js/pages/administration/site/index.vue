<template>
  <div>
    <h3>{{ $t('administerService') }}</h3>
    <div class="q-pa-md">
      <div v-if="catalogueOfServices.length === 0" style="width: 100%; text-align: center">
        <p>{{ $t('noServiceToAdminister') }}</p>
      </div>
      <div v-else>
        <q-input
          v-model="searchFilter"
          :placeholder="$t('search')"
          outlined
          dense
          clearable
          class="q-mb-md"
        >
          <template v-slot:prepend>
            <q-icon name="search" />
          </template>
        </q-input>

        <q-list bordered separator class="rounded-borders">
          <q-item
            v-for="element in filteredServices"
            :key="element.id"
            clickable
            v-ripple
            @click="push(element.id)"
            class="service-item"
          >
            <q-item-section avatar>
              <q-avatar color="primary" text-color="white" icon="business" />
            </q-item-section>

            <q-item-section>
              <q-item-label class="text-h6 text-weight-medium">{{ element.title }}</q-item-label>
              <q-item-label caption lines="1">
                <div class="row q-gutter-xs q-mt-xs">
                  <q-chip
                    v-for="acl in element.acls.filter(a => a.user.username === storeUser.username)"
                    :key="acl.id"
                    size="sm"
                    color="primary"
                    text-color="white"
                    :icon="acl.type === 'ROLE_ADMINSITE' ? 'admin_panel_settings' : 'verified_user'"
                  >
                    {{ acl.type === 'ROLE_ADMINSITE' ? $t('administrator') : $t('moderator') }}
                  </q-chip>
                </div>
              </q-item-label>
            </q-item-section>

            <q-item-section side>
              <div v-if="servicesData.length>0" class="row items-center q-gutter-md">
                <q-badge
                  v-if="getDataFromService(element.id).pendingBookingsFromService > 0"
                  color="red"
                  text-color="white"
                  :label="getDataFromService(element.id).pendingBookingsFromService + ' ' + $t('bookingsAwait') "
                  class="q-px-sm"
                />
                <q-icon name="chevron_right" color="grey" size="24px" />
              </div>
            </q-item-section>
          </q-item>
        </q-list>

        <div v-if="filteredServices.length === 0 && searchFilter" class="text-center q-mt-md text-grey-6">
          {{ $t('noResultsFound') }}
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import {useQuasar} from "quasar";
import {getRolesAdminSite} from "../../../utils/userUtils";
import {user} from "../../../store/counter";
import {getServiceByType, getDataServiceWithAcl} from "../../../api/Service";
import {ref} from "vue";

const storeUser = user();
const catalogueOfServices = ref([]);
const servicesData = ref([]);

export default {
  name: "index.vue",
  data() {
    return {
      catalogueOfServices,
      $q: useQuasar(),
      informatique: 0,
      audiovisuel: 0,
      salle: 0,
      storeUser,
      searchFilter: '',
      servicesData
    }
  },
  mounted() {
    this.$q.loading.show();
    if(storeUser.roles.length > 0) {
      this.getServices();
    }
  },
  computed: {
    getStoredRoles() {
      return storeUser.roles;
    },
    filteredServices() {
      if (!this.searchFilter) {
        return this.catalogueOfServices;
      }
      const search = this.searchFilter.toLowerCase();
      return this.catalogueOfServices.filter(service =>
        service.title.toLowerCase().includes(search)
      );
    }
  },
  methods: {
    getDataFromService(id) {
      return servicesData.value.find(service => service.id === id);
    },
    push(id) {
      this.$router.push('/administration/site/' + id);
    },
    getServices() {
      let self = this;
      let roles = getRolesAdminSite();
      getServiceByType('bookings').then(function (response) {
        catalogueOfServices.value = response.data;
        self.$q.loading.hide();
        getDataServiceWithAcl('Bookings', roles).then(function (response) {
          servicesData.value = response.data;
        })
      })
    }
  },
  watch: {
    getStoredRoles: function () {
      this.getServices();
    }
  }
}
</script>

<style scoped>
h3 {
  text-align: center;
  margin-bottom: 1.5rem;
}

.service-item:hover {
  background-color: rgba(0, 0, 0, 0.03);
}

</style>