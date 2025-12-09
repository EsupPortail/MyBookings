import { defineStore } from "pinia"

export const booking = defineStore(
    'booking',
    {
        state: () => {
            return {
                selection: [],
                selected: false,
                events: [],
                configuration: [],
                updated: false,
            }
        },
        getters: {
            getSelection() {
                return this.selection;
            },
            getSelect() {
                return this.selected;
            },
            getUpdated() {
                return this.updated;
            },
        },
        actions: {
            defineSelected(bool) {
                this.selected = bool;
            },
            defineSelection(row) {
                this.selection = row;
            },
            definedUpdated(bool) {
                this.updated = bool;
            },
            clearSelection() {
                this.selection = null;
                this.selected = false;
                this.updated = true;
            }
        }
    }
)