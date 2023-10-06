<!--
SPDX-FileCopyrightText: 2023 Andreas Palm

SPDX-License-Identifier: LicenseRef-EGPL-3.1
-->

<script setup>
import AddGlobalToArticle from "./AddGlobalToArticle.vue"
import {ref} from 'vue'
import GroupEdit from "./GroupEdit.vue";
import OptionEdit from "./OptionEdit.vue";
import axios from "axios";
import Variant from "./Variant.vue";

const props = defineProps({
  updateTables: Function
})
const model = ref(null);

document.getElementById('main').addEventListener('click', async (ev) => {
  console.log(ev);
  const target = ev.target;
  if (!target || !target.classList.contains('vueAction'))
    return;
  const ds = target.dataset;
  if (ds.action.endsWith('Delete')) {
    const cnf = confirm('Wirklich löschen?');
    if (!cnf)
      return;
    let url;
    switch (ds.action) {
      case 'groupDelete':
        url = ds.articleId > 0
            ? 'index.php?module=matrixprodukt&action=artikel&cmd=groupdelete'
            : 'index.php?module=matrixprodukt&action=list&cmd=delete';
        await axios.post(url, {groupId: ds.groupId});
        break;
      case 'optionDelete':
        url = ds.articleId > 0
            ? 'index.php?module=matrixprodukt&action=artikel&cmd=optiondelete'
            : 'index.php?module=matrixprodukt&action=optionenlist&cmd=delete';
        await axios.post(url, {optionId: ds.optionId});
        break;
      case 'variantDelete':
        url = 'index.php?module=matrixprodukt&action=artikel&cmd=variantdelete';
        await axios.post(url, {variantId: ds.variantId});
        break;
    }
    props.updateTables();
    return;
  }

  model.value = ds;
});
</script>

<template>
  <template v-if="model">
    <AddGlobalToArticle v-if="model.action === 'addGlobalToArticle'" @close="model=null" @save="updateTables(); model=null;" />
    <GroupEdit v-else-if="model.action === 'groupEdit'" v-bind="model" @close="model=null" @save="updateTables(); model=null;" />
    <OptionEdit v-else-if="model.action === 'optionEdit'" v-bind="model" @close="model=null" @save="updateTables(); model=null;" />
    <Variant v-else-if="model.action === 'variantEdit'" v-bind="model" @close="model=null" @save="updateTables(); model=null;" />
  </template>
</template>
