<template>

  <q-dialog v-model="isOpenDialog" persistent>
    <q-card>
      <q-card-section class="q-pt-lg">
        <span class="text-h6" v-html="message"></span>

        <div v-if="showComment" class="q-mt-md">
          <q-input type="textarea" v-model="commentText" label="Commentaire (optionnel)" autogrow />
        </div>
      </q-card-section>

      <q-card-actions align="right">
        <q-btn flat :label="cancelLabel" color="dark" v-close-popup />
        <q-btn flat :label="okLabel" :color="okColor" @click="handleOkAction" />
      </q-card-actions>
    </q-card>
  </q-dialog>

</template>

<script setup>
import { ref } from 'vue';


const props = defineProps({
  message: {
    type: String,
    default: 'Voulez-vous supprimer cet objet ?'
  },
  cancelLabel: {
    type: String,
    default: 'Annuler'
  },
  okLabel: {
    type: String,
    default: 'Supprimer'
  },
  okColor: {
    type: String,
    default: 'negative'
  },
  okAction: {
    type: Function,
    default() { console.error('no function send into the component') }
  },
  showComment: {
    type: Boolean,
    default: false
  }
})

const isOpenDialog = ref(false)
const commentText = ref("")

function handleOkAction() {
  if (props.showComment) {
    props.okAction(commentText.value)
  } else {
    props.okAction()
  }
}
</script>