import {Notify} from "quasar";
import {loadFromUrl} from "./Api";

export async function getGroup(id) {
    return loadFromUrl('/api/admin_group/'+id, 'GET', 'application/json');
}

export async function getAllGroups() {
    return loadFromUrl('/api/groups', 'GET', 'application/json');
}

export async function getAllGroupsNoService() {
    return loadFromUrl('/api/groups?exists[Service]=false', 'GET', 'application/json');
}

export async function getGroupByService(id) {
    return loadFromUrl('/api/groups?Service.id='+id, 'GET', 'application/json');
}

export async function getAllGroupByService(id) {
    return loadFromUrl("/api/service_groups?Service.id="+id, "GET", 'application/json');
}

export async function getAdminGroups() {
    return loadFromUrl('/api/admin_groups', 'GET', 'application/json');
}

export async function postGroup(data, isAdmin= false) {
    let url = 'groups'
    if(isAdmin) {
        url = 'admin_group'
    }
    return loadFromUrl('/api/'+url, 'POST', 'application/json', 'application/json', data).then(function (response) {
        Notify.create({
            type: 'positive',
            message: 'Le groupe a été créé !',
            position: 'top',
        })
    })
}

export async function editGroup(id, data, isAdmin= false) {
    let url = 'groups'
    if(isAdmin) {
        url = 'admin_group'
    }

    return loadFromUrl('/api/'+url+'/'+id, 'PATCH', 'application/json', 'application/merge-patch+json', data).then(function (response) {
        Notify.create({
            type: 'positive',
            message: 'Le groupe a été modifié !',
            position: 'top',
        })
    })
}

export async function deleteGroup(id) {
    return loadFromUrl('/api/groups/'+id, 'DELETE').then(function (response) {
        Notify.create({
            type: 'positive',
            message: 'La requête a été supprimée !',
            position: 'top',
        })
    })
}

export async function loadUserFromGroup(id) {
    return loadFromUrl('/api/groups/'+id+'/users', 'GET', 'application/json').then(function (response) {
        Notify.create({
            type: 'positive',
            message: 'Les utilisateurs ont été chargés !',
            position: 'top',
        })
    })
}