import {createVueApp} from "@res/js/vue";
import Calendar from "@modules/Calendar/www/js/Calendar.vue";

createVueApp(Calendar, {small: true}).mount('#vueapp_calendar')