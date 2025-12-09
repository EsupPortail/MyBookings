<template>
  <RemoveDialog
      v-model="confirmDelete"
      :message="`<b>Voulez vous vraiment supprimer l'option sélectionnée ?</b>`"
      :ok-action="remove"
  />
  <q-dialog v-model="dialogVisible" persistent>
    <q-card style="width: 700px; max-width: 80vw;">
      <q-card-section>
        <div class="text-h5">Les options du catalogue</div>
      </q-card-section>
      <q-tabs
          v-model="tab"
          dense
          class="bg-grey-2 text-grey-7"
          active-color="primary"
          indicator-color="primary"
          align="justify"
      >
        <q-tab name="add" label="Créer" />
        <q-tab name="edit" label="Editer" />
      </q-tabs>
      <q-separator/>
      <q-tab-panels v-model="tab" animated class="text-dark text-center">
        <q-tab-panel name="add">
          <q-form @submit="" ref="form">
            <q-input v-model="title" label="Ajouter une option" :rules="[val => !!val || 'Champ obligatoire']" @keyup.enter="validate" />
            <div class="q-gutter-sm">
              <div>
                <q-checkbox v-model="isAttribute" :label="$t('optionLabelAdmin')" />
              </div>
            </div>
          </q-form>
        </q-tab-panel>
        <q-tab-panel name="edit" class="q-pa-md">
          <q-select v-model="selectedOption" label="Sélectionnez une option" :options="options" option-label="title" option-value="id"></q-select>
          <div v-if="selectedOption!== null">
            <q-input v-model="inputOption" label="Modifier le titre" :rules="[val => !!val || 'Champ obligatoire']" @keyup.enter="validate"/>
            <div class="q-gutter-sm">
              <div>
                <q-checkbox v-model="isAttribute" label="Cette option conditionne l'attribution des ressources en fonction de son affectation." :label="$t('optionLabelAdmin')" />
              </div>
            </div>
            <q-chip square text-color="white"  color="negative" clickable @click="confirmDelete=true">Supprimer l'option</q-chip>
          </div>
        </q-tab-panel>
      </q-tab-panels>
      <q-card-actions class="text-primary flex justify-between">
        <q-chip square text-color="white"  color="dark" clickable @click="close" >Annuler</q-chip>
        <q-chip square text-color="white" color="primary" clickable @click="validate()" >Valider</q-chip>
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script>
import {ref} from "vue";
import {postCustomField, removeCustomField, updateCustomField} from "../../api/CustomField";
import RemoveDialog from "./RemoveDialog.vue";

const title = ref('');
const tab = ref('add');
const selectedOption = ref(null);
const inputOption = ref('');
const confirmDelete = ref(false);
const isAttribute = ref(false);
export default {
  name: "addOptionDialog",
  components: {RemoveDialog},
  emits: ["close", "submitted"],
  props: {
    id:String,
    openDialog:Boolean,
    options: Array
  },
  data() {
    return {
      dialogVisible: false,
      title,
      tab,
      selectedOption,
      inputOption,
      confirmDelete,
      isAttribute
    }
  },
  mounted() {
    this.dialogVisible  = this.openDialog;
  },
  methods: {
    validate() {
      if(tab.value === 'add') {
        this.$refs.form.validate();
        let self = this;
        if(title.value) {
          let body = {
            'title' : title.value,
            'type': 'option',
            'Catalog': '/api/catalogue_resources/'+this.id,
            'isAttribute': isAttribute.value
          }
          postCustomField(body).then(function () {
            self.$emit('submitted', false);
            title.value = '';
            isAttribute.value = false;
          })
        }
      } else {
        let self = this;
        let body = {
          'id': selectedOption.value.id,
          'title' : inputOption.value,
          'type': 'option',
          'Catalog': '/api/catalogue_resources/'+this.id,
          'isAttribute': isAttribute.value
        }
        updateCustomField(body).then(function () {
          self.$emit('submitted', false);
          selectedOption.value = null;
          inputOption.value = '';
          isAttribute.value = false;
        })
      }

    },
    close() {
      title.value = '';
      selectedOption.value = null;
      this.$emit('close', false);
    },
    remove() {
      let self = this;
      removeCustomField(selectedOption.value.id).then(function () {
        self.$emit('submitted');
        selectedOption.value = null;
        inputOption.value = '';
        confirmDelete.value = false;
      })
    }
  },
  watch: {
    openDialog: function () {
      this.dialogVisible  = this.openDialog;
    },
    selectedOption: function () {
      if(selectedOption.value !== null) {
        inputOption.value = selectedOption.value.title;
        isAttribute.value = selectedOption.value.isAttribute;
      } else {
        inputOption.value = '';
        isAttribute.value = false;
      }
    }
  }
}
</script>

<style scoped>

</style>