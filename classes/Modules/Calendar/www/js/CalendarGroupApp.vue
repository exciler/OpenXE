<script setup lang="ts">
import CalendarGroupDialog from "./CalendarGroupDialog.vue";
import {ref} from "vue";
import axios from "axios";
import {reloadDataTables} from "@res/js/jqueryBridge";
import {AlertErrorHandler} from "@res/js/ajaxErrorHandler";

const id = ref(null);

document.querySelector('#page_container')?.addEventListener('click', async (ev) => {
  const vueActionEl = ev.target.closest('.vue-action');
  if (vueActionEl === null)
    return;

  switch (vueActionEl.dataset.action) {
    case 'create':
      id.value = 0;
      break;
    case 'edit':
      id.value = parseInt(vueActionEl.dataset.id) || null;
      break;
    case 'delete':
      const answer = confirm('Wirklich löschen?');
      if (answer)
        await axios.post('index.php?module=kalender&action=gruppendelete', {
          id: parseInt(vueActionEl.dataset.id)
        }).then(reloadDataTables)
          .catch(AlertErrorHandler);
  }
})

function onClose()  {
  id.value=null;
}
function onChange() {
  reloadDataTables();
  onClose();
}

</script>

<template>
  <CalendarGroupDialog v-if="id !== null" :id="id" @close="onClose" @change="onChange" />
</template>

<style scoped>

</style>