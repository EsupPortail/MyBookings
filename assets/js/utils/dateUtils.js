import {date} from "quasar";

export function formatDateAPI(apiDate) {
    let newDate = stringToDateTime(apiDate);
    return newDate.toLocaleDateString()+' '+newDate.toLocaleTimeString();
}

export function shortFormatDateApi(apiDate) {
    let newDate = date.extractDate(apiDate, 'YYYY-M-D HH:mm:ss');
    return date.formatDate(newDate, 'DD/MM/YYYY HH:mm');
}

export function formatDatefromAPItoString(apiDate) {
    let newDate = new Date(apiDate);
    return newDate.toLocaleDateString()+' '+newDate.toLocaleTimeString();
}

export function shortFormatDatefromAPItoString(apiDate) {
    let newDate = new Date(apiDate);
    return newDate.toLocaleDateString()+' '+newDate.getHours()+ ':' + (newDate.getMinutes() < 10 ? '0' + newDate.getMinutes() : newDate.getMinutes());
}

export function dateRangerOverlaps(a_start, a_end, b_start, b_end) {
    if (a_start < b_start && b_start < a_end) return true; // b starts in a
    if (a_start < b_end && b_end < a_end) return true; // b ends in a
    if (a_start <= b_start && b_end <= a_end) return true; // b in a
    if (a_start >= b_start && b_end >= a_end) return true; // a in b
    return false;
}

export function dateRangerOverlapsEqual(a_start, a_end, b_start, b_end) {
    if (date.isSameDate(a_start, b_start) && date.isSameDate(a_end,b_end)) return 3; // a is b
    if (a_start < b_start && b_end < a_end) return 4; // b in a
    if (a_start <= b_start && date.isSameDate(a_end,b_end)) return 2; // b in a end equal
    if (date.isSameDate(a_start, b_start) && b_end <= a_end) return 1; // b equal in a
    return 0;
}

export function getDayMonthYear(date) {
    const month = (date.getMonth() + 1).toString().padStart(2, '0'); // Months are zero-indexed, so add 1
    const day = date.getDate().toString().padStart(2, '0');
    const year = date.getFullYear();
    return day+'/'+month+'/'+year;
}

export function dayToFullDay(day) {
    switch (day) {
        case 0:
            return 'sunday';
        case 1:
            return 'monday';
        case 2:
            return 'tuesday';
        case 3:
            return 'wednesday';
        case 4:
            return 'thursday';
        case 5:
            return 'friday';
        case 6:
            return 'saturday';
    }
}

//stringToDate used to support safari date
export function stringToDate(selectedDate) {
    let selected = date.extractDate(selectedDate, 'YYYY-M-DD');
    if(selectedDate.length === 8) {
        selected = date.extractDate(selectedDate, 'YYYY-M-D');
    } else if (selectedDate.length === 9) {
        let dateSplit = selectedDate.split('-');
        if(dateSplit[1].length === 2) {
            selected = date.extractDate(selectedDate, 'YYYY-MM-D');
        }
    }
    return selected;
}

export function checkDateFormat(val) {
    const regexDate = /^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/\d{4}$/;
    return (regexDate.test(val));
}

export function checkDateTimeFormat(val) {
    const regexDate = /^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/\d{4} ([01]\d|2[0-3]):[0-5]\d$/;
    return (regexDate.test(val));
}

export function stringToDateTime(selectedDate) {
    let selected = date.extractDate(selectedDate, 'YYYY-M-DD HH:mm');
    if(selectedDate.length === 14) {
        selected = date.extractDate(selectedDate, 'YYYY-M-D HH:mm');
    } else if (selectedDate.length === 15) {
        let dateSplit = selectedDate.split('-');
        if(dateSplit[1].length === 2) {
            selected = date.extractDate(selectedDate, 'YYYY-MM-D HH:mm');
        }
    }
    return selected;
}

export function dateSameDay(dateStart, dateEnd, type = "dateTime") {
    if(type === "dateTime") {
        dateStart = date.extractDate(dateStart, 'YYYY-M-D HH:mm:ss');
        dateEnd = date.extractDate(dateEnd, 'YYYY-M-D HH:mm:ss');

    } else {
        dateStart = new Date(dateStart);
        dateEnd = new Date(dateEnd);
    }

    if(dateStart.getUTCDate() === dateEnd.getUTCDate()) {
        return dateEnd.getHours()+ ':' + (dateEnd.getMinutes() < 10 ? '0' + dateEnd.getMinutes() : dateEnd.getMinutes());
    } else {
        return date.formatDate(dateEnd, 'DD/MM/YYYY HH:mm');
    }
}

export function dateStartEndCorrect(dateStart, dateEnd) {
    dateStart = date.extractDate(dateStart, 'YYYY-M-D HH:mm:ss');
    dateEnd = date.extractDate(dateEnd, 'YYYY-M-D HH:mm:ss');
    return dateStart < dateEnd;
}

export function getFirstDayOfWeek (date, $endOrStart) {
    // Cloner la date pour ne pas la modifier
    const clonedDate = new Date(date);
    // Récupérer le jour de la semaine (0 pour dimanche, 1 pour lundi, etc.)
    const day = clonedDate.getDay();
    // Calculer le décalage (décalage par rapport au lundi)
    let diff = clonedDate.getDate() + (7 - day) % 7; // Si dimanche, reculer jusqu'à lundi
    if($endOrStart === 0) {
        diff = clonedDate.getDate() - day + (day === 0 ? -6 : 1); // Si dimanche, reculer jusqu'à lundi
    }

    // Définir la date au premier jour de la semaine
    clonedDate.setDate(diff);
    return clonedDate;
}

export function isTimeSuperiorOrEqual(date1, date2) {
    // Extraire les heures, minutes, secondes et millisecondes des deux dates
    const time1 = date1.getHours() * 3600000 + date1.getMinutes() * 60000 + date1.getSeconds() * 1000 + date1.getMilliseconds();
    const time2 = date2.getHours() * 3600000 + date2.getMinutes() * 60000 + date2.getSeconds() * 1000 + date2.getMilliseconds();

    // Comparer les deux temps
    return time1 >= time2;
}

// Méthode pour convertir DD/MM/YYYY vers format serveur sans décalage timezone
export function convertDateForServer(dateValue) {
    if (!dateValue) return null;

    // Si c'est déjà au format ISO YYYY-MM-DD, on le retourne tel quel
    if (typeof dateValue === 'string' && dateValue.match(/^\d{4}-\d{2}-\d{2}$/)) {
        return dateValue;
    }

    // Si c'est au format DD/MM/YYYY (format d'affichage)
    if (typeof dateValue === 'string' && dateValue.match(/^\d{2}\/\d{2}\/\d{4}$/)) {
        const [day, month, year] = dateValue.split('/');
        // Formatage direct sans passer par un objet Date pour éviter les problèmes de timezone
        return `${year}-${month.padStart(2, '0')}-${day.padStart(2, '0')}`;
    }

    // Si c'est un objet Date
    if (dateValue instanceof Date && !isNaN(dateValue.getTime())) {
        const year = dateValue.getFullYear();
        const month = (dateValue.getMonth() + 1).toString().padStart(2, '0');
        const day = dateValue.getDate().toString().padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    // Si c'est une string représentant une date ISO ou autre format
    if (typeof dateValue === 'string') {
        try {
            const dateObj = new Date(dateValue);
            if (!isNaN(dateObj.getTime())) {
                const year = dateObj.getFullYear();
                const month = (dateObj.getMonth() + 1).toString().padStart(2, '0');
                const day = dateObj.getDate().toString().padStart(2, '0');
                return `${year}-${month}-${day}`;
            }
        } catch (error) {
            console.warn('Impossible de parser la date:', dateValue);
            return null;
        }
    }

    return null;
}

// Méthode pour convertir les heures HH:mm vers format serveur sans décalage timezone
export function convertTimeForServer(timeString) {
    if (!timeString) return null;

    // Si c'est déjà au format HH:mm, on le retourne tel quel
    if (typeof timeString === 'string' && timeString.match(/^\d{2}:\d{2}$/)) {
        return timeString;
    }

    // Si c'est un objet Date, on extrait juste l'heure et les minutes
    if (timeString instanceof Date) {
        const hours = timeString.getHours().toString().padStart(2, '0');
        const minutes = timeString.getMinutes().toString().padStart(2, '0');
        return `${hours}:${minutes}`;
    }

    return timeString;
}
