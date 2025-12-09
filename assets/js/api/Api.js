import axios from "axios";

export async function loadFromUrl(url, method= "get", accept="application/json", contentType=null, body=null) {
    let headers = {};
    if (contentType) {
        headers['Content-Type'] = contentType;
    }
    headers['accept'] = accept;
    return axios({
        method: method,
        data: body,
        url: url,
        headers: headers,
    })
}