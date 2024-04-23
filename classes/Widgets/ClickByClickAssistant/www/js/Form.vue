<script setup lang="ts">
import {Form, Field, ErrorMessage} from "vee-validate";
import InputRow from "./InputRow.vue";
import Spinner from "./Spinner.vue";
import axios from "axios";
import type {Page} from "../../Model";
import {ref} from "vue";
import {AlertErrorHandler} from "@res/js/ajaxErrorHandler";

const props = defineProps<{
  page: Page,
  storage: object
}>();
const emit = defineEmits<{
  submit: [storage: object, page?: Page]
}>();

const formError = ref<string|null>(null);

function onSubmit(values: object) {
  if(!props.page.submitType){
    throw new Error("Please define submitType in your JSON");
  }

  const data = {...props.storage, ...values};
  if(props.page.submitType === 'save') {
    emit('submit', data);
  } else if (props.page.submitUrl) {
    axios.post(props.page.submitUrl, data, {headers: {'Content-Type': 'application/x-www-form-urlencoded'}})
      .then((response) => {
        emit('submit', response.data.dataRequiredForSubmit, response.data.page);
      })
      .catch((error) => {
        if (error.response?.data?.error)
          formError.value = error.response.data.error;
        else
          AlertErrorHandler(error);
      })
  }
}
</script>

<template>
  <Form @submit="onSubmit" v-slot="{isSubmitting}" >
    <div class="flex-container" v-for="row in page.form" :key="row.id">
      <InputRow v-if="row.inputs && row.inputs.length > 0" :row="row" />
      <span v-for="button in row.surveyButtons ?? []" class="survey-button-container">
        <Field name="surveyChoice" type="checkbox" :value="button.value" />
        <label class="button button-secondary" :for="button.value">{{ button.title }}</label>
      </span>
    </div>
    <div class="flex-container" v-if="page.link">
      <div class="add-row">
        <a class="link" :href="page.link.link" >{{ page.link.title }}</a>
      </div>
    </div>
    <transition name="fade">
      <template>
        <ErrorMessage name="surveyChoice" />
      </template>
    </transition>
    <div v-if="formError" class="errorMsg">{{ formError }}</div>
    <button
        v-for="button in page.ctaButtons"
        class="button button-primary cta center"
        :type="button.action"
    >
      {{ button.title }}
      <Spinner v-if="isSubmitting" />
    </button>
  </Form>
</template>

<style scoped>

</style>