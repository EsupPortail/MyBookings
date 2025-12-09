<template>
  <q-card-section>
    <q-img src="/images/MyBookings_bleu_uca.png" fit="scale-down" height="150px" />
  </q-card-section>
  <q-card-section class="row justify-center">
    <q-btn class="col-3" color="primary" icon="school" :label="$t('schoolAccount')" no-caps size="lg" href="/book"/>
  </q-card-section>
  <q-card-section class="row justify-center">
    <q-expansion-item
        class="col-3 shadow-1 overflow-hidden"
        expand-separator
        icon="person"
        label="Membre extérieur"
        header-class="bg-primary text-white text-h6"
        expand-icon-class="text-white"
    >
      <q-card>
        <q-card-section>
          <p>Vous ne pouvez vous connecter que si un responsable de ressource vous a préalablement invité.</p>
          <div class="row justify-between">
            <q-input
                class='col-10'
                type="email"
                name="email"
                label="Adresse mail"
                v-model="email"
                :rules="[
                    val => !!val || 'Veuillez renseigner votre adresse mail',
                    val => /.+@.+\..+/.test(val) || 'Adresse mail invalide'
                ]"
            />
            <div class="col-auto">
              <q-btn icon="send" color="primary" type="submit" padding="md" size="md" round @click="sendLogin"/>
            </div>
          </div>
          <div>
            <q-banner v-if="response !== null" dense inline-actions :class="isError ? 'bg-red-5 text-white' : 'bg-green-5 text-white'">
              <template v-if="isError" v-slot:avatar>
                <q-icon name="warning" color="white" />
              </template>
              <template v-if="!isError" v-slot:avatar>
                <q-icon name="check" color="white" />
              </template>
              {{ response }}
            </q-banner>
          </div>
        </q-card-section>
      </q-card>
    </q-expansion-item>
  </q-card-section>
</template>

<route lang="json">
{
  "name": "home",
  "meta": {
    "requiresAuth": false,
    "public": true
  }
}
</route>
<script>
import axios from "axios";
import {ref} from "vue";

const response = ref(null);
const isError = ref(false);

export default {
  name: "home",
  data() {
    return {
      email: '',
      response,
      isError
    }
  },
  methods: {
    sendLogin() {
      let bodyFormData = new FormData();
      bodyFormData.set('email', this.email);
      axios({
        method: "POST",
        data: bodyFormData,
        url: "/login",
      }).then(function (resp) {
        response.value = 'Un mail de confirmation vous a été envoyé';
        isError.value = false;
      }).catch(function (error) {
        if(error.response.status === 403) {
          response.value = "Adresse mail inconnue";
        } else if(error.response.status === 429) {
          response.value = "Trop de tentatives de connexion";
        }
        isError.value = true;
    });
    }
  },
}
</script>

<style>
  body {
    background: url("https://cdn.uca.fr/images/ent/background-ent2-mountain-1.jpg") no-repeat center center fixed;
  }
</style>