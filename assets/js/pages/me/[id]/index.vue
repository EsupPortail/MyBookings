<template>
  <div class="booking-detail q-pa-md">
    <q-card flat bordered class="booking-card">
      <q-card-section class="bg-primary text-white">
        <div class="row items-center justify-between">
          <div class="col">
            <div class="text-h5 text-weight-bold">
              <q-icon name="event" class="q-mr-sm" />
              {{ booking?.title || 'Réservation' }}
              <q-chip
                  :color="statusConfig.color"
                  text-color="white"
                  :icon="statusConfig.icon"
                  size="md"
              >
                {{ statusConfig.label }}
              </q-chip>
            </div>
            <div class="text-subtitle2 q-mt-xs" v-if="booking?.catalogueResource?.subType">
              {{ booking.catalogueResource.subType.title }}
            </div>
          </div>
          <div class="col-auto row items-center q-gutter-sm">
            <q-btn
              v-if="booking?.status === 'accepted'"
              color="negative"
              icon="delete"
              :label="$t('booking.deleteBooking')"
              @click="confirmDelete"
            />
          </div>
        </div>
      </q-card-section>

      <q-dialog v-model="showDeleteDialog" persistent>
        <q-card style="min-width: 350px">
          <q-card-section class="row items-center">
            <q-avatar icon="warning" color="negative" text-color="white" />
            <span class="q-ml-sm text-h6">{{$t('booking.deleteBooking')}}</span>
          </q-card-section>

          <q-card-section>
            {{$t('booking.deleteConfirmation')}}
            <br />
            <strong>{{ booking?.title }}</strong>
          </q-card-section>

          <q-card-actions align="right">
            <q-btn flat :label="$t('common.cancel')" color="primary" v-close-popup />
            <q-btn
              flat
              label="Oui, annuler"
              color="negative"
              @click="handleDelete"
              :loading="deleting"
            />
          </q-card-actions>
        </q-card>
      </q-dialog>

      <q-separator />

      <q-card-section v-if="booking" class="q-pa-none">
        <div class="row">
          <div class="col-12 col-md-7 q-pa-lg">
            <div class="info-section q-mb-lg">
              <div class="section-title">
                <q-icon name="people" color="primary" class="q-mr-sm" />
                {{$t('booking.users')}}
              </div>
              <div class="q-mt-sm">
                <q-chip
                  v-for="(user, index) in booking.user"
                  :key="index"
                  color="primary"
                  text-color="white"
                  icon="person"
                >
                  {{ user.displayUserName }}
                </q-chip>
                <span v-if="!booking.user?.length" class="text-grey-6 text-italic">
                  Aucun utilisateur
                </span>
              </div>
            </div>

            <div class="info-section q-mb-lg">
              <div class="section-title">
                <q-icon name="schedule" color="primary" class="q-mr-sm" />
                {{ $t('sites.bookingPeriods') }}
              </div>
              <div class="dates-container q-mt-sm">
                <q-card flat bordered class="date-card">
                  <q-card-section class="q-pa-sm text-center">
                    <div class="text-caption text-grey-7">{{$t('booking.startDate')}}</div>
                    <div class="text-subtitle1 text-weight-medium">
                      {{ formatDate(booking.dateStart) }}
                    </div>
                  </q-card-section>
                </q-card>
                <q-icon name="arrow_forward" size="sm" color="grey-5" class="q-mx-md" />
                <q-card flat bordered class="date-card">
                  <q-card-section class="q-pa-sm text-center">
                    <div class="text-caption text-grey-7">{{$t('booking.endDate')}}</div>
                    <div class="text-subtitle1 text-weight-medium">
                      {{ formatDate(booking.dateEnd) }}
                    </div>
                  </q-card-section>
                </q-card>
              </div>
            </div>

            <div class="info-section q-mb-lg">
              <div class="section-title">
                <q-icon name="comment" color="primary" class="q-mr-sm" />
                {{$t('common.userComment')}}
              </div>
              <q-card flat bordered class="comment-card q-mt-sm">
                <q-card-section>
                  <p v-if="booking.userComment" class="text-body1 text-italic q-ma-none">
                    « {{ booking.userComment }} »
                  </p>
                  <p v-else class="text-grey-6 text-italic q-ma-none">
                    Aucun commentaire
                  </p>
                </q-card-section>
              </q-card>
            </div>

            <div class="info-section q-mb-lg" v-if="booking.status !== 'pending'">
              <div class="section-title">
                <q-icon name="rate_review" color="secondary" class="q-mr-sm" />
                {{$t('common.adminComment')}}
              </div>
              <q-card flat bordered class="comment-card q-mt-sm">
                <q-card-section>
                  <p v-if="booking.confirmComment" class="text-body1 text-italic q-ma-none">
                    « {{ booking.confirmComment }} »
                  </p>
                  <p v-else class="text-grey-6 text-italic q-ma-none">
                    Aucun commentaire
                  </p>
                </q-card-section>
              </q-card>
            </div>

            <div class="info-section" v-if="booking.status !== 'pending' && booking.status !== 'refused' && booking.Resource?.length">
              <div class="section-title">
                <q-icon name="inventory_2" color="positive" class="q-mr-sm" />
                {{$t('booking.assignResource')}}
              </div>
              <q-list bordered separator class="rounded-borders q-mt-sm">
                <q-item v-for="resource in booking.Resource" :key="resource.id">
                  <q-item-section avatar>
                    <q-avatar color="primary" text-color="white" icon="devices" />
                  </q-item-section>
                  <q-item-section>
                    <q-item-label>{{ resource.title }}</q-item-label>
                    <q-item-label caption v-if="resource.inventoryNumber">
                      N° inventaire : #{{ resource.inventoryNumber }}
                    </q-item-label>
                  </q-item-section>
                </q-item>
              </q-list>
            </div>

            <div class="info-section q-mt-lg" v-if="booking.Options?.length">
              <div class="section-title">
                <q-icon name="tune" color="info" class="q-mr-sm" />
                Options
              </div>
              <div class="q-mt-sm q-gutter-sm">
                <q-chip
                  v-for="option in booking.Options"
                  :key="option.id"
                  outline
                  color="info"
                >
                  {{ option.label || option.title }}
                </q-chip>
              </div>
            </div>
          </div>

          <div class="col-12 col-md-5 bg-grey-1">
            <div class="image-container q-pa-lg">
              <q-img
                :src="'/uploads/' + booking.catalogueResource?.image"
                :ratio="4/3"
                class="rounded-borders shadow-2"
                spinner-color="primary"
              >
                <template v-slot:error>
                  <div class="absolute-full flex flex-center bg-grey-3">
                    <div class="text-center text-grey-6">
                      <q-icon name="image_not_supported" size="64px" />
                      <div class="q-mt-sm">Aucune image disponible</div>
                    </div>
                  </div>
                </template>
              </q-img>

              <div class="q-mt-md" v-if="booking.catalogueResource">
                <q-card flat bordered>
                  <q-card-section>
                    <div class="text-h6">{{ booking.catalogueResource.title }}</div>
                    <q-badge
                      v-if="booking.catalogueResource.type"
                      color="secondary"
                      class="q-mt-sm"
                    >
                      {{ booking.catalogueResource.type.title }}
                    </q-badge>
                    <div class="text-caption text-grey-7 q-mt-sm" v-if="booking.catalogueResource.service">
                      Localisation : {{ booking.catalogueResource.localization.title }}
                    </div>
                  </q-card-section>
                </q-card>
              </div>
            </div>
          </div>
        </div>
      </q-card-section>

      <q-card-section v-else class="flex flex-center" style="min-height: 300px;">
        <q-spinner-dots color="primary" size="50px" />
      </q-card-section>
    </q-card>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router/auto'
import { useQuasar } from 'quasar'
import axios from 'axios'
import {deleteBooking, loadBookingById} from '../../../api/Booking.js'

const route = useRoute()
const router = useRouter()
const $q = useQuasar()

const booking = ref(null)
const showDeleteDialog = ref(false)
const deleting = ref(false)

const statusConfig = computed(() => {
  const configs = {
    pending: { label: 'Confirmation en attente', color: 'warning', icon: 'hourglass_empty' },
    accepted: { label: 'Confirmé', color: 'positive', icon: 'check_circle' },
    progress: { label: 'En cours', color: 'info', icon: 'play_circle' },
    returned: { label: 'Terminé', color: 'secondary', icon: 'task_alt' },
    refused: { label: 'Refusé', color: 'negative', icon: 'cancel' }
  }
  return configs[booking.value?.status] || { label: 'Inconnu', color: 'grey', icon: 'help' }
})

const formatDate = (dateString) => {
  if (!dateString) return ''
  const date = new Date(dateString)
  return date.toLocaleDateString('fr-FR', {
    weekday: 'short',
    day: '2-digit',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  })
}

const getBooking = async () => {
  try {
    booking.value = await loadBookingById(route.params.id);
  } catch (error) {
    console.error('Erreur lors du chargement de la réservation:', error)
  }
}

const confirmDelete = () => {
  showDeleteDialog.value = true
}

const handleDelete = async () => {
  deleting.value = true
  try {
    await deleteBooking(route.params.id)
    $q.notify({
      type: 'positive',
      message: 'Réservation annulée avec succès'
    })
    router.push('/me')
  } catch (error) {
    console.error('Erreur lors de l\'annulation:', error)
    $q.notify({
      type: 'negative',
      message: 'Erreur lors de l\'annulation de la réservation'
    })
  } finally {
    deleting.value = false
    showDeleteDialog.value = false
  }
}

onMounted(() => {
  getBooking()
})
</script>

<style scoped>
.booking-detail {
  max-width: 1200px;
  margin: 0 auto;
}

.booking-card {
  border-radius: 12px;
  overflow: hidden;
}

.section-title {
  font-size: 1rem;
  font-weight: 600;
  color: #37474f;
  display: flex;
  align-items: center;
}

.dates-container {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
}

.date-card {
  min-width: 150px;
  border-radius: 8px;
}

.comment-card {
  border-radius: 8px;
  background-color: #fafafa;
}

.image-container {
  position: sticky;
  top: 20px;
}

.info-section {
  padding-bottom: 1rem;
  border-bottom: 1px solid #e0e0e0;
}

.info-section:last-child {
  border-bottom: none;
}

@media (max-width: 1024px) {
  .dates-container {
    flex-direction: column;
    align-items: stretch;
  }

  .date-card {
    width: 100%;
  }
}
</style>