<template>
  <div>
    <q-card bordered class="my-card">
      <q-card-section>
        <div class="row">
          <div class="col">
            <h1 class="text-h6">{{ $t('catalogues.createResourceGroup') }}</h1>
          </div>
        </div>
      </q-card-section>
      <q-separator inset />
    <div class="q-pa-md">
      <q-form
          ref="form"
          @submit="onSubmit"
          class="q-gutter-md"
      >
        <q-card-section class="sectionCustom">
          <q-input
              outlined
              v-model="catalogue.title"
              :label="$t('title') + '*'"
              lazy-rules
              :rules="[ val => val && val.length > 0 || $t('Required')]"
          />
          <q-input
              v-model="catalogue.description"
              outlined
              type="textarea"
              :label="$t('description') + '*'"
              lazy-rules
              :rules="[ val => val && val.length > 0 || $t('Required')]"
          />
          <q-select
              outlined
              v-model="catalogue.type"
              :options="options"
              option-value="id"
              option-label="label"
              style="width: 100%; margin-bottom: 20px"
              :label="$t('type') + '*'"
              @update:model-value="getSubCategory"
          />

          <q-select
              outlined
              v-model="catalogue.subType"
              :options="subOptions"
              option-value="id"
              option-label="title"
              style="width: 100%"
              :label="$t('resourceSubType') + '*'"
          />
        </q-card-section>
        <q-card-section class="sectionCustom">
        <h2 class="text-h5 q-mb-lg">{{ $t('catalogues.resourceDefinition') }}</h2>
        <div class="q-gutter-xs" style="margin-top: -30px">
          <q-radio v-model="resourceMod" checked-icon="task_alt" unchecked-icon="panorama_fish_eye" val="auto" :label="$t('common.automatic')" />
          <q-radio v-model="resourceMod" checked-icon="task_alt" unchecked-icon="panorama_fish_eye" val="man" :label="$t('common.manual')" />
        </div>
        <div v-if="resourceMod === 'man'" class="q-gutter-xs row items-start" v-for="(item, index) in catalogue.resources">
          <q-input
              outlined
              v-model="item.title"
              :label="$t('catalogues.resourceTitle') + '*'"
              style="width: 45%"
              lazy-rules
              :rules="[ val => val && val.length > 0 || $t('Required')]"
          />
          <q-input
              outlined
              v-model="item.inventoryNumber"
              :label="$t('catalogues.inventoryNumber')"
              style="width: 45%"
          />
          <q-btn v-if="index+1 === catalogue.resources.length" round icon="add" color="positive" @click="addResource" class="q-mt-sm"/>
          <q-btn v-if="index+1 > 1" icon="remove" round color="negative" @click="delResource(index)" class="q-mt-sm"/>
        </div>
        <div v-if="resourceMod === 'auto'">
          <div class="q-gutter-xs row items-start" >
            <q-input
                outlined
                v-model="catalogue.resourcesTitle"
                :label="$t('catalogues.resourcesTitle') + '*'"
                style="width: 45%"
                lazy-rules
                :rules="[ val => val && val.length > 0 || $t('Required')]"
            />
            <q-input
                outlined
                v-model="catalogue.number"
                type="number"
                :label="$t('catalogues.resourceCount') + '*'"
                style="width: 45%"
                lazy-rules
                :rules="[ val => val && val > 0 || $t('Required')]"
            />
          </div>
          <p v-if="this.catalogue.number>0">{{ $t('catalogues.resourceReferencing') }} :</p>
          <ul>
            <li v-for="item in parseInt(this.catalogue.number)" v-if="catalogue.number<10">{{catalogue.resourcesTitle}}_{{item}}</li>
            <li v-for="item in 9" v-if="this.catalogue.number>=10">
              <span v-if="item=== 5">...</span>
              <span v-else-if="item<5">{{catalogue.resourcesTitle}}_{{item}}</span>
              <span v-else>{{catalogue.resourcesTitle}}_{{this.catalogue.number-(9-item)}}</span>
            </li>
          </ul>
        </div>
        </q-card-section>
        <q-card-section class="sectionCustom">

        <h5>{{$t('catalogues.imagesDefinition')}}</h5>
        <div style="margin-left: 30%">
          <q-uploader
              label="Téléverser une image"
              @added="fileSelected"
              @removed="fileRemoved"
              ref="imageUploader"
              accept=".jpg, .png, .jpeg"
              style="width: 40%"
              flat
              bordered
          />
        </div>
        <div style="margin-left: 88%">
          <q-btn :label="$t('send')" type="submit" color="primary"/>
        </div>
        </q-card-section>
      </q-form>
    </div>
    </q-card>
  </div>
</template>

<route lang="json">
{
  "name": "createCatalogue",
  "title": "Créer",
  "icon": "add",
  "meta": {
    "requiresAuth": false
  }
}
</route>

<script>
import { counter } from '../../../../../store/counter';
import {ref} from 'vue';
import axios from 'axios';
import { useQuasar } from 'quasar';
import { useRoute } from 'vue-router/auto';
export default {
  setup() {
    const nbCounter = counter();
    const route = useRoute();
    const id = route.params.id;

    function incrementCounter() {
      nbCounter.increment();
    }
    window.stores = { incrementCounter }
    return {
      nbCounter,
      incrementCounter,
      id
    }
  },
  data () {
    return {
      defaultCatalogue: {
        title:'',
        description:'',
        type:'',
        subType:'',
        image:'',
        resources:[{
          title: '',
          inventoryNumber: null,
        }],
        sites:'',
        number:0,
        resourcesTitle:'',
      },
      catalogue: this.getEmptyForm(),
      resourceMod: ref('man'),
      selectedFile: false,
      options: [],
      subOptions: [],
      idService: null,
      maxlist:0,
      $q: useQuasar()
    }
  },
  mounted() {
    this.idService = this.id;
    this.getCategory();
  },
  methods: {
    onSubmit() {
      let self = this;
      let bodyFormData = new FormData();
      bodyFormData.append('image', this.catalogue.image);
      bodyFormData.append('title', this.catalogue.title);
      bodyFormData.append('description', this.catalogue.description);
      bodyFormData.append('type', this.catalogue.type.id);
      bodyFormData.append('subType', this.catalogue.subType.id);
      if(this.resourceMod === 'auto') {
        this.catalogue.resources = [];
        for(let i =1; i<=this.catalogue.number; i++) {
          this.catalogue.resources.push({
            title: this.catalogue.resourcesTitle+'_'+i,
            inventoryNumber: null,
          })
        }
      }
      bodyFormData.append('resources', JSON.stringify(this.catalogue.resources));
      bodyFormData.append('service',  this.idService);
      bodyFormData.append('number', this.catalogue.number);

      axios({
        method: "post",
        url: "/api/catalogue",
        data: bodyFormData,
        headers: { "Content-Type": "multipart/form-data" },
      })
      .then(function (response) {
        self.incrementCounter();
        //On vide le formulaire et on reset les règles des champs
        Object.assign(self.$data, self.$options.data.call(self));
        self.$refs.form.reset();
        self.$refs.imageUploader.reset();
        //Notification de l'utilisateur
        self.$q.notify({
          type: 'positive',
          message: 'Le catalogue a été créé !',
          position: 'top',
        })
      })
      .catch(function (response) {
        //handle error
        console.log(response);
        //Notification d'erreur
        self.$q.notify({
          type: 'negative',
          message: 'Erreur : impossible de créer le catalogue',
          position: 'top',
        })
      });
    },
    fileSelected (files) {
      if (files.length !== 0) {
        this.selectedFile = true
      }
      this.catalogue.image=files[0];
    },
    fileRemoved () {
      this.selectedFile = false
    },
    addResource () {
      this.catalogue.resources.push({
        title: '',
        inventoryNumber: null,
      })
    },
    delResource (index) {
      this.catalogue.resources.splice(index, 1);
    },
    getEmptyForm() {
      return {
        title:'',
        description:'',
        type:'',
        image:'',
        resources:[{
          title: '',
          inventoryNumber: null,
        }],
        service:'',
        number:0,
        resourcesTitle:'',
      }
    },
    resetForm () {
      this.catalogue = this.getEmptyForm()
    },
    getCategory() {
      let self = this;
      axios({
        method: "get",
        url: "/api/category",
      })
        .then(function (response) {
          self.options = response.data;
        });
    },
    getSubCategory(selected) {
      let self = this;
      axios({
        method: "get",
        url: "/api/categories/"+selected.id,
      })
        .then(function (response) {
          self.subOptions = response.data.enfants;
        });
    }
  }
}
</script>

<style>
h5 {
  margin-top: 20px;
}
.sectionCustom {
  margin-top: 0;
}
#formStyle {
  margin-top: 1%;
  margin-left: 29%;
}

.q-gutter-md.row {
  margin-left: 0px;
}
</style>