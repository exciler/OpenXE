<script setup>
import FileUpload from "primevue/fileupload";

function onUpload(evt) {
  const data = JSON.parse(evt.xhr.responseText);
  if (data.url !== undefined)
    window.location.href = data.url;
}
</script>

<template>
  <FileUpload mode="basic" chooseLabel="&#10010; Neuer Eintrag mit Vorlagendatei" name="jsonfile"
              url="index.php?module=importvorlage&action=list&cmd=jsonupload"
              auto
              @upload="onUpload">
    <template #uploadicon><span></span></template>
  </FileUpload>
</template>
