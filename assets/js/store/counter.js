import { defineStore } from "pinia"
import {getUserName, getUserRoles} from "../api/User";

export const counter = defineStore(
    'counter',
    {
        state: () => {
            return {
                counter: 0
            }
        },
        actions: {
            increment() {
                this.counter++
            },
        }
    }
)

export const user = defineStore(
    'user',
    {
        state: () => {
            return {
                username: null,
                roles: [],
                leftDrawer: false,
                isMobile: false,
                isLoading: false,
            }
        },
        actions: {
            setRoles(roleList) {
                this.roles = roleList;
            },
            setUsername(username) {
                this.username= username;
            },
            getUsernameUpdated() {
                return this.username;
            },
            async getUsername() {
                this.username = await getUserName();
                return this.username;
            },
            async getRoles() {
                this.roles = await getUserRoles();
                return this.roles;
            },
            hasRoles(role) {
                return Object.values(this.roles).some(str => str === role);
            },
            isUserAdminSite(id, isRessourcerie = false) {
                let admin = "ROLE_ADMIN"
                if (isRessourcerie) {
                    admin = "ROLE_ADMIN_RESSOURCERIE";
                }
                return Object.values(this.roles).some(str => (str === "ROLE_ADMINSITE_" + id || str === admin));
            },

            isUserAdminOrModeratorSite(id, isRessourcerie = false) {
                let admin = "ROLE_ADMIN"
                if (isRessourcerie) {
                    admin = "ROLE_ADMIN_RESSOURCERIE";
                }
                return Object.values(this.roles).some(str => (str === "ROLE_ADMINSITE_" + id || str === "ROLE_MODERATOR_" + id || str === admin));
            },

            isUserAdminOrModerator(isRessourcerie = false) {
                let admin = "ROLE_ADMIN"
                if (isRessourcerie) {
                    admin = "ROLE_ADMIN_RESSOURCERIE";
                }
                return Object.values(this.roles).some(str => (str.includes("ROLE_ADMINSITE") || str.includes("ROLE_MODERATOR") || str === admin));
            }
        }
    }
)