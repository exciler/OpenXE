<script setup lang="ts">
import Dialog from "primevue/dialog";
import InputText from "primevue/inputtext";
import Editor from "primevue/editor";
import MultiSelect from "primevue/multiselect";
import {useForm} from "vee-validate";
import {onMounted, ref} from "vue";
import axios from "axios";

const props = defineProps<{
  id: Number
}>();
const emit = defineEmits<{
  close
}>();

const {defineField, handleSubmit, resetForm} = useForm();
const [subject] = defineField('subject');
const [body] = defineField('body');
const [recipients] = defineField('recipients');
const recipientOptions = ref([]);

onMounted(() => {
  axios.get('index.php?module=kalender&action=invitation', {params: {id: props.id}})
      .then(response =>  {
        const {recipientOptions: opts, ...values} = response.data;
        recipientOptions.value = opts;
        resetForm({values: values});
      })
})

const onSubmit = handleSubmit(values => {
  axios.post('index.php?module=kalender&action=invitation', values);
})

</script>

<template>
  <Dialog id="TerminDialogEinladung" header="Einladung versenden" modal visible style="width: 1000px" @update:visible="emit('close')">
    <div class="grid gap-1" style="grid-template-columns: 1fr 3fr;">
      <label>{|Betreff|}:</label>
      <InputText v-model="subject" />
      <label>{|Text|}:</label>
      <Editor v-model="body" editor-style="height: 300px" />
      <label>{|Empfänger|}:</label>
      <MultiSelect v-model="recipients" :options="recipientOptions" />
    </div>
    <template #footer>
      <Button label="ABBRECHEN" @click="emit('close')" />
      <Button label="Einladung SENDEN" @click="onSubmit" />
    </template>
  </Dialog>
</template>
