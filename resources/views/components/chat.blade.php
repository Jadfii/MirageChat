@extends('layouts.app')

@section('content')
<section class="h-100 content" v-bind:class="{ 'userlist-hide': !states.userlist, 'sidebar-hide': !states.sidebar }">
  <div class="container-fluid h-100">
    <div class="row h-100">
      <div class="chat-wrapper">
        <div id="messages" class="inline-scroll scroll-light" v-bind:class="{ emptyfill: !active_messages.length }">
          <h1 v-if="active_messages.length < 1 && channels.length > 0">No messages in this channel yet.</h1>
          <h1 v-else-if="channels.length == 0">You are not in any channels yet.</h1>
          <message-frame
            v-if="active_messages.length > 0"
            v-for="(message, index) in active_messages"
            :delete_message="deleteMessage"
            :edit_message="editMessage"
            :key="message.message_id"
            :index="index | parse_int"
            :current_user="current_user"
            :user="findUser(message.user_id)"
            :message="message"
            :mention="hasMention(message.content)"
            :mentions="getMentions(message.content)"
            :close="isCloseMessage(index)"
            :close_before="isCloseMessage(index+1)"
            :bottom="index == Object.keys(active_messages).length - 1"
            :scrolled_bottom="messages_bottom">
          </message-frame>
        </div>
        <div class="chat-box-wrapper">
          <div class="chat-box">
            <div v-show="states.typing.suggested_mentions.length > 0 && states.typing.autocomplete" class="autocomplete-container">
              <div v-for="(user, index) in states.typing.suggested_mentions" @mouseover="states.typing.selected_mention = index" @click="completeMention(findUser(user))" :class="{ active: index == states.typing.selected_mention }" class="autocomplete-child">
                <div class="autocomplete-child-inner">
                  <status-badge v-bind:status="findUser(user).status"></status-badge>
                  <avatar :src="'storage/avatars/' + user + '.png'" :size="'20px'" :user="findUser(user)"></avatar>
                  <a class="autocomplete-name">@{{ findUser(user).username }}</a>
                </div>
              </div>
            </div>
            <div class="form-group chat-box-inner">
              <textarea @keydown="startTyping" v-model="states.typing.message" v-focus="states.typing.focused" @focus="states.typing.focused = true" @blur="states.typing.focused = false" id="message" type="text" class="inline-scroll scroll-dark form-field form-control" name="message"></textarea>
              <label class="text-label" for="message">Message</label>
              <a @click="sendMessage" href="javascript:void(0)" id="chat-send" class="chat-send"><i class="material-icons">send</i></a>
            </div>
          </div>
          <div v-if="active_typing !== ''" class="chat-typing-wrapper">
            <div class="typing-indicator">
              <span></span>
              <span></span>
              <span></span>
            </div>
            <p class="chat-typing" v-html="active_typing"></p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
@endsection
