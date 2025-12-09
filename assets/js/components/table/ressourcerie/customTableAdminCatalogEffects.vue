<template>

    <RemoveDialog
        v-model="confirmDelete"
        :message="`Voulez vous vraiment supprimer l'exemplaire ?</b>`"
        :ok-action="deleteResource"
    />
    <q-table
        title="Treats"
        :rows="rows"
        :columns="columns"
        row-key="id"
        :filter="filter"
        :loading="loading"
        :visible-columns="visibleColumns"
        :rows-per-page-options="[10, 50, 100]"
        flat
    >
      <template v-slot:top>
        <q-input dense debounce="300" color="primary" v-model="filter">
          <template v-slot:append>
            <q-icon name="search" />
          </template>
        </q-input>
        <q-space />
        <q-btn v-if="isEditable" color="primary" :disable="loading" icon="add" @click="addResourceDialog = true" />
      </template>

      <template v-slot:body="props">
        <q-tr :props="props">
          <q-td key="image" :props="props">
            <div>
              <q-img :src="'/uploads/'+props.row.image">
                <template v-slot:error>
                  <div class="absolute-full flex flex-center bg-grey text-white">
                    <q-icon size="md" name="no_photography"/>
                  </div>
                </template>
              </q-img>
            </div>
          </q-td>
          <q-td key="title" :props="props">
            {{ props.row.title }}
          </q-td>
          <q-td key="AdditionalInformations" :props="props">
            <div v-if="props.row.AdditionalInformations !== null && props.row.AdditionalInformations !== ''">{{ props.row.AdditionalInformations }}</div>
            <div v-else>N/R</div>
          </q-td>
          <q-td key="inventoryNumber" :props="props">
            <div v-if="props.row.inventoryNumber !== null && props.row.inventoryNumber !== ''">{{ props.row.inventoryNumber }}</div>
            <div v-else>N/R</div>
          </q-td>
          <q-td key="actions" :props="props">
            <q-btn v-if="isEditable && user.isUserAdminSite(idService, true)" round color="negative" icon="delete" size="0.7rem" style="margin-right: 10px" @click="deleteRow(props.row.id, props.rowIndex)"><q-tooltip>Supprimer l'exemplaire</q-tooltip></q-btn>
            <q-btn v-if="isEditable && user.isUserAdminSite(idService, true)" round color="secondary" icon="edit" size="0.7rem" style="margin-right: 10px" @click="openResourceEditor(props.row)"><q-tooltip>Modifier l'exemplaire</q-tooltip></q-btn>
            <!-- <q-btn round color="primary" icon="bookmark_add" size="0.7rem"><q-tooltip>Réserver pour quelqu'un</q-tooltip></q-btn> -->
          </q-td>
        </q-tr>
      </template>
    </q-table>

  <q-dialog
      v-model="addResourceDialog"
  >
    <q-card style="width: 700px; max-width: 80vw;">
      <q-card-section>
        <div class="text-h6">Ajouter un exemplaire</div>
      </q-card-section>
      <q-card-section class="q-pt-none">
        <q-input dense ref="newResourceName" v-model="newRessource.name" label="Nom" :rules="[val => (val && val.length > 0) || 'Ce champ ne peut pas être vide']" @keydown.enter.prevent="addRow" autofocus />
      </q-card-section>
      <q-card-section class="q-pt-none">
        <q-input dense v-model="newRessource.inventoryNumber" label="Numéro d'inventaire" autofocus />
      </q-card-section>
      <q-card-actions align="right" class="bg-white text-teal">
        <q-btn flat label="Annuler" color="dark" v-close-popup />
        <q-btn flat label="Envoyer" color="primary" @click="addRow" />
      </q-card-actions>
    </q-card>
  </q-dialog>
  <q-dialog
      v-model="dialogEdit"
  >
    <q-card style="width: 700px; max-width: 80vw;">
      <q-card-section>
        <div class="text-h6">Modifier un exemplaire</div>
      </q-card-section>
      <q-card-section class="q-pt-none">
        <q-uploader
            label="Téléverser une image"
            @added="fileSelected"
            @removed="fileRemoved"
            ref="imageUploader"
            accept=".jpg, .png, .jpeg"
            style="width: 100%"
            flat
            bordered
        />
      </q-card-section>
      <q-card-section class="q-pt-none">
        <q-input dense ref="editResourceName" v-model="newRessource.name" label="Nom" :rules="[val => (val && val.length > 0) || 'Ce champ ne peut pas être vide']" @keydown.enter.prevent="editResource" autofocus />
      </q-card-section>
      <q-card-section class="q-pt-none">
        <q-input dense ref="editResourceInfo" v-model="newRessource.informations" label="Informations complémentaires" autofocus />
      </q-card-section>
      <q-card-section class="q-pt-none">
        <q-input dense v-model="newRessource.inventoryNumber" label="Numéro d'inventaire" autofocus />
      </q-card-section>
      <q-card-section class="q-pt-none" v-if="actuator !== undefined">
      <q-select v-if="user.roles.includes('ROLE_ADMIN')" dense v-model="newRessource.actuator_profile" :options="actuatorProfiles"  label="Profil actionneur">
      </q-select>
        <q-select v-else dense v-model="newRessource.actuator_profile" disabled  label="Profil actionneur">
        </q-select>
      </q-card-section>
      <q-card-actions align="right" class="bg-white text-teal">
        <q-btn flat label="Annuler" color="dark" v-close-popup />
        <q-btn flat label="Envoyer" color="primary" @click="editResource" />
      </q-card-actions>
    </q-card>
  </q-dialog>
  <!-- <resource-view-dialog :openResourceDialog="openResourceDialog" @close="openResourceDialog=false" :idResource="resourceId"></resource-view-dialog> -->
</template>

<script>
import {ref} from "vue";
import { user } from "../../../store/counter.js";
import ResourceViewDialog from "../../dialog/resourceViewDialog.vue";
import {deleteResource, editResource, postResource} from "../../../api/Resource.js";
import RemoveDialog from "../../dialog/RemoveDialog.vue";
import {getActuatorProfiles} from "../../../api/Actuator.js";

const columns = [
  { name: 'image', align: 'center', label: 'Image', field: 'image', sortable: false },
  {
    name: 'title',
    required: true,
    label: 'Nom de l\'exemplaire',
    align: 'left',
    field: 'title',
    sortable: true
  },
  { name: 'AdditionalInformations', align: 'center', label: 'Informations complémentaires', field: 'AdditionalInformations', sortable: true },
  { name: 'inventoryNumber', align: 'center', label: 'Numéro d\'inventaire', field: 'inventoryNumber', sortable: true },
  { name: 'actuator_profile', align: 'center', label: 'Profil actionneur', field: 'actuator_profile', sortable: true },
]
const newResourceName = ref(null)
const editResourceName = ref(null)
const rows = ref([]);
const addResourceDialog = ref(false);
const confirmDelete = ref(false);
const loading = ref(false);
const actuatorProfiles = ref([]);
export default {
  name: "customTable",
  components: {RemoveDialog, ResourceViewDialog},
  props: {
    idService: String,
    resources: Array,
    isEditable: Boolean,
    catalogue: String,
    actuator: Number
  },
  data() {
    return {
      user: user(),
      columns,
      rows,
      loading,
      filter: '',
      rowCount: 0,
      pagination : {
        page: 1,
        rowsPerPage: 1000
      },
      newRessource: {
        image: null,
        name: '',
        informations: '',
        inventoryNumber: '',
        id: "",
        actuator_profile: '',
      },
      addResourceDialog,
      dialogEdit: false,
      newResourceName,
      editResourceName,
      actuatorProfiles,
      visibleColumns: ["image", "title","AdditionalInformations", "inventoryNumber", "actions", "actuator_profile"],
      openResourceDialog: false,
      resourceId: null,
      refresh: 0,
      confirmDelete,
      resourceToDelete: null,
      indexResourceToDelete: null,
    }
  },
  mounted() {
    rows.value = this.resources
    this.rowCount = this.resources.length
    let index = this.columns.findIndex(element => element.name === 'actions');
    if(index > -1) {
      this.columns.splice(index, 1);
    }
    if(this.actuator === undefined) {
        this.visibleColumns.pop();
    }
    this.columns.push({ name: 'actions',  align: 'right', label: ''});
    if(this.isEditable === true) {
      if(this.actuator !== undefined) {
        this.getProfiles();
      }
    }

  },
  methods: {
    addRow () {
      this.$refs.newResourceName.validate();
      if (!this.$refs.newResourceName.hasError) {
        let self = this;
        postResource(this.newRessource.name, this.catalogue, this.newRessource.informations, this.newRessource.inventoryNumber).then(function (response) {
          addResourceDialog.value = false;
          rows.value.push({'id': response, 'title' : self.newRessource.name, 'inventoryNumber' : self.newRessource.inventoryNumber === undefined ? '' : self.newRessource.inventoryNumber, 'AdditionalInformations' : self.newRessource.informations === undefined ? '' : self.newRessource.informations})
          self.newRessource.informations = "";
          self.newRessource.name = "";
          self.newRessource.inventoryNumber = "";
        })
      }
    },
    editResource() {
      this.$refs.editResourceName.validate();
      if ( !this.$refs.editResourceName.hasError) {
        let self = this;
        editResource(this.newRessource.name, this.newRessource.id, this.newRessource.informations, this.newRessource.inventoryNumber, this.newRessource.image, this.newRessource.actuator_profile).then(function (response) {
          let editIndex = rows.value.findIndex(obj => obj.id === response);
          rows.value[editIndex].title = self.newRessource.name;
          if(self.newRessource.image !== undefined && self.newRessource.image !== null ) {
            if(self.newRessource.image.type !== undefined) {
              self.refresh+= 1;
              rows.value[editIndex].image = 'resource_'+response+'.'+self.newRessource.image.type.split('/')[1]+'?refresh='+self.refresh;
            }
          }
          rows.value[editIndex].AdditionalInformations = self.newRessource.informations;
          rows.value[editIndex].inventoryNumber = self.newRessource.inventoryNumber;
          rows.value[editIndex].actuator_profile = self.newRessource.actuator_profile;
          self.dialogEdit = false;
          self.newRessource.name = "";
          self.newRessource.informations = "";
          self.newRessource.inventoryNumber = "";
          self.newRessource.actuator_profile= "";
          self.newRessource.id = null;
        })
      }
    },
    getProfiles() {
      getActuatorProfiles(this.actuator).then(function (response) {
        actuatorProfiles.value = response.data;
      })
    },
    openResourceEditor(row) {
      this.newRessource.image = row.image;
      this.newRessource.name = row.title;
      this.newRessource.informations = row.AdditionalInformations;
      this.newRessource.inventoryNumber = row.inventoryNumber;
      this.newRessource.id = row.id;
      this.newRessource.actuator_profile = row.actuator_profile;
      this.dialogEdit = true;
    },
    deleteResource() {
      let index = this.indexResourceToDelete;
      deleteResource(this.resourceToDelete).then(function () {
        rows.value.splice(index, 1);
        confirmDelete.value = false;
        loading.value = false;
      })
    },
    deleteRow(id, index) {
      this.resourceToDelete = id;
      this.indexResourceToDelete = index;
      confirmDelete.value = true;
    },
    // openResourceInfoDialog(id) {
    //   this.resourceId = id;
    //   this.openResourceDialog = true
    // },
    fileSelected (files) {
      if (files.length !== 0) {
        this.selectedFile = true
      }
      this.newRessource.image=files[0];
    },
    fileRemoved () {
      this.selectedFile = false
    },
  }
}
</script>

<style scoped>

</style>