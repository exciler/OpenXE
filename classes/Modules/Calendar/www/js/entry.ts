import {createVueApp} from "@res/js/vue";
import Calendar from "@modules/Calendar/www/js/Calendar.vue";

const mountTarget = document.querySelector('#vueapp_calendar');
if (mountTarget) {
    createVueApp(Calendar).mount(mountTarget);
}

