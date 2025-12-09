import {Notify} from "quasar";
import {loadFromUrl} from "./Api";

export async function postResource(name, catalog, informations = null, inventoryNumber = null) {
    let bodyFormData = new FormData();
    bodyFormData.append('name', name);
    bodyFormData.append('informations', informations);
    bodyFormData.append('inventoryNumber', inventoryNumber);
    bodyFormData.append('catalogue', catalog);
    let responseData = null;
    await loadFromUrl("/api/resource", "POST", "application/json", "multipart/form-data", bodyFormData).then(function (response) {
        responseData = response.data;
        Notify.create({
            type: 'positive',
            message: 'Une ressource a été ajoutée',
            position: 'top',
        })
    })

    return responseData;
}

export async function editResource(name, resource, informations = null, inventoryNumber = null, image = null, options= null,editedCustomFields=null, actuatorProfile = null) {
    let bodyFormData = new FormData();
    bodyFormData.append('name', name);
    bodyFormData.append('informations', informations);
    bodyFormData.append('inventoryNumber', inventoryNumber);
    bodyFormData.append('image', image);
    bodyFormData.append('options', options);
    bodyFormData.append('editedCustomFields', editedCustomFields);
    bodyFormData.append('actuator_profile', actuatorProfile);
    let responseData = null
    await loadFromUrl("/api/resource/"+resource, "POST", "application/json", "multipart/form-data", bodyFormData).then(function (response) {
        responseData = response.data;
        Notify.create({
            type: 'positive',
            message: 'Une ressource a été modifié',
            position: 'top',
        })
    })

    return responseData;
}

export async function deleteResource(id) {
    return loadFromUrl("/api/resource/"+id, "DELETE", "application/json").then(function () {
        Notify.create({
            type: 'positive',
            message: 'La ressource a été supprimée.',
            position: 'top',
        })
    })
}