import axios from "axios";
import response from "qrcode-vue3/src/core/QROptions";
import {loadFromUrl} from "./Api";

export async function getRules() {
    return loadFromUrl("/api/rules", "GET", "application/json");
}

export async function getRuleResources(id) {
    return loadFromUrl("/api/rule_resources?Rule.id="+id, "GET", "application/json");
}