<!--
SPDX-FileCopyrightText: 2023 Andreas Palm

SPDX-License-Identifier: AGPL-3.0-only
-->

<script setup>
const props = defineProps({
  height: String,
  width: String,
  title: String,
  buttons: Object
})

const emit = defineEmits(['close'])
</script>

<template>
  <transition name="modal">
  <div class="modal-mask">
    <div class="modal-wrapper">
      <div class="modal-container ui-dialog ui-widget ui-widget-content ui-corner-all ui-front ui-draggable" v-bind:style="{ 'min-height': props.height, width: props.width }">
        <div class="ui-dialog-titlebar ui-widget-header ui-corner-all ui-helper-clearfix ui-draggable-handle">
          <span class="ui-dialog-title">{{ props.title }}</span>
          <button class="ui-button ui-widget ui-state-default ui-corner-all ui-button-icon-only ui-dialog-titlebar-close" type="button" role="button" title="Close" @click="emit('close')">
            <span class="ui-button-icon-primary ui-icon ui-icon-closethick"></span>
            <span class="ui-button-text">Close</span>
          </button>
        </div>
        <div class="ui-dialog-content"><slot></slot></div>
        <div class="ui-dialog-buttonpane ui-widget-content ui-helper-clearfix">
          <div class="ui-dialog-buttonset">
            <button v-for="(val,key) in buttons" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only" type="button" role="button" @click="val">
              <span class="ui-button-text">{{ key.toUpperCase() }}</span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </div></transition>
</template>

<style scoped>
.modal-mask {
  position: fixed;
  z-index: 999;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0, 0, 0, 0.5);
  display: table;
  transition: opacity 0.3s ease;
}

.modal-wrapper {
  display: table-cell;
  vertical-align: middle;
}

.modal-container {
  margin: 0px auto;
  transition: all 0.3s ease;
  position: relative;
  max-width: 90%;
  max-height: 90%;
}

.modal-enter {
  opacity: 0;
}

.modal-leave-active {
  opacity: 0;
}

.modal-enter .modal-container,
.modal-leave-active .modal-container {
  -webkit-transform: scale(1.1);
  transform: scale(1.1);
}
</style>