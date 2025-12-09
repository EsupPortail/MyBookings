import { defineStore } from "pinia"
import {ref} from "vue";
import {date} from "quasar";

export const basket = defineStore(
    'basket',
    {
        state: () => {
            return {
                start: null,
                end: null,
                type: null,
                subtype: null,
                localization: null,
                startBooking: null,
                endBooking:null,
                selection: null,
                rules: null,
                bookingConditions: {
                    allowUsersToBook: true,
                    allowUsersCheckDates: true,
                    allowUsersToBookWithRules: true,
                },
                cart: [],
                number: 1,
                users: [],
                comment: null,
                resourceId: null,
                selectionNStart: null,
                selectionNEnd: null,
                sendBookings: false
            }
        },
        getters: {
            getStartBooking() {
                return this.startBooking;
            },
            getEndBooking() {
                return this.endBooking;
            },
            getStart() {
                return this.start;
            },
            getStartDate() {
                let dateString = this.start;
                if (dateString !== null) {
                    let dateParts = dateString.split("/");
                    let day = parseInt(dateParts[0], 10);
                    let month = parseInt(dateParts[1], 10);
                    let year = parseInt(dateParts[2], 10);
                    return year+'-'+month+'-'+day;
                }
                return false
            },
            getEndDate() {
                let dateString = this.end;
                if (dateString !== null) {
                    let dateParts = dateString.split("/");
                    let day = parseInt(dateParts[0], 10);
                    let month = parseInt(dateParts[1], 10);
                    let year = parseInt(dateParts[2], 10);
                    return year + '-' + month + '-' + day;
                }
                return false;
            },
            getStartFullDay() {
                let dateString = date.extractDate(this.start, 'DD/MM/YYYY');
                return date.formatDate(dateString, 'dddd');
            },
            getStartDay() {
                let dateString = date.extractDate(this.start, 'DD/MM/YYYY');
                return date.formatDate(dateString, 'DD');
            },
            getStartYear() {
                let dateString = date.extractDate(this.start, 'DD/MM/YYYY');
                return date.formatDate(dateString, 'YYYY');
            },
            getStartMonth() {
                let dateString = date.extractDate(this.start, 'DD/MM/YYYY');
                return date.formatDate(dateString, 'MMMM');
            },
            getEnd() {
                return this.end;
            },
            getSelection() {
                return this.selection;
            },
            getCart() {
                return this.cart;
            },
        },
        actions: {
            defineSelection(id) {
                this.selection = id;
            },
            removeFromCart(index) {
                this.cart.splice(index, 1);
            },
        }
    }
)