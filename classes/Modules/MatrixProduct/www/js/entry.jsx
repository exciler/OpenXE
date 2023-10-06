// SPDX-FileCopyrightText: 2023 Andreas Palm
//
// SPDX-License-Identifier: LicenseRef-EGPL-3.1

import ArticleApp from "./ArticleApp.vue";
import {createVueApp} from "@theme/main";

const app = createVueApp(ArticleApp, {updateTables}).mount('#vueapp')

function updateTables() {
    window.$('#main .dataTable').DataTable().ajax.reload();
}
