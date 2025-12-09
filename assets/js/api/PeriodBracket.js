import {loadFromUrl} from "./Api";

export async function getPeriodBracket(id) {
    return loadFromUrl("/api/period_brackets/service/" + id, "GET", "application/json");
}

export async function addPeriodBracket(data) {
    return await loadFromUrl("/api/period_brackets", "POST", "application/json", "application/json", data);
}

export async function getBracket(id) {
    return await loadFromUrl("/api/period_brackets/" + id, "GET", "application/json");
}

export async function deleteBracket(id) {
    return await loadFromUrl("/api/period_brackets/" + id, "DELETE");
}

export async function addPeriodToBracket(data) {
    return await loadFromUrl("/api/periods", "POST", "application/json", "application/json", data);
}

export async function deletePeriod(id) {
    return await loadFromUrl("/api/periods/" + id, "DELETE");
}

export async function updatePeriod(id, data) {
    return await loadFromUrl("/api/periods/" + id, "PATCH", "application/json", "application/merge-patch+json", data);
}