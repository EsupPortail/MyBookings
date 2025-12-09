<template>
  <q-dialog v-model="dialog" persistent>
    <q-card style="width: 700px; max-width: 80vw;">
      <q-card-section>
        <div class="text-h5">{{ $t('externalUsers.addUserDialog') }}</div>
      </q-card-section>
      <q-card-section>
        <q-form ref="form">
          <q-input v-model="name" :label="$t('externalUsers.fullName')" :rules="[val => !!val || $t('externalUsers.requiredField')]" />
          <q-input type="email" v-model="email" :label="$t('externalUsers.email')" :rules="[val => !!val || $t('externalUsers.requiredField'), val => emailTest(val)]" />
        </q-form>
      </q-card-section>
      <q-card-actions align="right">
        <q-btn color="primary" :label="$t('externalUsers.validate')" @click="send" />
        <q-btn color="negative" :label="$t('common.cancel')" @click="close" />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script>
import {addExternalUserToService} from "../../../../api/Service";
import {ref} from "vue";
const email = ref('');
const name = ref('');

export default {
  name: "addUsers",
  props: {
    openDialog: Boolean,
    id: String
  },
  data() {
    return {
      dialog: false,
      name,
      email,
    }
  },
  methods: {
    send() {
      let self = this;
      this.$refs.form.validate().then(function (response) {
        if(response === true) {
          let body = new FormData();
          body.append('name', name.value);
          body.append('email', email.value);
          addExternalUserToService(self.id, body).then(function (response) {
            self.$emit('reload');
          })
        }
      })
    },
    close() {
      this.$emit('close');
    },
    emailTest(val) {
      if(!val.includes('@uca.fr')) {
        return /.+@.+\..+/.test(val) || this.$t('externalUsers.invalidEmail');
      } else {
        return this.$t('externalUsers.invalidEmail');
      }
    },
  },
  watch: {
    openDialog: function (val) {
      this.dialog = val;
    }
  }
}
</script>

<style scoped>

</style>