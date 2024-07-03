<script setup lang="ts">
import {ref} from "vue";
import axios from "axios";
import {useToast} from "primevue/usetoast";
import Dialog from "primevue/dialog";

const toast = useToast();
const errorCount = ref(0);
const bodyEl = document.querySelector('body')
const referrerParams = {
  smodule: bodyEl?.dataset.module,
  sid: bodyEl?.dataset.id
}
const chatboxCounter = document.querySelector('.chatbox.counter');

const severityMap = new Map<string, string>([
    ['default', 'secondary'],
    ['notice', 'info'],
    ['warning', 'warn']
])

function poll() {
  axios.get('index.php?module=welcome&action=poll', {
    params: {
      invisible: document.visibilityState === 'hidden',
      cmd: 'messages',
      ...referrerParams
    }
  }).then(response => {
    errorCount.value = 0;
    response.data.forEach(item => {
      switch (item.event) {
        case 'logout':
          document.location = 'index.php';
          return;
        case 'chatbox':
          if (chatboxCounter) {
            chatboxCounter.textContent = item.message;
            chatboxCounter.classList.toggle('nachrichtenboxzahl_red', item.message);
          }
          return;
        case 'notification':
          toast.add({
            severity: severityMap.get(item.type) || item.type,
            summary: item.title,
            detail: item.message,
            life: item.priority ? 0 : 10000
          });
          return;
      }
    })
  }).catch(() => {
    errorCount.value++;
  }).finally(() => {
    setTimeout(poll, document.visibilityState === "visible" ? 5000 : 25000);
  })
}

poll();
</script>

<template>
  <Dialog modal :visible="errorCount > 2" header="Die Verbindung zum Server wurde unterbrochen" @update:visible="errorCount = 0">
    <p>
      Möglicherweise ist Ihr Computer nicht mehr mit dem Internet verbunden?
    </p>
    <p><small>Sobald die Verbindung wieder hergestellt werden kann, wird diese Meldung entfernt.</small></p>
  </Dialog>
</template>
