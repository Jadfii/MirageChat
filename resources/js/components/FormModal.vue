<template>
  <div class="modal fade scale modal-alert" tabindex="-1" role="dialog" :id="id">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <form @submit.prevent="func">
        <div class="modal-content">
          <div class="modal-text">
            <div class="modal-text-inline">
              <div class="modal-header">
                <h5 class="modal-title">{{ title }}</h5>
              </div>
              <div class="modal-body form">
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
      </form>
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
          func: [Function],
        },
        methods: {
          handleSubmit: function(e) {
            //
          },
        }
    }
</script>
