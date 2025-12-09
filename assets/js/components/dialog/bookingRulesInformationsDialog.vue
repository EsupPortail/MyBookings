<script setup>
  import {basket} from "../../store/basket";

  const props = defineProps({
    dialog: Boolean
  });

  const emit = defineEmits(['closeDialog']);
  const basketUser = basket();
  const close = () => {
    emit('closeDialog');
  };

  const showRules = () => {
    let array = [];
    Object.keys(basketUser.rules).forEach(rule => {
      if(!rule.includes('indispo'))
        array.push([rule, basketUser.rules[rule].join(', ')]);
    });
    return {...array};
  }

  const showIndispo = () => {
    let array = [];
    Object.keys(basketUser.rules).forEach(rule => {
      if(rule.includes('indispo'))
        array.push([rule, basketUser.rules[rule].join(', ')]);
    });
    return {...array};
  }
</script>

<template>
  <q-dialog v-model="props.dialog">
    <q-card class="bg-white" style="width: 700px">
      <q-card-section class=" q-px-none">
        <div class="row justify-around">
          <div class="col-md-9 col-xs-9">
            <div class="text-h5">{{$t('checkRules')}}</div>
          </div>
          <div>
            <div class="col-auto">
              <q-btn dense round color="dark" icon="close" @click="close">
                <q-tooltip>
                  Fermer la fenêtre
                </q-tooltip>
              </q-btn>
            </div>
          </div>
        </div>
      </q-card-section>
      <q-separator inset />
      <q-card-section>
        <div v-for="rule in showRules()">
          <div class="text-left">
            <div class="text-bold text-h6">{{ rule[0] }}</div>
            {{ rule[1] }}
          </div>
        </div>
        <div v-for="indispo in showIndispo()">
          <div class="text-left">
            <div class="text-bold text-h6 text-negative">{{ indispo[0] }}</div>
            {{ indispo[1] }}
          </div>
        </div>
      </q-card-section>
    </q-card>
  </q-dialog>

</template>

<style scoped>

</style>