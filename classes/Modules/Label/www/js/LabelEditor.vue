<script setup lang="ts">
import Dialog from "primevue/dialog";
import Dropdown from "primevue/dropdown";
import ColorPicker from "primevue/colorpicker";
import {useForm} from "vee-validate";
import {useI18n} from "vue-i18n";
import Button from "primevue/button";
import {ref, watchEffect} from "vue";
import axios from "axios";
import {AlertErrorHandler} from "@res/js/ajaxErrorHandler";
import {reloadDataTables} from "@res/js/jqueryBridge";
defineProps({
  groups: Array<Object>
})

document.querySelector('a.neubuttonlink')?.addEventListener('click', () => id.value = 0);

document.querySelector('#datatablelabels_list')?.addEventListener('click', (evt) => {
      if (evt.target instanceof Element) {
        let link = evt.target.closest<HTMLElement>('a.datatablelabels-edit');
        id.value = parseInt(link?.dataset.id ?? '') || null;
      }
    });

const {t} = useI18n();
const id = ref<Number|null>(null);
const {defineField, handleSubmit, resetForm} = useForm();
const [title] = defineField('title');
const [type] = defineField('type');
const [group_id] = defineField('group_id');
const [hexcolor] = defineField('hexcolor');

const onSubmit = handleSubmit(async values => {
  await axios.post('index.php?module=datatablelabels&action=edit&cmd=save', {...values, id: id.value})
      .catch(AlertErrorHandler)
      .then(() => {
        onClose();
        reloadDataTables();
      })
})

watchEffect(async () => {
  if (!id.value) {
    resetForm();
    return;
  }

  await axios.post('index.php?module=datatablelabels&action=edit&cmd=get', {id: id.value })
      .then((response) => resetForm({values: response.data.data}));
});

const onClose = () => id.value = null;
</script>

<template>
  <Dialog :header="t('common.edit')" :visible="id !== null" modal @update:visible="onClose" style="width: 900px">
    <form @submit="onSubmit">
      <div class="grid gap-1" style="grid-template-columns: 25% 75%">
        <label>{{ t('common.description') }}</label>
        <div>
          <input type="text" v-model="title" />
        </div>
        <label>{{ t('type') }}</label>
        <div>
          <input type="text" v-model="type" />
          <small>Nur Kleinbuchstaben und Zahlen [a-z, 0-9]</small>
        </div>
        <label>{{ t('common.group') }}</label>
        <div>
          <Dropdown v-model="group_id" :options="groups" optionValue="id" optionLabel="title" />
        </div>
        <label>{{ t('common.color') }}</label>
        <ColorPicker v-model="hexcolor" default-color="#000000" />
      </div>
    </form>
    <template #footer>
      <Button :label="t('common.cancel').toUpperCase()" @click="onClose" />
      <Button :label="t('common.save').toUpperCase()" @click="onSubmit" />
    </template>
  </Dialog>
</template>

<style scoped>

</style>

<i18n src="./locales.yaml" lang="yaml" />