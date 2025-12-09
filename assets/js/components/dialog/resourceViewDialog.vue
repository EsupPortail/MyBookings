<template>
  <q-dialog v-model="dialogVisible" persistent>
    <q-card style="width: 700px; max-width: 80vw;">
      <q-card-section>
        <div class="text-h5">Accéder directement au lien de réservation de la ressource</div>
      </q-card-section>
      <q-separator/>
      <q-card-section>
        <div style="text-align: center">
          <a target="_blank" :href="url">{{url}}</a>
          <QRCodeVue3
              width="500"
              height="500"
              :value="url"
              :cornersSquareOptions="{ type: 'square', color: '#000' }"
              :cornersDotOptions="{ type: 'square', color: '#000' }"
              :imageOptions="{ hideBackgroundDots: true, imageSize: 0.4, margin: 0 }"
              :dotsOptions="{
            type: 'square',
            color: '#000',
            gradient: {
              type: 'linear',
              rotation: 0,
              colorStops: [
                { offset: 0, color: '#006D82' },
                { offset: 1, color: '#006D82' },
              ],
            },
          }"
              fileExt="png"
              :download="true"
              downloadButton="q-btn"
              :downloadOptions="{ name: this.id, extension: 'png' }"
          />
        </div>
      </q-card-section>
      <q-card-actions align="right">
        <q-btn color="primary" label="Fermer" @click="close" />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script>
import QRCodeVue3 from "qrcode-vue3";

export default {
  name: "resourceViewDialog",
  components: {
    QRCodeVue3
  },
  props: {
    openResourceDialog:Boolean,
    id: String,
    linkName: String
  },
  data() {
    return {
      dialogVisible: false,
      url: '',
      imageUCA: null
    }
  },
  mounted() {
    this.dialogVisible  = this.openResourceDialog;
    this.url='https://'+document.domain+'/book/'+this.linkName+'/'+this.id
  },
  methods: {
    close() {
      this.$emit('close');
    }
  },
  watch: {
    openResourceDialog: function () {
      this.dialogVisible  = this.openResourceDialog;
    },
    id: function () {
      this.url='https://'+document.domain+'/book/'+this.linkName+'/'+this.id;
    }
  }
}
</script>

<style scoped>

</style>