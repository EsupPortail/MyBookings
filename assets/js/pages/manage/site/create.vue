<template>
  <div>
    <h4>{{$t('sites.create')}}</h4>
    <div id="formStyle" class="q-pa-md" style="max-width: 800px">
      <q-form
          ref="form"
          @submit="onSubmit"
          class="q-gutter-md"
      >
        <q-input
            outlined
            v-model="site.title"
            :label="$t('title')+ '*'"
            lazy-rules
            :rules="[ val => val && val.length > 0 || $t('Required') ]"
        />
        <q-select
          v-model="site.type"
          :label="$t('common.type') + '*'"
         :options="['Bookings', 'Ressourcerie']"
         filled
        >

        </q-select>
        <q-select
            filled
            v-model="site.admin"
            use-input
            use-chips
            multiple
            input-debounce=200
            :label="$t('administrator')+'(s)'"
            :options="options"
            @filter="filter"
            new-value-mode="add-unique"
            :hint="$t('sites.adminHint')"
        >
          <template v-slot:no-option>
            <q-item>
              <q-item-section class="text-grey">
                No results
              </q-item-section>
            </q-item>
          </template>
        </q-select>
        <div>
          <q-btn :label="$t('send')" type="submit" color="primary"/>
        </div>
      </q-form>
    </div>
  </div>
</template>

<route lang="json">
{
  "name": "createSite",
  "title": "Créer un site",
  "icon": "add",
  "meta": {
    "requiresAuth": false
  }
}
</route>

<script>
import {counter} from "../../../store/counter";
import {searchUser} from "../../../api/User";
import {ref} from "vue";
import {createService} from "../../../api/Service";

const options = ref([]);
const nbCounter = counter();
export default {
  name: "create.vue",
  setup() {
    return {
      nbCounter,
    }
  },
  data() {
    return {
      site: {
        title: '',
        type: 'Bookings',
        admin: []
      },
      options,
    }
  },
  methods: {
    onSubmit() {
      let bodyFormData = new FormData();
      bodyFormData.append('title', this.site.title);
      bodyFormData.append('type', this.site.type);
      bodyFormData.append('admin', JSON.stringify(this.site.admin));
      createService(bodyFormData).then(function (response) {
        nbCounter.increment();
      })
      Object.assign(this.$data, this.$options.data.call(this));
      this.$refs.form.reset();
    },
    filter(val, update) {
      update(() => {
        if(val === '') {
          options.value = []
        } else {
          if (val.length > 2) {
            searchUser(val).then(function (response) {
              options.value= []
              response.forEach(user => options.value.push(user));
            })
          }
        }
      })
    },
  }
}
</script>

<style scoped>

</style>