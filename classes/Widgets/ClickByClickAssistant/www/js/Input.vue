<script setup lang="ts">
import {ref} from "vue";
import {ErrorMessage, useField, useFormValues} from "vee-validate";
import {ISchema, string} from "yup";
import Dropdown from "primevue/dropdown";
import InputText from "primevue/inputtext";

const props = defineProps<{
  type: string,
  validation: boolean,
  name: string,
  label: string,
  customErrorMsg?: string,
  options?: Array<any>,
  connectedTo?: string,
  value?: string | number
}>();

const inputType = ref(props.type);
const {value: fieldValue, handleChange, handleBlur, meta} = useField(props.name, validate);
const formValues = useFormValues();

function togglePasswordVisibility(){
  inputType.value = inputType.value === 'password' ? 'text' : 'password';
}

async function validate(value: string) {
  if (!props.validation)
    return true;

  let msg: string, schema: ISchema<any>;
  switch (props.type) {
    case 'select':
      return true;
    case 'email':
      msg = props.customErrorMsg || "Adresse nicht gültig";
      schema = string().required(msg).email(msg);
      break;
    case 'password':
      if (props.connectedTo) {
        schema = string().oneOf([formValues.value[props.connectedTo]], props.customErrorMsg || "Bitte wiederholen Sie das Passwort");
      } else {
        schema = string().required(props.customErrorMsg || "Mindestens vier Zeichen")
            .min(4, props.customErrorMsg || "Mindestens vier Zeichen");
      }
      break;
    default:
      msg = props.customErrorMsg || "Mindestens zwei Zeichen";
      schema = string().required(msg).min(2, msg);
      break;
  }
  try {
    await schema.validate(value);
  } catch (err) {
    return err.errors;
  }
  return true;
}
</script>

<template>
  <div
      class="app-input"
      :class="{'input-error': meta.validated && !meta.valid, 'select': type ==='select'}"
  >
    <select
        v-if="type === 'select'"
        v-model="fieldValue"
        :name="name"
        style="width: 100%"
    >
      <option v-for="opt in options" :key="opt.value" :value="opt.value">{{opt.text}}</option>
    </select>
    <input
        v-else
        v-model="fieldValue"
        :class="{'hasValue': fieldValue?.length > 0 }"
        :name="name"
        :type="inputType"
    />
    <div
        v-if="type === 'password'"
        class="reveal"
        @click="togglePasswordVisibility"
    />
    <label :for="name">
      {{ label }}
      <span v-if="validation">(Pflichtfeld)</span>
    </label>
    <transition name="fade">
      <ErrorMessage
          as="div"
          class="input-error"
          :name="name"
      />
    </transition>
  </div>
</template>

<style scoped>

</style>