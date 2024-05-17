<script setup lang="ts">
import Dialog from "primevue/dialog";
import Button from "primevue/button";
import AutoComplete from "@res/vue/AutoComplete.vue";
import {useForm} from "vee-validate";
import Calendar from "primevue/calendar";
import ColorPicker from "primevue/colorpicker";
import Listbox from "primevue/listbox";
import Checkbox from "primevue/checkbox";
import InputText from "primevue/inputtext";
import Card from "primevue/card";
import Textarea from "primevue/textarea";
import {onMounted, ref} from "vue";
import axios from "axios";
import {AlertErrorHandler} from "@res/js/ajaxErrorHandler";

const props = defineProps<{
  id: Number,
  start?: Date,
  end?: Date
}>();

const emit = defineEmits<{
  close: [],
  change: [],
  invite: [id: Number]
}>();

const defaults = {
  color: '#0B8092',
  beginDate: props.start,
  endDate: props.end,
};
const {defineField, resetForm, handleSubmit, setFieldValue} = useForm();
const [title] = defineField('title');
const [description] = defineField('description');
const [location] = defineField('location');
const [address] = defineField('address');
const [contactPerson] = defineField('contactPerson');
const [internalAddress] = defineField('internalAddress');
const [project] = defineField('project');
const [beginDate] = defineField('beginDate');
const [endDate] = defineField('endDate');
const [allDay] = defineField('allDay');
const [reminder] = defineField('reminder');
const [color] = defineField('color');
const [isPublic] = defineField('isPublic');
const [userIds] = defineField('userIds');
const [groupIds] = defineField('groupIds');
const users = ref([]);
const groups = ref([]);

onMounted(() => {
  axios.get('index.php?module=kalender&action=eventedit', {params: {id: props.id}})
      .then(response => {
        users.value = response.data.users;
        groups.value = response.data.groups;
        let formvalues = {...defaults, ...response.data.event};
        if (!(formvalues.beginDate instanceof Date))
          formvalues.beginDate = new Date(formvalues.beginDate);
        if (!(formvalues.endDate instanceof Date))
          formvalues.endDate = new Date(formvalues.endDate);
        resetForm({values: formvalues});
      })
      .catch(AlertErrorHandler);
})

function toggleDatesChange() {
  //workaround to trigger re-formatting date values
  beginDate.value = beginDate.value;
  endDate.value = endDate.value;
}

function navigate(url: string) {
  document.location.href = url;
}

const onSubmit = handleSubmit(val => {
  if (!val.color.startsWith('#'))
    val.color = '#'+val.color;
  return axios.post('index.php?module=kalender&action=eventsave', val)
      .then((response):Number => {
        emit('change');
        return response.data.id
      })
      .catch(AlertErrorHandler);
})

const onSave = () => {
  return onSubmit().then(() => {emit('close')});
}

const onCopy = () => {
  setFieldValue('id', 0);
  return onSave();
}

const onDelete = () => {
  return axios.post('index.php?module=kalender&action=eventdelete', {id: props.id})
      .then(() => {
        emit('change');
        emit('close');
      })
      .catch(AlertErrorHandler);
}

const onInvite = () => {
  return onSubmit()
      .then(id => {
        emit('invite', id);
        emit('close');
      });
}
</script>

<template>
  <Dialog id="TerminDialog" header="Termin erstellen / bearbeiten" visible modal style="width: 1000px" @update:visible="emit('close')">
    <form>
      <div class="flex flex-row gap-2">
        <Card class="basis-1/2">
          <template #content>
          <div class="grid gap-1" style="grid-template-columns: 1fr 3fr;">
            <div style="grid-column: span 2;">
              <div id="submitError" style="color:red;"></div>
              <div id="googleStatus"></div>
            </div>
            <label>{|Titel|}:</label>
            <InputText v-model="title" />
            <label>{|Beschreibung|}:</label>
            <Textarea v-model="description" ></Textarea>
            <label>{|Ort|}:</label>
            <InputText v-model="location" />
            <label>{|Termin mit Adresse|}:</label>
            <div class="with-link">
              <AutoComplete v-model="address"
                            ajaxFilter="adresse"
                            style="flex-grow: 1"
                            :option-label="(item) => item.name"
                            force-selection
              />
              <img v-if="address?.id > 0"
                   src="@theme/new/images/forward.svg"
                   @click="navigate('index.php?module=adresse&action=edit&id=' + address?.id)"
              />
            </div>
            <label>{|Ansprechpartner bei Adresse|}:</label>
            <div class="with-link">
              <AutoComplete v-model="contactPerson"
                            ajaxFilter="ansprechpartneradresse"
                            :additional-query-params="{'adresse': address?.id ?? 0}"
              />
              <img v-if="contactPerson?.id > 0"
                   src="@theme/new/images/forward.svg"
                   @click="navigate('index.php?module=adresse&action=ansprechpartner&id=' + contactPerson?.id)"
              />
            </div>
            <label>{|Verantwortlicher intern*|}:</label>
            <AutoComplete v-model="internalAddress"
                          ajaxFilter="mitarbeiterid"
            />
            <label>{|Projekt|}:</label>
            <AutoComplete v-model="project" ajaxFilter="projektname" />
            <label>{|Von|}:</label>
            <Calendar v-model="beginDate" :showTime="!allDay" />
            <label>{|Bis|}:</label>
            <Calendar v-model="endDate" :showTime="!allDay" />
            <label>{|Ganztags|}:</label>
            <Checkbox v-model="allDay" binary @change="toggleDatesChange" />
            <label class="erinnerung">{|Erinnerung|}:</label>
            <Checkbox v-model="reminder" binary />
            <label>{|Farbe|}:</label>
            <ColorPicker v-model="color" />
            <label>{|&Ouml;ffentlich|}:</label>
            <Checkbox v-model="isPublic" binary />
          </div>
          </template>
        </Card>
        <Card class="basis-1/2">
          <template #content>
            <div class="grid gap-1" style="grid-template-columns: 1fr 3fr;">
              <label>{|Teilnehmer intern*|}:</label>
              <Listbox v-model="userIds" :options="users" option-value="id" option-label="name" />
              <label>{|Gruppenkalender|}:</label>
              <Listbox v-model="groupIds" :options="groups" option-value="id" option-label="bezeichnung" />
            </div>
          </template>
        </Card>
      </div>
      <input type="hidden" name="submitForm" value="1">
      <input type="hidden" name="mode" id="mode" value="">
      <input type="hidden" name="eventid" id="eventid" value="">
    </form>
    <template #footer>
      <Button label="Speichern" @click="onSave" />
      <Button label="Kopieren" :disabled="id === 0" @click="onCopy" />
      <Button label="Löschen" :disabled="id === 0" @click="onDelete" />
      <Button label="Einladung" @click="onInvite" />
      <Button label="Abbrechen" @click="emit('close')" />
    </template>
  </Dialog>
</template>

<style scoped>
div.with-link {
  display: flex;
  flex-direction: row;
}
div.with-link .p-component {
  flex-grow: 1;
}
div.with-link img {
  width: 2.5rem;
  padding: 0.25rem;
  cursor: pointer;
}
</style>