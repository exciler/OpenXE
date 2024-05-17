<script setup lang="ts">
import Dialog from "primevue/dialog";
import InputText from "primevue/inputtext";
import ColorPicker from "primevue/colorpicker";
import Checkbox from "primevue/checkbox";
import MultiSelect from "primevue/multiselect";
import Button from "primevue/button";
import {useForm} from "vee-validate";
import {onMounted, ref} from "vue";
import axios from "axios";
import {AlertErrorHandler} from "@res/js/ajaxErrorHandler";

const props = defineProps<{
  id: Number
}>();
const emit = defineEmits<{
  close,
  change
}>();

const defaults = {
  color: '#0B8092',
  members: [],
  active: true
};

const {defineField, handleSubmit, resetForm} = useForm();
const [description] = defineField('name');
const [color] = defineField('color');
const [active] = defineField('active');
const [members] = defineField('members');
const memberOptions = ref([]);
const memberOptionLabelFunc = item => [item.mitarbeiternummer, item.name].join(' ');

onMounted(async() => {
  await axios.get('index.php?module=kalender&action=gruppenedit', {params: props})
      .then(response => {
        const {memberOptions: mo, ...values} = response.data;
        memberOptions.value = mo;
        resetForm({values: {...defaults, ...values}});
      })
      .catch(AlertErrorHandler);
})

const onSubmit = handleSubmit(async values => {
  if (!values.color.startsWith('#'))
    values.color = '#'+values.color;
  await axios.post('index.php?module=kalender&action=gruppensave', {...values, id: props.id})
      .then(() => emit('change'))
      .catch(AlertErrorHandler);
});

</script>

<template>
  <Dialog visible modal header="Kalendergruppe bearbeiten" style="width: 600px;" @update:visible="emit('close')">
    <div class="grid gap-1" style="grid-template-columns: 1fr 2fr;">
      <label for="description">Bezeichnung:</label>
      <InputText v-model="description" />
      <label for="color">Farbe:</label>
      <ColorPicker v-model="color" default-color="#0B8092" />
      <label for="active">Aktiv:</label>
      <Checkbox v-model="active" binary />
      <label for="members">Mitglieder</label>
      <MultiSelect
          v-model="members"
          display="chip"
          filter
          :options="memberOptions"
          :option-label="memberOptionLabelFunc"
          option-value="id" />
    </div>
    <template #footer>
      <Button label="Abbrechen" @click="emit('close')" />
      <Button label="Speichern" @click="onSubmit" />
    </template>
  </Dialog>
</template>