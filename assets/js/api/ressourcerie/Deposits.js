import axios from "axios";

export async function getDeposits() {
    return axios({
        method: "get",
        url: "/api/deposits",
    });
}
