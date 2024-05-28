<script setup lang="ts">
import Card from "primevue/card";
import Checkbox from "primevue/checkbox";
import FullCalendar from "@fullcalendar/vue3";
import {defaultOptions} from "./CalendarDefaults";
import {CalendarOptions} from "@fullcalendar/core";
import EventDialog from "./EventDialog.vue";
import {onMounted, ref} from "vue";
import EventInvitation from "@modules/Calendar/www/js/EventInvitation.vue";
import axios from "axios";

const props = withDefaults(defineProps<{
  options?: CalendarOptions,
  small?: Boolean
}>(), {
  small: false
});
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
const params = ref({});
const groups = ref([]);

const onInvite = (id:Number) => {
  inviteEvent.value = id;
}

const onChange = () => {
  fullCalendar.value.getApi().refetchEvents();
}

const saveUserParameter = (parameter) => {
  axios.post('index.php?module=kalender&action=viewoptions', {
    options: {    [parameter]: params.value[parameter]}
  }).then(onChange);
}

onMounted(() => {
  axios.get('index.php?module=kalender&action=viewoptions')
      .then(response => {
        params.value = response.data.options;
        groups.value = response.data.groups;
      });
})
</script>

<template>
  <div class="flex flex-row gap-2">
    <Card class="grow">
      <template #content>
        <FullCalendar :options="{...defaultOptions, ...events, ...options}" ref="fullCalendar" />
      </template>
    </Card>
    <div v-if="!small" class="flex flex-column gap-2" style="width: 200px">
      <Card>
        <template #title>Auswahl</template>
        <template #content>
          <div class="selection flex flex-column gap-1">
            <div>
              <Checkbox v-model="params.adresse_kalender_aufgaben" @change="saveUserParameter('adresse_kalender_aufgaben')" binary />
              <label>Aufgaben</label>
            </div>
            <div>
              <Checkbox v-model="params.adresse_kalender_termine" @change="saveUserParameter('adresse_kalender_termine')" binary />
              <label>Nur eigene Termine</label>
            </div>
            <div>
              <Checkbox v-model="params.adresse_kalender_urlaub" @change="saveUserParameter('adresse_kalender_urlaub')" binary />
              <label>Urlaub/Abwesend</label>
            </div>
            <div>
              <Checkbox v-model="params.adresse_kalender_projekte" @change="saveUserParameter('adresse_kalender_projekte')" binary />
              <label>Teilprojekt</label>
            </div>
          </div>
        </template>
      </Card>
      <Card>
        <template #title>Gruppenkalender</template>
        <template #content>
          <div class="selection flex flex-column gap-1">
            <div v-for="(val, key) in groups">
              <Checkbox v-model="params['kalender_gruppe_'+key]" @change="saveUserParameter('kalender_gruppe_'+key)" binary />
              <label>{{val}}</label>
            </div>
          </div>
        </template>
      </Card>
    </div>
  </div>
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
.selection label {
  margin-left: 0.5rem;
}
</style>