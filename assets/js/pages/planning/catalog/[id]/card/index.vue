<template>
  <table-card-planning :id="id"></table-card-planning>
</template>
  
<script>
import TableCardPlanning from "../../../../../components/table/planning/tableCardPlanning.vue";
import { useRoute } from 'vue-router/auto';

export default {
  name: "catalogPlanningViewAnonymous",
  components: {TableCardPlanning},
  setup() {
    const route = useRoute();
    const id = route.params.id;
    return { id };
  }
}
</script>