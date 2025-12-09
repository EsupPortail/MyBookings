<template>
  <custom-table-catalogue v-if="isLoaded" :type="null" :subType="basketUser.selection.subType.id" :service-filter="null" :is-editable="false"></custom-table-catalogue>
</template>

<route lang="json">
{
"name": "scheduleRessource"
}
</route>

<script>
import { basket } from '../../../../../store/basket';
const basketUser = basket();
import {date} from "quasar";
import CustomTableCatalogue from "../../../../../components/table/customTableCatalogue.vue";
import {getCatalogueOfResourceById} from "../../../../../api/CatalogRessource";
import {parseResourceFromCatalogue} from "../../../../../utils/bookingUtils";
import { useRoute } from 'vue-router/auto';

export default {
  components: {CustomTableCatalogue},
  setup() {
    const route = useRoute();
    const id = route.params.id;
    return { id };
  },
  data() {
    return {
      events: null,
      basketUser,
      isLoaded: false
    }
  },
  mounted() {
    let dateStart = new Date();
    this.basketUser.start = date.formatDate(dateStart, 'DD/MM/YYYY');
    this.basketUser.end = this.basketUser.start;
    this.basketUser.resourceId=this.id;
    this.getCatalogueOfResource();
  },
  methods: {
    getCatalogueOfResource() {
      let self = this;
      getCatalogueOfResourceById(this.id).then(function (response) {
        let resources = [];
        response.forEach(function(element) {
          resources.push(element)
        });
        self.basketUser.selection = resources[0];
        self.basketUser.selection.resource = parseResourceFromCatalogue(self.basketUser.selection, self.id);
        self.isLoaded = true;
      })
    },
  },
}

</script>

<style>
</style>