import {user} from "../store/counter";
import {basket} from "../store/basket";

export function isUserAdmin() {
    const storeUser = user();
    const basketUser = basket();
    return storeUser.roles.includes('ROLE_ADMINSITE_'+basketUser.selection.service.id);
}

export function isUserAdminOrModerator() {
    const storeUser = user();
    const basketUser = basket();
    return storeUser.roles.includes('ROLE_ADMINSITE_'+basketUser.selection.service.id) || storeUser.roles.includes('ROLE_MODERATOR_'+basketUser.selection.service.id);
}

export function clearRolesWithoutAdmin() {
    const storeUser = user();
    return storeUser.roles.filter(role => role.includes('ROLE_GROUP'));
}

export function getRolesId(roles) {
    return roles.map(role => role.split('_')[2]);
}

export function getRolesAdminSite() {
    const storeUser = user();
    return getRolesId(storeUser.roles.filter(role => role.includes('ROLE_ADMINSITE') || role.includes('ROLE_MODERATOR')));
}

export function userCheckRoles(roles) {
    const storeUser = user();
    return storeUser.roles.some(role => roles.includes(role));
}

