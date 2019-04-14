@extends('layouts.app')

@section('chat')
<div class="flex flex-column w-100 h-100">
  <div v-if="channels.length == 0" class="flex flex-fill align-items-center justify-content-center">
    <h1>You are not in any channels</h1>
  </div>
  <div v-else :class="{ 'dark': user_options.dark_mode }" class="flex-fill scrollbar" id="messages" style="height: 0px; overflow-y: scroll;">
    <message
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
    :scrolled_bottom="messages_bottom"
    :dark_mode="user_options.dark_mode"
    ></message>
  </div>
  <div :class="[ user_options.dark_mode ? 'border-darker' : 'border-light' ]" class="p-4 flex align-items-center justify-content-center flex-column border-top">
    <div class="flex flex-column w-100 position-relative">
      <!--<div v-show="states.typing.suggested_mentions.length > 0 && states.typing.autocomplete" class="flex flex-column bg-dark position-absolute p-3 rounded-top" style="left: 0; right: 0; bottom: 100%;">
        <div class="autocomplete-item rounded p-2" v-for="(user, index) in states.typing.suggested_mentions" @mouseover="states.typing.selected_mention = index" @click="completeMention(findUser(user))" :class="{ active: index == states.typing.selected_mention }">
          <status-badge class="border-0 mr-1" theme="dark" :status="findUser(user).status">
            <img height="30px" class="rounded-circle" :data-user_id="findUser(user).id" :src="'{{ asset('storage/avatars') }}/' + findUser(user).id + '.png'">
          </status-badge>
          <span class="mx-2 text-light">@{{ findUser(user).username }}</span>
        </div>
      </div>-->
      <at-menu width="100%" v-show="states.typing.suggested_mentions.length > 0 && states.typing.autocomplete" theme="dark" style="bottom: 100%;" class="flex-fill border-0 bg-dark position-absolute" mode="vertical">
          <li v-for="(user, index) in states.typing.suggested_mentions" @mouseover="states.typing.selected_mention = index" @click="completeMention(findUser(user))" :class="{ active: index == states.typing.selected_mention }" class="at-menu__item">
            <div class="at-menu__item-link">
                <div class="position-relative flex align-items-center" style="overflow: hidden;">
                  <status-badge class="border-0 mr-1" theme="dark" :status="findUser(user).status">
                    <img height="30px" class="rounded-circle" :data-user_id="findUser(user).id" :src="'{{ asset('storage/avatars') }}/' + findUser(user).id + '.png'">
                  </status-badge>
                  <span class="mx-2 text-light">@{{ findUser(user).username }}</span>
                </div>
            </div>
          </li>
      </at-menu>
      <at-textarea
      id="message_box"
      v-on:focus="states.typing.focused = true"
      v-on:blur="states.typing.focused = false"
      :class="{ 'dark': user_options.dark_mode }"
      class="w-100"
      v-model="states.typing.message"
      placeholder="Message"
      max-length="2000"
      autosize
      autofocus
      resize="none"
      :disabled="channels.length == 0"
      name="message">
      </at-textarea>
    </div>
    <div class="align-self-start" style="height: 10px;">
      <div class="flex flex-row align-items-center" v-if="active_typing !== ''">
        <div class="typing-indicator">
          <span></span>
          <span></span>
          <span></span>
        </div>
        <span v-html="active_typing"></span>
      </div>
    </div>
  </div>
</div>
@endsection
