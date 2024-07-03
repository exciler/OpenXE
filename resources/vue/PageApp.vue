<script setup lang="ts">
import {useToast} from "primevue/usetoast";
import Toast from "primevue/toast";
import Notifications from "@modules/SystemNotification/www/js/Notifications.vue";

const toast = useToast();

</script>

<template>
  <Toast />
  <Notifications />
</template>
