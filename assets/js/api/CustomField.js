import {Notify} from "quasar";
import {loadFromUrl} from "./Api";

export async function postCustomField(data) {
    return loadFromUrl('/api/custom_fields', 'POST', 'application/json', 'application/json', data).then(function (response) {
        Notify.create({
            type: 'positive',
            message: 'La requête a été créée !',
            position: 'top',
        })
    })
}

export async function updateCustomField(customField) {
    return loadFromUrl('/api/custom_fields/' + customField.id, "PATCH", "application/json", "application/merge-patch+json", customField);
}

export async function removeCustomField(id) {
    const response = await loadFromUrl('/api/custom_fields/' + id, 'DELETE').then(function (response) {
        Notify.create({
            type: 'positive',
            message: "L'option a été supprimée",
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
