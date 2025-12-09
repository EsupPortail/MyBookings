<template>
  <catalogue-sidebar v-if="showCatalogueSideBar(this.$router.currentRoute.value.name)"></catalogue-sidebar>
  <site-sidebar v-if="$router.currentRoute.value.fullPath.includes('/site') && $router.currentRoute.value.fullPath.includes('/administration/site') === false"></site-sidebar>
  <book-sidebar v-if="$router.currentRoute.value.fullPath.includes('/book') && $router.currentRoute.value.fullPath.includes('/booking') === false"></book-sidebar>
</template>

<script>
import CatalogueSidebar from "./sidebar/catalogueSidebar.vue";
import SiteSidebar from "./sidebar/siteSidebar.vue";
import BookSidebar from "./sidebar/bookSidebar.vue";
export default {
  name: "customSidebar",
  components: {BookSidebar, SiteSidebar, CatalogueSidebar},
  computed() {
    this.showCatalogueSideBar();
  },
  methods: {
    showCatalogueSideBar(routename) {
      let result = false;
      switch (routename){
        case 'serviceDetailAdmin' :
        case 'createCatalogue' :
        case 'editCatalogue' :
        case 'catalogueDetail' :
        case 'catalogueListAdmin' :
        case 'manageBookingsFromService' :
        case 'workflowListAdmin' :
        case 'createWorkflow' :
        case 'manageUsersFromService' :
        case 'editSite' :
        case 'managePeriodsForService' :
        case 'managePeriodBracket' :
          result = true;
          break;

        default :
          result = false;
          break;
      }
      return result
    }
  },
  $route (to, from){
    this.showCatalogueSideBar()
  },
}
</script>

<style scoped>

</style>