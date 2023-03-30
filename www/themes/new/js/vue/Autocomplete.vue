<!--
SPDX-FileCopyrightText: 2023 Andreas Palm

SPDX-License-Identifier: AGPL-3.0-only
-->

<script setup>
defineProps(['modelValue'])
defineEmits(['update:modelValue'])
</script>

<template>
  <input type="text" :value="modelValue" @input="$emit('update:modelValue', $event.target.value)" style="width: 100%" />
</template>
