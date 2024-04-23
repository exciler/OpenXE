<script setup lang="ts">
import "../css/click_by_click_assistant.css";
import {computed, onMounted, ref} from "vue";
import Form from "./Form.vue";
import Media from "./Media.vue";
import Pagination from "./Pagination.vue";
import type {Page} from "../../Model";

const props = defineProps<{
  pages: Array<Page>,
  allowClose: boolean,
  pagination: boolean
}>();
const emit = defineEmits(['close', 'completeStep']);

const model = ref(props.pages)
const activePage = ref(0);
const currentTransition = ref('');
const dataStorage = ref({});

const page = computed(() => props.pages[activePage.value]);

onMounted(function(){
  props.pages.forEach((page) => {
    const current = page.dataRequiredForSubmit;
    dataStorage.value = {...dataStorage.value, ...current}
  })
});

function nextPage(){
  activePage.value++;
  currentTransition.value = 'next';
}

function link(link: string)
{
  window.location.href = link;
}

function onFormSubmit(storeValues: object, addPage: Page) {
  dataStorage.value = storeValues;
  if (addPage !== undefined) {
    model.value.splice(activePage.value+1, 0, addPage);
  }

  nextPage();
}
</script>

<template>
  <div class="click-by-click-assistant">
    <div class="wrapper">
      <div class="container">
        <div v-if="allowClose" class="app-close-button" @click="emit('close')"></div>
        <transition :name="currentTransition" mode="out-in">
          <div class="page">
            <Media v-if="page.headerMedia" :media="page.headerMedia"></Media>
            <div class="page-content">
              <div v-if="!page.headerMedia && page.icon" class="header-icon" :class="page.icon"></div>
              <h2 v-html="page.headline"></h2>
              <h3 v-if="page.subHeadline" v-html="page.subHeadline"></h3>
              <template v-if="page.type === 'defaultPage'"
                        :data-pageIndex="activePage">
                <p class="page-text" v-if="page.text" v-html="page.text"></p>
                <div class="flex-container" v-if="page.link">
                  <div v-if="page.link" class="link">
                    <a class="link" :href="page.link.link" >{{ page.link.title }}</a>
                  </div>
                </div>
                <template v-for="button in page.ctaButtons">
                  <button v-if="button.action === 'next'"
                          class="button button-primary cta center"
                          @click="nextPage()">{{ button.title }}</button>
                  <button v-if="!button.link && button.action === 'close'"
                          class="button button-primary cta center"
                          @click="emit('close')">{{ button.title }}</button>
                  <button v-if="!button.link && button.action === 'completeStep'"
                          class="button button-primary cta center"
                          @click="emit('completeStep')">{{ button.title }}</button>
                  <button v-if="button.link && button.action === 'close'"
                          class="button button-primary cta center"
                          @click="link(button.link)">{{ button.title }}</button>
                </template>
              </template>
              <Form v-else-if="(page.type === 'form' || page.type === 'survey')"
                    :page="page"
                    :storage="dataStorage"
                    @submit="onFormSubmit"
              />
              <Pagination v-if="pagination" :count="pages.length" :active="activePage" />
            </div>
          </div>
        </transition>
      </div>
    </div>
  </div>
</template>

<style scoped>

</style>