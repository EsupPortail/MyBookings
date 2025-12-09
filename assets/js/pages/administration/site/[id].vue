<template>
  <div>
    <nav role="navigation" aria-label="Navigation du site" class="site-navigation">
      <ul class="nav-list">
        <li class="nav-item">
          <router-link
            :to="{ name: 'serviceDetailAdmin' }"
            class="nav-link"
            :aria-label="$t('sites.siteDetail')"
          >
            {{ $t('sites.siteDetail') }}
          </router-link>
        </li>
        <li class="nav-item">
          <router-link
            :to="{ name: 'catalogueListAdmin' }"
            class="nav-link"
            :aria-label="$t('serviceCatalogues')"
          >
            {{ $t('serviceCatalogues') }}
          </router-link>
        </li>
        <li class="nav-item">
          <router-link
            :to="{ name: 'managePeriodsForService' }"
            class="nav-link"
            :aria-label="$t('sites.bookingPeriods')"
          >
            {{ $t('sites.bookingPeriods') }}
          </router-link>
        </li>
        <li class="nav-item">
          <router-link
            :to="{ name: 'workflowListAdmin' }"
            class="nav-link"
            :aria-label="$t('sites.workflows')"
          >
            {{ $t('sites.workflows') }}
          </router-link>
        </li>
        <li class="nav-item">
          <router-link
            :to="{ name: 'manageUsersFromService' }"
            class="nav-link"
            :aria-label="$t('sites.userManagement')"
          >
            {{ $t('sites.userManagement') }}
          </router-link>
        </li>
        <li class="nav-item">
          <router-link
            :to="{ name: 'manageBookingsFromService' }"
            class="nav-link"
            :aria-label="$t('sites.bookings')"
          >
            {{ $t('sites.bookings') }}
          </router-link>
        </li>
      </ul>
    </nav>
    <router-view />
  </div>
</template>

<route lang="json">
{
"meta": {
"requiresAuth": false,
"dynamic": true
}
}
</route>

<script>
export default {
  name: 'SiteAdministration'
}
</script>

<style scoped>
.site-navigation {
  margin-bottom: 5px;
  border-bottom: 2px solid #e0e0e0;
}

.nav-list {
  display: flex;
  list-style: none;
  margin: 0;
  padding: 0;
  justify-content: space-around;
}

.nav-item {
  flex: 1;
}

.nav-link {
  display: block;
  padding: 12px 16px;
  text-align: center;
  text-decoration: none;
  color: var(--q-primary, #1976d2);
  font-weight: 500;
  border-bottom: 2px solid transparent;
  transition: all 0.3s ease;
  position: relative;
  bottom: -2px;
}

.nav-link:hover {
  background-color: rgba(25, 118, 210, 0.08);
}

.nav-link:focus {
  outline: 2px solid var(--q-primary, #1976d2);
  outline-offset: -2px;
}

.nav-link.router-link-exact-active {
  color: var(--q-primary, #1976d2);
  border-bottom-color: var(--q-primary, #1976d2);
  font-weight: 600;
}

@media (prefers-contrast: more) {
  .nav-link {
    border: 1px solid currentColor;
  }

  .nav-link.router-link-exact-active {
    background-color: var(--q-primary, #1976d2);
    color: white;
  }
}
</style>

