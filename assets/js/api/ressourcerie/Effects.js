import axios from "axios";
import { Notify } from 'quasar'
import {buildUrlFromPagination} from "../../utils/paginationUtils";

export async function bookEffects(resourceIds, targetId) {
    let axiosResponse = null;
    await axios({
        method: "post",
        url: "/api/effects",
        data: {resourceIds, targetId},
        headers: { "Content-Type": "multipart/form-data" },
    }).then(function (response) {
        axiosResponse = response.data;
        Notify.create({
            type: 'positive',
            message: 'Le bien a bien été enregistrée',
            position: 'top',
        })
    }).catch(function (error) {
        axiosResponse = error.response.data;
        Notify.create({
            type: 'negative',
            message: 'Un problème est survenu pour la réservation de ce bien',
            position: 'top',
        })
    });

    return axiosResponse;
}

export async function getBookingEffects(targetId, status, pagination) {
    let statusQuery = '';
    if (status) {
        if (typeof status === 'string') {
            statusQuery = '&status=' + status;
        }
        else if (Array.isArray(status)) {
            status.forEach(element => {
               statusQuery += '&status[]=' + element;
            });
        }
    }
    let targetIdQuery = targetId ? '&targetId='+targetId : '';
    return axios({
        method: "get",
        url: buildUrlFromPagination("/api/effectBookings?catalogueResource.service.type=Ressourcerie"+targetIdQuery + statusQuery, pagination),
        headers: {
            'accept': 'application/ld+json'
        },
    });
}

export async function confirmBookingEffects(id, body) {
    let axiosResponse = null;
    await axios({
        method: "post",
        url: "/api/effects/" + id + "/confirm",
        data: body,
        headers: {"Content-Type": "multipart/form-data"},
    }).then(function (response) {
        axiosResponse = response.data;
        Notify.create({
            type: 'positive',
            message: 'La demande a bien été confirmée',
            position: 'top',
        });
    }).catch(function (error) {
        axiosResponse = error.response.data;
        Notify.create({
            type: 'negative',
            message: 'Un problème est survenu lors de la confirmation de la demande',
            position: 'top',
        });
    });

    return axiosResponse;
}

export async function closeBookingEffects(id) {
    let axiosResponse = null;
    await axios({
        method: "post",
        url: "/api/effects/" + id + "/close",
    }).then(function (response) {
        axiosResponse = response.data;
        Notify.create({
            type: 'positive',
            message: 'La demande a bien été clôturée',
            position: 'top',
        });
    }).catch(function (error) {
        axiosResponse = error.response.data;
        Notify.create({
            type: 'negative',
            message: 'Un problème est survenu lors de la clôture de la demande',
            position: 'top',
        });
    });

    return axiosResponse;
}

export async function refuseBookingEffects(id) {
    let axiosResponse = null;
    await axios({
        method: "post",
        url: "/api/effects/" + id + "/refuse",
    }).then(function (response) {
        axiosResponse = response.data;
        Notify.create({
            type: 'positive',
            message: 'La demande a bien été refusée',
            position: 'top',
        });
    }).catch(function (error) {
        axiosResponse = error.response.data;
        Notify.create({
            type: 'negative',
            message: 'Un problème est survenu lors du refus de la demande',
            position: 'top',
        });
    });

    return axiosResponse;
}

export async function cancelBookingEffects(id) {
    let axiosResponse = null;
    await axios({
        method: "post",
        url: "/api/effects/" + id + "/cancel",
    }).then(function (response) {
        axiosResponse = response.data;
        Notify.create({
            type: 'positive',
            message: 'La demande a bien été annulée',
            position: 'top',
        });
    }).catch(function (error) {
        axiosResponse = error.response.data;
        Notify.create({
            type: 'negative',
            message: 'Un problème est survenu lors de l\'annulation de la demande',
            position: 'top',
        });
    });

    return axiosResponse;
}

export async function getEffects(status = null, serviceId = null) {
    let statusQuery = status ? '?status=' + status : '';
    let serviceIdQuery = serviceId ? (status ? '&service.id=' : '?service.id=') + serviceId : '';
    return axios({
        method: "get",
        url: "/api/effects" + statusQuery + serviceIdQuery,
        headers: {
            'accept': 'application/ld+json'
        },
    });
}