// SPDX-FileCopyrightText: 2023 Andreas Palm
//
// SPDX-License-Identifier: LicenseRef-EGPL-3.1

import '@res/css/vue.css';
import "@res/css/primevue.scss";
import {createApp} from "vue";
import { createI18n } from 'vue-i18n';
import PrimeVue from "primevue/config";
import messages from '@intlify/unplugin-vue-i18n/messages';
import {de} from"primelocale/de.json";

const numberFormats = {
    'de': {
        currency: {style: 'currency', currency: 'EUR', notation: 'standard'},
        weight: {style: 'unit', unit: 'kilogram', minimumFractionDigits: 3, maximumFractionDigits: 3}
    }
}

const i18n = createI18n({
    locale: 'de',
    fallbackLocale: 'de',
    missingWarn: false,
    fallbackWarn: false,
    messages,
    numberFormats
})

export function createVueApp(rootComponent, rootProps) {
    return createApp(rootComponent, rootProps)
        .use(PrimeVue, {
            locale: de
        })
        .use(i18n);
}