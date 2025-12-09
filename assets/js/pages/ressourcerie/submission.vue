<script setup>
import { ref, onMounted } from "vue";
import CreateEffect from "../../components/forms/Ressourcerie/createEffect.vue";
import { getDeposits } from "../../api/ressourcerie/Deposits.js";

const deposits = ref([]);

const formattedStatus = ref([
    {
      value: 'rc_pending',
      label: 'En attente de modération par l\'entité',
      icon: 'hourglass_empty',
      color: 'grey',
    },
    {
      value: 'rc_validated',
      label: 'En attente de validation par le SIT',
      icon: 'hourglass_empty',
      color: 'blue',
    },
    {
      value: 'rc_published',
      label: 'Publié',
      icon: 'check_circle',
      color: 'green',
    },
    {
      value: 'rc_disabled',
      label: 'Refusé/désactivé',
      icon: 'cancel',
      color: 'red',
    }
])

function getStatusByValue(value) {
  const status = formattedStatus.value.find(status => status.value === value);
  return status ? status : value;
}

function updateList() {
  getDeposits().then(response => {
    deposits.value = response.data;
  });
}

onMounted(() => {
  getDeposits().then(response => {
    deposits.value = response.data;
  });
});

</script>

<template>

  <div class="row q-gutter-lg">
    
    <q-card bordered class="col">
      <create-effect @sent="updateList"/>
    </q-card>

    <q-card bordered class="col q-pa-lg">
      
      <div class="text-h5 q-mb-md">Mes dépôts</div>
      <q-scroll-area style="height: 600px">
      <q-list>

        <div v-for="value in deposits" :key="value.id">
          <q-item>
            <q-item-section side>
              <q-icon :name="getStatusByValue(value.status)?.icon" :color="getStatusByValue(value.status)?.color">
              <q-tooltip>
                {{ getStatusByValue(value.status)?.label}}
              </q-tooltip>
              </q-icon>
            </q-item-section>

            <q-item-section>
              <q-item-label>{{ value.title }}</q-item-label>
              <q-item-label caption lines="2">{{ value.description }}</q-item-label>
            </q-item-section>

            <q-item-section side>
              <q-item-label>{{ value.type.title }}</q-item-label>
              <q-item-label caption lines="2">{{ value.service.title }}</q-item-label>
            </q-item-section>
  
            <q-item-section side top>
            </q-item-section>
          </q-item>

          <q-separator spaced inset v-if="value.id !== deposits[deposits.length - 1].id" />
        </div>


      </q-list>
      </q-scroll-area>
    </q-card>
    
  </div>
  
</template>

<style scoped>

</style>