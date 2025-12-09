<template>
 <table-list-planning :id="id"></table-list-planning>
</template>

<script setup>
import TableListPlanning from "../../../../../components/table/planning/tableListPlanning.vue";
import { useRoute } from 'vue-router/auto';

const route = useRoute();
const id = route.params.id;
</script>