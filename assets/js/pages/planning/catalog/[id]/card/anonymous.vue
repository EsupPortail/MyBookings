<template>
  <table-card-planning :id="id" :anonymous="true"></table-card-planning>
</template>

<route type="json">
{
  name: "anonymousCard",
  meta: {
    public: true
  }
}
</route>

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