import {Notify} from "quasar";
import {loadFromUrl} from "./Api";

export async function getProvisionsFromWorkflow(workflowId) {
    return loadFromUrl("/api/provisions?workflow.id="+workflowId, "GET", "application/json");
}

export async function getProvisionsFromCatalog(catalogId) {
    return loadFromUrl("/api/catalogue_resources/"+catalogId+'/provisions', "GET", "application/json");
}

export async function addProvision(body, notify=true) {
    return loadFromUrl("/api/provisions", "POST", "application/json", "application/json", body).then(function (response) {
        if (notify) {
            Notify.create({
                type: 'positive',
                message: 'Le planning de réservation a été créé !',
                position: 'top',
            })
        }
    })
}

export async function updateProvision(provisionId, body) {
    return loadFromUrl("/api/provisions/" + provisionId, "PATCH", "application/json", "application/merge-patch+json", body).then(function (response) {
        Notify.create({
            type: 'positive',
            message: 'Le planning de réservation a été modifié !',
            position: 'top',
        })
    })
}

export async function deleteProvisionFromCatalog(catalogId, provisionId, body) {
    return loadFromUrl("/api/catalogue/"+catalogId+'/provision/'+provisionId, "DELETE", "application/json", body).then(function (response) {
        Notify.create({
            type: 'positive',
            message: 'Le planning de réservation a été supprimé !',
            position: 'top',
        })
    })
}