<template>
  <div>
    <q-tabs
      
      dense
      align="right"
      active-color="primary"
      indicator-color="primary"
      class="text-primary"
    >
      <q-route-tab name="catalogue" label="Dépôts" to="/ressourcerie/sit/deposits" />
      <q-route-tab name="effectBookings" label="Demandes" to="/ressourcerie/sit/effectBookings" @click="bookingStore.clearSelection()" />
      <q-route-tab name="unfoundEffects" label="Demandes de biens inexistants" to="/ressourcerie/sit/unfoundEffects" />
    </q-tabs>

  </div>
  <router-view />
</template>

<script>
// import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { booking } from '../../store/booking'

export default {
  setup () {
    // const tab = ref('effectBookings')
    const bookingStore = booking()
    const route = useRoute()
    const router = useRouter()
    return {
      // tab,
      bookingStore,
      route,
      router,
    }
  },
  mounted() {
    if (this.route.path === '/ressourcerie/sit') {
      this.router.replace('/ressourcerie/sit/effectBookings')
    }
  },
}
</script>