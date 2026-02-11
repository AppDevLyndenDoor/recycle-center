import Layout from '../js/layouts/MainLayout.vue';
import '../css/app.css';
import '../css/style.css';
import { library } from '@fortawesome/fontawesome-svg-core'
/* import font awesome icon component */
import { faLine, faMicrosoft } from '@fortawesome/free-brands-svg-icons';

import {
    faSun,
    faRectangleList,
} from '@fortawesome/free-regular-svg-icons'
import {
    faCalendarDays,
    faTruck,
    faClipboard,
    faBook,
    faQrcode,
    faMagnifyingGlass,
    faReceipt,
    faM,
    faSquareXmark,
    faSquareCheck,
    faBars,
    faRightFromBracket,
    faRightToBracket,
    faTriangleExclamation,
    faUser,
    faPrint,
    faMoon,
    faGear,
    faShield,
    faTable,
    faXmarksLines,
    faGears,
    faBriefcase,
    faFile,
    faFolderOpen,
    faCopy,
    faFloppyDisk,
    faTrash,
    faTrashCan,
    faSpinner,
    faFilterCircleXmark,
    faBoxOpen,
    faScrewdriverWrench,
    faArrowUp,
    faArrowDown,
    faCodeFork,
    faLock,
    faUsers,
    faPenToSquare,
    faBug,
    faCalculator,
    faSquareCaretRight,
    faSquareCaretLeft,
    faMessage,
    faUpload,
    faBell,
    faPenRuler,
    faCheck,
    faTag,
    faFileLines,
    faPaperPlane,
    faMicrochip,
    faCompassDrafting,
    faBinoculars,
    faGraduationCap,
    faSquare,
    faCircleQuestion,
} from '@fortawesome/free-solid-svg-icons'
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome'

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createPinia } from 'pinia'
import { createApp, h } from 'vue';
import type { DefineComponent } from 'vue';
import '../css/app.css';
import { initializeTheme } from './composables/useAppearance';

/* add icons to the library */
library.add(faCalendarDays);
library.add(faTruck);
library.add(faClipboard);
library.add(faBook);
library.add(faQrcode);
library.add(faMagnifyingGlass);
library.add(faRectangleList);
library.add(faReceipt);
library.add(faM);
library.add(faSquareXmark);
library.add(faSquareCheck);
library.add(faBars);
library.add(faRightFromBracket);
library.add(faRightToBracket);
library.add(faTriangleExclamation);
library.add(faUser);
library.add(faPrint);
library.add(faSun);
library.add(faMoon);
library.add(faGear);
library.add(faShield);
library.add(faTable);
library.add(faXmarksLines);
library.add(faGears);
library.add(faBriefcase);
library.add(faFile);
library.add(faFolderOpen);
library.add(faCopy);
library.add(faFloppyDisk);
library.add(faTrash);
library.add(faSpinner);
library.add(faTrashCan);
library.add(faFilterCircleXmark);
library.add(faBoxOpen);
library.add(faScrewdriverWrench);
library.add(faArrowUp);
library.add(faArrowDown);
library.add(faCodeFork);
library.add(faLock);
library.add(faUsers);
library.add(faPenToSquare);
library.add(faBug);
library.add(faCalculator);
library.add(faSquareCaretRight);
library.add(faSquareCaretLeft);
library.add(faMessage);
library.add(faMicrosoft);
library.add(faUsers);
library.add(faUpload);
library.add(faBell);
library.add(faPenRuler);
library.add(faCheck);
library.add(faTag);
library.add(faFileLines);
library.add(faPaperPlane);
library.add(faMicrochip);
library.add(faCompassDrafting);
library.add(faBinoculars);
library.add(faGraduationCap);
library.add(faSquare);
library.add(faCircleQuestion);

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const pinia = createPinia();

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: async (name) => {
        const page = (await resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob('./pages/**/*.vue'),
        )).default as DefineComponent;

        // Set a default layout (if not explicitly defined on the page)
        page.layout = page.layout || Layout;

        return page;
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(pinia)
            .component('font-awesome-icon', FontAwesomeIcon)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();
