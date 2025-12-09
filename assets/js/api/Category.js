import { Notify } from "quasar";
import {loadFromUrl} from "./Api";

export async function removeCategory(id) {
    const response = await loadFromUrl('/api/categories/' + id, 'DELETE').then(function (response) {
        Notify.create({
            type: 'positive',
            message: 'La catégorie a été supprimée !',
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

export async function getCategory(id) {
    return loadFromUrl('/api/categories/' + id, 'GET', 'application/json');
}

export async function getCategoryByType(type) {
    return loadFromUrl('/api/categories?type=' + type, 'GET', 'application/json');
}

export async function getAllCategories() {
    return loadFromUrl('/api/category/all', 'GET', 'application/json');
}

export async function updateCategory(category) {
    return loadFromUrl('/api/categories/' + category.id, 'PATCH', 'application/json', 'application/merge-patch+json', category);
}

export async function createCategory(body) {
    const response = await loadFromUrl('/api/categories', 'POST', 'application/json', 'application/json', body).then(function (response) {
        Notify.create({
            type: 'positive',
            message: 'La catégorie a été crée !',
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