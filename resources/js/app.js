/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require('./bootstrap');
import VuejsDialog from 'vuejs-dialog';
import VTooltip from 'v-tooltip';
import { focus } from 'vue-focus';
window.moment = require('moment');
window.Vue = require('vue');
window.autosize = require('autosize');
window.isOnline = require('is-online');
window.away = require('away');
window.Push = require('push.js');

/**
 * Load custom Vue components and register them from their respective files
 */
Vue.component('modal', require('./components/Modal.vue'));
Vue.component('form-modal', require('./components/FormModal.vue'));
Vue.component('status-badge', require('./components/StatusBadge.vue'));
Vue.component('avatar', require('./components/Avatar.vue'));
Vue.component('user-frame', require('./components/UserFrame.vue'));
Vue.component('settings-frame', require('./components/SettingsFrame.vue'));
Vue.component('message-frame', require('./components/MessageFrame.vue'));

/**
 * Declare Vue filters
 */
 // Filter to capitalise strings
Vue.filter("capitalise", function(value) {
  if (!value) {
    return "";
  }
  return value.toLowerCase().split(" ").map((s) => s.charAt(0).toUpperCase() + s.substring(1)).join(" ");
});

// Filter to replace underscores with spaces
Vue.filter("normalise", function(value) {
  if (!value) {
    return "";
  }
  return value.replace("_", " ");
});

// Filter to return a integer value for data printed using Vue
Vue.filter("parse_int", function(value) {
  return parseInt(value);
});

// Filter to convert mentions into readable HTML
Vue.filter("mention", function(content) {
  if (this.hasMention(content)) {
    content = content.replace(content.substring(content.lastIndexOf('<@'), content.lastIndexOf('>')), '');

  }
});

// Global event bus
Vue.prototype.$eventHub = new Vue();

// Allow use of moment in Vue
Vue.prototype.moment = moment;

// Use VueDialog
Vue.use(VuejsDialog, {
  okText: 'Confirm',
  cancelText: 'Cancel',
  backdropClose: true,
});

// Use v-tooltip
Vue.use(VTooltip);

// Pass initial state data into a global variable inside app.js file
var initialState = window.__INITIAL_STATE__;

var throttle = _.throttle((func) => {
  func();
}, 5000, { 'trailing': false });

/**
 * Declare Vue instance and attach it to the app container.
 * Reactive data and their default values declared.
 */
const App = new Vue({
  el: "#app",
  directives: {
    focus: focus
  },
  data: {
     current_user: initialState.current_user,
     users: initialState.users,
     online_users: [],
     channels: initialState.channels,
     messages: initialState.messages,
     states: {
        loaded: false,
        offline: false,
        userlist: true,
        sidebar: true,
        account: {
          avatar: initialState.avatar,
          avatar_upload: initialState.avatar,
        },
        settings: {
          display: false,
          account_edit: false,
          active_tab: 'account_details',
        },
        current_channel: [],
        modal: {
          item: null,
          title: '',
          message: '',
        },
        popover: {
          open: false,
          item: {},
        },
        typing: {
          active: false,
          message: '',
          focused: false,
          autocomplete: false,
          suggested_mentions: [],
          selected_mention: 0,
          channel: [],
          users: [],
          timer: null,
        },
        messages: {
          scroll: {
            position: null,
            height: null,
            outer_height: null,
          }
        },
     },
     window: {
       width: window.innerWidth,
       height: window.innerHeight,
     },
  },
  created: function() {
    window.axios.defaults.headers.common['Authorization'] = 'Bearer: ' + this.current_user.api_token;
    window.axios.defaults.params = {}
    window.axios.defaults.params['api_token'] = this.current_user.api_token;

    var App_this = this;
    _.delay(function() {
      App_this.states.loaded = true;
    }, 4000);

    // Set current channel to first channel in list
    if (this.channels.length > 0) {
      this.states.current_channel = this.channels[0].channel_id;
    }

    // Set reactive height/width properties in Vue
    window.addEventListener('resize', () => {
      this.window.width = window.innerWidth;
      this.window.height = window.innerHeight;
    });

    // For reactivity/user experience - if window is small, hide user list
    if (this.window.width <= 768) {
      this.states.userlist = false;
    } else {
      this.states.userlist = true;
    }

    if (this.total_unread > 0) {
      document.title = '(' + this.total_unread + ') ' + document.title;
    }

    this.$eventHub.$on('tooltip:toggle', function(data) {
      App_this.togglePopover(data);
    });

    $(document).on("click", function(e) {
      if (App_this.states.popover.open) {
        if (!$(".v-tooltip.popover").is(e.target) && $(".v-tooltip.popover").data("toggle") !== "popover" && $(".v-tooltip.popover").has(e.target).length === 0) {
          App_this.$eventHub.$emit('tooltip:toggle', {
            'show': false,
          });
        }
      }
    });

    this.scrollBottomMessages();
  },
  watch: {
    messages: function() {
      var App_this = this;
      var new_message = this.messages[this.messages.length - 1];
      Vue.nextTick(function() {
        if (App_this.messages_bottom && new_message.channel_id == App_this.states.current_channel) {
          App_this.scrollBottomMessages();
        }
      });
    },
    'states.settings.display': function() {
      if (this.states.settings.account_edit) {
        this.states.settings.account_edit = false;
      }
    },
    'states.current_channel': function() {
      var App_this = this;
      // Scroll to bottom of message container on channel change
      Vue.nextTick(function() {
        $('#messages').scrollTop(1E10);
        App_this.states.typing.message = '';
      });
      if (this.unread[this.states.current_channel] > 0) {
        this.readChannel(this.states.current_channel);
      }
    },
    'states.typing.focused': function() {
      var App_this = this;
    },
    'states.modal.item': function() {
      autosize($('textarea'));
    },
    'window.width': function() {
      if (this.window.width <= 768) {
        this.states.userlist = false;
      } else {
        this.states.userlist = true;
      }
    },
    total_unread: function(val) {
      var title = document.title.replace(/ *\([^)]*\) */g, "");
      switch (val) {
        case 0:
          document.title = title;
          break;
        default:
          document.title = '(' + val + ') ' + title;
          break;
      }
    },
    'states.typing.message': function(val) {
      var App_this = this;
      this.startAutocomplete(val);
    },
  },
  computed: {
    messages_bottom: function() {
      return this.states.messages.scroll.outer_height + this.states.messages.scroll.position == this.states.messages.scroll.height;
    },
    // Computed to sort users by username
    users_sorted: function() {
      return _.sortBy(this.users, ['username']);
    },
    // Computed data to seperate users by their status
    usersStatus: function() {
      var users = this.active_users;
      return {
        "online": users.filter(function(user) {
          return user.status !== "offline";
        }),
        "offline": users.filter(function(user) {
          return user.status == "offline";
        }),
      }
    },
    // Return true/false if user is logged in
    logged_in: function() {
      return _.has(this.current_user, 'id');
    },
    // Get channel model for current channel
    active_channel: function() {
      return _.find(this.channels, {'channel_id': this.states.current_channel});
    },
    // Get messages from current channel
    active_messages: function() {
      return this.findMessages(this.states.current_channel);
    },
    // Get users with access to current channel
    active_users: function() {
      if (this.active_channel) {
        return this.getMembers(this.states.current_channel);
      } else {
        return this.users_sorted;
      }
    },
    // Set the message for the current typing users
    active_typing: function() {
      var App_this = this;
      var typing = this.states.typing.users
        .filter(typing => typing.channel_id == this.states.current_channel)
        .map(function(obj) {
          return App_this.findUser(obj.user_id).username;
        });
      switch (typing.length) {
        case 0:
          return '';
          break;
        case 1:
          return '<b>' + typing.pop() + '</b> is typing...';
          break;
        default:
          return '<b>' + _.initial(typing).join('</b>, <b>') + '</b> and <b>' + typing.pop() + '</b> are typing...';
          break;
      }
    },
    // Get number of unread messages
    unread: function() {
      var App_this = this;
      var channels = {};
      _.each(App_this.channels, function(value) {
        channels[value.channel_id] = _.filter(App_this.messages, function(obj) {
          return obj.channel_id == value.channel_id && !obj.read;
        }).length;
      });
      return channels;
    },
    // Get total number of unread messages
    total_unread: function() {
      var App_this = this;
      var total = 0;
      _.each(App_this.unread, function(value) {
        total += value;
      });
      return total;
    },
    // Get tooltip location
    tooltip_location: function() {
      this.$forceUpdate();
      if (this.states.popover.item.pos) {
        var x = this.states.popover.item.pos.x;
        if (this.states.popover.item.placement == 'right') {
          x+= this.states.popover.item.width;
        }
        return {
          'transform': 'translate3d(' + x + 'px, ' + this.states.popover.item.pos.y + 'px, 0px)',
        };
      } else {
        return {};
      }
    }
  },
  methods: {
    // Scroll to bottom of messages container
    scrollBottomMessages: function() {
      var scrollTimer = setInterval(function() {
        $('#messages').scrollTop(1E10);
      }, 100);

      setTimeout(function() {
        clearInterval(scrollTimer);
      }, 1000);
    },
    // Method to toggle popover state
    togglePopover: function(data) {
      var App_this = this;
      if (data.user) {
        data.user = App_this.findUser(data.user);
      }
      if (data.show) {
        setTimeout(function() {
          App_this.states.popover.open = data.show;
          App_this.states.popover.item = data;
        }, 100);
      } else {
        App_this.states.popover.open = data.show;
        App_this.states.popover.item = data;
      }
    },
    // Method to find user from user_id
    findUser: function(user_id) {
      return _.find(this.users, {'id': user_id});
    },
    // Method to find message from message_id
    findMessage: function(message_id) {
      return _.find(this.messages, {'message_id': message_id});
    },
    // Method to find channel from channel_id
    findChannel: function(channel_id) {
      return _.find(this.channels, {'channel_id': channel_id});
    },
    // Method to find messages from channel
    findMessages: function(channel_id) {
      return this.messages.filter(message => message.channel_id == channel_id);
    },
    // Method to get members as array from channel
    getMembers: function(channel_id) {
      var App_this = this;
      var channel = this.findChannel(channel_id);
      if (typeof channel == 'undefined') {
        return [];
      } else {
        return _.sortBy(JSON.parse(channel.members).map(user => App_this.findUser(user)), ['username']);
      }
    },
    // Method to determine if user is member of channel
    isMember: function(user_id, channel) {
      var App_this = this;
      if (typeof channel == 'undefined') {
        return false;
      }
      return typeof _.find(App_this.getMembers(channel.channel_id), {'id': user_id}) !== 'undefined';
    },
    // Method to get number of unread messages in channel
    getUnread: function(channel_id) {
      return this.messages.filter(function(message) {
        return message.channel_id == channel_id && !message.read;
      }).length;
    },
    // Method to read a message
    readMessage: function(message_id) {
      var App_this = this;
      axios.put('/api/messages/' + message_id + '/read')
       .then(function (response) {
         console.log(response);
         if (App_this.messages.filter(obj => obj.message_id == response.data.message_id).length > 0) {
           Vue.set(App_this.messages, App_this.messages.findIndex(obj => obj.message_id == message_id), response.data);
         }
         return true;
       })
       .catch(function (error) {
         console.log(error);
         return false;
      });
    },
    // Method to read all messages in a channel
    readChannel: function(channel_id) {
      var App_this = this;
      axios.put('/api/channels/' + channel_id + '/read')
       .then(function (response) {
         console.log(response);
         _.each(response.data, function(value) {
           Vue.set(App_this.messages, App_this.messages.findIndex(obj => obj.message_id == value.message_id), value);
         });
       })
       .catch(function (error) {
         console.log(error);
      });
    },
    // Method to check if item in array is the last
    isLast: function(index, array) {
      return index == array.length - 1;
    },
    // Method to check if tab is currently active
    isActiveTab: function(tab) {
      window.this = this;
      return window.this.states.settings.active_tab == tab;
    },
    // Check if message was sent within 1 hour of the last message
    isCloseMessage: function(index) {
      if (index > this.active_messages.length - 1 || !this.active_messages[index] || index == 0) {
        return false;
      }
      window.index_before = index - 1;
      return (this.active_messages[index].user_id == this.active_messages[window.index_before].user_id && moment.duration(moment(this.active_messages[index].created_at).diff(moment(this.active_messages[window.index_before].created_at))).asHours() < 1);
    },
    // Functions to send a message
    sendMessage: function(e) {
      var App_this = this;
      if (!e.shiftKey && !this.states.typing.autocomplete) {
        e.preventDefault();
        var content = $(e.target).closest(".chat-box").find("textarea");
        var channel = this.states.current_channel;

        if (typeof content.val() !== 'undefined' && content.val().trim() !== '') {
          axios.post('/api/channels/' + channel, {
             content: content.val(),
          })
          .then(function (response) {
            autosize.update(content);
            content.focus();

            App_this.states.typing.active = false;
            App_this.states.typing.message = '';
            App_this.states.typing.suggested_mentions = [];
            App_this.states.typing.selected_mention = 0;
            App_this.states.typing.users.splice(App.states.typing.users.findIndex(user => user.user_id == App_this.current_user.id), 1);

            window['channel_'+App_this.states.typing.channel.toString()].whisper('typing', {
              user_id: App_this.current_user.id,
              channel_id: App_this.states.typing.channel,
              typing: false,
            });
            console.log(response);
          })
          .catch(function (error) {
             console.log(error);
             App_this.$dialog.alert({
               title: 'Your message is too long',
               body: error.response.data.errors.content[0],
             }, {
               animation: 'none',
               okText: 'Okay',
             })
             .then(function (dialog) {
               //
             });
          });
        }
      }
    },
    // Function to enable user to edit a message of theirs
    editMessage: function(e) {
      var message_id = parseInt($(e.target).closest(".chat-message").attr("data-message_id"));
      if (this.states.modal.item && this.states.modal.item.hasOwnProperty("content")) {
        var modal = $('#message-edit-modal');
        var content = modal.find("textarea").val().trim();

        if (content !== this.states.modal.item.content) {
          axios.put('/api/messages/' + this.states.modal.item.message_id, {
             content: content,
          })
           .then(function (response) {
             console.log(response);
             modal.modal('hide');
           })
           .catch(function (error) {
             console.log(error);
          });
        } else {
          modal.modal('hide');
        }
      } else {
        this.states.modal.item = this.findMessage(message_id);
      }
    },
    // Function to delete a message
    deleteMessage: function(e) {
      var message_id = parseInt($(e.target).closest(".chat-message").attr("data-message_id"));

      this.$dialog.confirm({
        title: 'Delete message',
        body: 'Are you sure you want to delete the message "' + this.findMessage(message_id).content + '"?',
      }, {
        loader: true,
        animation: 'none',
      })
      .then(function (dialog) {
        axios.delete('/api/messages/' + message_id)
         .then(function (response) {
           console.log(response);
           dialog.close();
         })
         .catch(function (error) {
           console.log(error);
        });
      })
      .catch(function () {
        //
      });
    },
    // Method to create a channel
    createChannel: function(e) {
      var App_this = this;
      var formData = $(e.target).serializeArray();
      var members = [];
      Object.keys(formData.filter(data => data.name == 'members')).forEach(function (key) {
        members.push(formData.filter(data => data.name == 'members')[key].value);
      });

      var modal = $('#channel-create-modal');

      axios.post('/api/channels', {
         name: _.find(formData, {'name': 'channel_name'}).value,
         members: members,
      })
       .then(function (response) {
         console.log(response);
         modal.modal('hide');
         modal.find('form').trigger("reset");
         App_this.states.current_channel = response.data.channel_id;
       })
       .catch(function (error) {
         console.log(error);
      });
    },
    // Method to edit a channel
    editChannel: function(e) {
      var formData = $(e.target).serializeArray();
      var members = [];
      Object.keys(formData.filter(data => data.name == 'members')).forEach(function (key) {
        members.push(formData.filter(data => data.name == 'members')[key].value);
      });

      var modal = $('#channel-edit-modal');

      axios.put('/api/channels/' + this.states.modal.item.channel_id, {
         name: _.find(formData, {'name': 'channel_name'}).value,
         members: members,
      })
       .then(function (response) {
         console.log(response);
         modal.modal('hide');
       })
       .catch(function (error) {
         console.log(error);
      });
    },
    // Method to delete channel
    deleteChannel: function(e) {
      var App_this = this;
      var modal = $('#channel-edit-modal');

      modal.modal('hide');

      this.$dialog.confirm({
        title: 'Delete channel',
        body: 'Are you sure you want to delete the channel "' + App_this.states.modal.item.name + '"?',
      }, {
        loader: true,
        animation: 'fade',
      })
      .then(function (dialog) {
        axios.delete('/api/channels/' + App_this.states.modal.item.channel_id)
         .then(function (response) {
           console.log(response);
           dialog.close();
           _.remove(App_this.messages, function(obj) {
             obj.channel_id == App_this.states.modal.item.channel_id;
           })
         })
         .catch(function (error) {
           console.log(error);
        });
      })
      .catch(function () {
        //
      });
    },
    // Method to edit user account
    editAccount: function(e) {
      var object = new FormData();
      // Remove empty values
      var formData = $(e.target).serializeArray().filter(function (obj) {
        return obj.value !== '';
      });
      var App_this = this;

      // Remove values that do not differ from the current user account
      Object.keys(App_this.current_user).forEach(function (key) {
        formData = formData.filter(function (obj) {
          return !(obj.name == key && obj.value == App_this.current_user[key]);
        });
      });

      var data = {};
      Object.keys(formData).forEach(function (key) {
        data[formData[key].name] = formData[key].value;
        object.append(formData[key].name, formData[key].value);
      });

      var avatar = null;
      if ($(e.target).find('input[type="file"]').val()) {
        avatar = $(e.target).find('input[type="file"]')[0].files[0];
        object.append('avatar', avatar);
      }

      if (Object.keys(data).length > 0 || avatar !== null) {
        axios.post('/api/users/' + this.current_user.id, object, {
          headers: {
            'content-type': 'multipart/form-data'
          }
        })
         .then(function (response) {
           console.log(response);
           App_this.current_user = response.data;
         })
         .catch(function (error) {
           console.log(error);
           App_this.$dialog.alert({
             title: error.response.data.message,
             body: error.response.data.errors[0][0],
           }, {
             animation: 'none',
             okText: 'Okay',
           })
           .then(function (dialog) {
             //
           });
        });
      }

      this.states.settings.account_edit = false;
    },
    // Handle avatar change on account edit page
    editAvatar: function(e) {
      var avatar = $(e.target)[0].files[0];
      this.states.account.avatar_upload = URL.createObjectURL(avatar);
    },
    // Method to update user status
    updateStatus: function(status) {
      var App_this = this;
      axios.put('/api/users/' + App_this.current_user.id + '/' + status)
       .then(function (response) {
         console.log(response);
         App_this.current_user = response.data;
       })
       .catch(function (error) {
         console.log(error);
      });
    },
    // Method to initiate typing indicator
    startTyping: function(e) {
      var App_this = this;
      this.states.typing.autocomplete = this.hasAutocomplete(this.states.typing.message) !== false;

      // If enter key pressed
      if (e.which == 13 && !this.states.typing.autocomplete) {
        this.sendMessage(e);
        return;
      } else if ((e.which == 13 || e.which == 9 || (e.which >= 38 && e.which <= 40)) && this.states.typing.autocomplete) { // If enter or arrow keys
        this.handleAutocomplete(e);
        return;
      }

      if (!App_this.states.typing.active) {
        App_this.states.typing.channel = App_this.states.current_channel;
        App_this.states.typing.users.push({
          'user_id': App_this.current_user.id,
          'channel_id': App_this.states.typing.channel,
        });
        App_this.states.typing.active = true;
        window['channel_'+App_this.states.typing.channel.toString()].whisper('typing', {
          user_id: App_this.current_user.id,
          channel_id: App_this.states.typing.channel,
          typing: true,
        });
      } else {
        clearTimeout(App_this.states.typing.timer);
      }

      App_this.states.typing.timer = setTimeout(function() {
        App_this.states.typing.users.splice(App.states.typing.users.findIndex(user => user.user_id == App_this.current_user.id), 1);

        window['channel_'+App_this.states.typing.channel.toString()].whisper('typing', {
          user_id: App_this.current_user.id,
          channel_id: App_this.states.typing.channel,
          typing: false,
        });
        App_this.states.typing.timer = null;
        App_this.states.typing.active = false;
      }, 5000);
    },
    // Method to check if message contains a mention
    hasMention: function(content) {
      var App_this = this;
      var match = content.match(/\<@(.*?)\>/);
      if (match !== null) {
        _.each(match, function(val) {
          if (!App_this.findUser(val)) {
            return false;
          }
        });
        return true;
      } else {
        return false;
      }
    },
    // Method to replace mentions with usernames
    getMentions: function(content) {
      var App_this = this;
      var users = [];
      var match = content.match(/\<@(.*?)\>/);
      if (this.hasMention(content)) {
        _.each(match, function(val) {
          if (typeof App_this.findUser(val) !== 'undefined') {
            users.push(App_this.findUser(val));
          }
        });
        return users;
      } else {
        return [];
      }
    },
    // Method to check if message initiates autocomplete
    hasAutocomplete: function(val) {
      var App_this = this;
      if (val.length > 0 && val.includes(' ')) {
        var words = val.split(' ');
        var word = words[words.length - 1];
        if (word.charAt(0) == '@') {
          return word;
        }
      } else {
        if (val.charAt(0) == '@') {
          if (val.length > 0) {
            return val;
          }
        }
      }
      return false;
    },
    // Method to do the autocompletion/suggestions
    startAutocomplete: function(val) {
      var App_this = this;
      if (this.hasAutocomplete(val) !== false) {
        this.states.typing.autocomplete = true;
        var word = this.hasAutocomplete(val);
        word = word.replace('@', '');
        _.each(App_this.active_users, function(value) {
          if (value.username.substr(0, word.length).toUpperCase() == word.toUpperCase()) {
            if ((App_this.states.typing.suggested_mentions.filter(obj => obj == value.id).length == 0)) {
              App_this.states.typing.suggested_mentions.push(value.id);
            }
          } else {
            App_this.states.typing.suggested_mentions = App_this.states.typing.suggested_mentions.filter(obj => obj !== value.id);
          }
        });
      } else {
        this.states.typing.suggested_mentions = [];
        this.states.typing.selected_mention = 0;
      }
    },
    // Method to select and pick mentions
    handleAutocomplete: function(e) {
      if (e.which == 38) { // Arrow key up
        e.preventDefault();
        if (this.states.typing.selected_mention > 0) {
          this.states.typing.selected_mention = this.states.typing.selected_mention - 1;
        }
      } else if (e.which == 40) { // Arrow key down
        e.preventDefault();
        if (this.states.typing.selected_mention < this.states.typing.suggested_mentions.length - 1) {
          this.states.typing.selected_mention = this.states.typing.selected_mention + 1;
        }
      } else if ((e.which == 13 || e.which == 9) && this.states.typing.autocomplete) { // Enter key
        e.preventDefault();

        this.completeMention(this.findUser(this.states.typing.suggested_mentions[this.states.typing.selected_mention]));
      }
    },
    // Method to fill textarea with mention after using autocomplete menu
    completeMention: function(user) {
      this.states.typing.message = this.states.typing.message.substring(0, this.states.typing.message.lastIndexOf('@'));
      this.states.typing.message += '<@' + user.id + '> ';
      this.states.typing.focused = true;
      this.states.typing.autocomplete = false;

      this.states.typing.selected_mention = 0;
    },
  },
});

// Automatically resize text areas to fit text height
autosize($('textarea'));

// Detect if browser is offline
isOnline().then(online => {
  App.states.offline = !online;
});

// Detect if user is away from browser
var idle_timer = away(300000);
idle_timer.on('idle', function() {
  App.updateStatus("away");
});
idle_timer.on('active', function() {
  App.updateStatus("online");
});

$(window).focus(function() {
  if (App.unread[App.states.current_channel] > 0) {
    App.readChannel(App.states.current_channel);
  }
  Push.clear();

  if (App.states.typing.focused) {
    App.states.typing.autocomplete = true;
  }
});

$(window).blur(function() {
  if (App.states.typing.autocomplete) {
    App.states.typing.focused = true;
    App.states.typing.autocomplete = false;
  }
});

// Start listening to channel websocket through Laravel Echo
function listenToChannel(channel_id) {
  window['channel_' + channel_id.toString()] = Echo.private('channels.' + channel_id)
     .listen('MessageNew', (e) => {
        console.log(e);
        if (App.messages.filter(obj => obj.message_id == e.message.message_id).length == 0) {
          if (!document.hasFocus()) {
            e.message.read = false;
            if (!Push.Permission.has()) {
              Push.Permission.request(function() {
                window['notifications_' + e.message.message_id] = Push.create(
                  App.findUser(e.message.user_id).username + ' (Channel: ' + App.findChannel(e.message.channel_id).name + ')', {
                    body: e.message.content,
                    icon: 'https://api.adorable.io/avatars/100/' + e.message.user_id, // CHANGE THIS
                    onClick: function() {
                      window.focus();
                      this.close();
                      App.states.current_channel = e.message.channel_id;
                      App.readChannel(e.message.channel_id);
                    }
                });
              }, function() {
                console.log('Please allow notifications to recieve desktop push alerts.');
              });
            } else {
              window['notifications_' + e.message.message_id] = Push.create(
                App.findUser(e.message.user_id).username + ' (Channel: ' + App.findChannel(e.message.channel_id).name + ')', {
                  body: e.message.content,
                  icon: 'https://api.adorable.io/avatars/100/' + e.message.user_id, // CHANGE THIS
                  onClick: function() {
                    window.focus();
                    this.close();
                    App.states.current_channel = e.message.channel_id;
                    App.readChannel(e.message.channel_id);
                  }
              });
            }
          } else {
            if (App.states.current_channel == e.message.channel_id) {
              if (App.current_user.id == e.message.user_id) {
                e.message.read = true;
              } else {
                e.message.read = App.readMessage(e.message.message_id);
              }
            }
          }
          App.messages.push(e.message);
        }
     })
     .listen('MessageRemove', (e) => {
        console.log(e);
        App.messages.splice(App.messages.findIndex(message => message.message_id == e.message.message_id), 1);
     })
     .listen('MessageUpdate', (e) => {
        console.log(e);
        Vue.set(App.messages, App.messages.findIndex(message => message.message_id == e.message.message_id), e.message);
     })
     .listen('ChannelRemove', (e) => {
        console.log(e);
        App.channels.splice(App.channels.indexOf(e.channel), 1);
        if (App.channels.length > 0) {
          App.states.current_channel = App.channels[0].channel_id;
        } else {
          App.states.current_channel = '';
        }
        Echo.leave('channels.' + e.channel.channel_id);
     })
     .listenForWhisper('typing', (e) => {
         console.log(e);
         var typing_user = {
           'user_id': e.user_id,
           'channel_id': e.channel_id,
         };

         if (e.typing && !App.states.typing.users.filter(obj => obj.user_id == e.user_id).length > 0) {
           App.states.typing.users.push(typing_user);
         } else {
           App.states.typing.users.splice(App.states.typing.users.findIndex(user => user.user_id == typing_user.user_id), 1);
         }
     });
}

if (App.logged_in && document.getElementById('messages')) {
  // Detect scroll on messages container
  $('#messages').scroll(function() {
    Vue.nextTick(function() {
      App.states.messages.scroll.position = $('#messages')[0].scrollTop;
      App.states.messages.scroll.height = $('#messages')[0].scrollHeight;
      App.states.messages.scroll.outer_height = $('#messages').outerHeight();
    });
  });

  Vue.nextTick(function() {
    App.states.messages.scroll.position = $('#messages')[0].scrollTop;
    App.states.messages.scroll.height = $('#messages')[0].scrollHeight;
    App.states.messages.scroll.outer_height = $('#messages').outerHeight();
  });

  // Join presence channel through Laravel Echo
  Echo.join('presence')
     .here((users) => {
         console.log(users);
     })
     .joining((user) => {
         console.log("Connecting: "+user.username);
     })
     .leaving((user) => {
         console.log("Disconnecting: "+user.username);
     })
     .listen('UserUpdate', (e) => {
        console.log(e.user);
        Vue.set(App.users, App.users.findIndex((user_find => user_find.id == e.user.id)), e.user);
        var src = $(".avatar[data-user_id='" + e.user.id + "']").attr('src');
        if (src.indexOf('?') > 0) {
          src = src.substring(0, src.indexOf('?')) + '?' + Date.now();
        } else {
          src = src + '?' + Date.now();
        }
        $(".avatar[data-user_id='" + e.user.id + "']").attr('src', src);
        if (e.user.id == App.current_user.id) {
          App.current_user.status = e.user.status;
          App.current_user.username = e.user.username;
        }
     });

     Echo.private('users.' + App.current_user.id)
       .listen('ChannelNew', (e) => {
          console.log(e);
          App.channels.push(e.channel);
          listenToChannel(e.channel.channel_id);
       })
       .listen('ChannelUpdate', (e) => {
          console.log(e);
          if (!App.isMember(App.current_user.id, e.channel)) {
            App.states.current_channel = App.channels[0].channel_id;
            App.messages = App.messages.filter(function(message) {
              return message.channel_id !== e.channel.channel_id;
            });
            App.channels.splice(App.channels.findIndex(channel => channel.channel_id == e.channel.channel_id), 1);
            Echo.leave('channels.' + e.channel.channel_id);
          } else {
            if (typeof App.findChannel(e.channel.channel_id) == 'undefined') {
              App.channels.push(e.channel);
              listenToChannel(e.channel.channel_id);
              axios.get('/api/channels/' + e.channel.channel_id + '/messages')
               .then(function (response) {
                 console.log(response);
                 App.messages.push(...response.data);
               })
               .catch(function (error) {
                 console.log(error);
              });
            } else {
              Vue.set(App.channels, App.channels.findIndex((channel => channel.channel_id == e.channel.channel_id)), e.channel);
            }
          }
       });

     Object.keys(App.channels).forEach(function(key) {
       // Join private channel through Laravel Echo
       listenToChannel(App.channels[key].channel_id);
     });
}

// Listen to key events on window
// Must be done instead of Vue @keydown as we must ignore focus
window.addEventListener('keydown', function(e) {
  // If ESC key pressed
  if (e.keyCode === 27) {
    App.states.settings.display = false;
  }
});

// Initialise Bootstrap tooltips
$("body").tooltip({
    selector: '[data-toggle="tooltip"]'
});

// Toggle v-tooltip popovers on click through data-toggle
$(document).on("click", '[data-toggle="popover"]', function(e) {
  console.log($(e.target).closest('[data-toggle="popover"]').data("user_id").toString());
  App.$eventHub.$emit('tooltip:toggle', {
    'show': true,
    'placement': $(e.target).closest('[data-toggle="popover"]').data("placement"),
    'user': $(e.target).closest('[data-toggle="popover"]').data("user_id").toString(),
    'width': $(e.target).closest('[data-toggle="popover"]').width(),
    'pos': {
      'x': $(e.target).closest('[data-toggle="popover"]').offset().left,
      'y': $(e.target).closest('[data-toggle="popover"]').offset().top,
    },
  });
});

// Ensure tooltips are closed when the toggle element is clicked
$(document).on("mouseleave click", '[data-toggle="tooltip"]', function(e) {
  $(".tooltip").tooltip("hide");
});

// Allow floating labels on input boxes
$(".form-field").on("change paste keyup blur focus", function(e) {
    var element_label = $("label[for='" + $(this).attr('name') + "']");
    if (!$(this).val()) {
        $(element_label).removeClass("active");
    } else {
        $(element_label).addClass("active");
    }
});
