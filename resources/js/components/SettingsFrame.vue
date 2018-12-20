<template>
  <div class="heading-section" :class="{ 'flex-column': false }">
      <div class="heading-desc" v-if="false">
          <h6 v-if="setting.type == 0" class="heading-subtitle">{{ setting.name }}</h6>
          <h4 v-else class="setting-name">{{ setting.name }}</h4>
          <p v-if="setting.desc.length > 0" class="heading-text">{{ setting.desc }}</p>
      </div>
      <slot></slot>
  </div>
</template>

<script>
    export default {
        mounted() {
            //
        },
        props: {
          setting: [Array, Object],
        },
    }
</script>
