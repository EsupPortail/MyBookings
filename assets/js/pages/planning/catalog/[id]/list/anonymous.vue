<template>
  <table-list-planning :id="id" :anonymous="true"></table-list-planning>
</template>

<route type="json">
{
  name: "anonymousList",
  meta: {
    public: true
  }
}
</route>


<script>
import TableListPlanning from "../../../../../components/table/planning/tableListPlanning.vue";
import { useRoute } from 'vue-router/auto';

export default {
  name: "catalogPlanningViewAnonymous",
  components: {TableListPlanning},
  setup() {
    const route = useRoute();
    const id = route.params.id;
    return { id };
  }
}
</script>