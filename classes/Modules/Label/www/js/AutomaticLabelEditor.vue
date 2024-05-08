<script setup lang="ts">
import Dialog from "primevue/dialog";
import Dropdown from "primevue/dropdown";
import {useForm} from "vee-validate";
import {useI18n} from "vue-i18n";
import Button from "primevue/button";
import {ref, watchEffect} from "vue";
import axios from "axios";
import {AlertErrorHandler} from "@res/js/ajaxErrorHandler";
import {reloadDataTables} from "@res/js/jqueryBridge";
import AutoComplete from "@res/vue/AutoComplete.vue";
defineProps({
  actions: Array<Object>,
  selections: Array<Object>
})

type Project = { abkuerzung: String, name: String};

document.querySelector('a.neubuttonlink')?.addEventListener('click', () => id.value = 0);

document.querySelector('#datatablelabels_automaticlabelslist')?.addEventListener('click', (evt) => {
      if (evt.target instanceof Element) {
        let link = evt.target.closest<HTMLElement>('a.datatablelabels-automaticlabeledit');
        id.value = parseInt(link?.dataset.id ?? '') || null;
      }
    });

const {t} = useI18n();
const id = ref<Number|null>(null);
const {defineField, handleSubmit, resetForm} = useForm();
const [labelname] = defineField('labelname');
const [action] = defineField('action');
const [selection] = defineField('selection');
const [project] = defineField('project');

const onSubmit = handleSubmit(async values => {
  await axios.post('index.php?module=datatablelabels&action=automaticlabelsedit&cmd=save', {...values, id: id.value})
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

  await axios.post('index.php?module=datatablelabels&action=automaticlabelsedit&cmd=get', {id: id.value })
      .then((response) => resetForm({values: response.data.data}));
});

const onClose = () => id.value = null;
</script>

<template>
  <Dialog :header="t('common.edit')" :visible="id !== null" modal @update:visible="onClose" style="width: 900px">
    <form @submit="onSubmit">
      <h2>Automatisches Label</h2>
      <div class="grid gap-1" style="grid-template-columns: 25% 75%">
        <label>{{ t('label') }}</label>
        <AutoComplete
          v-model="labelname"
          ajaxFilter="label_type"
        />
        <label>{{ t('action') }}</label>
        <Dropdown v-model="action" :options="actions" optionValue="value" optionLabel="text" />
        <label>{{ t('selection') }}</label>
        <Dropdown v-model="selection" :options="selections" optionValue="value" optionLabel="text" />
        <label>{{ t('common.project') }}</label>
        <AutoComplete
            v-model="project"
            dataKey="id"
            :optionLabel="(item:Project) => [item.abkuerzung, item.name].join(' ')"
            ajaxFilter="projektname"
        />
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