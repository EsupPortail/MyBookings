import '../css/app.css';
import {createApp, ref} from "vue"
import { createPinia } from 'pinia'
import { createRouter, createWebHistory } from 'vue-router'
import { routes } from 'vue-router/auto-routes'
import App from "./App.vue"
import AppRessourcerie from "./App_ressourcerie.vue"
import {Quasar, Notify, Loading, LoadingBar, Dialog} from 'quasar'
import quasarLang from 'quasar/lang/fr'
import { createI18n } from 'vue-i18n'
import messages from '@intlify/unplugin-vue-i18n/messages'

// localStorage locale
const savedLocale = localStorage.getItem('userLocale') || 'fr';

const i18n = createI18n({
    legacy: true,
    locale: savedLocale,
    globalInjection: true,
    messages
})
// Import icon libraries
import '@quasar/extras/material-icons/material-icons.css'
import quasarIconSet from 'quasar/icon-set/svg-material-icons'
// Import Quasar css
import 'quasar/src/css/index.sass'
import '@quasar/extras/animate/flipInX.css';
import '@quasar/extras/animate/flipOutX.css';
import {user} from "./store/counter";

const router = createRouter({
    history: createWebHistory(),
    routes
})

const pinia = createPinia();

const app = createApp(App)
    .use(router)
    .use(pinia)
    .use(i18n)
    .use(Quasar, {
        plugins: {Notify, Loading, LoadingBar, Dialog}, // import Quasar plugins and add here
        lang: quasarLang,
        iconSet: quasarIconSet,
    });

const ressourcerie = createApp(AppRessourcerie)
    .use(router)
    .use(pinia)
    .use(i18n)
    .use(Quasar, {
        plugins: {Notify, Loading, LoadingBar, Dialog}, // import Quasar plugins and add here
        lang: quasarLang,
        iconSet: quasarIconSet,
    });

(async () => {
    await router.isReady();
    if(router.currentRoute.value.meta.public === undefined || router.currentRoute.value.meta.public === false) {
        const UserStore = user();
        await UserStore.getRoles();
    }

    app.mount('#app');
    ressourcerie.mount('#app_ressourcerie');
})();