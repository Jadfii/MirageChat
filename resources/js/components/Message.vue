<template>
  <div :class="{
    'border-bottom': !bottom && !close_before,
    'pb-0 pt-3 px-3': close_before && !close,
    'pb-1': close_before,
    'pb-3': close && !close_before,
    'p-3': !close && !close_before,
    'px-3': close
    }" class="flex flex-row position-relative" v-bind:data-message_id="message.message_id">
    <div v-if="!close" class="flex align-items-center align-self-start mr-3">
      <img height="40px" class="rounded-circle" :data-user_id="message.user_id" :src="'/storage/avatars/' + message.user_id + '.png'">
    </div>
    <div :style="[close ? {'margin-left': 'calc(40px + 1rem)'} : {'margin': '0'}]" class="flex flex-column" style="width: 90%;">
      <div v-if="!close" class="flex flex-row align-items-center">
        <span class="font-weight-bold">{{ user.username }}</span>
        <span class="ml-2" style="font-size: 12px;">{{ moment(message.created_at).calendar() }}</span>
      </div>
      <div class="content">
        <p>{{ message.content }}</p>
        <a v-if="hasImageURL()" v-for="(URL, index) in getURLs()" v-bind:href="URL" target="_blank" rel="noopener noreferrer"><img style="max-width: 30vw;" v-bind:src="URL" class="rounded mt-1"></img></a>
      </div>
    </div>
    <div class="align-self-start ml-auto">
      <a class="icon dropdown-toggle" :id="'message-action-' + index" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="icon icon-more-vertical"></i></a>
      <div class="dropdown-menu" :aria-labelledby="'message-action-' + index">
        <a class="dropdown-item flex justify-content-center text-center" @click="edit_message" data-toggle="modal" data-target="#" v-if="message.user_id == current_user.id">Edit</a>
        <a class="dropdown-item flex justify-content-center text-center" @click="delete_message(message.message_id)" v-if="message.user_id == current_user.id">Delete</a>
        <a class="dropdown-item flex justify-content-center text-center" @click="copyID(message.message_id)">Copy ID</a>
      </div>
    </div>
  </div>
</template>

<script>
    export default {
        mounted() {
          var App_this = this;

          // RegEx pattern from https://stackoverflow.com/questions/5717093/check-if-a-javascript-string-is-a-url/45567717#45567717
          this.url_pattern = new RegExp('^(https?:\\/\\/)?'+ // protocol
          '((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.)+[a-z]{2,}|'+ // domain name and extension
          '((\\d{1,3}\\.){3}\\d{1,3}))'+ // OR ip (v4) address
          '(\\:\\d+)?'+ // port
          '(\\/[-a-z\\d%@_.~+&:]*)*'+ // path
          '(\\?[;&a-z\\d%@_.,~+&:=-]*)?'+ // query string
          '(\\#[-a-z\\d_]*)?$','i'); // fragment locator

          this.urls_pattern = /((?:(http|https|Http|Https|rtsp|Rtsp):\/\/(?:(?:[a-zA-Z0-9\$\-\_\.\+\!\*\'\(\)\,\;\?\&\=]|(?:\%[a-fA-F0-9]{2})){1,64}(?:\:(?:[a-zA-Z0-9\$\-\_\.\+\!\*\'\(\)\,\;\?\&\=]|(?:\%[a-fA-F0-9]{2})){1,25})?\@)?)?((?:(?:[a-zA-Z0-9][a-zA-Z0-9\-]{0,64}\.)+(?:(?:aero|arpa|asia|a[cdefgilmnoqrstuwxz])|(?:biz|b[abdefghijmnorstvwyz])|(?:cat|com|coop|c[acdfghiklmnoruvxyz])|d[ejkmoz]|(?:edu|e[cegrstu])|f[ijkmor]|(?:gov|g[abdefghilmnpqrstuwy])|h[kmnrtu]|(?:info|int|i[delmnoqrst])|(?:jobs|j[emop])|k[eghimnrwyz]|l[abcikrstuvy]|(?:mil|mobi|museum|m[acdghklmnopqrstuvwxyz])|(?:name|net|n[acefgilopruz])|(?:org|om)|(?:pro|p[aefghklmnrstwy])|qa|r[eouw]|s[abcdeghijklmnortuvyz]|(?:tel|travel|t[cdfghjklmnoprtvwz])|u[agkmsyz]|v[aceginu]|w[fs]|y[etu]|z[amw]))|(?:(?:25[0-5]|2[0-4][0-9]|[0-1][0-9]{2}|[1-9][0-9]|[1-9])\.(?:25[0-5]|2[0-4][0-9]|[0-1][0-9]{2}|[1-9][0-9]|[1-9]|0)\.(?:25[0-5]|2[0-4][0-9]|[0-1][0-9]{2}|[1-9][0-9]|[1-9]|0)\.(?:25[0-5]|2[0-4][0-9]|[0-1][0-9]{2}|[1-9][0-9]|[0-9])))(?:\:\d{1,5})?)(\/(?:(?:[a-zA-Z0-9\;\/\?\:\@\&\=\#\~\-\.\+\!\*\'\(\)\,\_])|(?:\%[a-fA-F0-9]{2}))*)?(?:\b|$)/gi;

          var content = App_this.message.content;
          var mention = null;
          var message = $("[data-message_id='" + App_this.message.message_id + "']").find("p")[0];
          this.$nextTick(function() {
            for (var i = 0; i < App_this.mentions.length; i++) {
              if (i == 0) {
                message.innerHTML = '';
                message.appendChild(document.createTextNode(content.substr(0, content.indexOf('<@'))));
              }

              mention = document.createElement("a");
              mention.classList.add("mention");
              mention.appendChild(document.createTextNode("@" + App_this.mentions[i].username));
              mention.setAttribute("data-user_id", App_this.mentions[i].id);
              mention.setAttribute("data-toggle", "popover");
              mention.setAttribute("data-placement", "right");

              message.appendChild(mention);
              message.appendChild(document.createTextNode(content.substr(content.indexOf('<@' + App_this.mentions[i].id + '>') + 2 + App_this.mentions[i].id.length + 1, content.length).replace(/(\<@.*?\>)/gi, '')));
            }
            var url = null;
            if (this.hasURL()) {
              var URLs = this.getURLs();
              for (var i = 0; i < URLs.length; i++) {
                if (i == 0) {
                  message.innerHTML = '';
                  message.appendChild(document.createTextNode(content.substr(0, content.indexOf(URLs[i]))));
                }

                url = document.createElement("a");
                url.setAttribute("target", "_blank");
                url.setAttribute("rel", "noopener noreferrer");
                url.setAttribute("href", URLs[i]);
                url.appendChild(document.createTextNode(URLs[i]));

                message.appendChild(url);
                if (URLs.length > i + 1) {
                  message.appendChild(document.createTextNode(content.substring(content.indexOf(URLs[i]) + URLs[i].length, content.indexOf(URLs[i + 1]))));
                } else {
                  message.appendChild(document.createTextNode(content.substring(content.indexOf(URLs[i]) + URLs[i].length, content.length)));
                }
              }
            }
            if (App_this.scrolled_bottom) {
              $('#messages').scrollTop(1E10);
            }
          });
        },
        data: function() {
            return {
              url_pattern: null,
              urls_pattern: null,
            }
        },
        methods: {
          hasURL: function() {
            var App_this = this;
            return _.find(this.message.content.split(' '), function(val) {
              return val.match(App_this.url_pattern);
            }) != null;
          },
          hasImageURL: function() {
            var App_this = this;
            return _.find(this.message.content.split(' '), function(val) {
              return val.match(/\.(jpeg|jpg|gif|png)$/);
            }) != null;
          },
          getURLs: function() {
            return this.message.content.match(this.urls_pattern);
          },
          copyID: function(message_id) {
            navigator.clipboard.writeText(message_id).then(function() {
              //
            }, function(err) {
              console.error('Error copying ID to clipboard: ', err);
            });
          },
        },
        props: {
          current_user: [Array, Object],
          message: {
            default: function () {
              return null
            },
            type: [Array, Object]
          },
          user: [Array, Object],
          index: [Number],
          bottom: [Boolean],
          close: [Boolean],
          close_before: [Boolean],
          delete_message: [Function],
          edit_message: [Function],
          mention: [Boolean],
          mentions: [Array],
          scrolled_bottom: [Boolean],
        },
    }
</script>
