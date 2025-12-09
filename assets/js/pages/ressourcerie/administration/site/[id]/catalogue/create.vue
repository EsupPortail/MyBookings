<template>
  <div>
    <q-card bordered class="my-card">
      <create-effect :id="id" />
      <div class="q-pa-md"></div>
    </q-card>
  </div>
</template>

<route lang="json">
{
  "name": "createCatalogueRessourcerie",
  "title": "Créer",
  "icon": "add",
  "meta": {
    "requiresAuth": false
  }
}
</route>

<script>
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import CreateEffect from '../../../../../../components/forms/Ressourcerie/createEffect.vue';

export default {
  components: { CreateEffect },
  setup() {
    const route = useRoute();
    const id = computed(() => route.params.id);
    return { id };
  },
}
</script>