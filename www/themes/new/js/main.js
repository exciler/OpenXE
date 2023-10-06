// SPDX-FileCopyrightText: 2023 Andreas Palm
//
// SPDX-License-Identifier: LicenseRef-EGPL-3.1

import 'primevue/resources/themes/bootstrap4-light-blue/theme.css';
import '../css/vue.css';
import {createApp} from "vue";
import PrimeVue from "primevue/config";
import {usePassThrough} from "primevue/passthrough";

const OpenXE_PT = {
    autocomplete: {
        panel: { class: 'ui-autocomplete ui-front ui-widget ui-widget-content'},
        list: { class: 'ui-menu'},
        item: { class: 'ui-menu-item'}
    }
}

export function createVueApp(rootComponent, rootProps) {
    return createApp(rootComponent, rootProps).use(PrimeVue, /*{ unstyled: true,  pt: OpenXE_PT }*/);
}