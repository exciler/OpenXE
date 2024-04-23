<script setup lang="ts">
import Input from "./Input.vue";
import {InputRow} from "../../Model";

const props = defineProps<{row: InputRow}>();
</script>

<template>
  <div class="app-row-container">
    <div class="app-input-row" :class="{'reduced-width': row.inputs.length === 1 }">
      <div class="app-row-valid"></div>
      <Input
          v-for="(input, inputIndex) in row.inputs"
          :type="input.type"
          :validation="input.validation"
          :customErrorMsg="input.customErrorMsg"
          :name="input.name"
          :label="input.label"
          :value="input.value"
          :connectedTo="input.connectedTo"
          :options="input.options"
          :key="inputIndex"
      />
    </div>
    <div v-if="row.link" class="add-row"><a class="link" :href="row.link.link" >{{ row.link.title }}</a></div>
  </div>
</template>

<style scoped>

</style>