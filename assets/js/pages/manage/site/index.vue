<template>
  <div>
    <h4>{{$t('sites.manage')}}</h4>
    <div class="q-pa-md q-gutter-sm" style="text-align: center">
      <q-btn
          padding="lg"
          color="primary"
          :label="$t('sites.add')"
          icon="add"
          to="/manage/site/create"
      />
    </div>
  </div>
</template>

<script>
export default {
  name: "index.vue"
}
</script>

<style scoped>

</style>