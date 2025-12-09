import axios from "axios";
import { Notify } from 'quasar';
import {loadFromUrl} from "./Api";

export async function createService(bodyFormData) {
    return loadFromUrl("/api/new/service", "POST", "application/json", "multipart/form-data", bodyFormData).then(function (response) {
        //Notification de l'utilisateur
        Notify.create({
            type: 'positive',
            message: 'Le site a été créé !',
            position: 'top',
        })
    })
}

export async function getService(id) {
    const response = await loadFromUrl("/api/services/"+id, "GET", "application/json");
    return response.data;
}

export async function getServices(ids) {
    let servicesQuery = ''
    ids.forEach(function(element) {
        servicesQuery += '&id[]=' + element;
    });

    return loadFromUrl("/api/services?"+servicesQuery, "GET", "application/json");
}

export async function getDataServiceWithAcl(type, acl) {
    const acls = acl.map(element => `&acls.service.id[]=${element}`).join('');
    return loadFromUrl("/api/services-data?type="+type + acls, "GET", "application/json");
}

export async function getServiceByType(type){
    return loadFromUrl("/api/services?type="+type, "GET", "application/json");
}


export async function getUserService(id) {
    const response = await loadFromUrl("/api/service/"+id+"/users", "GET", "application/json");
    return response.data;
}

export async function getAllServices() {
    const response = await loadFromUrl("/api/services", "GET", "application/json");
    return response.data;
}

export async function deleteService(id){
    const response = await loadFromUrl("/api/service/"+id, "DELETE", "application/json");
    return response.data;
}

export async function addUserToService(username, role, site){
    let bodyFormData = new FormData();
    let roleToAdd = "ROLE_ADMINSITE"
    if(role === "Modérateur") {
        roleToAdd = "ROLE_MODERATOR"
    }
    bodyFormData.append('user', username);
    bodyFormData.append('ROLE', roleToAdd);
    bodyFormData.append('site', site);

    return await loadFromUrl("/api/user/roles", "POST", "application/json", "multipart/form-data", bodyFormData).then(function (response) {
        //Notification de l'utilisateur
        Notify.create({
            type: 'positive',
            message: "L'utilisateur a été ajouté.",
            position: 'top',
        })
    }).catch(function (response) {
        //Notification de l'utilisateur
        Notify.create({
            type: 'negative',
            message: "Erreur : impossible d\'ajouter l'utilisateur au service",
            position: 'top',
        })
    })
}

export async function editService(id, body) {
    const response = await  axios({
        method: "patch",
        url: "/api/services/"+id,
        data: body,
        headers: { "Content-Type": "application/merge-patch+json" },
    }).then(function (response) {
        Notify.create({
            type: 'positive',
            message: 'Le service a été modifié.',
            position: 'top',
        })
    })
    .catch(function (response) {
        //Notification utilisateur
        Notify.create({
            type: 'negative',
            message: 'Erreur : impossible de modifier le service.',
            position: 'top',
        })
    });

    return response;
}

export async function addExternalUserToService(id, body) {
    return loadFromUrl("/api/service/"+id+"/users/add", "POST", "application/json", "multipart/form-data", body).then(function (response) {
        Notify.create({
            type: 'positive',
            message: 'L\'utilisateur a été ajouté.',
            position: 'top',
        })
    })
    .catch(function (response) {
        //Notification utilisateur
        Notify.create({
            type: 'negative',
            message: 'Erreur : impossible d\'ajouter l\'utilisateur au service.',
            position: 'top',
        })
    });
}

export async function deleteExternalUserFromService(serviceId, id) {
    return loadFromUrl("/api/service/"+serviceId+"/users/"+id, "DELETE", "application/json");
}