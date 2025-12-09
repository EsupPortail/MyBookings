import { Notify } from "quasar";
import {loadFromUrl} from "./Api";

export async function removeLocalization(id) {
    const response = await loadFromUrl('/api/localizations/' + id, 'DELETE').then(function (response) {
        Notify.create({
            type: 'positive',
            message: 'La localisation a été supprimée !',
            position: 'top',
        })
        return true
    }).catch(function (error) {
        Notify.create({
            type: 'negative',
            message: "Erreur : " + error,
            position: 'top',
        })
    })

    return response === true;
}

export async function getLocalization(id) {
    return loadFromUrl('/api/localizations/' + id, 'GET', 'application/json');
}

export async function getParentLocalizations() {
    return loadFromUrl('/api/localizations?exists[parent]=false', 'GET', 'application/json');
}

export async function updateLocalization(localization) {
    if(localization.parent !== null && localization.parent.id !== undefined) {
        localization.parent = '/api/localizations/'+localization.parent.id;
    }
    return loadFromUrl('/api/localizations/' + localization.id, "PATCH", "application/json", "application/merge-patch+json", localization);
}

export async function createLocalization(body) {
    const response = await loadFromUrl('/api/localizations', 'POST', 'application/json', 'application/json', body).then(function (response) {
        Notify.create({
            type: 'positive',
            message: 'La localisation a été crée !',
            position: 'top',
        })
        return true
    }).catch(function (error) {
        Notify.create({
            type: 'negative',
            message: "Erreur : " + error,
            position: 'top',
        })
    })

    return response.data;
}