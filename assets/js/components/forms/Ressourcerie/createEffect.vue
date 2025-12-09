<script setup>

import axios from "axios";
import {date} from "quasar";
import {onMounted, ref} from "vue";
import {getCategoryByType} from "../../../api/Category";
import {addProvision} from "../../../api/Provision";
import {useQuasar} from "quasar";
import {getServiceByType} from "../../../api/Service";

const $q = useQuasar();
const emit = defineEmits(['sent']);

const props = defineProps({
  id: {
    type: String
  }
});

const options = ref([]);
const catalogue = ref({
  title:'',
  description:'',
  type:'',
  image:'',
  resources:[{
    title: '',
    inventoryNumber: null,
  }],
  number:1,
  resourcesTitle:'',
});
const idService = ref(null);
const selectedFile = ref(false);
const imageUploader = ref(null);
const selectedSite = ref(null);
const sitesOptions = ref([]);

const getEmptyForm = () => {
  return {
    title:'',
    description:'',
    type:'',
    image:'',
    resources:[{
      title: '',
      inventoryNumber: null,
    }],
    number:1,
    resourcesTitle:'',
  }
};

const resetForm = () => {
  catalogue.value = getEmptyForm();
  selectedSite.value = null;
};

const getRessourcerieCategories = () => {
  getCategoryByType('ressourcerie')
      .then(function (response) {
        options.value = response.data;
      })
};

const getServicesOptions = () => {
  getServiceByType('ressourcerie').then((response) => {
    sitesOptions.value = response.data;
    selectedSite.value = [];
  })
}

const fileSelected = (files) => {
  if (files.length !== 0) {
    selectedFile.value = true
  }
  catalogue.value.image=files[0];
}

const fileRemoved = () => {
  selectedFile.value = false
}

const onSubmit = () => {
  let bodyFormData = new FormData();
  bodyFormData.append('image', catalogue.value.image);
  bodyFormData.append('title', catalogue.value.title);
  bodyFormData.append('description', catalogue.value.description);
  bodyFormData.append('type', catalogue.value.type.id);
  catalogue.value.resources = [];
  for(let i = 1; i <= catalogue.value.number; i++) {
    catalogue.value.resources.push({
      title: catalogue.value.title + '_' + i,
      inventoryNumber: null,
    })
  }
  bodyFormData.append('resources', JSON.stringify(catalogue.value.resources));

  if(props.id === undefined) {
    bodyFormData.append('service',  selectedSite.value.id);
  } else {
    bodyFormData.append('service',  idService.value);
  }

  bodyFormData.append('number', catalogue.value.number);

  axios({
    method: "post",
    url: "/api/catalogue",
    data: bodyFormData,
    headers: { "Content-Type": "multipart/form-data" },
  })
      .then(function (response) {
        //On vide le formulaire et on reset les règles des champs
        //Object.assign(self.$data, self.$options.data.call(self));
        // self.$refs.form.reset();
        resetForm()
        imageUploader.value.reset();

        //$refs.imageUploader.reset();

        // Get the id of the created catalogue to send it to a new provision
        let catalogueId = response.data
        let dateNow = new Date();
        // Set the start date to 7 days from now and end date to now
        let dateStart = new Date(dateNow.setDate(dateNow.getDate() + 7))
        // @TODO Set empty in the future
        let dateEnd = new Date(dateNow.setDate(dateNow.getDate() + 14))
        // Add the provision data to the form data
        addProvision({
          'dateStart': date.formatDate(dateStart, 'YYYY-MM-DD'),
          'dateEnd': date.formatDate(dateEnd, 'YYYY-MM-DD'),
          'days': [],
          'minBookingTime': date.formatDate(dateNow, 'HH:mm'),
          'maxBookingTime': date.formatDate(dateNow, 'HH:mm'),
          'maxBookingDuration': 0,
          'BookingInterval': 0,
          'maxBookingByDay': 0,
          'maxBookingByWeek': 0,
          'allowMultipleDay': false,
          'attachedWorkflow': JSON.stringify({id:0}),
          'catalogueResource': '/api/catalogue_resources/' + catalogueId
        }, false)

        //Notification de l'utilisateur
        $q.notify({
          type: 'positive',
          message: 'Le catalogue a été créé !',
          position: 'top',
        })

        emit("sent");
      })
      .catch(function (response) {
        //handle error
        console.log(response);
        //Notification d'erreur
        $q.notify({
          type: 'negative',
          message: 'Erreur : impossible de créer le catalogue',
          position: 'top',
        })
      });
}

onMounted(()=> {
  idService.value = props.id;
  getRessourcerieCategories();
  if(props.id === undefined)
    getServicesOptions();
});

</script>

<template>
  <q-form
      ref="form"
      @submit="onSubmit"
      class="q-gutter-md"
  >
    <q-card-section>

      <div class="text-h5 q-my-md">Définition d'un bien</div>

      <q-input
          outlined
          v-model="catalogue.title"
          label="Titre*"
          lazy-rules
          :rules="[ val => val && val.length > 0 || 'Champ obligatoire']"
      />

      <q-input
          v-model="catalogue.description"
          outlined
          type="textarea"
          label="Description*"
          lazy-rules
          :rules="[ val => val && val.length > 0 || 'Champ obligatoire']"
      />

      <q-select
          outlined
          v-model="catalogue.type"
          :options="options"
          option-value="id"
          option-label="title"
          label="Catégorie*"
          lazy-rules
          :rules="[ val => val && val.id > 0 || 'Champ obligatoire']"
      />

      <q-select
          v-if="props.id === undefined"
          outlined
          :options="sitesOptions"
          v-model="selectedSite"
          option-value="id"
          option-label="title"
          label="Site de localisation du bien*"
          lazy-rules
          :rules="[val => val && val.id > 0 || 'Sélectionnez la localisation du bien']">
      </q-select>


      <div class="row justify-between q-pb-lg">

        <q-input
            outlined
            v-model="catalogue.number"
            type="number"
            label="Nombre d'exemplaires*"
            class="col-5"
            lazy-rules
            :rules="[ val => val && val > 0 || 'Champ obligatoire']"
        />
        <q-uploader
            label="Téléverser une image"
            @added="fileSelected"
            @removed="fileRemoved"
            ref="imageUploader"
            accept=".jpg, .png, .jpeg"
            class="col-5"
            flat
            bordered
        />
      </div>

      <div class="row justify-end">
        <q-btn label="Envoyer" type="submit" color="primary"/>
      </div>

    </q-card-section>

  </q-form>
</template>

<style scoped>

</style>