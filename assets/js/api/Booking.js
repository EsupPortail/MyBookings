import {Notify} from 'quasar';
import {buildUrlFromPagination} from "../utils/paginationUtils";
import {loadFromUrl} from "./Api";

export async function loadBookingsByResourceByStatus(id, status, pagination) {
    return loadFromUrl(buildUrlFromPagination('/api/bookings?Resource.id='+id+ (status ? '&status='+status : ''), pagination), "GET", "application/ld+json");
}

export async function loadBookingById(id) {
    const response = await loadFromUrl('/api/bookings/'+id, "GET", "application/json");
    return response.data;
}

export async function loadBookingsByCatalogByStatus(catalogId, status) {
    return loadFromUrl('/api/bookings?catalogueResource.service.id='+catalogId+'&status='+status, "GET", "application/json");
}

export async function loadBookingsByCatalogIdAndStatus(catalogId, pagination, status = null) {
    return loadFromUrl(buildUrlFromPagination('/api/bookings?catalogueResource.id='+catalogId + (status ? '&status='+status : ''), pagination), "GET", "application/ld+json");
}

export async function loadBookingsFromManyCatalogs(catalogs, startDate, endDate, routePath, idResource = null) {
    let route = 'countBookings';
    let catalogUrl= '';
    let resourceUrl = '';
    if(routePath === 'anonymous') {
        route = 'getAnonymousBookings';
    } else if(routePath === 'planning') {
        route = 'getBookings';
    }

    if(catalogs !== null) {
        catalogs.forEach(function(element) {
            catalogUrl+= '&catalogueResource.id[]='+element.id;
        });
    }

    if(idResource !== null) {
        resourceUrl = '&resource.id='+idResource;
    }

    return loadFromUrl('/api/'+route+'?status[]=pending&status[]=accepted&status[]=progress&dateStart='+startDate+'&dateEnd='+endDate+catalogUrl+resourceUrl, "GET", "application/json");
}

export async function loadBookingsFromUser(catalogs, startDate, endDate, username) {
    let catalogUrl= '';
    catalogs.forEach(function(element) {
        catalogUrl+= '&catalogueResource.id[]='+element.id;
    });
    return loadFromUrl('/api/getBookings?status=returned&user.username='+username+'&dateStart='+startDate+'&dateEnd='+endDate+catalogUrl, "GET", "application/json");
}

export async function loadMyBookings() {
    return loadFromUrl('/api/booking/me', "GET", "application/json");
}

export async function sendBookings(bodyFormData) {
    let axiosResponse = null;
    await loadFromUrl('/api/booking/new', "POST", "application/json", "multipart/form-data", bodyFormData).then(function (response) {
        axiosResponse = response.data;
        Notify.create({
            type: 'positive',
            message: 'La réservation a bien été enregistrée',
            position: 'top',
            timeout: 10000,
        })
    }).catch(function (error) {
        axiosResponse = error.response.data;
        Notify.create({
            type: 'negative',
            message: 'Un problème est survenu',
            position: 'top',
        })
    });
    return axiosResponse;
}

export async function loadBookingByUsername(username) {
    return loadFromUrl('/api/myBookings', "GET", "application/json");
}

export async function deleteBooking(id, admin = false) {
    let url = `/api/booking/${id}${admin ? "/admin" : ""}`;
    return loadFromUrl(url, "DELETE")
}

export async function refuseBooking(id, comment = "") {
    return loadFromUrl("/api/booking/"+id+"/refuse", "post", "application/json", "multipart/form-data", { comment });
}

export async function getBookingHistory(id) {
    return loadFromUrl("/api/booking/"+id+"/history", "get", "application/json");
}

export async function editBooking(id, body) {
    return loadFromUrl("/api/booking/"+id, "post", "application/json", "multipart/form-data", body);
}

export async function startBooking(id, body) {
    return loadFromUrl("/api/booking/"+id+"/start", "post", "application/json", "multipart/form-data", body);
}

export async function endBooking(id, body) {
    return loadFromUrl("/api/booking/"+id+"/end", "post", "application/json", "multipart/form-data", body);
}

export async function confirmBooking(id, body) {
    return loadFromUrl("/api/booking/"+id+"/confirm", "post", "application/json", "multipart/form-data", body);
}

export async function getBookingRestrictionForUser(route, username, catalogId, resourceId, dateStart, dateEnd, users, number)
{
    const paramNumber = number ? `&number=${number}` : "";

    let paramUsers = "";
    if (Array.isArray(users) && users.length) {
        paramUsers = users.map(user => `&users[]=${user.value}`).join("");
    }

    const resourceUrl = resourceId !== null ? `&resource=${resourceId}` : "";
    return loadFromUrl("/api/booking/new/"+username+"/"+route+"?catalog="+catalogId+"&startDate="+dateStart+"&endDate="+dateEnd+paramUsers+resourceUrl+paramNumber, "get", "application/json");
}