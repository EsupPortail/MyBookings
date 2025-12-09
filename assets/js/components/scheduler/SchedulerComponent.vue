<script>
import { scheduler } from "dhtmlx-scheduler";
import ical from 'ical';
import { colors } from 'quasar'
const { getPaletteColor, changeAlpha } = colors


const getColor = function (i) {
  let colors = [
    getPaletteColor("primary"),
    getPaletteColor("secondary"),
    getPaletteColor("positive"),
    getPaletteColor("info"),
    getPaletteColor("dark"),
    getPaletteColor("warning"),
    getPaletteColor("negative"),
    "indigo",
    "purple",
    "brown",
    "deep-orange",
    "blue-grey",
    "cyan",
    "amber",
    "lime",
    "teal",
  ]
  let color = colors[i];
  if(i >= colors.length) {
    i = i % colors.length;
    color = changeAlpha(colors[i], -0.1);
  }
  return color;
}
export default {
  name: "SchedulerComponent",
  data() {
    return {
      lastLoadedUrl: null,
      lastLoadTime: 0
    }
  },
  props: {
    idResource: Number,
    events: {
      type: Array,
      default() {
        return []
      },
    },
    block: {
      type: Array,
      default() {
        return []
      },
    },
    catalogue: String,
    site: String|Number,
    mode: {
      type: String,
      default: "week",
    }
  },
  mounted: function () {
    scheduler.clearAll();
    scheduler.skin = "material";

    scheduler.config.header = [
      "day",
      "week",
      "month",
      "prev",
      "date",
      "next"
    ]

    scheduler.templates.event_text = function(start, end, ev) {
      let body = "<b>";
      if(ev.text !== 'Réservation') {
        body += "<i>"+ev.text+"</i><br/>";
      }
      if(ev.organizer) {
        body += ev.organizer;
      }
      body += "</b>";
      return body;
    };

    scheduler.templates.tooltip_text = function(start,end,ev) {
      let body = "<b>";
      if(ev.text) {
        body += "Titre de la réservation : <i>"+ev.text+"</i><br/>";
      }
      if(ev.organizer) {
        body += "Organisateur : " + ev.organizer + "<br/>";
      }
      body += "Ressource : " + ev.location;
      body += "</b>";
      return body;
    };
    scheduler.config.first_hour = 7;
    scheduler.config.last_hour = 22;
    scheduler.config.start_on_monday = true;
    scheduler.i18n.setLocale("fr");
    scheduler.config.readonly = true;
    scheduler.plugins({
      limit: true,
      tooltip: true,
      all_timed: true,
    })

    scheduler.config.all_timed=true;
    scheduler.deleteMarkedTimespan();
    this.block.forEach(function (blockDate) {
      scheduler.addMarkedTimespan(blockDate);
    })
    this.init();
    this.loadCalendar();
    scheduler.parse(this.$props.events);
  },
  unmounted() {
    scheduler.clearAll();
  },
  computed: {
    getIdResource: function () {
      return this.idResource;
    }
  },
  methods: {
    init() {
      scheduler.init(
          this.$refs.SchedulerComponent,
          new Date(),
          this.mode
      );
    },
    loadCalendar() {
     if(this.idResource === undefined) {
      this.loadEvents("/generate/site/"+this.site+"/catalogue/"+this.catalogue+".ics");
     } else {
      this.loadEvents("/generate/site/"+this.site+"/resource/"+this.idResource+".ics");
     }
    },


    loadEvents(url) {
      let arrayColor = [];
      // Vérifier si nous avons déjà chargé cette URL récemment
      if (this.lastLoadedUrl === url && Date.now() - this.lastLoadTime < 1000) {
        return; // Éviter les doubles chargements
      }

      this.lastLoadedUrl = url;
      this.lastLoadTime = Date.now();

      fetch(url)
        .then(response => response.text())
        .then(data => {

          const jcalData = ical.parseICS(data);
          console.log("jcalData", jcalData);
          const events = [];

          Object.keys(jcalData).forEach(function (event) {
            if (!jcalData[event].start || !jcalData[event].end) return;

            let organizer = null;
            if (jcalData[event].organizer) {
              organizer = jcalData[event].organizer.params?.CN;
            }
            const summary = jcalData[event].summary;
            const start = jcalData[event].start;
            const end = jcalData[event].end;
            const location = jcalData[event].location || "Inconnu";

            if (arrayColor[location] === undefined) {
              arrayColor[location] = getColor(Object.keys(arrayColor).length);
            }

            events.push({
              start_date: new Date(start),
              end_date: new Date(end),
              location,
              text: summary,
              color: arrayColor[location],
              organizer
            });
          });

          // Je fais une intervalle pour rajouter les événements dans la liste un par un
          let index = 0;
          const interval = setInterval(() => {
            if (index >= events.length) {
              clearInterval(interval);
              return;
            }
            scheduler.addEvent(events[index]);
            index++;
          }, 5); // 10 ms ça rendait bien
        });
      }
  },
  watch: {
    getIdResource: function () {
      scheduler.clearAll();
      this.init();
      this.loadCalendar();
    }
  }
}
</script>

<style lang="css">
@import "dhtmlx-scheduler/codebase/dhtmlxscheduler_material.css";
</style>

<template>
  <div ref="SchedulerComponent" style="width:100%; height:100%;"></div>
</template>
