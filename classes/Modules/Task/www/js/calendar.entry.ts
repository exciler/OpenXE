import FullCalendar from "@fullcalendar/vue3";
import {defaultOptions} from "@modules/Calendar/www/js/CalendarDefaults";
import {createVueApp} from "@res/js/vue";
import {CalendarOptions, EventClickArg, EventDropArg} from "@fullcalendar/core";
import axios from "axios";

const eventClick = function(arg: EventClickArg) {
    AufgabenEdit(arg.event.id);
};

const eventDrop = function (arg: EventDropArg){
    axios
        .post('./index.php?module=aufgaben&action=dragdropaufgabe', {
            id: arg.event.id,
            date: arg.event.start
        })
        .catch(() => {
            alert("Eintrag kann nicht verschoben werden");
            arg.revert();
        });
};

const options: CalendarOptions = {
    events: "./index.php?module=aufgaben&action=data",
    eventClick: eventClick,
    eventDrop: eventDrop,
}

createVueApp(FullCalendar, {options: {...defaultOptions, ...options}}).mount('#calendar');