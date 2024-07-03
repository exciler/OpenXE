import sidebar from './sidebar';
import "./scripts.js";
import "./profilemenu";
import "./tabs";
import "./yuiTools";
import {createVueApp} from "@res/js/vue";
import PageApp from "@res/vue/PageApp.vue";
import ToastService from "primevue/toastservice";

sidebar();

const pageAppEl = document.createElement('div');
document.querySelector('body')?.append(pageAppEl);
createVueApp(PageApp).use(ToastService).mount(pageAppEl);
