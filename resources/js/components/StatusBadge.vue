<template>
  <span class="status-badge"
  :class="[ status ? `status-badge--${status}` : '',
  size ? `status-badge--${size}` : '',
  theme ? `status-badge--${theme}` : '' ]">
    <slot></slot>
    <sup
    class="status-badge__content rounded-circle"
    data-toggle="tooltip"
    data-placement="top"
    :title="status_format"
    :data-original-title="status_format"
    :data-offset-tooltip="offset"
    ></sup>
  </span>
</template>

<script>

    export default {
        mounted() {
          //
        },
        computed: {
          status_format: function() {
            return this.status.charAt(0).toUpperCase() + this.status.slice(1);
          },
        },
        props: {
          status: [String],
          size: [String],
          theme: {
            default: 'light',
            type: String,
          },
          offset: [String],
        },
    }
</script>
