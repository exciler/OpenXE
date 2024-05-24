import {CalendarOptions} from "@fullcalendar/core";
import deLocale from "@fullcalendar/core/locales/de";
import dayGridPlugin from "@fullcalendar/daygrid";
import timeGridPlugin from "@fullcalendar/timegrid"
import interactionPlugin from "@fullcalendar/interaction";

export const defaultOptions:CalendarOptions = {
    plugins:[dayGridPlugin, timeGridPlugin, interactionPlugin],
    initialView: dayGridPlugin.initialView,
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay'
    },
    locale: deLocale,
    weekNumbers: true,
    selectable: true,
    editable: true,
    height: 650,
    events: "./index.php?module=kalender&action=data"
}
