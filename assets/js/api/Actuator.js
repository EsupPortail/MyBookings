import {loadFromUrl} from "./Api";

export async function getActuatorProfiles(id) {
    return loadFromUrl("/api/actuators/"+id+"/profiles", "GET", "application/json");
}

export async function getActuators() {
    return loadFromUrl("/api/actuator/read", "GET", "application/json");
}

export async function addActuator(actuatorTitle, actuatorType) {
    return loadFromUrl("/api/actuators", "POST", "application/ld+json","application/ld+json", { title: actuatorTitle, type: actuatorType });
}

export async function removeActuator(actuatorId) {
    return loadFromUrl("/api/actuators/"+actuatorId, "DELETE", "application/json");
}

export async function getActuatorsHealth() {
    return loadFromUrl("/api/actuator/health", "GET", "application/json");
}