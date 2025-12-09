<script setup>

import {getBracket, deletePeriod, deleteBracket} from "../../../../../api/PeriodBracket";
import {onMounted, ref, computed} from "vue";
import {useRoute, useRouter} from "vue-router";
import {date, useQuasar} from 'quasar';
import {useI18n} from 'vue-i18n';
import AddPeriodDialog from '../../../../../components/dialog/Administration/periods/addPeriodDialog.vue';
import {user} from "../../../../../store/counter";

const {t} = useI18n();

const route = useRoute();
const router = useRouter();
const bracket = ref(null);
const filter = ref('');
const showAddDialog = ref(false);
const showEditDialog = ref(false);
const periodToEdit = ref(null);
const $q = useQuasar();
const userStore = user();
const serviceId = route.params.id;
const getDayName = (dayNumber) => {
  const days = [
    t('days.sunday'),
    t('days.monday'),
    t('days.tuesday'),
    t('days.wednesday'),
    t('days.thursday'),
    t('days.friday'),
    t('days.saturday')
  ];
  return days[dayNumber % 7];
}

const formatDays = (days) => {
  if (!days || days.length === 0) return t('periods.noDays');

  // Tri des jours dans l'ordre chronologique de la semaine
  // On s'assure que 0 (dimanche) est placé à la fin
  const sortedDays = [...days].sort((a, b) => {
    // Convertir 0 (dimanche) en 7 pour le tri
    const dayA = a === 0 ? 7 : a;
    const dayB = b === 0 ? 7 : b;
    return dayA - dayB;
  });

  const dayNames = sortedDays.map(day => getDayName(day));
  return dayNames.join(', ');
}

const formatDate = (dateValue) => {
  return dateValue ? date.formatDate(dateValue, 'DD/MM/YYYY') : t('common.notDefined');
}

const formatTime = (timeValue) => {
  return timeValue ? date.formatDate(timeValue, 'HH:mm') : t('common.notDefined');
}

const formatPeriodType = (type) => {
  switch (type) {
    case 'open':
      return t('periods.open');
    case 'close':
      return t('periods.close');
    default:
      return type;
  }
}

const filteredPeriods = computed(() => {
  if (!bracket.value?.periods) return [];

  if (!filter.value) return bracket.value.periods;

  const searchTerm = filter.value.toLowerCase();
  return bracket.value.periods.filter(period => {
    // Vérifier si un des jours correspond à la recherche
    const matchesDay = period.day && period.day.some(day => {
      const dayName = getDayName(day).toLowerCase();
      return dayName.includes(searchTerm);
    });

    // Vérifier si le type correspond à la recherche (en tenant compte de la traduction)
    const periodType = formatPeriodType(period.type).toLowerCase();

    return periodType.includes(searchTerm) ||
           period.type?.toLowerCase().includes(searchTerm) ||
           matchesDay ||
           formatDate(period.dateStart)?.toLowerCase().includes(searchTerm) ||
           formatDate(period.dateEnd)?.toLowerCase().includes(searchTerm);
  });
});

const getPeriodBracket = async () => {
  let reponse = await getBracket(route.params.idBracket);
  bracket.value = reponse.data;
}

const handleOpenAddDialog = () => {
  showAddDialog.value = true;
}

const handleOpenEditDialog = (period) => {
  periodToEdit.value = period;
  showEditDialog.value = true;
}

const handlePeriodAdded = (newPeriod) => {
  // Rafraîchir les données après ajout d'une période
  getPeriodBracket();
}

const handlePeriodUpdated = (updatedPeriod) => {
  // Rafraîchir les données après modification d'une période
  getPeriodBracket();

  $q.notify({
    type: 'positive',
      message: t('periods.updateSuccess'),
    position: 'top'
  });
}

const handleDeletePeriod = (period) => {
  $q.dialog({
    title: t('common.confirmDelete'),
    message: `${t('periods.deleteConfirmation.title')}<br><br>
             ${t('periods.deleteConfirmation.type', { type: formatPeriodType(period.type) })}<br>
             ${period.day && period.day.length > 0 ? t('periods.deleteConfirmation.days', { days: formatDays(period.day) }) + '<br>' : ''}
             ${t('periods.deleteConfirmation.dates', { dates: formatDate(period.dateStart) + ' - ' + formatDate(period.dateEnd) })}<br>
             ${t('periods.deleteConfirmation.hours', { hours: formatTime(period.timeStart) + ' - ' + formatTime(period.timeEnd) })}`,
    html: true,
    cancel: {
      label: t('common.cancel'),
      color: 'grey',
      flat: true
    },
    ok: {
      label: t('common.delete'),
      color: 'negative'
    },
    persistent: true
  }).onOk(async () => {
    try {
      await deletePeriod(period.id);

      $q.notify({
        type: 'positive',
        message: t('periods.deleteSuccess'),
        position: 'top'
      });

      // Rafraîchir les données
      await getPeriodBracket();
    } catch (error) {
      console.error('Erreur lors de la suppression de la période:', error);
      $q.notify({
        type: 'negative',
        message: error.response?.data?.detail || t('periods.deleteError'),
        position: 'top'
      });
    }
  });
};

const handleDeleteBracket = () => {
  $q.dialog({
    title: $t('common.confirmDelete'),
    message: `${t('periods.deleteBracketConfirmation.title')}<br><br>
             ${t('periods.deleteBracketConfirmation.name', { name: bracket.value?.title || t('common.untitled') })}<br>
             ${t('periods.deleteBracketConfirmation.count', { count: bracket.value?.periods?.length || 0 })}<br><br>
             <span class="text-negative">⚠️ ${t('periods.deleteWarning')}</span>`,
    html: true,
    cancel: {
      label: $t('common.cancel'),
      color: 'grey',
      flat: true
    },
    ok: {
      label: $t('common.deletePermanently'),
      color: 'negative'
    },
    persistent: true
  }).onOk(async () => {
    try {
      await deleteBracket(route.params.idBracket);

      $q.notify({
        type: 'positive',
        message: $t('periods.deleteBracketSuccess'),
        position: 'top'
      });

      // Rediriger vers la page parent (liste des brackets)
      router.push(`/administration/site/${route.params.id}/periods`);
    } catch (error) {
      console.error('Erreur lors de la suppression du bracket:', error);
      $q.notify({
        type: 'negative',
        message: error.response?.data?.detail || 'Erreur lors de la suppression du groupe de périodes',
        position: 'top'
      });
    }
  });
};

onMounted(() => {
  getPeriodBracket();
})
</script>

<route lang="json">
{
"name": "managePeriodBracket"
}
</route>

<template>
<q-page padding v-if="bracket">
  <div class="q-pa-md">
    <div class="row items-center q-mb-md">
      <div class="col">
        <h1 class="text-h5">{{$t('periods.manage')}}</h1>
        <h2 v-if="bracket.title" class="text-subtitle2 text-grey-7 q-my-xs">
          {{ bracket.title }}
        </h2>
      </div>
      <div class="col-auto">
        <q-btn
          v-if="userStore.isUserAdminSite(serviceId)"
          color="negative"
          icon="delete"
          :label="$t('periods.deleteBracket')"
          outline
          @click="handleDeleteBracket"
        >
          <q-tooltip>
            {{$t('periods.deleteBracketTooltip')}}
          </q-tooltip>
        </q-btn>
      </div>
    </div>

    <div class="q-mb-md row">
      <q-input
        v-model="filter"
        dense
        debounce="300"
        :placeholder="$t('periods.searchPlaceholder')"
        class="col"
      >
        <template v-slot:append>
          <q-icon name="search" />
        </template>
      </q-input>

      <q-btn
        v-if="userStore.isUserAdminSite(serviceId)"
        color="primary"
        icon="add"
        :label="$t('periods.addPeriod')"
        class="q-ml-sm"
        @click="handleOpenAddDialog"
      />
    </div>

    <q-list bordered separator>
      <q-item
        v-for="period in filteredPeriods"
        :key="period.id"
        clickable
        v-ripple
        class="period-item"
      >
        <q-item-section>
          <q-item-label class="text-weight-bold">{{ formatPeriodType(period.type) }}</q-item-label>
          <q-item-label caption>
            <span v-if="period.day && period.day.length > 0" class="q-mr-sm">
              <q-icon name="event" size="xs" /> {{ formatDays(period.day) }}
            </span>
            <span v-if="period.dateStart || period.dateEnd" class="q-mr-sm">
              <q-icon name="date_range" size="xs" /> {{ formatDate(period.dateStart) }} - {{ formatDate(period.dateEnd) }}
            </span>
            <span v-if="period.timeStart || period.timeEnd">
              <q-icon name="schedule" size="xs" /> {{ formatTime(period.timeStart) }} - {{ formatTime(period.timeEnd) }}
            </span>
          </q-item-label>
        </q-item-section>

        <q-item-section side>
          <div class="row items-center">
            <q-btn v-if="userStore.isUserAdminSite(serviceId)" flat round color="primary" icon="edit" size="sm" @click="handleOpenEditDialog(period)" />
            <q-btn v-if="userStore.isUserAdminSite(serviceId)" flat round color="negative" icon="delete" size="sm" @click="handleDeletePeriod(period)" />
          </div>
        </q-item-section>
      </q-item>

      <q-item v-if="filteredPeriods.length === 0">
        <q-item-section class="text-grey text-center">
          <div>Aucune période trouvée</div>
        </q-item-section>
      </q-item>
    </q-list>

    <!-- Dialog pour ajouter une période -->
    <AddPeriodDialog
      :period-bracket-id="Number(route.params.idBracket)"
      :visible="showAddDialog"
      :existing-periods="bracket?.periods || []"
      @close="showAddDialog = false"
      @period-added="handlePeriodAdded"
    />

    <!-- Dialog pour éditer une période -->
    <AddPeriodDialog
      :period-bracket-id="Number(route.params.idBracket)"
      :visible="showEditDialog"
      :existing-periods="bracket?.periods || []"
      :edit-mode="true"
      :period-to-edit="periodToEdit"
      @close="showEditDialog = false"
      @period-updated="handlePeriodUpdated"
    />
  </div>
</q-page>
</template>

<style scoped>
.period-item:hover {
  background-color: #f5f5f5;
}

/* Assure la lisibilité des icônes avec leur texte */
.q-item-label.caption .q-icon {
  margin-right: 2px;
  position: relative;
  top: -1px;
}
</style>