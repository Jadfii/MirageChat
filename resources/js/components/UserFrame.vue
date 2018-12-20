<template>
  <div v-bind:data-user_id="user.id" v-bind:data-username="user.username" class="user">
    <status-badge :profile="profile" :style="" v-bind:status="user.status" v-if="!message"></status-badge>
    <avatar :src="avatar" :popover="popover" :profile="profile" :size="profile ? '90px' : null" :margin="margin" :user="user"></avatar>
    <h6 v-if="text" class="username">{{ user.username }}</h6>
    <p v-if="message" class="chat-timestamp">{{ moment(message.time).calendar() }}</p>
    <span v-if="message && !message.read" class="badge badge-danger badge-hidden">New</span>
  </div>
</template>

<script>
    export default {
        mounted() {
          //
        },
        data: function() {
          return {
            size: 0,
          }
        },
        props: {
          user: [Array, Object],
          text: {
            default: false,
            type: Boolean
          },
          src: {
            default: '',
            type: [String]
          },
          message: {
            default: function () {
              return null
            },
            type: [Array, Object]
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
        methods: {
          decodeJSON() {
            console.log(this.user, JSON.parse(this.user));
            return JSON.parse(this.user);
          }
        },
        computed: {
          avatar: function() {
            if (this.src !== '') {
              return this.src;
            }
            return '/storage/avatars/'+this.user.id+'.png';
          },
        },
    }
</script>
