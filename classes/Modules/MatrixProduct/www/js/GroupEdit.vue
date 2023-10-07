<!--
SPDX-FileCopyrightText: 2023 Andreas Palm

SPDX-License-Identifier: LicenseRef-EGPL-3.1
-->

<script setup>
import {ref, onMounted} from "vue";
import Modal from "@theme/vue/Modal.vue";
import axios from "axios";
import Autocomplete from "@theme/vue/Autocomplete.vue";

const props = defineProps({
  groupId: String,
  articleId: String
});
const emit = defineEmits(['save', 'close']);

const model = ref({});

onMounted(async () => {
  if (props.groupId > 0) {
    const url = props.articleId > 0
        ? 'index.php?module=matrixprodukt&action=artikel&cmd=groupedit'
        : 'index.php?module=matrixprodukt&action=list&cmd=edit';
    model.value = await axios.get(url, {
      params: props
    }).then(response => response.data)
  }
})

async function save() {
  if (!parseInt(props.groupId) > 0)
    model.value.groupId = 0;
  const url = props.articleId > 0
      ? 'index.php?module=matrixprodukt&action=artikel&cmd=groupsave'
      : 'index.php?module=matrixprodukt&action=list&cmd=save';
  await axios.post(url, {...props, ...model.value})
      .catch(error => alert(error.response.data))
      .then(response => {emit('save')});
}
const buttons = {
  abbrechen: () => emit('close'),
  speichern: save
}
</script>

<template>
  <Modal title="Gruppe anlegen/bearbeiten" width="500px" :buttons="buttons" @close="emit('close')">
    <table>
      <tr>
        <td>Name:</td>
        <td><input type="text" size="40" v-model="model.name"></td>
      </tr>
      <tr>
        <td>Name Extern:</td>
        <td><input type="text" size="40" v-model="model.nameExternal"></td>
      </tr>
      <tr>
        <td>Projekt:</td>
        <td><Autocomplete v-model="model.projectId" /></td>
      </tr>
      <tr v-if="model.articleId">
        <td>Sortierung:</td>
        <td><input type="text" size="8" v-model="model.sort"></td>
      </tr>
      <tr>
        <td>Pflicht:</td>
        <td><input type="checkbox" v-model="model.required"></td>
      </tr>
      <tr>
        <td>Aktiv:</td>
        <td><input type="checkbox" v-model="model.active"></td>
      </tr>
    </table>
  </Modal>
</template>
