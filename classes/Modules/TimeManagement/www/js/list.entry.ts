import {createVueApp} from "@res/js/vue";
import TimeManagementCalendar from "@modules/TimeManagement/www/js/TimeManagementCalendar.vue";
import {CalendarOptions} from "@fullcalendar/core";

const options: CalendarOptions = {
    events: "./index.php?module=zeiterfassung&action=create&cmd=mitarbeiteransichtdata",
    initialView: "timeGridWeek",
    editable: false
}

createVueApp(TimeManagementCalendar, {options}).mount('#vueapp_calendar');