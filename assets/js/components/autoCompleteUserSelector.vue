<template>
  <q-select
      ref="searchParticipant"
      id="searchParticipant"
      v-model="users"
      outlined
      use-input
      fill-input
      use-chips
      multiple
      input-debounce=200
      label="Ajouter des participants à la réservation (Nom Prénom)"
      :options="options"
      @add="addUser"
      @filter="filter"
      @update:model-value="emitUsers"
      new-value-mode="add-unique"
  >
    <template v-slot:no-option>
      <q-item>
        <q-item-section class="text-grey">
          Aucun résultat
        </q-item-section>
      </q-item>
    </template>
  </q-select>
</template>

<script>
import {searchInAllUser, searchUserAsAdmin} from "../api/User";
import {ref} from "vue";
const options = ref([]);
export default {
  name: "autoCompleteUserSelector",
  props: {
    isAdmin: Boolean,
  },
  data() {
    return {
      users: [],
      options,
      allowUsersToBook: true,
    }
  },
  methods: {
    addUser() {
      this.$refs.searchParticipant.updateInputValue('')
    },
    filter(val, update) {
      update(() => {
        if(val === '') {
          options.value = []
        } else {
          if (val.length > 1) {
            if(this.isAdmin) {
              searchUserAsAdmin(val).then(function (response) {
                options.value = []
                response.data.forEach(user => options.value.push(user));
              })
            } else {
              searchInAllUser(val).then(function (response) {
                options.value = []
                response.data.forEach(user => options.value.push(user));
              })
            }
          }
        }
      })
    },
    emitUsers() {
      this.$emit('update', this.users);
    }
  }
}

</script>

<style scoped>

</style>