<script setup lang="ts">
import axios from "axios";
import ClickByClickAssistant from "../../../../Widgets/ClickByClickAssistant/www/js/ClickByClickAssistant.vue";
import {AlertErrorHandler} from "@res/js/ajaxErrorHandler";
import {ref} from "vue";

const model = ref(null);

document.querySelectorAll<HTMLElement>('.createbutton').forEach((el) => {
  el.addEventListener('click', onClickCreate)
});

const autoOpen = document.querySelector<HTMLElement>('.autoOpenModule');
if (autoOpen && autoOpen.dataset.module)
  handleCreate(autoOpen.dataset.module);

function onClickCreate(event: Event) {
  const module = (event.currentTarget as HTMLElement).dataset.module;
  if (module)
    handleCreate(module);
}

function handleCreate(module: String) {
  axios.post('index.php?module=zahlungsweisen&action=create&cmd=getAssistant', {paymentmodule: module},
      {headers: {'Content-Type': 'application/x-www-form-urlencoded'}})
      .then(function (response) {
        if (response.data.pages === undefined && response.data.location !== undefined) {
          window.location = response.data.location;
          return;
        }
        model.value = response.data.pages;
      })
      .catch(AlertErrorHandler);
}
</script>

<template>
  <ClickByClickAssistant v-if="model" :pages="model" pagination allowClose @close="model=null" />
</template>

<style scoped>

</style>