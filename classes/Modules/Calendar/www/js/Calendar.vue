<script setup lang="ts">
import FullCalendar from "@fullcalendar/vue3";
import {defaultOptions} from "./CalendarDefaults";
import {CalendarApi, CalendarOptions} from "@fullcalendar/core";
import EventDialog from "./EventDialog.vue";
import {ref} from "vue";
import EventInvitation from "@modules/Calendar/www/js/EventInvitation.vue";
import "@res/css/fullcalendar.scss"

const props = defineProps<{
  options?: CalendarOptions
}>();
const events:CalendarOptions = {
  select(arg) {
    selectionStart.value = arg.start;
    selectionEnd.value = arg.end;
    editEvent.value = 0;
  },
  eventClick(arg)  {
    editEvent.value = arg.event.id;
  }
}
const editEvent = ref<Number|null>(null);
const inviteEvent = ref<Number|null>(null);
const selectionStart = ref<Date>();
const selectionEnd = ref<Date>();
const fullCalendar = ref(null);


const onInvite = (id:Number) => {
  inviteEvent.value = id;
}

const onChange = () => {
  fullCalendar.value.getApi().refetchEvents();
}
</script>

<template>
  <FullCalendar :options="{...defaultOptions, ...events, ...options}" ref="fullCalendar" />
  <EventDialog
      v-if="editEvent != null"
      :start="selectionStart"
      :end="selectionEnd"
      :id="editEvent"
      @close="editEvent=null"
      @change="onChange"
      @invite="onInvite"
  />
  <EventInvitation
      v-if="inviteEvent"
      :id="inviteEvent"
      @close="inviteEvent=null"
  />
</template>

<style scoped>

</style>