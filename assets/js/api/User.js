import {Notify} from 'quasar';
import {loadFromUrl} from "./Api";


export async function getUserName() {
    const response = await loadFromUrl("/api/user/me", "GET", "application/json");
    return response.data.username; // Assuming the response contains a username field
}

export async function getUserRoles() {
    const response = await loadFromUrl("/api/user/roles", "GET", "application/json");
    return Object.values(response.data);
}

export async function searchUser(val) {
    const response = await loadFromUrl("/api/user/search?query="+val, "GET", "application/json");
    return response.data;
}

export async function searchInAllUser(val) {
    return loadFromUrl("/api/user/search/all?query="+val, "GET", "application/json");
}

export async function searchUserAsAdmin(val) {
    return loadFromUrl("/api/user/search/admin?query="+val, "GET", "application/json");
}

export async function getExternalUserFromService(id) {
    return loadFromUrl("/api/users?acls.type=ROLE_EXTERNAL&acls.service.id="+id, "GET", "application/json");
}


export async function removeUserRole(id) {
    return await loadFromUrl("/api/user/roles/"+id, "DELETE", "application/json").then(function (response) {
        Notify.create({
            type: 'positive',
            message: "Le rôle a été retiré à l'utilisateur.",
            position: 'top',
        })
    })
    .catch(function (response) {
        //Notification utilisateur
        Notify.create({
            type: 'negative',
            message: "Erreur : impossible de retirer le rôle à l'utilisateur.",
            position: 'top',
        })
    });
}

export async function getActiveUsersCount() {
    const response = await loadFromUrl("/api/users/active-count", "GET", "application/json");
    return response.data;
}
