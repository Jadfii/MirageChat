<template>
  <div class="modal fade scale modal-alert" tabindex="-1" role="dialog" :id="id" v-bind:data-item="item">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-text">
          <div class="modal-text-inline">
            <div class="modal-header">
              <h5 class="modal-title">{{ title }}</h5>
            </div>
            <div class="modal-body">
              <slot name="body"></slot>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <div class="modal-footer-inner">
            <slot name="footer"></slot>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
    export default {
        mounted() {
            //
        },
        props: {
          title: [String],
          message: [String],
          id: [String],
          item: [Number, String],
          func: [Function],
        },
        methods: {
          handleSubmit: function(e) {
            this.$eventHub.$emit('modal:submit');
          },
        }
    }
</script>
