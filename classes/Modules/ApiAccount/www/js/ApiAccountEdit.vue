<script setup>
import axios from "axios";
import {ref} from "vue";
import Dialog from "primevue/dialog";
import AutoComplete from "@res/vue/AutoComplete.vue";
import Button from "primevue/button";
import {reloadDataTables} from "@res/js/jqueryBridge.js";

const model = ref(null);
const permissions = window.apiAccountPermissions;

document.querySelector('a.neubuttonlink')
    .addEventListener('click', () => {
      model.value = Object({permissions:[]});
    });

document.querySelector('#api_account_list')
    .addEventListener('click', (evt) => {
      if (evt.target instanceof Element) {
        let link = evt.target.closest('a.get');
        if (link && link.dataset.id > 0)
          load(link.dataset.id);
      }
    });


async function save() {
  await axios.post('index.php?module=api_account&action=list&cmd=save', model.value)
      .then(() => {
        reloadDataTables();
        close();
      });
}

async function load(id) {
  await axios.post('index.php?module=api_account&action=list&cmd=get', {id: parseInt(id)})
      .then((response) => model.value = response.data);
}

function close() {
  model.value = null;
}
</script>

<template>
  <Dialog visible modal v-if="model" header="API Account" style="width: 940px" @update:visible="close">
    <div class="grid gap-1" style="grid-template-columns: 25% 75%">
      <label>API Account ID:</label>
      <span>{{model.id}}</span>
      <label>Aktiv:</label>
      <input type="checkbox" v-model="model.active" class="justify-self-start">
      <label>Bezeichnung:</label>
      <input type="text" v-model="model.name" />
      <label>Projekt:</label>
      <AutoComplete
          v-model="model.projectId"
          :optionLabel="item => [item.abkuerzung, item.name].join(' ')"
          ajaxFilter="projektname"
          forceSelection
      />
      <label>App Name / Benutzername:</label>
      <input type="text" v-model="model.remoteDomain" />
      <label>Initkey / Passwort:</label>
      <input type="text" v-model="model.initKey" />
      <label>Event URL:</label>
      <input type="text" v-model="model.eventUrl" />
      <label>Warteschlange Bezeichnung:</label>
      <input type="text" v-model="model.importQueueName" />
      <label>Import Warteschlange:</label>
      <input type="checkbox" v-model="model.importQueueActive" class="justify-self-start">
      <label>UTF8 Clean:</label>
      <input type="checkbox" v-model="model.cleanUtf8Active" class="justify-self-start">
      <label>Ohne HTML Umwandlung:</label>
      <input type="checkbox" v-model="model.isHtmlTransformation" class="justify-self-start">
      <h2>Permissions</h2><div></div>
      <template v-for="(list, group) in permissions">
        <label>{{ group }}</label>
        <div>
          <div v-for="item in list" style="margin-bottom: 2px">
            <input type="checkbox" :value="item.key" v-model="model.permissions" /> {{ item.key }}
          </div>
        </div>
      </template>
    </div>
    <template #footer>
      <Button label="ABBRECHEN" @click="close" />
      <Button label="SPEICHERN" @click="save" :disabled="!model.name"/>
    </template>
  </Dialog>
</template>

<style scoped>

</style>