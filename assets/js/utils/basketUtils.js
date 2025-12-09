import {userCheckRoles} from "./userUtils";
import {date} from "quasar";


export function getActualParameterFromProvisions(dateSelected, provisions, parameter) {
    let value = false;
    provisions.forEach(function (provision) {
        let provisionStart = date.extractDate(provision.dateStart, 'YYYY-MM-DDTHH:mm:ssZ');
        let provisionEnd = date.extractDate(provision.dateEnd, 'YYYY-MM-DDTHH:mm:ssZ');
        if(provisionStart <= dateSelected && dateSelected <= provisionEnd) {
            let UTCDay = dateSelected.getUTCDay();
            let daySelected = getDayFromKey(UTCDay);
            if(provision.days.includes(daySelected) && value === false) {
                provision.allGroups.forEach(function (group) {
                    if(userCheckRoles('ROLE_GROUP_'+group.id)) {
                        value = provision[parameter];
                    }
                })
            }
        }
    })
    return value;
}

function getDayFromKey(key) {
    switch (key) {
        case 1:
            return 'monday';
        case 2:
            return 'tuesday';
        case 3:
            return 'wednesday';
        case 4:
            return 'thursday';
        case 5:
            return 'friday';
        case 6:
            return 'saturday';
        case 0:
            return 'sunday';
    }
}

export function isResourcesHasRules(catalog) {
    let hasRules = false;
    catalog.resource.forEach(function (res) {
        hasRules = res.customFieldResources.length > 0;
    })
    return hasRules;
}

export function getImgFromSpecificResource(catalog, idResource) {
    let imgUrl = null
    catalog.resource.forEach(function (res) {
        if(res.id === idResource) {
            imgUrl = res.image;
        }
    })
    return imgUrl;
}