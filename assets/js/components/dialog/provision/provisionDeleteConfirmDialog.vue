<template>
  <q-dialog v-model="openConfirm" persistent>
    <q-card>
      <q-card-section class="row items-center">
        <q-avatar icon="delete" color="negative" text-color="white" />
        <span class="q-ml-sm">Voulez vous vraiment supprimer le planning ? Cette action est définitive.</span>
      </q-card-section>

      <q-card-actions align="right">
        <q-btn flat label="Annuler" color="primary" @click="close" />
        <q-btn flat label="Confirmer" color="primary" @click="deletePlanning" />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script>
import {deleteProvisionFromCatalog} from "../../../api/Provision";

export default {
  name: "provisionDeleteConfirmDialog",
  props: {
    openDialog:Boolean,
    catalogue:String,
    currentPlanning:Object,
  },
  data () {
    return {
      openConfirm: false,
    }
  },
  mounted() {
    this.openConfirm = this.openDialog;
  },
  methods: {
    deletePlanning() {
      let self = this;
      let bodyFormData = new FormData();
      bodyFormData.append('provision', JSON.stringify(this.currentPlanning));
      deleteProvisionFromCatalog(this.catalogue, this.currentPlanning.id, bodyFormData).then(function (response) {
        self.$emit('deleted', false);
      })
    },
    close() {
      this.$emit('close');
    }
  },
  watch: {
    openDialog: function () {
      this.openConfirm = this.openDialog === true;
    }
  }
}
</script>
<style scoped>

</style>