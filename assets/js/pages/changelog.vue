<template>
  <q-card>
    <q-card-section class="text-center">
      <div class="text-h2">Mises à jour</div>
    </q-card-section>

    <q-card-section>
      <div class="q-ma-md">
        <q-list bordered class="rounded-borders listUpdates" v-for="update in updates">
          <q-expansion-item
              :icon="update['icon']"
              :label="update['date']+' - '+update['title']"
          >
            <q-card>
              <q-card-section>
                {{update['content']}}
              </q-card-section>
            </q-card>
          </q-expansion-item>
        </q-list>
      </div>
    </q-card-section>
  </q-card>
</template>

<route type="json">
{
  name: "changelog",
  meta: {
    public: true
  }
}
</route>

<script>
import jsonUpdates from '../data/updates.json';
export default {
  name: "changeLog",
  data() {
    return {
      updates: jsonUpdates
    }
  }

}
</script>

<style>
  .listUpdates {
    margin: auto;
  }

  @media (min-width: 1000px) {
    .listUpdates {
      max-width: 60%;
    }
  }
</style>