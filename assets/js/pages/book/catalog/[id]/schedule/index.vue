<template>
    <custom-table-catalogue  v-if="basketUser.selection !== null" :type="null" :subType="basketUser.selection.subType.id" :service-filter="null" :is-editable="false" :localizationFilter="null"></custom-table-catalogue>
</template>

<route lang="json">
{
"name": "scheduleCatalog"
}
</route>

<script>
import {basket} from "../../../../../store/basket";
import {date} from "quasar";
import CustomTableCatalogue from "../../../../../components/table/customTableCatalogue.vue";
import {getCatalogueById} from "../../../../../api/CatalogRessource";
import { useRoute } from 'vue-router/auto';
const basketUser = basket();
export default {
  name: "index.vue",
  components: {CustomTableCatalogue},
  setup() {
    const route = useRoute();
    const id = route.params.id;
    return { id };
  },
  data() {
    return {
      basketUser,
    }
  },
  mounted() {
    this.basketUser.selection = null;
    let dateStart = new Date();
    this.basketUser.start = date.formatDate(dateStart, 'DD/MM/YYYY');
    this.basketUser.end = this.basketUser.start;
    this.basketUser.resourceId=null;
    this.getCatalogue();
  },
  methods: {
    getCatalogue() {
      let self = this;
      getCatalogueById(this.id).then(function (result) {
        self.basketUser.selection =  result;
      })
    },
  },
}
</script>

<style scoped>
</style>