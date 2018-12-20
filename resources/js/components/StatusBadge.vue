<template>
  <span
  class="status-badge"
  v-bind:class="status"
  :style="profile ? {'height': '20px','width': '20px','top': '68px','left': '68px',} : null"
  data-toggle="tooltip"
  data-placement="top"
  v-bind:title="status | capitalise"
  v-bind:data-original-title="status | capitalise"
  ></span>
</template>

<script>
    export default {
        mounted() {
          //
        },
        props: {
          status: [String],
          size: {
            default: '13px',
            type: String
          },
          profile: {
            default: false,
            type: [Boolean],
          },
        },
    }
</script>
