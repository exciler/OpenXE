<script setup lang="ts">
import FullCalendar from "@fullcalendar/vue3";
import {defaultOptions} from "@modules/Calendar/www/js/CalendarDefaults";
import {CalendarOptions} from "@fullcalendar/core";
import {ref} from "vue";
const props = defineProps<{
  options?: CalendarOptions,
}>();

const fullCalendar = ref(null);
document.querySelector('#tabs')?.addEventListener('tabs-activate', () => {
  fullCalendar.value.getApi().render();
});
</script>

<template>
  <FullCalendar :options="{...defaultOptions, ...props.options}" ref="fullCalendar" />
</template>
