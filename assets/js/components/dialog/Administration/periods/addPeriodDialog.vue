<script setup>
import { ref, watch, computed } from 'vue';
import { date } from 'quasar';
import { useI18n } from 'vue-i18n';
import { addPeriodToBracket, updatePeriod } from '../../../../api/PeriodBracket';

const { t } = useI18n();
const props = defineProps({
  periodBracketId: {
    type: Number,
    required: true
  },
  visible: {
    type: Boolean,
    default: false
  },
  existingPeriods: {
    type: Array,
    default: () => []
  },
  editMode: {
    type: Boolean,
    default: false
  },
  periodToEdit: {
    type: Object,
    default: null
  }
});

const emit = defineEmits(['close', 'periodAdded', 'periodUpdated']);

const showDialog = ref(props.visible);
const loading = ref(false);
const errorMessage = ref('');
const showDateFields = ref(false);

const formData = ref({
  type: '',
  day: [],
  dateStart: null,
  dateEnd: null,
  timeStart: date.formatDate(new Date().setMinutes(0), 'HH:mm'),
  timeEnd: date.formatDate(new Date(new Date().setHours(new Date().getHours() + 1)).setMinutes(0), 'HH:mm'),
  periodBracket: `/api/period_brackets/${props.periodBracketId}`
});

const dayOptions = computed(() => [
  { label: t('days.monday'), value: 1 },
  { label: t('days.tuesday'), value: 2 },
  { label: t('days.wednesday'), value: 3 },
  { label: t('days.thursday'), value: 4 },
  { label: t('days.friday'), value: 5 },
  { label: t('days.saturday'), value: 6 },
  { label: t('days.sunday'), value: 0 }
]);

const typeOptions = computed(() => [
  { label: t('periods.open'), value: 'open' },
  { label: t('periods.close'), value: 'close' },
]);

const showDayField = computed(() => {
  return formData.value.type === 'open';
});

const dialogTitle = computed(() => {
  return props.editMode ? t('periods.editPeriod') : t('periods.addPeriod');
});

const submitButtonLabel = computed(() => {
  return props.editMode ? t('common.edit') : t('common.add');
});

const resetForm = () => {
  if (props.editMode && props.periodToEdit) {
    // Mode édition : pré-remplir avec les données existantes
    formData.value = {
      type: props.periodToEdit.type || '',
      day: Array.isArray(props.periodToEdit.day) ? [...props.periodToEdit.day] : [],
      dateStart: props.periodToEdit.dateStart ? date.formatDate(new Date(props.periodToEdit.dateStart), 'YYYY-MM-DD') : null,
      dateEnd: props.periodToEdit.dateEnd ? date.formatDate(new Date(props.periodToEdit.dateEnd), 'YYYY-MM-DD') : null,
      timeStart: props.periodToEdit.timeStart ? date.formatDate(new Date(props.periodToEdit.timeStart), 'HH:mm') : date.formatDate(new Date().setMinutes(0), 'HH:mm'),
      timeEnd: props.periodToEdit.timeEnd ? date.formatDate(new Date(props.periodToEdit.timeEnd), 'HH:mm') : date.formatDate(new Date(new Date().setHours(new Date().getHours() + 1)).setMinutes(0), 'HH:mm'),
      periodBracket: `/api/period_brackets/${props.periodBracketId}`
    };
    // Afficher les champs de date si des dates sont déjà définies
    showDateFields.value = !!(props.periodToEdit.dateStart && props.periodToEdit.dateEnd);
  } else {
    // Mode ajout : formulaire vide
    formData.value = {
      type: '',
      day: [],
      dateStart: null,
      dateEnd: null,
      timeStart: date.formatDate(new Date().setMinutes(0), 'HH:mm'),
      timeEnd: date.formatDate(new Date(new Date().setHours(new Date().getHours() + 1)).setMinutes(0), 'HH:mm'),
      periodBracket: `/api/period_brackets/${props.periodBracketId}`
    };
    // Masquer les champs de date par défaut
    showDateFields.value = false;
  }
  errorMessage.value = '';
};

/**
 * Vérifie si deux plages de dates se chevauchent
 */
const datesOverlap = (start1, end1, start2, end2) => {
  const startDate1 = new Date(start1);
  const endDate1 = new Date(end1);
  const startDate2 = new Date(start2);
  const endDate2 = new Date(end2);

  return startDate1 <= endDate2 && startDate2 <= endDate1;
};

/**
 * Vérifie si deux créneaux horaires se chevauchent
 */
const timesOverlap = (timeStart1, timeEnd1, timeStart2, timeEnd2) => {
  // Convertir les heures en minutes pour faciliter la comparaison
  const convertTimeToMinutes = (timeStr) => {
    if (!timeStr) return 0;
    const [hours, minutes] = timeStr.split(':').map(Number);
    return hours * 60 + minutes;
  };

  const start1Minutes = convertTimeToMinutes(timeStart1);
  const end1Minutes = convertTimeToMinutes(timeEnd1);
  const start2Minutes = convertTimeToMinutes(timeStart2);
  const end2Minutes = convertTimeToMinutes(timeEnd2);

  return start1Minutes < end2Minutes && start2Minutes < end1Minutes;
};

/**
 * Vérifie s'il y a des conflits avec les périodes existantes
 */
const checkPeriodConflicts = () => {
  const newPeriod = formData.value;

  // Si pas de jours sélectionnés pour une période d'ouverture, pas de conflit à vérifier
  if (newPeriod.type === 'open' && (!newPeriod.day || newPeriod.day.length === 0)) {
    return null;
  }

  for (const existingPeriod of props.existingPeriods) {
    // En mode édition, ignorer la période en cours de modification
    if (props.editMode && props.periodToEdit && existingPeriod.id === props.periodToEdit.id) {
      continue;
    }

    // Vérifier seulement les périodes du même type
    if (existingPeriod.type !== newPeriod.type) {
      continue;
    }

    // Vérifier si les plages de dates se chevauchent
    if (!datesOverlap(newPeriod.dateStart, newPeriod.dateEnd, existingPeriod.dateStart, existingPeriod.dateEnd)) {
      continue;
    }

    // Vérifier si les créneaux horaires se chevauchent
    if (!timesOverlap(newPeriod.timeStart, newPeriod.timeEnd, existingPeriod.timeStart, existingPeriod.timeEnd)) {
      continue; // Pas de conflit si les heures ne se chevauchent pas
    }

    // Pour les périodes d'ouverture, vérifier les jours en commun
    if (newPeriod.type === 'open') {
      const newDays = newPeriod.day || [];
      const existingDays = existingPeriod.day || [];

      // Vérifier s'il y a des jours en commun
      const commonDays = newDays.filter(day => existingDays.includes(day));

      if (commonDays.length > 0) {
        const dayNames = commonDays.map(day => {
          const dayOption = dayOptions.find(option => option.value === day);
          return dayOption ? dayOption.label : `Jour ${day}`;
        });

        return t('periods.openPeriodConflict', {
          days: dayNames.join(', '),
          startDate: existingPeriod.dateStart,
          endDate: existingPeriod.dateEnd,
          startTime: existingPeriod.timeStart,
          endTime: existingPeriod.timeEnd
        });
      }
    } else {
      // Pour les périodes de fermeture, tout chevauchement de dates et d'heures est un conflit
      return t('periods.closePeriodConflict', {
        startDate: existingPeriod.dateStart,
        endDate: existingPeriod.dateEnd,
        startTime: existingPeriod.timeStart,
        endTime: existingPeriod.timeEnd
      });
    }
  }

  return null;
};

const handleSubmit = async () => {
  try {
    loading.value = true;
    errorMessage.value = '';

    // Vérifier que les dates sont valides
    if (new Date(formData.value.dateEnd) < new Date(formData.value.dateStart)) {
      errorMessage.value = t('periods.endDateError');
      loading.value = false;
      return;
    }

    // Vérifier les conflits de périodes
    const conflictError = checkPeriodConflicts();
    if (conflictError) {
      errorMessage.value = conflictError;
      loading.value = false;
      return;
    }

    let response;
    if (props.editMode && props.periodToEdit) {
      // Mode édition
      response = await updatePeriod(props.periodToEdit.id, formData.value);
      emit('periodUpdated', response.data);
    } else {
      // Mode ajout
      response = await addPeriodToBracket(formData.value);
      emit('periodAdded', response.data);
    }

    // Fermer la dialog via l'événement close
    emit('close');

    // Réinitialiser le formulaire
    resetForm();
  } catch (error) {
    console.error('Erreur lors de l\'opération sur la période:', error);
    const operation = props.editMode ? 'edit' : 'add';
    errorMessage.value = error.response?.data?.detail || t('periods.operationError', { operation: t(`common.${operation}`) });
  } finally {
    loading.value = false;
  }
};

const closeDialog = () => {
  emit('close');
  resetForm();
};

// Observer les changements de la prop visible
watch(() => props.visible, (newValue) => {
  showDialog.value = newValue;

  // Si le dialogue s'ouvre, réinitialiser le formulaire
  if (newValue) {
    resetForm();
  }
});

// Observer les changements des props d'édition pour pré-remplir le formulaire
watch([() => props.editMode, () => props.periodToEdit], ([editMode, periodToEdit]) => {
  if (editMode && periodToEdit && props.visible) {
    formData.value = {
      type: periodToEdit.type || '',
      day: Array.isArray(periodToEdit.day) ? [...periodToEdit.day] : [],
      dateStart: periodToEdit.dateStart ? date.formatDate(new Date(periodToEdit.dateStart), 'YYYY-MM-DD') : null,
      dateEnd: periodToEdit.dateEnd ? date.formatDate(new Date(periodToEdit.dateEnd), 'YYYY-MM-DD') : null,
      timeStart: date.formatDate(new Date(periodToEdit.timeStart), 'HH:mm'),
      timeEnd: date.formatDate(new Date(periodToEdit.timeEnd), 'HH:mm'),
      periodBracket: `/api/period_brackets/${props.periodBracketId}`
    };
  }
}, { immediate: true, deep: true });

const toggleDateFields = () => {
  showDateFields.value = !showDateFields.value;
  if (!showDateFields.value) {
    // Si on masque les champs, réinitialiser les dates à null
    formData.value.dateStart = null;
    formData.value.dateEnd = null;
  }
};
</script>

<template>
  <q-dialog v-model="showDialog" persistent @hide="closeDialog">
    <q-card style="min-width: 350px">
      <q-card-section class="row items-center">
        <div class="text-h6">{{ dialogTitle }}</div>
        <q-space />
        <q-btn icon="close" flat round dense v-close-popup @click="closeDialog" />
      </q-card-section>

      <q-card-section>
        <q-form @submit.prevent="handleSubmit">
          <div class="q-gutter-y-md">
            <q-select
              v-model="formData.type"
              :options="typeOptions"
              option-label="label"
              option-value="value"
              emit-value
              map-options
              :label="$t('periods.periodType') + ' *'"
              lazy-rules
              :rules="[val => !!val || $t('periods.typeRequired')]"
            />

            <q-select
              v-if="showDayField"
              v-model="formData.day"
              :options="dayOptions"
              option-label="label"
              option-value="value"
              emit-value
              map-options
              multiple
              use-chips
              :label="$t('periods.weekDays')"
              :hint="$t('periods.selectDaysHint')"
              lazy-rules
              :rules="[val => val.length > 0 || $t('periods.daysRequired')]"
            />

            <!-- Bouton pour afficher/masquer les champs de dates -->
            <div class="row items-center q-gutter-sm">
              <q-btn
                 no-caps
                 dense
                :label="showDateFields ? $t('periods.removeDates') : $t('periods.addDates')"
                :icon="showDateFields ? 'remove_circle' : 'date_range'"
                :color="showDateFields ? 'negative' : 'positive'"
                outline
                @click="toggleDateFields"
              />
            </div>

            <div class="row q-gutter-md" v-if="showDateFields">
              <q-input
                v-model="formData.dateStart"
                filled
                type="date"
                :label="$t('periods.startDate')"
                class="col"
              />

              <q-input
                v-model="formData.dateEnd"
                filled
                type="date"
                :label="$t('periods.endDate')"
                class="col"
              />
            </div>
            <span class="text-caption text-grey-6">
              {{ $t('periods.datesApplyInfo') }}
            </span>

            <div class="row q-gutter-md">
              <div class="col">
                <q-input
                  v-model="formData.timeStart"
                  filled
                  :label="$t('periods.startTime') + ' *'"
                  readonly
                  lazy-rules
                  :rules="[val => !!val || $t('periods.startTimeRequired')]"
                >
                  <template v-slot:append>
                    <q-icon name="access_time" size="xs" />
                  </template>

                  <template v-slot:default>
                    <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                      <q-time
                        v-model="formData.timeStart"
                        format24h
                        mask="HH:mm"
                        :options="(hour, minute) => hour >= 0 && hour <= 23"
                      >
                        <div class="row items-center justify-end q-pr-sm">
                          <q-btn :label="$t('common.close')" color="primary" flat v-close-popup />
                        </div>
                      </q-time>
                    </q-popup-proxy>
                  </template>
                </q-input>
              </div>

              <div class="col">
                <q-input
                  v-model="formData.timeEnd"
                  filled
                  :label="$t('periods.endTime') + ' *'"
                  readonly
                  lazy-rules
                  :rules="[val => !!val || 'L\'heure de fin est requise']"
                >
                  <template v-slot:append>
                    <q-icon name="access_time" size="xs" />
                  </template>

                  <template v-slot:default>
                    <q-popup-proxy cover transition-show="scale" transition-hide="scale">
                      <q-time
                        v-model="formData.timeEnd"
                        format24h
                        mask="HH:mm"
                        :options="(hour, minute) => hour >= 0 && hour <= 23"
                      >
                        <div class="row items-center justify-end q-pr-sm">
                          <q-btn label="Fermer" color="primary" flat v-close-popup />
                        </div>
                      </q-time>
                    </q-popup-proxy>
                  </template>
                </q-input>
              </div>
            </div>

            <div v-if="errorMessage" class="text-negative">
              {{ errorMessage }}
            </div>
          </div>

          <q-card-actions align="right" class="q-mt-md">
            <q-btn flat :label="$t('common.cancel')" color="primary" @click="closeDialog" />
            <q-btn type="submit" :label="submitButtonLabel" color="primary" :loading="loading" />
          </q-card-actions>
        </q-form>
      </q-card-section>
    </q-card>
  </q-dialog>
</template>

<style scoped>
.q-card {
  max-width: 500px;
  width: 100%;
}
</style>