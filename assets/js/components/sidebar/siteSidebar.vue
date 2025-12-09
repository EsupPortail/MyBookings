<template>
  <q-drawer show-if-above v-model="leftDrawerOpen" side="left" bordered>
    <div style="text-align: center; margin-top: 10px">
      <p>{{$t('sites')}}</p>
    </div>
    <q-separator/>
    <q-list>
      <template v-for="site in sites">
        <q-item clickable :to="'/administration/site/'+site.id" v-ripple>
          <q-item-section>
            {{ site.title  }}
          </q-item-section>
        </q-item>
      </template>
    </q-list>
  </q-drawer>
</template>

<script>
import { counter, user } from '../../store/counter';
import {ref} from 'vue';
import {getAllServices} from "../../api/Service";
const sites = ref([]);
export default {
  name: "siteSidebar",
  setup () {
    const leftDrawerOpen = ref(false)
    const store = counter();
    const storedUser = user();
    return {
      store,
      storedUser,
      tab: ref('tab1'),
      leftDrawerOpen,
      toggleLeftDrawer () {
        leftDrawerOpen.value = !leftDrawerOpen.value
      }
    }
  },
  data() {
    return {
      sites,
      subMenu : [],
      subMenuTitle: '',
    }
  },
  methods:{
    loadSites() {
      getAllServices().then(function (response) {
          sites.value = response;
        })
    },
  },
  computed: {
    count () {
      return this.store.counter;
    },
    roles() {
      return this.storedUser.roles;
    }
  },
  mounted() {
    this.storedUser.getRoles();
    if(this.$router.currentRoute.value.name === "manage-site") {
      this.loadSites()
    }
  },
  watch:{
    $route (to, from){
      let self = this;
      if(this.$route.matched[0].path.includes("/manage/site")) {
        this.loadSites()
      } else {
        sites.value = [];
        self.subMenuTitle = '';
      }
    },
    count(newCount, oldCount) {
      this.loadSites();
    },
    roles(newRoles, oldRoles) {
      this.loadSites()
    },
  }


}
</script>
<style scoped>

</style>