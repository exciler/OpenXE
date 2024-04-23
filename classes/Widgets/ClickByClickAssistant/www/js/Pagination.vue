<script setup lang="ts">
const props = defineProps<{
  count: number,
  active: number
}>();
</script>

<template>
  <div class="app-pagination">
    <div v-for="i in count" :class="{'active': i === active}"></div>
  </div>
</template>
