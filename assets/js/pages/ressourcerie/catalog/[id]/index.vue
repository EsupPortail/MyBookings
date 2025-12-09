<template>
  <div class="q-pa-md row q-gutter-md flex-center">
    <q-card class="my-card" flat bordered>
      <q-img
          src="https://www.ikea.com/fr/fr/images/products/ekedalen-table-extensible-chene__0736964_pe740828_s5.jpg?f=s"
          style="max-height: 300px"
          fit="contain"
      />

      <q-card-section>
        <div class="text-overline text-orange-9">Mobilier</div>
        <div class="text-h5 q-mt-sm q-mb-xs">Grand bureau</div>
        <div class="text-caption text-grey">
          Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
        </div>
      </q-card-section>
      <q-separator inset />

      <q-card-actions class="float-right">
        <q-btn flat color="primary" label="Réserver" />
      </q-card-actions>
    </q-card>
  </div>
</template>

<script>
import { ref } from 'vue'
export default {
  data() {
    return {
      slide: ref(1),
    }
  }
}
</script>

<style>
.uncropped-image {
  background-size: contain;  /* don't crop the image  */
  background-repeat: no-repeat;  /* only show the image one time  */
  background-color: transparent;  /* color to fill empty space with  */
}
</style>