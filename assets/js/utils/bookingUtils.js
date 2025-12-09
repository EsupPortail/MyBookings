import {
    dateRangerOverlaps,
    dayToFullDay,
    getFirstDayOfWeek,
    isTimeSuperiorOrEqual,
    stringToDateTime
} from "./dateUtils";
import {date, Notify} from "quasar";
import {booking} from "../store/booking";
import {getActualParameterFromProvisions} from "./basketUtils";
import {user} from "../store/counter";
import {basket} from "../store/basket";
import {endBooking, getBookingRestrictionForUser} from "../api/Booking";


export function getNumbers(start,stop){
    return new Array(stop-start).fill(start).map((n,i)=>n+i);
}

export function countInSlot(dateStart, dateEnd, basketUserStart, maxBookingDuration) {
    let countBooking = 0;
    if(dateStart.getDate() === basketUserStart.getDate() && dateStart.getMonth() === basketUserStart.getMonth()) {
        let restTime = dateEnd-dateStart
        if(restTime > 0) {
            restTime = restTime/1000/60
        }
        countBooking = restTime/maxBookingDuration;
    }
    return countBooking;
}

export function countBetweenSlot(dateStart, dateEnd, basketUserStart, basketUserEnd, maxBookingDuration) {
    let countBooking = 0;
    if(dateStart >= basketUserStart && basketUserEnd >= dateEnd) {
        let restTime = dateEnd-dateStart
        if(restTime > 0) {
            restTime = restTime/1000/60
        }
        countBooking = restTime/maxBookingDuration;
    }
    return countBooking;
}

export function parseResourceFromCatalogue(catalogue, id) {
    let parsedResource = []
    catalogue.resource.forEach(function (resource) {
        if(resource.id == id) {
            parsedResource =  [resource];
        }
    });
    return parsedResource;
}

export function countBookedBookingPerDay(userTarget, selectionId, maxBookingDuration, basketStart, events) {
    let countBooking = 0;
    let basketUserStart = date.extractDate(basketStart, 'DD/MM/YYYY');
    events.forEach(function (event) {
        event.user.forEach(function (user) {
            if(((user.username === userTarget.username) || (userTarget.displayName === user.displayUserName)) && event.catalogueResource.id === selectionId) {
                let dateStart = new Date(event.dateStart);
                let dateEnd = new Date(event.dateEnd);
                countBooking += countInSlot(dateStart, dateEnd, basketUserStart, maxBookingDuration)
            }
        })
    })
    return countBooking;
}

export function countBookedBookingPerWeek(userTarget, selectionId, maxBookingDuration, basketStart, events) {
    let countBooking = 0;
    let basketUserStart = date.extractDate(basketStart, 'DD/MM/YYYY');
    basketUserStart = getFirstDayOfWeek(basketUserStart, 0);
    let basketUserEnd = getFirstDayOfWeek(basketUserStart, 1);
    events.forEach(function (event) {
        event.user.forEach(function (user) {
            if(((user.username === userTarget.username) || (userTarget.displayName === user.displayUserName)) && event.catalogueResource.id === selectionId) {
                let dateStart = new Date(event.dateStart);
                let dateEnd = new Date(event.dateEnd);
                countBooking += countBetweenSlot(dateStart, dateEnd, basketUserStart,basketUserEnd, maxBookingDuration)
            }
        })
    })
    return countBooking;
}

export function countBasketBookingPerDay(selectionId, maxBookingDuration, basketStart, basketCart, user = null) {
    let countBooking = 0;
    let basketUserStart = date.extractDate(basketStart, 'DD/MM/YYYY');
    basketCart.forEach(function (booking) {
        if(user === null) {
            if(booking.catalogue.id === selectionId) {
                countBooking += countInSlot(stringToDateTime(booking.dateStart), stringToDateTime(booking.dateEnd), basketUserStart, maxBookingDuration)
            }
        } else {
            const bookingUsers = JSON.parse(booking.users);
            bookingUsers.forEach(function (bookingUser) {
                if(user.displayName === bookingUser.displayName && booking.catalogue.id === selectionId) {
                    countBooking += countInSlot(stringToDateTime(booking.dateStart), stringToDateTime(booking.dateEnd), basketUserStart, maxBookingDuration);
                }
            });
        }

    });
    return countBooking;
}

export function countBasketBookingPerWeek(selectionId, maxBookingDuration, basketStart, basketCart, user) {
    let countBooking = 0;
    let basketUserStart = date.extractDate(basketStart, 'DD/MM/YYYY');
    basketUserStart = getFirstDayOfWeek(basketUserStart, 0);
    let basketUserEnd = getFirstDayOfWeek(basketUserStart, 1);
    basketCart.forEach(function (booking) {
        if(user === null) {
            if(booking.catalogue.id === selectionId) {
                countBooking += countBetweenSlot(stringToDateTime(booking.dateStart), stringToDateTime(booking.dateEnd), basketUserStart,basketUserEnd, maxBookingDuration)
            }
        } else {
            const bookingUsers = JSON.parse(booking.users);
            bookingUsers.forEach(function (bookingUser) {
                if(user.displayName === bookingUser.displayName && booking.catalogue.id === selectionId) {
                    countBooking += countBetweenSlot(stringToDateTime(booking.dateStart), stringToDateTime(booking.dateEnd), basketUserStart,basketUserEnd, maxBookingDuration);
                }
            });
        }

    });
    return countBooking;
}

export function getRestOfTime(n, type) {
    const bookingStore = booking();
    let hourPerDay = 60*24;
    const restOfMin =  Math.round((hourPerDay / ((60/bookingStore.configuration['interval']) * 24))*(bookingStore.configuration['minBooking']+n));
    if(type === 'hour') {
        const hour = Math.trunc(restOfMin / 60);
        return hour >= 10 ? hour : '0'+hour;
    } else {
        const min = 60 * ((restOfMin / 60) % 1);
        return min > 10 ? min : min + '0';
    }
}

export function checkMaxBookingPerWeek(userParam = null, after= false) {
    const basketUser = basket();
    const bookingStore = booking();
    const storeUser = user();
    let dateSelected =  date.extractDate(basketUser.start, 'DD/MM/YYYY');
    dateSelected.setHours(12);
    let maxBooking = getActualParameterFromProvisions(dateSelected, basketUser.selection.Provisions,'maxBookingByWeek');
    let selectedHours = basketUser.selectionNEnd-basketUser.selectionNStart;
    let comparison = maxBooking > selectedHours;
    if(after) {
        comparison = maxBooking >= selectedHours;
    }
    if(maxBooking > 0) {
        if(comparison) {
            let maxBookingDuration = getActualParameterFromProvisions(dateSelected, basketUser.selection.Provisions, 'maxBookingDuration');
            let numberOf = selectedHours;
            numberOf += countBookedBookingPerWeek(userParam === null ? storeUser : userParam, basketUser.selection.id, maxBookingDuration, basketUser.start, bookingStore.events);
            numberOf += countBasketBookingPerWeek(basketUser.selection.id, maxBookingDuration, basketUser.start, basketUser.cart, userParam);
            return numberOf <= maxBooking;
        } else {
            return false;
        }
    }
    return true;
}

export function checkMaxBookingPerDay(userParam = null, after = false) {
    const basketUser = basket();
    const bookingStore = booking();
    const storeUser = user();
    const dateNow = new Date();
    let dateSelected =  date.extractDate(basketUser.start, 'DD/MM/YYYY');
    dateSelected.setHours(dateNow.getHours());
    let maxBooking = getActualParameterFromProvisions(dateSelected, basketUser.selection.Provisions,'maxBookingByDay');
    let selectedHours = basketUser.selectionNEnd-basketUser.selectionNStart;
    let comparison = maxBooking > selectedHours;
    if(after) {
        comparison = maxBooking >= selectedHours;
    }
    if(maxBooking > 0) {
        if(comparison) {
            let maxBookingDuration = getActualParameterFromProvisions(dateSelected, basketUser.selection.Provisions, 'maxBookingDuration');
            let numberOf = selectedHours;
            numberOf += countBookedBookingPerDay(userParam === null ? storeUser : userParam, basketUser.selection.id, maxBookingDuration, basketUser.start, bookingStore.events);
            numberOf += countBasketBookingPerDay(basketUser.selection.id, maxBookingDuration, basketUser.start, basketUser.cart, userParam);
            return numberOf <= maxBooking;
        } else {
            return false;
        }
    }
    return true;
}

export function mergeBookings (n, i, mode) {
    const basketUser = basket();
    const storeUser = user();
    let selectedDate = basketUser.getStartDate
    let timeSelected = n.hour;
    let dateStart = date.extractDate(basketUser.start+' '+n.hour, 'DD/MM/YYYY HH:mm:ss');
    let dateEnd = date.addToDate(dateStart, {minutes: n.interval});
    let endTimeSelected = date.formatDate(dateEnd, 'HH:mm:ss');
    basketUser.selectionNStart = i;
    basketUser.selectionNEnd = i+1;
    const dateNow = new Date();
    if(dateStart > dateNow) {
        if((checkMaxBookingPerWeek() && checkMaxBookingPerDay()) || storeUser.isUserAdminSite(basketUser.selection.service.id)) {
            let startBooking = selectedDate+' '+timeSelected;
            let endBooking = selectedDate+' '+endTimeSelected;
            basketUser.cart.forEach(function (booking, index) {
                if(basketUser.selection.id === booking.catalogue.id) {
                    if(booking.resourceId === basketUser.resourceId || booking.resourceId === undefined) {
                        if(mode === 'before') {
                            basketUser.cart[index].dateStart = startBooking;
                            basketUser.selectionNStart = basketUser.selectionNStart-1;
                        } else {
                            basketUser.cart[index].dateEnd = endBooking;
                            basketUser.selectionNEnd = basketUser.selectionNEnd+1;
                        }
                    }
                }
            })
        } else {
            alert('Nombre de réservation maximum atteint pour cette ressource');
        }
    } else {
        alert('Impossible de faire une réservation sur ce créneau')
    }
}

export function getDateListFromEvents(events, date) {
    let listEvents = null;
    events.forEach((event) => {
        if(event.schedule[date]) {
            listEvents = event.schedule[date]
        }
    })

    return listEvents;
}

export function getStartEndBookingFromN(n, bookings, selectedDate, interval) {
    let localn = 0;
    let start = null;
    let end = null;
    Object.entries(bookings).forEach(([hour]) => {
        if(localn === n) {
            start = selectedDate + ' ' + hour;
        }

        if(localn === n+1) {
            end = selectedDate + ' ' + hour;
        }
        localn++;
    });

    if(end === null) {
        end = date.formatDate(date.addToDate(new Date(start), {minutes: interval}), 'YYYY-MM-DD HH:mm:ss');
    }
    return {
        startBooking: start,
        endBooking: end
    }
}

/**
 * Gère la fusion des réservations à partir du dialogue
 * @param {number} n - L'index de la réservation
 * @param {string} mode - Mode de fusion ('before' ou 'after')
 * @param {string} type - Type de modification ('start' ou 'end')
 * @returns {Promise<void>}
 */
export async function mergeBookingsFromDialog(n, mode, type) {
    const basketUser = basket();
    const bookingStore = booking();
    // Ajuster l'index si le mode est 'before'
    const adjustedIndex = mode === 'before' ? n - 1 : n;

    // Récupérer la date sélectionnée et la formater
    const start = date.extractDate(basketUser.start, 'DD/MM/YYYY');
    const formattedDate = date.formatDate(start, 'YYYY-MM-DD');

    // Récupérer les événements pour la date formatée
    const events = Object.values(bookingStore.events);
    const listEvents = getDateListFromEvents(events, formattedDate);
    // Si aucun événement n'est trouvé, afficher une notification et sortir
    if (listEvents === null) {
        showErrorNotification("Impossible d'étendre la réservation.");
        return;
    }

    const bookings = listEvents.bookings;
    const arrayOfBookings = Object.values(bookings);

    // Vérifier si l'index est valide
    if (adjustedIndex < 0 || adjustedIndex >= arrayOfBookings.length) {
        showErrorNotification("Impossible d'étendre plus la réservation");
        return;
    }

    // Obtenir les heures de début et de fin de réservation
    const selectedDate = basketUser.getStartDate;
    const { startBooking, endBooking } = getStartEndBookingFromN(adjustedIndex, bookings, selectedDate, listEvents.interval);


    // Traiter la modification selon le type et le mode
    await processBookingModification(adjustedIndex, mode, type, startBooking, endBooking, arrayOfBookings.length);
}

/**
 * Traite la modification de réservation selon le type et le mode
 * @param {number} n - Index ajusté
 * @param {string} mode - Mode de fusion
 * @param {string} type - Type de modification
 * @param {string} startBooking - Heure de début
 * @param {string} endBooking - Heure de fin
 * @param {number} maxBooking - Nombre maximum de créneaux de réservation
 * @returns {Promise<void>}
 */
async function processBookingModification(n, mode, type, startBooking, endBooking, maxBooking) {
    if (type === 'start') {
        await processStartModification(n, mode, startBooking, endBooking);
    } else {
        await processEndModification(n, mode, startBooking, endBooking, maxBooking);
    }
}

/**
 * Traite les modifications de l'heure de début
 */
async function processStartModification(n, mode, startBooking, endBooking) {
    if (mode === 'before') {
        const result = await checkEventDates(startBooking, basketUser.endBooking, n);
        if (result) {
            basketUser.startBooking = startBooking;
            if (n >= 0) {
                basketUser.selectionNStart = n;
            }
        }
    } else if (basketUser.selectionNStart + 1 !== basketUser.selectionNEnd) {
        basketUser.startBooking = endBooking;
        basketUser.selectionNStart = n + 1;
    }
}

/**
 * Traite les modifications de l'heure de fin
 */
async function processEndModification(n, mode, startBooking, endBooking, maxBooking)
{
    const basketUser = basket();
    if (mode === 'before') {
        if (basketUser.selectionNStart !== basketUser.selectionNEnd - 1) {
            basketUser.endBooking = startBooking;
            basketUser.selectionNEnd = n;
        }
    } else {
        const result = await checkEventDates(basketUser.startBooking, endBooking, n);
        if (result) {
            basketUser.endBooking = endBooking;
            if (n < maxBooking) {
                basketUser.selectionNEnd = n + 1;
            }
        }
    }
}

/**
 * Affiche une notification d'erreur
 * @param {string} message - Message d'erreur
 */
function showErrorNotification(message) {
    Notify.create({
        type: 'negative',
        message: message,
        position: 'top',
    });
}

export async function checkEventDates(dateStart, dateEnd, n, init= false) {
    const basketUser = basket();
    const storeUser = user();
    return getBookingRestrictionForUser('check', storeUser.username, basketUser.selection.id, basketUser.resourceId, dateStart, dateEnd, basketUser.users, basketUser.number)
    .then(() => {
        basketUser.bookingConditions.allowUsersToBookWithRules = true;
        let res = true;
        if(n !== null)
            res =  getBookingRest(n) < basketUser.selection.resource.length;

        return res;
    })
    .catch((error) => {
        let res = true;
        let message = "Merci de respecter les règles de réservation.";
        if(error.response.data.rules !== undefined) {
            let allowedByRules = true;
            Object.keys(error.response.data.rules).forEach(rule => {
                if(!rule.includes('indisponible'))
                    allowedByRules = false;
            })
            if(!allowedByRules)
                basketUser.rules = error.response.data.rules;

            basketUser.bookingConditions.allowUsersToBookWithRules = allowedByRules;
        } else {
            basketUser.bookingConditions.allowUsersToBookWithRules = true;
        }
        if(error.response.data.restrictions !== undefined) {
            res = false;
            if(error.response.data.restrictions instanceof Array) {
                error.response.data.restrictions.forEach(function (restriction) {
                    Notify.create({
                        type: 'negative',
                        message: restriction,
                        position: 'top',
                    });
                });
            } else {
                Notify.create({
                    type: 'negative',
                    message: error.response.data.restrictions,
                    position: 'top',
                });
            }

        }

        if(init) {
            Notify.create({
                type: 'negative',
                message: message,
                position: 'top',
            });
        }


        return res;

    });
}

export function checkEachResourcesFromBooking(idResource, event) {
    let eventHasSameResource = false;
    event.Resource.forEach(function (resource) {
        if(idResource === resource.id) {
            eventHasSameResource = true
        }
    })
    return eventHasSameResource;
}

function checkIfBookingDateCorrespond(dateStart, dateEnd, bookDateStart, bookDateEnd) {
    let isNotSame = 0;
    if (date.isBetweenDates(dateStart, bookDateStart, bookDateEnd, {inclusiveFrom: true})) {
        isNotSame = 1
    }
    if (date.isBetweenDates(dateEnd, bookDateStart, bookDateEnd, {inclusiveTo: true})) {
        isNotSame = 1
    }
    if (date.isBetweenDates(bookDateStart, dateStart, dateEnd, {inclusiveFrom: true})) {
        isNotSame = 1
    }
    return isNotSame;
}

export function getBookingRest(n, resourceId = null) {
    const basketUser = basket();
    if(n === undefined) {
        return 1;
    }
    return getInBookings(n, 'remaining', resourceId) === 0 ? 1 : (1-(getInBookings(n, 'remaining', resourceId) / basketUser.selection.resource.length));
}

export function getInBookings(n, seek, resourceId = null){
    const basketUser = basket();
    const bookingStore = booking();
    let start = date.extractDate(basketUser.start + ' ' + n.hour, 'DD/MM/YYYY HH:mm:ss');
    let end = date.addToDate(start, {minutes: n.interval});
    let remaining = basketUser.getSelection.resource.length;
    let users = [];
    let startDate = date.formatDate(start, 'YYYY-MM-DD');
    let startTime = date.formatDate(start, 'HH:mm:ss');

    // Utiliser le resourceId passé en paramètre, sinon fallback sur basketUser.resourceId
    const currentResourceId = resourceId !== null ? resourceId : basketUser.resourceId;

    bookingStore.events.forEach(function (element) {
        if(currentResourceId !== undefined && currentResourceId !== null) {
            if(element.id == currentResourceId) {
                if(element.schedule[startDate] !== undefined) {
                    remaining -= element.schedule[startDate]['bookings'][startTime];
                }
            }
        } else {
            if(element.id == basketUser.selection.id) {
                if(element.schedule[startDate] !== undefined)
                    remaining -= element.schedule[startDate]['bookings'][startTime];
            }
        }
    });

    basketUser.cart.forEach(function (element) {
        if(element.catalogue.id === basketUser.selection.id) {
            if(currentResourceId !== undefined && currentResourceId !== null) {
                if(currentResourceId === element.resourceId) {
                    let elementStart = date.extractDate(element.dateStart, 'YYYY-M-D HH:mm:ss');
                    let elementEnd = date.extractDate(element.dateEnd, 'YYYY-M-D HH:mm:ss');
                    if(dateRangerOverlaps(start, end, elementStart, elementEnd) === true) {
                        remaining = remaining-element.number;
                    }
                }
            } else {
                let elementStart = date.extractDate(element.dateStart, 'YYYY-M-D HH:mm:ss');
                let elementEnd = date.extractDate(element.dateEnd, 'YYYY-M-D HH:mm:ss');
                if(dateRangerOverlaps(start, end, elementStart, elementEnd) === true) {
                    remaining = remaining-element.number;
                }
            }
        }
    });
    if(seek === 'users') {
        return users
    }
    return remaining
}

export function getParamFromEvents(events, date, param) {
    let paramReturn = null;
    events.forEach(function (event) {
        if(event.schedule[date]) {
            paramReturn =  event.schedule[date][param];
        }
    });

    return paramReturn;
}