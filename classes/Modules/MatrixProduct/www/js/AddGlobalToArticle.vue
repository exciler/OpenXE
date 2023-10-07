<!--
SPDX-FileCopyrightText: 2023 Andreas Palm

SPDX-License-Identifier: LicenseRef-EGPL-3.1
-->

<script setup>
import {ref, onMounted} from "vue";
import Modal from "@theme/vue/Modal.vue";
import axios from "axios";

const props = defineProps({
  articleId: String
})
const emit = defineEmits(['save', 'close']);

const model = ref(null);
const group = ref(null);
const selected = ref([]);
onMounted(async () => {
  model.value = await fetch('index.php?module=matrixprodukt&action=list&cmd=selectoptions')
      .then(x => x.json())
})
async function save() {
  await axios.post('index.php?module=matrixprodukt&action=artikel&cmd=addoptions', {
    articleId: props.articleId,
    optionIds: selected
  })
      .catch(error => alert(error.response.data))
      .then(response => {emit('save')});
}

const buttons = {
  abbrechen: () => emit('close'),
  speichern: save
}

</script>

<template>
  <Modal title="Globale Optionen hinzufügen" width="400px" :buttons="buttons" @close="emit('close')">
    <table v-if="model">
      <tr>
        <td>Gruppe:</td>
        <td>
          <div class="ui-widget">
          <select name="matrixproduktGroup_name" id="matrixproduktGroup_name" v-model="group">
            <option v-for="(value,key) in model.groups" :value="key">{{value}}</option>
          </select>
          </div>
        </td>
      </tr>
      <tr>
        <td style="vertical-align: top;">Optionen:</td>
        <td>
          <select multiple name="matrixprodukt_options" id="matrixprodukt_options" v-model="selected">
            <template  v-for="item in model.options">
              <option :value="item.id" v-if="item.gruppe === group">{{item.name}}</option>
            </template>
          </select>
        </td>
      </tr>
    </table>
  </Modal>
</template>