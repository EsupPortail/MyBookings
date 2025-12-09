<script setup>
import { ref, onMounted, computed } from 'vue';
import { useI18n } from 'vue-i18n';
import { getActuators, addActuator, removeActuator, getActuatorsHealth } from '../../api/Actuator';
import { Notify, Dialog } from 'quasar';

const { t } = useI18n();
const actuators = ref([]);
const loading = ref(false);
const searchFilter = ref('');
const error = ref(null);

// État de connexion réel de chaque actionneur
const connectionStatus = ref({});
// État de connexion des providers
const userProviders = ref([]);

const loadActuatorsHealth = async () => {
  try {
    const healthResponse = await getActuatorsHealth();
    const healthData = healthResponse.data;

    // Parser les actionneurs
    if (healthData && healthData.actuators) {
      Object.keys(healthData.actuators).forEach(type => {
        const actuatorsByType = healthData.actuators[type];
        Object.keys(actuatorsByType).forEach(actuatorTitle => {
          const status = actuatorsByType[actuatorTitle];
          connectionStatus.value[actuatorTitle] = status === 'UP';
        });
      });
    }

    // Parser les user providers
    if (healthData && healthData.user_provider) {
      userProviders.value = Object.keys(healthData.user_provider).map(providerName => ({
        name: providerName,
        status: healthData.user_provider[providerName]
      }));
    }
  } catch (e) {
    console.error('Erreur lors du chargement du statut des actionneurs:', e);
  }
};

const loadActuators = async () => {
  loading.value = true;
  error.value = null;
  try {
    const response = await getActuators();
    actuators.value = response.data;

    // Charger l'état de santé des actionneurs
    await loadActuatorsHealth();
  } catch (e) {
    error.value = t('actuators.loadingError');
    console.error(e);
  } finally {
    loading.value = false;
  }
};

const filteredActuators = computed(() => {
  if (!searchFilter.value) {
    return actuators.value;
  }
  const search = searchFilter.value.toLowerCase();
  return actuators.value.filter(actuator =>
    actuator.title.toLowerCase().includes(search) ||
    actuator.type.toLowerCase().includes(search)
  );
});

const connectedActuators = computed(() => {
  return actuators.value.filter(a => !a.linked);
});

const enableActuators = computed(() => {
  return actuators.value.filter(a => connectionStatus.value[a.title]);
});

const availableActuators = computed(() => {
  return actuators.value.filter(a => !a.added);
});


const toggleActuator = async (actuator) => {
  const wasAdded = actuator.added;

  try {
    if (wasAdded) {
      // Afficher une popup de confirmation avant de retirer l'actionneur
      Dialog.create({
        title: t('actuators.disconnectConfirmation'),
        message: `${t('actuators.disconnectMessagePart1')} "<strong>${actuator.title}</strong>" ?<br><br>
                  <span class="text-negative">⚠️ ${t('actuators.disconnectMessagePart2')}</span>`,
        html: true,
        ok: {
          label: t('actuators.disconnectButton'),
          color: 'negative',
          unelevated: true
        },
        cancel: {
          label: t('actuators.cancel'),
          color: 'grey',
          flat: true
        },
        persistent: true
      }).onOk(async () => {
        try {
          // Retirer l'actionneur
          await removeActuator(actuator.id);
          actuator.added = false;
          Notify.create({
            type: 'positive',
            message: `${t('actuators.disconnectSuccessPart1')} "${actuator.title}" ${t('actuators.disconnectSuccessPart2')}`,
            position: 'top-right'
          });
          //refresh the list of actuators to update connection status
          await loadActuators();
        } catch (e) {
          console.error('Erreur lors de la suppression de l\'actionneur:', e);
          Notify.create({
            type: 'negative',
            message: t('actuators.disconnectError'),
            position: 'top-right'
          });
          // Revenir à l'état précédent en cas d'erreur
          actuator.added = wasAdded;
        }
      });
    } else {
      // Ajouter l'actionneur
      await addActuator(actuator.title, actuator.type);
      actuator.added = true;
      Notify.create({
        type: 'positive',
        message: `${t('actuators.connectSuccessPart1')} "${actuator.title}" ${t('actuators.connectSuccessPart2')}`,
        position: 'top-right'
      });
      await loadActuators();
    }
  } catch (e) {
    console.error('Erreur lors de la modification de l\'actionneur:', e);
    Notify.create({
      type: 'negative',
      message: t('actuators.modificationError'),
      position: 'top-right'
    });
    // Revenir à l'état précédent en cas d'erreur
    actuator.added = wasAdded;
  }
};

const getActuatorIcon = (type) => {
  // Retourner une icône en fonction du type d'actionneur
  return type.toUpperCase() === 'CASTEL' ? 'router' : 'devices';
};

const getActuatorColor = (type) => {
  return type.toUpperCase() === 'CASTEL' ? 'primary' : 'secondary';
};

onMounted(() => {
  loadActuators();
});
</script>

<template>
  <div class="q-pa-md">
    <div class="row items-center q-mb-md">
      <div class="col">
        <h3 class="text-h4 q-my-none">{{ t('actuators.title') }}</h3>
        <p class="text-grey-7">{{ t('actuators.description') }}</p>
      </div>
      <div class="col-auto">
        <q-btn
          flat
          round
          icon="refresh"
          @click="loadActuators"
          :loading="loading"
        >
          <q-tooltip>{{ t('actuators.refresh') }}</q-tooltip>
        </q-btn>
      </div>
    </div>

    <!-- Statistiques -->
    <div class="row q-col-gutter-md q-mb-lg">
      <div class="col-12 col-sm-4">
        <q-card flat bordered>
          <q-card-section>
            <div class="text-h6">{{ actuators.length }}</div>
            <div class="text-grey-7">{{ t('actuators.totalActuators') }}</div>
          </q-card-section>
        </q-card>
      </div>
      <div class="col-12 col-sm-4">
        <q-card flat bordered>
          <q-card-section>
            <div class="text-h6 text-positive">{{ connectedActuators.length }}</div>
            <div class="text-grey-7">{{ t('actuators.connectedActuators') }}</div>
          </q-card-section>
        </q-card>
      </div>
      <div class="col-12 col-sm-4">
        <q-card flat bordered>
          <q-card-section>
            <div class="text-h6 text-orange">{{ enableActuators.length }}</div>
            <div class="text-grey-7">{{ t('actuators.availableActuators') }}</div>
          </q-card-section>
        </q-card>
      </div>
    </div>

    <!-- Barre de recherche -->
    <q-input
      v-model="searchFilter"
      :placeholder="t('actuators.searchPlaceholder')"
      outlined
      dense
      clearable
      class="q-mb-md"
    >
      <template v-slot:prepend>
        <q-icon name="search" />
      </template>
    </q-input>

    <!-- Message d'erreur -->
    <q-banner v-if="error" class="bg-negative text-white q-mb-md" rounded>
      <template v-slot:avatar>
        <q-icon name="error" />
      </template>
      {{ error }}
    </q-banner>

    <!-- Chargement -->
    <div v-if="loading && actuators.length === 0 && userProviders.length === 0" class="text-center q-pa-xl">
      <q-spinner size="50px" color="primary" />
      <p class="text-grey-7 q-mt-md">{{ t('actuators.loading') }}</p>
    </div>

    <!-- User Providers -->
    <div v-if="userProviders.length > 0" class="q-mb-xl">
      <h5 class="text-h5 q-mb-md">
        <q-icon name="account_circle" class="q-mr-sm" />
        {{ t('actuators.userProviders') }}
      </h5>
      <q-list bordered separator class="rounded-borders">
        <q-item
          v-for="provider in userProviders"
          :key="provider.name"
          class="actuator-item"
        >
          <q-item-section avatar>
            <q-avatar
              color="indigo"
              text-color="white"
              icon="verified_user"
            />
          </q-item-section>

          <q-item-section>
            <q-item-label class="text-h6">{{ provider.name.toUpperCase() }}</q-item-label>
            <q-item-label caption>
              <q-chip
                size="sm"
                color="indigo"
                text-color="white"
              >
                USER PROVIDER
              </q-chip>
            </q-item-label>
          </q-item-section>

          <q-item-section side>
            <div class="row items-center q-gutter-xs">
              <q-badge
                :color="provider.status === 'UP' ? 'positive' : 'negative'"
                rounded
                class="connection-badge"
              >
                <q-icon
                  :name="provider.status === 'UP' ? 'check_circle' : 'error'"
                  size="xs"
                  class="q-mr-xs"
                />
                {{ provider.status === 'UP' ? t('actuators.available') : t('actuators.unavailable') }}
              </q-badge>
            </div>
          </q-item-section>
        </q-item>
      </q-list>
    </div>

    <!-- Liste vide -->
    <div v-if="!loading && actuators.length === 0 && userProviders.length === 0" class="text-center q-pa-xl">
      <q-icon name="devices_other" size="80px" color="grey-5" />
      <p class="text-h6 text-grey-7 q-mt-md">{{ t('actuators.noActuatorsAvailable') }}</p>
    </div>

    <!-- Liste des actionneurs -->
    <div v-if="actuators.length > 0">
      <!-- Actionneurs connectés -->
      <div class="q-mb-xl">
        <h5 class="text-h5 q-mb-md">
          <q-icon name="link" class="q-mr-sm" />
          {{ t('actuators.connectedSection') }}
        </h5>
        <q-list bordered separator class="rounded-borders">
          <q-item
            v-for="actuator in filteredActuators.filter(a => a.added)"
            :key="actuator.title"
            class="actuator-item"
          >
            <q-item-section avatar>
              <q-avatar
                :color="getActuatorColor(actuator.type)"
                text-color="white"
                :icon="getActuatorIcon(actuator.type)"
              />
            </q-item-section>

            <q-item-section>
              <q-item-label class="text-h6">{{ actuator.title }}</q-item-label>
              <q-item-label caption>
                <q-chip
                  size="sm"
                  :color="getActuatorColor(actuator.type)"
                  text-color="white"
                >
                  {{ actuator.type.toUpperCase() }}
                </q-chip>
              </q-item-label>
            </q-item-section>

            <q-item-section side>
              <div class="row items-center q-gutter-md">
                <div class="row items-center q-gutter-xs">
                  <q-badge
                    :color="connectionStatus[actuator.title] ? 'positive' : 'negative'"
                    rounded
                    class="connection-badge"
                  >
                    <q-icon
                      :name="connectionStatus[actuator.title] ? 'check_circle' : 'error'"
                      size="xs"
                      class="q-mr-xs"
                    />
                    {{ connectionStatus[actuator.title] ? t('actuators.available') : t('actuators.unavailable') }}
                  </q-badge>
                </div>

                <!-- Bouton pour retirer -->
                <q-btn
                  flat
                  round
                  icon="link_off"
                  color="negative"
                  @click="toggleActuator(actuator)"
                  size="sm"
                >
                  <q-tooltip>{{ t('actuators.disconnect') }}</q-tooltip>
                </q-btn>
              </div>
            </q-item-section>
          </q-item>
        </q-list>
      </div>

      <!-- Actionneurs disponibles -->
      <div v-if="availableActuators.length > 0">
        <h5 class="text-h5 q-mb-md">
          <q-icon name="add_circle_outline" class="q-mr-sm" />
          {{ t('actuators.availableSection') }}
        </h5>
        <q-list bordered separator class="rounded-borders">
          <q-item
            v-for="actuator in filteredActuators.filter(a => !a.added)"
            :key="actuator.title"
            class="actuator-item"
          >
            <q-item-section avatar>
              <q-avatar
                :color="getActuatorColor(actuator.type)"
                text-color="white"
                :icon="getActuatorIcon(actuator.type)"
                class="opacity-60"
              />
            </q-item-section>

            <q-item-section>
              <q-item-label class="text-h6">{{ actuator.title }}</q-item-label>
              <q-item-label caption>
                <q-chip
                  size="sm"
                  :color="getActuatorColor(actuator.type)"
                  text-color="white"
                  outline
                >
                  {{ actuator.type.toUpperCase() }}
                </q-chip>
              </q-item-label>
            </q-item-section>

            <q-item-section side>
              <div class="row items-center q-gutter-md">
                <!-- Indicateur de connexion -->
                <div class="row items-center q-gutter-xs">
                  <q-badge
                    :color="connectionStatus[actuator.title] ? 'positive' : 'negative'"
                    rounded
                    outline
                    class="connection-badge"
                  >
                    <q-icon
                      :name="connectionStatus[actuator.title] ? 'check_circle' : 'error'"
                      size="xs"
                      class="q-mr-xs"
                    />
                    {{ connectionStatus[actuator.title] ? t('actuators.connected') : t('actuators.disconnected') }}
                  </q-badge>
                </div>

                <!-- Bouton pour ajouter -->
                <q-btn
                  unelevated
                  icon="add"
                  color="positive"
                  @click="toggleActuator(actuator)"
                  size="sm"
                  :label="t('actuators.add')"
                >
                  <q-tooltip>{{ t('actuators.connect') }}</q-tooltip>
                </q-btn>
              </div>
            </q-item-section>
          </q-item>
        </q-list>
      </div>

      <!-- Aucun résultat de recherche -->
      <div v-if="filteredActuators.length === 0 && searchFilter" class="text-center q-pa-xl">
        <q-icon name="search_off" size="60px" color="grey-5" />
        <p class="text-h6 text-grey-7 q-mt-md">{{ t('actuators.noResultsFound') }}</p>
        <p class="text-grey-6">{{ t('actuators.tryDifferentSearch') }}</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.actuator-item {
  transition: all 0.2s ease;
}

.actuator-item:hover {
  background-color: rgba(0, 0, 0, 0.02);
}

.connection-badge {
  padding: 4px 12px;
  font-weight: 500;
}

.opacity-60 {
  opacity: 0.6;
}
</style>