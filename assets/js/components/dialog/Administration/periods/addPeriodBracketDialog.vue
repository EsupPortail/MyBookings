<script setup>
import { ref, defineEmits } from 'vue'
import { useQuasar } from 'quasar'
import { addPeriodBracket } from '../../../../api/PeriodBracket'

const emit = defineEmits(['created', 'close'])
const $q = useQuasar()

// Propriétés reçues du parent
const props = defineProps({
  serviceId: {
    type: [String, Number],
    required: true
  },
  open: {
    type: Boolean,
    default: false
  }
})

const title = ref('')
const loading = ref(false)

const submitForm = async () => {
  if (!title.value.trim()) {
    $q.notify({
      color: 'negative',
      message: 'Veuillez saisir un titre pour la période',
      icon: 'warning'
    })
    return
  }

  loading.value = true

  try {
    const response = await addPeriodBracket({
      title: title.value,
      service: `/api/services/${props.serviceId}`
    })

    $q.notify({
      color: 'positive',
      message: 'Période créée avec succès',
      icon: 'check_circle'
    })

    emit('created', response.data)
    closeDialog()
  } catch (error) {
    console.error('Erreur lors de la création de la période:', error)
    $q.notify({
      color: 'negative',
      message: 'Erreur lors de la création de la période',
      icon: 'error'
    })
  } finally {
    loading.value = false
  }
}

const closeDialog = () => {
  title.value = ''
  emit('close')
}
</script>

<template>
  <q-dialog
    :model-value="open"
    @update:model-value="closeDialog"
    persistent
  >
    <q-card style="min-width: 350px">
      <q-card-section class="row items-center">
        <div class="text-h6">Ajouter une période</div>
        <q-space />
        <q-btn icon="close" flat round dense v-close-popup />
      </q-card-section>

      <q-card-section>
        <q-form @submit.prevent="submitForm">
          <q-input
            v-model="title"
            label="Titre de la période"
            :rules="[val => (val && val.length > 0) || 'Veuillez saisir un titre']"
            autofocus
            filled
          />
        </q-form>
      </q-card-section>

      <q-card-actions align="right">
        <q-btn
          flat
          label="Annuler"
          color="grey-7"
          v-close-popup
          :disable="loading"
        />
        <q-btn
          flat
          label="Créer"
          color="primary"
          @click="submitForm"
          :loading="loading"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<style scoped>

</style>