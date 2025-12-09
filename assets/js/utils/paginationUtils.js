import {date} from "quasar";

export function fromJsonLd(data) {
    const ret = {
        rowsNumber: 0,
        first: null,
        next: null,
        previous: null,
        current: null,
        sortBy: 'dateStart',
        descending: false,
        searchBy : '',
        filter: '',
        page: 1,
        rowsPerPage: 20,
        filterStart: date.formatDate(new Date(Date.now() - ( 3600 * 1000 * 24)), 'DD/MM/YYYY HH:00'),
        filterEnd: date.formatDate(new Date(), 'DD/MM/YYYY HH:00'),
        filterByDate: false,
    }
    ret.rowsNumber = data["totalItems"]
    if(data["view"]) {
        ret.previous = data["view"]["previous"] ? data["view"]["previous"] : null
        ret.current = data["view"]["@id"]
        // Correction extraction page
        const pageMatch = data["view"]["@id"].match(/page=(\d+)/);
        ret.page = pageMatch ? parseInt(pageMatch[1]) : 1;
        ret.next = data["view"]["next"]
        ret.first = data["view"]["first"]
        // Correction extraction rowsPerPage
        const perPageMatch = data["view"]["@id"].match(/itemsPerPage=(\d+)/);
        if (perPageMatch) {
            ret.rowsPerPage = parseInt(perPageMatch[1]);
        }
    }
    return ret
}

export function buildUrlFromPagination(url, pagination) {
    let exportURl = url;
    //Base url pagination
    // exportURl += "&page="+pagination.page+"&itemsPerPage="+pagination.rowsPerPage;
    exportURl += "&page="+pagination.page+"&itemsPerPage="+pagination.rowsPerPage;
    //url with search filter
    exportURl += (pagination.searchBy && pagination.filter ? '&'+pagination.searchBy.value+'='+pagination.filter : '');
    //url with date filter
    exportURl += (pagination.filterByDate ? '&dateStart[after]='+getDateForFilter(pagination.filterStart)+'&dateEnd[before]='+getDateForFilter(pagination.filterEnd) : '');
    //url with sort filter
    let sortType = 'desc';
    pagination.descending === true ? sortType = 'desc' : sortType = 'asc';
    exportURl += '&order['+pagination.sortBy+']='+sortType;
    return exportURl;
}

function getDateForFilter(dateFiltered) {
    let dateExported = date.extractDate(dateFiltered,'DD/MM/YYYY HH:00' );
    return date.formatDate(dateExported,'YYYY-MM-DD HH:00' );
}