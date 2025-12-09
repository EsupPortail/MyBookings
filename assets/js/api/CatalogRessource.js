import {buildUrlFromPagination} from "../utils/paginationUtils";
import {loadFromUrl} from "./Api";

export async function getCatalogueOfResourceById(id) {
    const response = await loadFromUrl("/api/catalogue_resources?resource.id="+id, "GET", "application/json");
    return response.data;
}

export async function getCatalogueById(id) {
    const response = await loadFromUrl("/api/catalogue_resources/"+id, "GET", "application/json");
    return response.data;
}

export async function getCatalogueTitleResourcesById(id) {
    const response = await loadFromUrl("/api/catalogueTitleResources/"+id, "GET", "application/json");
    return response.data;
}

export async function getCatalogueByServiceId(id, pagination) {
    return loadFromUrl(buildUrlFromPagination("/api/bookings?catalogueResource.service.id="+id, pagination), "GET", "application/ld+json");
}

export async function getBookingsFromService(id, status, pagination) {
    return loadFromUrl(buildUrlFromPagination("/api/bookings?catalogueResource.service.id="+id+'&status='+status, pagination), "GET", "application/ld+json");
}

export function getEffectsByTypeCategoryAndKeywords(type, category, keywords, available = true) {
    return loadFromUrl("/api/effects?type.type="+type+"&type="+category+"&keywords="+keywords+'&available='+available, "GET", "application/json");
}

export function getEffectsByStatus(status = null) {
    let statusParam = status ? '?status=' + status : '';
    return loadFromUrl("/api/effects" + statusParam, "GET", "application/json");
}

export async function getCatalogFromParametersBySubtype(subType, acl) {
    return loadFromUrl("/api/catalogue_resources?subType="+subType+'&groups='+acl, "GET", "application/json");
}

export function getCatalogFromParametersByService(service) {
    return loadFromUrl("/api/catalogue_resources?service.id="+service, "GET", "application/json");
}

export function getCatalogFromParameters(acl) {
    return loadFromUrl("/api/catalogue_resources?groups="+acl, "GET", "application/json");
}

export async function removeCatalog(id) {
    return loadFromUrl('/api/catalogue/' + id, "DELETE");
}

export async function editCatalog(id, body) {
    return loadFromUrl("/api/catalogue/"+id, "POST", "application/json", "multipart/form-data", body);
}

export async function updateCatalogResource(catalogId, body) {
    return loadFromUrl("/api/catalogue_resources/" + catalogId, "PATCH", "application/json", "application/merge-patch+json", body);
}