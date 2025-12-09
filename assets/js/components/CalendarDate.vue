<template>

  <q-input outlined v-model="basketUser.start" label-color="primary" :label="$t('fromDate')" :rules="[val => checkDateFormat(val)|| $t('Required')]">
    <template v-slot:prepend>
      <q-icon name="event" class="cursor-pointer">
        <q-popup-proxy cover transition-show="scale" transition-hide="scale">
          <q-date v-model="basketUser.start" mask="DD/MM/YYYY" no-unset>
            <div class="row items-center justify-end">
              <q-btn v-close-popup :label="$t('return')" color="primary" flat />
            </div>
          </q-date>
        </q-popup-proxy>
      </q-icon>
    </template>
  </q-input>

</template>

<script setup>
import {basket} from '../store/basket';
import {ref, watch} from 'vue'
import {checkDateFormat} from "../utils/dateUtils";
import {date} from "quasar";

const basketUser = basket();

const calendar = ref(null);
const mobile = ref(false);

watch(() => basketUser.start, (newVal) => {
  basketUser.end = date.formatDate(date.addToDate(date.extractDate(newVal, 'DD/MM/YYYY'), {days: 1}), 'DD/MM/YYYY');
});
</script>

<style>

</style>