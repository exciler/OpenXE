<script setup lang="ts">
import type {Media} from "../../Model";

defineProps<{media: Media}>();
</script>

<template>
  <div>
  <iframe v-if="media.type === 'video'"
          class="media-youtube"
          :src="media.link + '?rel=0'"
          frameborder="0"
          allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture"
          allowfullscreen>
    </iframe>
    <img v-if="media.type === 'image'" class="media-image" :src="media.link"><img/>
  </div>
</template>