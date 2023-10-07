<!--
SPDX-FileCopyrightText: 2023 Andreas Palm

SPDX-License-Identifier: LicenseRef-EGPL-3.1
-->

<script setup>
import {ref, onMounted} from "vue";
import Modal from "@theme/vue/Modal.vue";
import axios from "axios";

const props = defineProps({
  optionId: String,
  groupId: String,
  articleId: String
});
const emit = defineEmits(['save', 'close']);

const model = ref({});

onMounted(async () => {
  if (props.optionId > 0) {
    const url = props.articleId > 0
        ? 'index.php?module=matrixprodukt&action=artikel&cmd=optionedit'
        : 'index.php?module=matrixprodukt&action=optionenlist&cmd=edit';
    model.value = await axios.get(url, {
      params: {
        optionId: props.optionId
      }
    }).then(response => response.data)
  } else {
    model.value = {...props}
  }
})

async function save() {
  const url = props.articleId > 0
      ? 'index.php?module=matrixprodukt&action=artikel&cmd=optionsave'
      : 'index.php?module=matrixprodukt&action=optionenlist&cmd=save';
  await axios.post(url, model.value)
      .catch(error => alert(error.response.data))
      .then(response => {emit('save')});
}
const buttons = {
  abbrechen: () => emit('close'),
  speichern: save
}
</script>

<template>
  <Modal title="Option anlegen/bearbeiten" width="500px" :buttons="buttons" @close="emit('close')">
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
        <td>Artikelnummer-Suffix:</td>
        <td><input type="text" v-model="model.articleNumberSuffix" /></td>
      </tr>
      <tr>
        <td>Sortierung:</td>
        <td><input type="text" size="8" v-model="model.sort"></td>
      </tr>
      <tr>
        <td>Aktiv:</td>
        <td><input type="checkbox" v-model="model.active"></td>
      </tr>
    </table>
  </Modal>
</template>
