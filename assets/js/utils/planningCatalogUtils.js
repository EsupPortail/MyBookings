import { date } from 'quasar';
import {getCatalogueTitleResourcesById} from "../api/CatalogRessource";
import { loadBookingsFromManyCatalogs } from "../api/Booking";

export async function loadResourcesAsync(id, anonymous = false) {
  let thisMorning = new Date();
  thisMorning.setHours(0, 0, 0, 0);
  
  let thisEvening = new Date();
  thisEvening.setHours(23, 59, 59, 999);

  let now = new Date();
  
  // Fetching data about resources
  const catalog = await getCatalogueTitleResourcesById(id);
  // Fetching bookings for today
  let pathUrl = anonymous ? 'anonymous' : 'planning'; // Determine the route path based on anonymity
  thisMorning = date.formatDate(thisMorning, 'YYYY-MM-DDTHH:mm:ss');
  thisEvening = date.formatDate(thisEvening, 'YYYY-MM-DDTHH:mm:ss');

  const bookings = await loadBookingsFromManyCatalogs([catalog], thisMorning, thisEvening, pathUrl);

  let catalogResources = catalog.resource;
  let resources = [];

  catalogResources.forEach(resource => {
    let currentBookings = bookings.data
      .filter(booking =>
        booking.Resource.some(bookingResource => bookingResource.id === resource.id)
      )
      .sort((a, b) => new Date(a.dateStart) - new Date(b.dateStart));

    currentBookings.forEach(currentBooking => {
      let dateStart = new Date(currentBooking.dateStart);
      let dateEnd = new Date(currentBooking.dateEnd);

      if (date.isBetweenDates(now, dateStart, dateEnd)) {
        resource['currentBooking'] = {
          ...currentBooking,
          dateStart: date.formatDate(currentBooking.dateStart, 'HH:mm'),
          dateEnd: date.formatDate(currentBooking.dateEnd, 'HH:mm')
        };
      } else if (now < dateStart && (!resource['upcomingBooking'] || new Date(resource['upcomingBooking']['dateStart']) > dateStart)) {
        resource['upcomingBooking'] = {
          ...currentBooking,
          dateStart: date.formatDate(currentBooking.dateStart, 'HH:mm'),
          dateEnd: date.formatDate(currentBooking.dateEnd, 'HH:mm')
        };
      }
    });

    resource.customFieldResources.forEach(customFieldResource => {
      if(customFieldResource.CustomField.labelGeneral == "Capacité") {
        resource.capacity = customFieldResource.Value;
      }
    });

    resources.push(resource);
  });


  return {
    title: catalog.title,
    image: catalog.image,
    resources: resources
  }
}

export function areResourcesIdentical(resource1, resource2) {
  if(resource1.id !== resource2.id) return false;
  if(resource1.title !== resource2.title) return false;
  if(resource1.capacity !== resource2.capacity) return false;
  
  if(compareBookings(resource1.currentBooking, resource2.currentBooking) === false) return false;
  if(compareBookings(resource1.upcomingBooking, resource2.upcomingBooking) === false) return false;

  return true;
}

function compareBookings(booking1, booking2){
  if(booking1 !== booking2) {
    // Si un seul des deux a du contenu :
    if(booking1 === undefined & booking2 !== undefined) return false;
    if(booking2 === undefined & booking1 !== undefined) return false;

    // Ici on doit comparer le contenu
    if(booking1.dateStart !== booking2.dateStart) return false;
    if(booking1.dateEnd !== booking2.dateEnd) return false;
    
    // Si aucun des deux n'est undefined, on doit en comparer le contenu
    if(booking1.user !== undefined && booking2.user !== undefined) {
      if(booking1.user.length !== booking2.user.length) return false;
      for(let i = 0; i < booking1.user.length; i++) {
        if(booking1.user[i].username !== booking2.user[i].username) return false;
      }
    } else {
      // Là il faut vérifier que les DEUX sont undefined 
      if(booking1.user !== booking2.user) return false;
    }
  }

  return true;
}


// Nouvelle méthode pour traiter les groupes de périodes
export function processPeriodBracket(periodBracket, blockingDate) {
  let self = this;
  let today = new Date();
  today.setHours(0, 0, 0, 0);
  let coveredDays = new Set();
  // Pour chaque jour de la semaine, on collecte toutes les plages d'ouverture
  let dayOpenings = {};
  for (let i = 0; i < 7; i++) {
    dayOpenings[i] = [];
  }
  periodBracket.periods.forEach(function (period) {
    if (period.type === 'open') {
      let timeStart = new Date(period.timeStart);
      let timeEnd = new Date(period.timeEnd);
      let start = timeStart.getHours() * 60 + timeStart.getMinutes();
      let end = timeEnd.getHours() * 60 + timeEnd.getMinutes();
      let days = period.day || [];
      let schedulerDays = days.map(day => parseInt(day)).filter(day => !isNaN(day));
      schedulerDays.forEach(day => {
        coveredDays.add(day);
        dayOpenings[day].push([start, end]);
      });
    } else if (period.type === 'close') {
      if (period.dateStart && period.dateEnd && period.timeStart && period.timeEnd) {
        let dateStart = new Date(period.dateStart);
        let dateEnd = new Date(period.dateEnd);
        let timeStart = new Date(period.timeStart);
        let timeEnd = new Date(period.timeEnd);
        let start = timeStart.getHours() * 60 + timeStart.getMinutes();
        let end = timeEnd.getHours() * 60 + timeEnd.getMinutes();
        blockingDate.push({
          start_date: dateStart,
          end_date: dateEnd,
          zones: [start, end],
          invert_zones: false,
          type: "dhx_time_block",
          css: "red_section"
        });
      } else if (period.dateStart && period.dateEnd) {
        let dateStart = new Date(period.dateStart);
        let dateEnd = new Date(period.dateEnd);
        if (dateEnd > today) {
          self.blockingDate.push({
            start_date: dateStart,
            end_date: dateEnd,
            zones: 'fullday',
            type: "dhx_time_block",
            css: "red_section"
          });
        }
      }
    }
  });
  // Pour chaque jour, calculer les zones de blocage (inverses des ouvertures)
  for (let day = 0; day < 7; day++) {
    if (dayOpenings[day].length > 0) {
      // Fusionner et trier les plages d'ouverture
      let openings = dayOpenings[day].sort((a, b) => a[0] - b[0]);
      let zones = [];
      let lastEnd = 0;
      openings.forEach(([start, end]) => {
        if (start > lastEnd) {
          zones.push(lastEnd, start);
        }
        lastEnd = Math.max(lastEnd, end);
      });
      if (lastEnd < 24 * 60) {
        zones.push(lastEnd, 24 * 60);
      }
      blockingDate.push({
        days: [day],
        zones: zones,
        invert_zones: false,
        type: "dhx_time_block",
        css: "blue_section"
      });
    }
  }
  return {'coveredDays': coveredDays, 'blockingDate': blockingDate};
}


// Méthode pour traiter l'ancien système (code existant légèrement modifié)
export function processLegacyPlanning(element, blockingDate) {
  let self = this;
  let today = new Date();
  today.setHours(0);
  today.setMinutes(0);
  today.setSeconds(0);
  today.setMilliseconds(0);
  let dateEnd = new Date(element.dateEnd);

  let coveredDays = new Set(); // Set local pour cette méthode

  if (dateEnd > today) {
    let days = [];
    let minBookingTime = new Date(element.minBookingTime);
    let maxBookingTime = new Date(element.maxBookingTime);

    element.days.forEach(function (day) {
      //On ajoute le jour seulement s'il est avant la date de fin
      let numDay = dayOfWeek(day);
      let last = today.getDate() - today.getDay() + 1;
      if (numDay === 0) {
        last += 6;
      } else {
        last += numDay - 1;
      }
      let dateDay = new Date(today.setDate(last));
      if (dateDay <= dateEnd) {
        days.push(numDay);
        // Ajouter le jour couvert au Set local
        coveredDays.add(numDay);
      }
    });

    if (days.length > 0) {
      blockingDate.push({
        days: days,
        zones: [maxBookingTime.getHours() * 60, 24 * 60, 0, minBookingTime.getHours() * 60],
        type: "dhx_time_block",
        css: "blue_section"
      });
    }
  }

  return {'coveredDays': coveredDays, 'blockingDate': blockingDate}; // Retourner le Set des jours couverts
}


function dayOfWeek(day) {
  switch (day) {
    case 'monday':
      return 1;
    case 'tuesday':
      return 2;
    case 'wednesday':
      return 3;
    case 'thursday':
      return 4;
    case 'friday':
      return 5
    case 'saturday':
      return 6;
    case 'sunday':
      return 0;
    default:
      return 0;
  }
}