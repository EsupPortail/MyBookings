<template>
  <q-card>
    <q-card-section class="text-center">
      <h1 class="text-h3 q-my-md">{{$t('documentation.title')}}</h1>
    </q-card-section>

    <q-card-section class="q-pb-none">
      <p>
      {{$t('documentation.subtitle')}}
      </p>
      <ul class="navigation-links">
        <li><a href="#reservation" aria-label="Aller à la section sur la réservation">{{$t('documentation.bookingResource')}}</a></li>
        <li><a href="#administration" aria-label="Aller à la section sur l'administration">{{$t('documentation.manageBookings')}}</a></li>
        <li><a href="#groupes" aria-label="Aller à la section sur les groupes d'utilisateurs">{{$t('documentation.manageGroups')}}</a></li>
        <li><a href="#utilisateurs-externes" aria-label="Aller à la section sur les utilisateurs externes">{{$t('documentation.manageUsers')}}</a></li>
      </ul>
    </q-card-section>

    <!-- Section Réservation -->
    <q-card-section id="reservation" class="q-py-md section-anchor">
      <h2 class="text-h4 q-mb-md">{{$t('documentation.bookingResource')}}</h2>
      <div class="row q-col-gutter-md items-center">
        <div class="col-12 col-md-5">
          <p>
            {{$t('documentation.bookingExplanation')}}
          </p>
        </div>
        <div class="col-12 col-md-7 row justify-center">
          <q-responsive :ratio="16/9" class="video-container">
            <iframe
              src="https://opencast.dsi.uca.fr/paella/ui/embed.html?id=04ddcd70-5d46-40e4-94c5-93024ddb92cc"
              allowfullscreen
              loading="lazy"
              :title="$t('documentation.bookingResource')"
              aria-label="Tutoriel vidéo expliquant le processus de réservation dans MyBookings"
            ></iframe>
          </q-responsive>
        </div>
      </div>
    </q-card-section>

    <!-- Section Administration -->
    <q-card-section id="administration" class="q-py-md section-anchor">
      <h2 class="text-h4 q-mb-md">{{$t('documentation.manageBookings')}}</h2>
      <p>
        {{$t('documentation.manageBookingsExplanation')}}
      </p>

      <!-- Création catalogue et planning -->
      <div class="row q-col-gutter-md q-mb-md">
        <div class="col-12 col-md-6">
          <h3 class="text-h6 q-mb-sm">{{$t('documentation.catalogCreation')}}</h3>
          <p>{{$t('documentation.catalogCreationExplanation')}}</p>
          <q-responsive :ratio="16/9" class="video-container">
            <iframe
              src="https://opencast.dsi.uca.fr/paella/ui/embed.html?id=4c5b5e98-2fa1-41bd-b153-d84089b6bc8a"
              allowfullscreen
              loading="lazy"
              :title="$t('documentation.catalogCreation')"
              aria-label="Tutoriel vidéo sur la création d'un catalogue de ressources"
            ></iframe>
          </q-responsive>
        </div>

        <div class="col-12 col-md-6">
          <h3 class="text-h6 q-mb-sm">{{$t('documentation.planningCreation')}}</h3>
          <p>{{$t('documentation.planningCreationExplanation')}}</p>
          <q-responsive :ratio="16/9" class="video-container">
            <iframe
              src="https://opencast.dsi.uca.fr/paella/ui/embed.html?id=50ca2726-2d55-4204-b9d2-e7d94c1004e3"
              allowfullscreen
              loading="lazy"
              :title="$t('documentation.planningCreation')"
              aria-label="Tutoriel vidéo sur la création d'un planning de mise à disposition pour un catalogue"
            ></iframe>
          </q-responsive>
        </div>
      </div>

      <!-- Workflow et modération -->
      <div class="row q-col-gutter-md">
        <div class="col-12 col-md-6">
          <h3 class="text-h6 q-mb-sm">{{$t('documentation.workflowCreation')}}</h3>
          <p>{{$t('documentation.workflowCreationExplanation')}}</p>
          <q-responsive :ratio="16/9" class="video-container">
            <iframe
              src="https://opencast.dsi.uca.fr/paella/ui/embed.html?id=bccac023-5879-4e85-afa5-314e2afd98dc"
              allowfullscreen
              loading="lazy"
              :title="$t('documentation.workflowCreation')"
              aria-label="Tutoriel vidéo sur la création d'un workflow personnalisé"
            ></iframe>
          </q-responsive>
        </div>

        <div class="col-12 col-md-6">
          <h3 class="text-h6 q-mb-sm">{{$t('documentation.moderationBooking')}}</h3>
          <p>{{$t('documentation.moderationExplanation')}}</p>
          <q-responsive :ratio="16/9" class="video-container">
            <iframe
              src="https://opencast.dsi.uca.fr/paella/ui/embed.html?id=9be1fe1f-df20-4dd3-aa50-8e82b0d9480e"
              allowfullscreen
              loading="lazy"
              :title="$t('documentation.moderationBooking')"
              aria-label="Tutoriel vidéo sur la modération des réservations"
            ></iframe>
          </q-responsive>
        </div>
      </div>
    </q-card-section>

    <!-- Section Groupes et utilisateurs externes -->
    <q-card-section id="groupes" class="q-py-md section-anchor">
      <h2 class="text-h4 q-mb-md">{{$t('documentation.groupManagement')}}</h2>
      <h3 class="text-h6 q-mb-sm">{{$t('documentation.groupCreation')}}</h3>


      <!-- Création groupe -->
      <div class="row q-col-gutter-md q-mb-md items-center">
        <div class="col-12 col-md-5">
          <p>
            {{$t('documentation.groupCreationExplanation')}}
          </p>
        </div>
        <div class="col-12 col-md-7 row justify-center">
          <q-responsive :ratio="16/9" class="video-container">
            <iframe
              src="https://opencast.dsi.uca.fr/paella/ui/embed.html?id=28497455-6228-4b4b-b37a-d4b14354036f"
              allowfullscreen
              loading="lazy"
              :title="$t('documentation.groupCreation')"
              aria-label="Tutoriel vidéo sur la création d'un groupe d'utilisateurs"
            ></iframe>
          </q-responsive>
        </div>
      </div>

      <!-- Utilisateur externe et authentification -->
      <div id="utilisateurs-externes" class="row q-col-gutter-md">
        <div class="col-12 col-md-6">
          <h3 class="text-h6 q-mb-sm">{{$t('documentation.userCreation')}}</h3>
          <p>{{$t('documentation.userCreationExplanation')}}</p>
          <q-responsive :ratio="16/9" class="video-container">
            <iframe
              src="https://opencast.dsi.uca.fr/paella/ui/embed.html?id=84666443-6385-4f8f-8d9b-01ea5ac35d55"
              allowfullscreen
              loading="lazy"
              :title="$t('documentation.userCreation')"
              aria-label="Tutoriel vidéo sur la création d'un utilisateur externe"
            ></iframe>
          </q-responsive>
        </div>

        <div class="col-12 col-md-6">
          <h3 class="text-h6 q-mb-sm">{{$t('documentation.authenticateUser')}}</h3>
          <p>
            {{$t('documentation.authenticateExplanation')}}
            <a href="/login" aria-label="Accéder au portail d'authentification">{{$t('documentation.accessPortail')}}</a>
          </p>
          <q-responsive :ratio="16/9" class="video-container">
            <iframe
              src="https://opencast.dsi.uca.fr/paella/ui/embed.html?id=d866909a-1455-4e1c-ba32-c49713b7d711"
              allowfullscreen
              loading="lazy"
              :title="$t('documentation.authenticateUser')"
              aria-label="Tutoriel vidéo sur l'authentification d'un utilisateur externe"
            ></iframe>
          </q-responsive>
        </div>
      </div>
    </q-card-section>
  </q-card>
</template>

<script>
export default {
  name: "help"
}
</script>

<style scoped>
h2 {
  color: var(--q-primary);
  border-bottom: 2px solid var(--q-primary);
  padding-bottom: 0.5rem;
}

/* Container pour les vidéos */
.video-container {
  max-width: 720px;
  width: 100%;
}

/* Responsive pour petits écrans */
@media (max-width: 599px) {
  h1 {
    font-size: 1.5rem !important;
  }

  h2 {
    font-size: 1.25rem !important;
  }

  h3 {
    font-size: 1.1rem !important;
  }
}

ul {
  line-height: 1.8;
}
</style>
