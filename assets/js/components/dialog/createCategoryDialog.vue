<template>
  <q-dialog v-model="openDialog" persistent>
    <q-card style="width: 600px;">
      <q-card-section>
        <div class="text-h5">{{ $t('categories.createNew') }}</div>
      </q-card-section>
      <q-separator/>
        <q-card-section>
        <q-input
            outlined
            v-model="newCategory.title"
            :label="$t('title') + '*'"
            lazy-rules
            :rules="[ val => val && val.length > 0 || $t('externalUsers.requiredField')]"
        />
        <q-select
            v-model="newCategory.parent"
            :label="$t('categories.parentCategory')"
            :options="options"
        >
        </q-select>
        <q-select
            v-model="newCategory.view"
            :label="$t('categories.catalogView')"
            :options="optionsView"
        >
        </q-select>
      </q-card-section>
      <q-card-actions align="right" class="bg-white text-teal">
        <q-btn flat :label="$t('common.cancel')" @click="sendClose" />
        <q-btn :label="$t('workflows.send')" type="submit" color="primary" @click="onSubmit"/>
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script>
import {ref} from "vue";
import {createCategory} from "../../api/Category";

const options = ref([]);

export default {
  name: "createCategoryDialog",
  data () {
    return {
      openDialog: ref(false),
      newCategory: {
        title: '',
        parent: [],
        view: null
      },
      options,
      optionsView: []
    }
  },
  props: {
    open: Boolean,
    category: Array
  },
  mounted() {
    this.initializeViewOptions();
    this.openDialog = this.open;
  },
  methods: {
    initializeViewOptions() {
      this.optionsView = [
        this.$t('categories.lot'),
        this.$t('categories.collection')
      ];
    },
    onSubmit() {
      let self = this;
      let bodyFormData = new FormData();
      bodyFormData.append('title', this.newCategory.title);
      if(this.newCategory.parent.id !== undefined) {
        bodyFormData.append('type', 'category');
        bodyFormData.append('parent', '/api/categories/'+this.newCategory.parent.id);
        bodyFormData.append('view', this.newCategory.view);
      } else {
        bodyFormData.append('type', 'category');
        bodyFormData.append('view', this.newCategory.view);
      }
      createCategory(JSON.stringify(Object.fromEntries(bodyFormData))).then(function (response) {
        //On vide le formulaire et on reset les règles des champs
        Object.assign(self.$data, self.$options.data.call(self));
        self.sendCloseAndRefreshList();
      });
    },
    sendClose() {
      this.$emit('close');
    },
    sendCloseAndRefreshList() {
      this.$emit('closeAndRefreesh');
    }
  },
  watch: {
    open: function () {
      this.newCategory = {
        title: '',
        parent: []
      };
      this.openDialog = this.open;
    },
    category: function () {
      options.value = [];
      this.category.forEach(function (element) {
          options.value.push(element);
      });
    }
  }
}
</script>

<style scoped>

</style>