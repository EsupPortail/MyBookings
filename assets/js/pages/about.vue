<template>
  <q-page padding class="column items-start q-pa-md">
    <div class="row items-center justify-between q-mb-md" style="width:100%">
      <div>
        <div class="text-h4 text-primary">MyBookings</div>
        <div class="text-subtitle2 text-grey">Solution de réservation et gestion de ressources</div>
      </div>
    </div>

    <q-card flat bordered class="q-pa-md">
      <q-card-section>
        <div class="text-h6 q-mb-sm">Résumé</div>
        <p>
          MyBookings fournit une solution logicielle complète pour la réservation, la gestion
          des accès et le suivi de parc matériel. Pensée pour les établissements d'enseignement supérieur et
          les organisations, elle combine une UX simple pour les utilisateurs finaux, une grande flexibilité pour les services et une
          architecture extensible pour les équipes IT.
        </p>
      </q-card-section>

      <q-separator />

      <q-card-section>
        <div class="row q-col-gutter-md">
          <div class="col-12 col-md-4">
            <q-card flat>
              <q-card-section>
                <div class="text-subtitle1">Étudiants & usagers</div>
                <ul class="text-caption">
                  <li>Réservation autonome d'espaces de travail collaboratif, salles ou box.</li>
                  <li>Accès automatique aux salles via contrôle d'accès.</li>
                  <li>Consultation claire des plannings, créneaux et disponibilités en temps réel.</li>
                  <li>Notifications automatiques : confirmation, rappel, informations d'accès.</li>
                </ul>
              </q-card-section>
            </q-card>
          </div>

          <div class="col-12 col-md-4">
            <q-card flat>
              <q-card-section>
                <div class="text-subtitle1">Administrateurs de service</div>
                <ul class="text-caption">
                  <li>Gestion de catalogues structurés en catégories et sous-catégories.</li>
                  <li>Création de workflows complets : confirmation, accès, restitution.</li>
                  <li>Définition de règles personnalisées (ex. capacité minimale, limitations horaires).</li>
                  <li>Mise à disposition fine : périodes, créneaux, groupes de visibilité, limitations par profil.</li>
                  <li>Modération des réservations lorsque le workflow le requiert.</li>
                  <li>Organisation multi-services avec droits distincts (administrateurs / modérateurs).</li>
                </ul>
              </q-card-section>
            </q-card>
          </div>

          <div class="col-12 col-md-4">
            <q-card flat>
              <q-card-section>
                <div class="text-subtitle1">Équipes techniques / DSI</div>
                <ul class="text-caption">
                  <li>Intégration native avec LDAP, CAS, Shibboleth et systèmes SSO existants.</li>
                  <li>Architecture API-first facilitant les interconnexions avec les SI.</li>
                  <li>Actionneurs pour piloter systèmes externes (ex. contrôle d'accès, inventaires).</li>
                  <li>Utilisation d'un conteneur de service séparé pour l'interaction avec les infrastructures locales.</li>
                  <li>Application pensée pour accepter de grands volumes.</li>
                  <li>Offre SaaS en hébergement dédié sur le data centre de l'UCA.</li>
                </ul>
              </q-card-section>
            </q-card>
          </div>
        </div>

      </q-card-section>

      <q-separator />

      <q-card-section>
      <div class="text-h6 q-mb-sm">Exemple d'utilisation</div>
        <ul class="text-caption">
          <li>Réservation de salles de travail et accès autonome avec la carte étudiant</li>
          <li>Réservation d'un véhicule de service pour des déplacements en mission</li>
          <li>Mutualisation de matériel scientifique d'un laboratoire avec d'autres entités internes ou externes à l'établissement</li>
          <li>Prêt de matériel informatique à des étudiants</li>
          <li>Réservation de salles de réunion</li>
          <li>Réservation d'un créneau pour une soutenance de thèses</li>
        </ul>
      </q-card-section>

      <q-separator />

      <q-card-section>
        <div class="text-h6 q-mb-sm">Fonctionnalités principales</div>

        <div class="q-gutter-sm">
          <q-chip flat outline label="Catalogue multi-type" class="q-mb-xs" />
          <q-chip flat outline label="Réservation (salles, véhicules, équipements)" class="q-mb-xs" />
          <q-chip flat outline label="Workflows & règles (capacité, durées, créneaux)" class="q-mb-xs" />
          <q-chip flat outline label="Actionneurs post-réservation (contrôle d'accès, notifications)" class="q-mb-xs" />
          <q-chip flat outline label="Gestion de parc & inventaire" class="q-mb-xs" />
          <q-chip flat outline label="Modalités d'authentification : LDAP / CAS / SSO" class="q-mb-xs" />
          <q-chip flat outline label="API REST" class="q-mb-xs" />
          <q-chip flat outline label="Modes multi-tenants et organisationnels" class="q-mb-xs" />

        </div>

      </q-card-section>

      <q-separator />

      <q-card-section>
        <div class="text-h6 q-mb-sm">Architecture & stack</div>

        <div class="row q-col-gutter-md">
          <div class="col-5">
            <q-card flat>
              <q-card-section>
                <div class="text-subtitle2">Architecture offre SaaS - Datacenter de l'UCA</div>
                <q-img src="/images/infra_saas_mybookings.png" alt="Schéma architecture offre Saas"/>
              </q-card-section>
            </q-card>
          </div>
          <div class="col-7">
            <q-card flat>
              <q-card-section>
                <div class="text-subtitle2">Stack technique applicative</div>
                <q-img src="/images/stack_mybookings.png" alt="Stack technique de l'application"/>
              </q-card-section>
            </q-card>
          </div>
        </div>

      </q-card-section>

      <q-separator />

      <q-card-section>
        <div class="text-h6 q-mb-sm">Pourquoi choisir MyBookings ?</div>
        <ul>
          <li>Solution prête à l'emploi, configurable et extensible.</li>
          <li>Approche API-first facilitant l'intégration dans un SI existant.</li>
          <li>Développé par une université pour les universités : souveraineté des données et maîtrise des coûts</li>
          <li>Maîtrise de l'accès au SI par les établissements (conteneur de service local)</li>
        </ul>

        <div class="q-mt-md row items-center">
          <q-btn label="Voir l'offre SaaS sur esup-portail" color="primary" class="q-mr-sm" href="https://www.esup-portail.org/offre-saas"/>
          <q-btn label="Voir le changelog" flat href="/changelog" />
        </div>
      </q-card-section>

    </q-card>
  </q-page>
</template>

<route type="json">
{
name: "about",
meta: {
public: true
}
}
</route>

<script setup>
</script>

<style scoped>
</style>