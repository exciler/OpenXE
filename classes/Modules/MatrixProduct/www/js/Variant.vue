<script setup>
import Modal from "@theme/vue/Modal.vue";
import AutoComplete from "primevue/autocomplete";
import Button from "primevue/button";
import Dialog from "primevue/dialog";
import Dropdown from "primevue/dropdown";
import {onMounted, ref} from "vue";
import axios from "axios";

const props = defineProps({
  articleId: String,
  variantId: String,
});
const emit = defineEmits(['save', 'close']);

const model = ref({});

onMounted(async () => {
  model.value = await axios.get('index.php?module=matrixprodukt&action=artikel&cmd=variantedit', {
    params: {...props}
  }).then(response => { return {...props, ...response.data}})
  articleSuggestions.value = [model.value.article];
})

async function save() {
  await axios.post('index.php?module=matrixprodukt&action=artikel&cmd=variantsave', {...props, ...model.value})
      .catch(error => alert(error.response.data))
      .then(response => {emit('save')});
}

const buttons = {
  abbrechen: () => emit('close'),
  speichern: save
}

const articleSuggestions = ref([]);
async function searchArticle(event) {
  const result = await axios.get('index.php?module=matrixprodukt&action=artikel&cmd=acarticles',
      {
        params: {
          query: event.query,
        }
      }).then(response => response.data)
  console.log(result);
  articleSuggestions.value = result;
}
</script>

<template>
  <Dialog visible header="Variante" style="width: 500px" @update:visible="emit('close')" class="p-fluid">
    <div class="flex" autofocus>
      <label class="col-md-3">Artikel</label>
      <div class="col-md-9">
        <AutoComplete v-model="model.variant"
                      :suggestions="articleSuggestions"
                      @complete="searchArticle"
                      :optionLabel="(item) => [item.nummer, item.name_de].join(' ')"
                      dataKey="id"
        /></div>
    </div>
    <div v-for="group in model.groups" class="flex">
      <label class="col-md-3">{{group.name}}:</label>
      <div class="col-md-9"><Dropdown v-model="group.selected" :options="group.options" optionLabel="name" optionValue="value" /></div>
    </div>
    <template #footer>
      <Button label="Abbrechen" @click="emit('close')" />
      <Button label="Speichern" @click="save"/>
    </template>
  </Dialog>
</template>

<style scoped>
.flex {
  display: flex;
  align-items: center;
}
</style>