import {loadFromUrl} from "./Api";

export async function getWorkflowsByService(serviceId) {
    return loadFromUrl("/api/workflows?ServiceId="+serviceId+'&ServiceNull=true', "GET", "application/json");
}

export async function getWorkflowById(id) {
    return loadFromUrl("/api/workflows/"+id, "GET", "application/json");
}

export async function postWorkFlow(title, autoValidation, autoStart, autoEnd, configuration, service) {
    const data = {"title": title, "autoValidation": autoValidation, "autoStart": autoStart, "autoEnd": autoEnd, "configuration":configuration, "Service": "/api/services/"+service}
    return loadFromUrl("/api/workflows", "POST", "application/json", "application/json", data);
}

export async function patchWorkFlow(title, autoValidation, autoStart, autoEnd, configuration, id) {
    const data= {"title": title, "autoValidation": autoValidation, "autoStart": autoStart, "autoEnd": autoEnd, "configuration":configuration}
    return loadFromUrl("/api/workflows/"+id, "PATCH", "application/json", "application/merge-patch+json", data);
}