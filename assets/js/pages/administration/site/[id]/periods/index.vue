<script setup>
import { ref, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useQuasar } from 'quasar'
import { getPeriodBracket } from "../../../../../api/PeriodBracket"
import AddPeriodBracketDialog from "../../../../../components/dialog/Administration/periods/addPeriodBracketDialog.vue"
import {user} from "../../../../../store/counter";

const $q = useQuasar()
const route = useRoute()
const router = useRouter()
const serviceId = route.params.id
const periodBrackets = ref([])
const loading = ref(true)
const showAddDialog = ref(false)
const userStore = user();
onMounted(async () => {
  try {
    const response = await getPeriodBracket(serviceId)
    periodBrackets.value = response.data
  } catch (error) {
    console.error('Erreur lors du chargement des données:', error)
    $q.notify({
      color: 'negative',
      message: 'Erreur lors du chargement des périodes',
      icon: 'report_problem'
    })
  } finally {
    loading.value = false
  }
})

const navigateToEdit = (bracketId) => {
  router.push(`/administration/site/${serviceId}/periods/${bracketId}`)
}

const addNewPeriod = () => {
  showAddDialog.value = true
}

const handlePeriodCreated = (newPeriod) => {
  periodBrackets.value.push(newPeriod);
  showAddDialog.value = false;
}
</script>

<route lang="json">
{
"name": "managePeriodsForService"
}
</route>

<template>
  <q-page>
    <div>
      <div class="row items-center">
        <h1 class="col text-h5">{{ $t('periods.management') }}</h1>
        <div v-if="!loading" class="col-auto">
          <q-btn
              v-if="userStore.isUserAdminSite(serviceId)"
              color="primary"
              icon="add"
              :label="$t('periods.add')"
              @click="addNewPeriod"
          />
        </div>
      </div>

      <q-card v-if="loading" flat bordered>
        <q-card-section class="flex flex-center">
          <q-spinner color="primary" size="3em" />
          <div class="q-ml-sm">{{ $t('periods.loading') }}</div>
        </q-card-section>
      </q-card>

      <div v-else>
        <q-banner v-if="periodBrackets.length === 0" class="bg-grey-2 q-my-md">
          <template v-slot:avatar>
            <q-icon name="info" color="primary" />
          </template>
          {{ $t('periods.noPeriodsDefined') }}
        </q-banner>

        <div v-else>

          <div class="row q-col-gutter-md">
            <div
              v-for="bracket in periodBrackets"
              :key="bracket.id"
              class="col-xs-12 col-sm-6 col-md-4"
            >
              <q-card
                class="period-card cursor-pointer"
                bordered
                flat
                @click="navigateToEdit(bracket.id)"
              >
                <q-card-section>
                  <div class="text-h6">{{ bracket.title }}</div>
                  <q-badge color="secondary" class="q-mt-sm">
                    {{ $t('periods.subPeriodsCount', { count: bracket.periods.length }) }}
                  </q-badge>
                </q-card-section>

                <q-card-actions align="right">
                  <q-btn
                    flat
                    round
                    color="primary"
                    icon="edit"
                    @click.stop="navigateToEdit(bracket.id)"
                  />
                </q-card-actions>
              </q-card>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Dialogue d'ajout de période -->
    <AddPeriodBracketDialog
      :service-id="serviceId"
      :open="showAddDialog"
      @close="showAddDialog = false"
      @created="handlePeriodCreated"
    />
  </q-page>
</template>

<style scoped>
.period-card {
  transition: all 0.3s;
}

.period-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}
</style>