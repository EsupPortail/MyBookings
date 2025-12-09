<template>
  <div>
    <h3>Administration</h3>
  </div>
</template>

<script>
import {ref} from 'vue';
import axios from 'axios';
import {useQuasar} from "quasar";
export default {
  name: "index.vue",
  mounted() {
    this.$router.push('/administration/site');
  }
}
</script>

<style scoped>
 h3 {
   text-align: center;
 }
</style>