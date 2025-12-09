<template>
  <q-input dense outlined ref="startBooking" v-model="dateTime" label-color="primary" :label="$t('fromDateTime')"
           :rules="[
                              val => checkDateTimeFormat(val)|| $t('Required'),
                           ]">
    <template v-slot:prepend>
      <q-icon name="event" class="cursor-pointer">
        <q-popup-proxy cover transition-show="scale" transition-hide="scale">
          <q-date v-model="dateTime" mask="DD/MM/YYYY HH:mm">
            <div class="row items-center justify-end">
              <q-btn v-close-popup :label="$t('return')" color="primary" flat />
            </div>
          </q-date>
        </q-popup-proxy>
      </q-icon>
    </template>
  </q-input>
</template>

<script>
import {checkDateTimeFormat} from "../utils/dateUtils";

export default {
  name: "CalendarDate",
  props: {
    dateValue: String,
  },
  data() {
    return {
      dateTime: '',
    }
  },
  mounted() {
    this.dateTime = this.dateValue
  },
  methods: {
    checkDateTimeFormat,
  },
  watch: {
    dateTime: function (val) {
      this.$emit('update', val)
    }
  }
}
</script>

<style>

</style>