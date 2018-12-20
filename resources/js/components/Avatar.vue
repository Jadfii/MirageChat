<template>
  <img
  class="float-left avatar"
  :src="src"
  :style="[profile || size !== '30px' ? {'height': size, 'width': size,} : null]"
  :class="{ 'mr-0': !margin }"
  :data-toggle="popover ? 'popover' : null"
  :data-user_id="user.id"
  data-placement="right"
  >
</template>

<script>
    export default {
        mounted() {
            //
        },
        props: {
          user: [Array, Object],
          src: [String],
          size: {
            default: '30px',
            type: String
          },
          margin: {
            default: true,
            type: [Boolean]
          },
          profile: {
            default: false,
            type: [Boolean],
          },
          popover: {
            default: false,
            type: [Boolean],
          },
        },
    }
</script>
