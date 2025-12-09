<template>
  <q-card bordered class="my-card">
    <q-card-section>
      <div class="row">
        <div class="col">
          <div class="text-h6">{{ $t('appSettings') }}</div>
        </div>
        <div class="col-auto" v-if="storedUser.hasRoles('ROLE_ADMIN')">
          <q-chip color="primary" text-color="white" icon="people">
            {{ activeUsersCount }} {{ $t('recentlyActiveUsers') }}
            <q-tooltip>{{ $t('usersConnectedLast10Min') }}</q-tooltip>
          </q-chip>
        </div>
      </div>
    </q-card-section>
    <q-separator inset />
    <q-card-section v-if="storedUser.hasRoles('ROLE_ADMIN')">
      <div class="q-pa-md row items-start q-gutter-md">
        <q-card v-ripple class="my-card bg-primary text-white cursor-pointer" @click="$router.push('/manage/site')">
          <q-card-section>
            <div class="text-h6"><q-icon name="domain"/>{{ $t('manageSites') }}</div>
          </q-card-section>
          <q-separator inset/>
          <q-card-actions class="justify-center">
            <q-btn flat :label="$t('access')"></q-btn>
          </q-card-actions>
        </q-card>
        <q-card v-ripple class="my-card bg-primary text-white cursor-pointer" @click="$router.push('/manage/category')">
          <q-card-section>
            <div class="text-h6"><q-icon name="category"/> {{ $t('manageCategories') }}</div>
          </q-card-section>
          <q-separator inset/>
          <q-card-actions class="justify-center">
            <q-btn flat :label="$t('access')"></q-btn>
          </q-card-actions>
        </q-card>
        <q-card v-ripple class="my-card bg-primary text-white cursor-pointer" @click="$router.push('/manage/localization')">
          <q-card-section>
            <div class="text-h6"><q-icon name="explore"/> {{ $t('manageLocalizations') }}</div>
          </q-card-section>
          <q-separator inset/>
          <q-card-actions class="justify-center">
            <q-btn flat :label="$t('access')"></q-btn>
          </q-card-actions>
        </q-card>
        <q-card v-ripple class="my-card bg-primary text-white cursor-pointer" @click="$router.push('/manage/group')">
          <q-card-section>
            <div class="text-h6"><q-icon name="group"/>{{ $t('manageGroups') }}</div>
          </q-card-section>
          <q-separator inset/>
          <q-card-actions class="justify-center">
            <q-btn flat :label="$t('access')"></q-btn>
          </q-card-actions>
        </q-card>
        <q-card v-ripple class="my-card bg-primary text-white cursor-pointer" @click="$router.push('/manage/actuators')">
          <q-card-section>
            <div class="text-h6"><q-icon name="settings_ethernet"/> {{ $t('manageActuators') }}</div>
          </q-card-section>
          <q-separator inset/>
          <q-card-actions class="justify-center">
            <q-btn flat :label="$t('access')"></q-btn>
          </q-card-actions>
        </q-card>
        <q-card v-ripple class="my-card bg-primary text-white cursor-pointer" @click="$router.push('/manage/rules')">
          <q-card-section>
            <div class="text-h6"><q-icon name="gavel"/>{{ $t('customRules') }}</div>
          </q-card-section>
          <q-separator inset/>
          <q-card-actions class="justify-center">
            <q-btn flat :label="$t('access')"></q-btn>
          </q-card-actions>
        </q-card>
      </div>
    </q-card-section>
    <q-card-section v-if="storedUser.hasRoles('ROLE_CAN_SWITCH')">
      <div class="text-h6">{{ $t('takeUserRole') }}</div>
      <div class="row q-gutter-sm">
        <div class="col-3">
          <auto-complete-user-selector :label="$t('searchUser')" :is-admin="true" @update="updateUser"/>
        </div>
        <div class="col q-mt-md">
          <q-btn square color="primary" :label="$t('assumeRole')" no-caps @click="switchToUser"/>
        </div>
      </div>
    </q-card-section>
  </q-card>
</template>

<script>
import {ref} from "vue";
import {user} from "../../store/counter";
import AutoCompleteUserSelector from "../../components/autoCompleteUserSelector.vue";
import {getActiveUsersCount} from "../../api/User";

export default {
  name: "index.vue",
  components: {AutoCompleteUserSelector},
  data() {
    const storedUser = user();
    const switchUser = ref('');
    return {
      storedUser,
      switchUser,
      activeUsersCount: 0
    }
  },
  mounted() {
    this.fetchActiveUsersCount();
  },
  methods: {
    async fetchActiveUsersCount() {
      if (this.storedUser.hasRoles('ROLE_ADMIN')) {
        try {
          const data = await getActiveUsersCount();
          this.activeUsersCount = data.count;
        } catch (error) {
          console.error('Erreur lors de la récupération des utilisateurs actifs:', error);
        }
      }
    },
    updateUser(users) {
      this.switchUser = users;
    },
    switchToUser() {
      if(this.switchUser){
        this.switchUser.forEach(user => {
          window.location.href = '/?_switch_user=' + user.uid;
        });
      }
    }
  }
}
</script>

<style scoped>

</style>