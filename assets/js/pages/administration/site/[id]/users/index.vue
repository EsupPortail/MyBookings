<template>
  <q-card bordered class="my-card">
    <q-card-section>
      <div class="row">
        <div class="col">
          <h1 class="text-h6">{{ $t('sites.userManagement') }}</h1>
        </div>
      </div>
    </q-card-section>
    <q-separator inset/>
    <manage-group :id="id"></manage-group>
  </q-card>
  <q-card>
      <manage-users :id="id"></manage-users>
  </q-card>

</template>

<route lang="json">
{
"name": "manageUsersFromService",
"meta": {
"requiresAuth": false,
"dynamic": true
}
}
</route>

<script>
import ManageGroup from "../../../../../components/table/administration/users/manageGroup.vue";
import ManageUsers from "../../../../../components/table/administration/users/manageUsers.vue";
import { useRoute } from 'vue-router/auto';
export default {
  name: "usersService",
  components: {ManageUsers, ManageGroup},
  setup() {
    const route = useRoute();
    const id = route.params.id;
    return {
      id
    }
  }
}
</script>

<style scoped>
h3 {
  text-align: center;
}

.my-card {
  margin-bottom: 30px;
}
</style>