<template>
  <div class="toggle-container ml-auto">
      <input @click="func" type="checkbox" class="toggle settings-toggle" :name="name" :id="name" :checked="user_options && user_options[name]">
      <label :for="name" class="toggle-label">{{ name || normalise || capitalise }}</label>
  </div>
</template>

<script>
    export default {
        mounted() {
            //
        },
        props: {
          name: [String],
          user_options: [Object],
          func: [Function],
        },
    }
</script>
