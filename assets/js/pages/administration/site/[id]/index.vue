<template>
  <div>
    <q-card bordered class="my-card">
        <q-card-section>
          <div class="row">
            <div class="col">
              <h1 class="text-h5">{{ $t('detailService') }} : <b>{{service.title}}</b></h1>
            </div>
            <div class="col-auto">
              <q-btn v-if="storedUser.isUserAdminSite(id)" round color="secondary" icon="edit" :to="{ name: 'editSite' }">
                <q-tooltip>
                  {{ $t('sites.edit') }}
                </q-tooltip>
              </q-btn>
            </div>
          </div>
        </q-card-section>
      <q-separator inset />
      <q-card-section>
          <h2  class="text-h6"><q-icon name="people" color="primary" style="margin-right: 5px; margin-bottom: 5px"></q-icon>{{ $t('serviceUsers') }} :</h2>
          <div class="q-pa-md row items-start q-gutter-md">
            <q-card v-if="isLoaded === false" flat bordered class="my-card" style="width: 200px; height: 156px">
              <q-card-actions align="center" style="height: 154px">
                <q-circular-progress
                    indeterminate
                    size="90px"
                    :thickness="0.2"
                    color="primary"
                    center-color="transparent"
                    track-color="transparent"
                    class="q-ma-md"
                />
                {{ $t('loading') }}

              </q-card-actions>
            </q-card>
            <q-card flat bordered v-for="user in users" class="my-card">
              <q-card-section vertical>
                <div class="text-h6">{{ user.username }}</div>
                <div class="text-subtitle2">{{ user.mail }}</div>
                  <div class="q-pa-md">
                    <q-chip v-if="user.role === 'ROLE_ADMINSITE'" color="primary" text-color="white">
                      {{ $t('administrator') }}
                    </q-chip>
                    <q-chip v-if="user.role === 'ROLE_MODERATOR'" color="primary" text-color="white">
                      {{ $t('moderator') }}
                    </q-chip>
                  </div>
              </q-card-section>
            </q-card>
          </div>
      </q-card-section>
    </q-card>
  </div>
</template>

<route lang="json">
{
  "name": "serviceDetailAdmin",
  "meta": {
  "requiresAuth": false,
  "dynamic": true
  }
}
</route>

<script>
import { useQuasar } from 'quasar';
import { user } from "../../../../store/counter";
import { ref } from "vue";
import { getService, getUserService } from "../../../../api/Service";
import { useRoute } from 'vue-router/auto';

const service = ref([]);
const users = ref([]);
const isLoaded = ref(false);

export default {
  setup() {
    const storedUser = user();
    const route = useRoute();
    const id = route.params.id;
    return {
      storedUser,
      id
    }
  },
  data() {
    return {
      isLoaded,
      service,
      bookings: [],
      users,
      $q: useQuasar(),
      selectedFile: false,
      newImage: '',
      tab: ref('pending')
    }
  },
  computed: {
    roles() {
      return this.storedUser.roles;
    }
  },
  mounted() {
    console.log(this.id);
    this.getService(this.id);
  },
  methods: {
    getService(id) {
      getService(id).then(function (response) {
        service.value = response
      });
      getUserService(id).then(function (response) {
        users.value = response;
        isLoaded.value = true;
      })
    },
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