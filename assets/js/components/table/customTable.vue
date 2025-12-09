<template>
  <div class="q-pa-md">
    <RemoveDialog
        v-model="confirmDelete"
        :message="`Voulez vous vraiment supprimer la ressource ?</b>`"
        :ok-action="deleteResource"
    />
    <q-table
        :aria-label="$t('affiliatedResources')"
        :rows="rows"
        :columns="columns"
        row-key="id"
        :filter="filter"
        :loading="loading"
        :visible-columns="visibleColumns"
        :rows-per-page-options="[10, 50, 100]"
        flat
    >
      <template v-slot:header="props">
        <q-tr :props="props">
          <q-th
              v-for="col in props.cols"
              :key="col.name"
              :props="props"
              role="rowheader"
          >
            {{ col.label }}
          </q-th>
        </q-tr>
      </template>
      <template v-slot:top>
        <q-input dense debounce="300" color="primary" v-model="filter" :label="$t('search')">
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
          <q-td key="options" :props="props">
            <div v-for="customFieldResources in props.row.customFieldResources">
              <q-chip color="white" text-color="primary"  v-if="customFieldResources.CustomField.type === 'option'">{{ customFieldResources.CustomField.title }}</q-chip>
            </div>
          </q-td>
          <q-td key="actuator_profile" :props="props">
            {{ props.row.actuator_profile }}
          </q-td>
          <q-td key="actions" :props="props">
            <q-btn v-if="isEditable" round color="negative" icon="delete" size="0.7rem" style="margin-right: 10px" @click="deleteRow(props.row.id, props.rowIndex)" />
            <q-btn v-if="isEditable" round color="secondary" icon="edit" size="0.7rem" style="margin-right: 10px" @click="openResourceEditor(props.row)" />
            <q-btn round color="primary" icon="qr_code_scanner" size="0.7rem" @click="openResourceInfoDialog(props.row.id)"/>
          </q-td>
        </q-tr>
      </template>
    </q-table>
  </div>
  <q-dialog
      v-model="addResourceDialog"
  >
    <q-card style="width: 700px; max-width: 80vw;">
      <q-card-section>
        <div class="text-h6">Ajouter une ressource</div>
      </q-card-section>
      <q-card-section class="q-pt-none">
        <q-input dense ref="newResourceName" v-model="newRessource.name" label="Nom" :rules="[val => (val && val.length > 0) || 'Ce champ ne peut pas être vide']" @keydown.enter.prevent="addRow" autofocus />
      </q-card-section>
      <q-card-section class="q-pt-none">
        <q-input dense v-model="newRessource.inventoryNumber" label="Numéro d'inventaire" autofocus />
      </q-card-section>
      <q-card-actions align="right" class="bg-white text-teal">
        <q-btn flat label="Envoyer" @click="addRow" />
        <q-btn flat label="Annuler" v-close-popup />
      </q-card-actions>
    </q-card>
  </q-dialog>
  <q-dialog
      v-model="dialogEdit"
  >
    <q-card style="width: 700px; max-width: 80vw;">
      <q-card-section>
        <div class="text-h6">Modifier une ressource</div>
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
      <q-card-section class="q-pt-none">
        <q-select dense v-model="newRessource.options" :options="optionList" emit-value map-options option-label="title" option-value="id" multiple label="Options"/>
      </q-card-section>
      <q-card-section class="q-pt-none">
        <q-input v-for="customField in newRessource.customFields" dense v-model="newRessource.editedCustomFields[customField.id]" :label="customField.label" autofocus />
      </q-card-section>
      <q-card-section class="q-pt-none" v-if="actuator !== undefined">
      <q-select v-if="user.roles.includes('ROLE_ADMIN')" dense v-model="newRessource.actuator_profile" :options="actuatorProfiles"  label="Profil actionneur">
      </q-select>
        <q-select v-else dense v-model="newRessource.actuator_profile" disabled  label="Profil actionneur">
        </q-select>
      </q-card-section>
      <q-card-actions align="right" class="bg-white text-teal">
        <q-btn flat label="Envoyer" @click="editResource" />
        <q-btn flat label="Annuler" v-close-popup />
      </q-card-actions>
    </q-card>
  </q-dialog>
  <resource-view-dialog :openResourceDialog="openResourceDialog" @close="openResourceDialog=false" :id="resourceId" :linkName="'resource'"></resource-view-dialog>
</template>

<script>
import {ref} from "vue";
import {user} from "../../store/counter.js";
import ResourceViewDialog from "../dialog/resourceViewDialog.vue";
import {deleteResource, editResource, postResource} from "../../api/Resource";
import RemoveDialog from "../dialog/RemoveDialog.vue";
import {getActuatorProfiles} from "../../api/Actuator";

const columns = [
  { name: 'image', align: 'center', label: 'Image', field: 'image', sortable: false },
  {
    name: 'title',
    required: true,
    label: 'Nom de la ressource',
    align: 'left',
    field: 'title',
    sortable: true
  },
  { name: 'AdditionalInformations', align: 'center', label: 'Informations complémentaires', field: 'AdditionalInformations', sortable: true },
  { name: 'inventoryNumber', align: 'center', label: 'Numéro d\'inventaire', field: 'inventoryNumber', sortable: true },
  { name: 'options', align: 'center', label: 'Options', field: 'customFieldResources', sortable: true },
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
  emits: ["reloadCatalog"],
  props: {
    resources: Array,
    isEditable: Boolean,
    catalogue: String,
    actuator: Number,
    optionList: Array
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
        options: [],
        customFields: [],
        editedCustomFields: []
      },
      addResourceDialog,
      dialogEdit: false,
      newResourceName,
      editResourceName,
      actuatorProfiles,
      visibleColumns: ["image", "title","AdditionalInformations","options", "inventoryNumber", "actions", "actuator_profile"],
      openResourceDialog: false,
      idCatalogue: null,
      resourceId: null,
      refresh: 0,
      confirmDelete,
      resourceToDelete: null,
      indexResourceToDelete: null,
    }
  },
  mounted() {
    this.idCatalogue = this.catalogue;
    rows.value = this.resources
    this.rowCount = this.resources.length
    let index = this.columns.findIndex(element => element.name === 'actions');
    if(index > -1) {
      this.columns.splice(index, 1);
    }
    if(this.actuator === undefined) {
        this.visibleColumns.pop();
    }
    this.columns.push({ name: 'actions',  align: 'right', label: 'Actions'});
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
        postResource(this.newRessource.name, this.idCatalogue, this.newRessource.informations, this.newRessource.inventoryNumber).then(function (response) {
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
        editResource(this.newRessource.name, this.newRessource.id, this.newRessource.informations, this.newRessource.inventoryNumber, this.newRessource.image, this.newRessource.options,this.newRessource.editedCustomFields, this.newRessource.actuator_profile).then(function (response) {
          self.$emit('reloadCatalog');
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
      this.newRessource.options = [];
      this.newRessource.customFields = [];
      this.newRessource.editedCustomFields = [];
      let self = this;
      row.customFieldResources.forEach(function (field) {
        if(field.CustomField.type === 'option') {
          self.newRessource.options.push(field.CustomField.id);
        }else {
          self.newRessource.customFields.push(field.CustomField);
          self.newRessource.editedCustomFields[field.CustomField.id] = field.Value;
        }
      })
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
    openResourceInfoDialog(id) {
      this.resourceId = id;
      this.openResourceDialog = true
    },
    fileSelected (files) {
      if (files.length !== 0) {
        this.selectedFile = true
      }
      this.newRessource.image=files[0];
    },
    fileRemoved () {
      this.selectedFile = false
    },
  },
  watch: {
    resources: function () {
      rows.value = this.resources;
    },
  }
}
</script>

<style scoped>

</style>