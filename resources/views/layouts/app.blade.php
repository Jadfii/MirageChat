<?php use App\Http\Controllers; ?>
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Laravel') }}</title>

    <!-- Scripts -->
    @auth
    <script>
        window.__INITIAL_STATE__ = {!! json_encode(array(
            'current_user' => App\User::select(App\User::$viewable)->where('id', Auth::user()->id)->get()->first(),
            'avatar' => asset('storage/avatars/'.Auth::user()->id.'.png'),
            'users' => Controllers\UserController::index(),
            'channels' => Controllers\ChannelController::index(),
            'messages' => Controllers\MessageController::index(),
        )) !!};
        window.__INITIAL_STATE__.messages = Array.from(Object.keys(window.__INITIAL_STATE__.messages), k=>window.__INITIAL_STATE__.messages[k]);
    </script>
    @else
    <script>
        window.__INITIAL_STATE__ = {!! json_encode(array(
            'current_user' => array(),
            'users' => array(),
            'channels' => array(),
            'messages' => array(),
        )) !!};
    </script>
    @endauth

    <script src="{{ asset('js/app.js') }}" defer></script>
    <script src='https://www.google.com/recaptcha/api.js' defer></script>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700,900" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">

    <!-- Styles -->
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
</head>
<body>
    <div class="app-container" :class="{ 'dark-mode': user_options && user_options.dark_mode }" id="app" @dragenter="modals.files.drag = true; states.upload_area = true;" @dragleave="modals.files.drag = false; states.upload_area = false;" @dragover.prevent="modals.files.drag = true; states.upload_area = true;" @drop.prevent="uploadFile">
        @if (Auth::guest() or !Auth()->user()->email_verified_at)
            <main>
              <div class="container-fluid px-0 h-100">
                <div class="row justify-content-center h-100 w-100">
                  <div class="col col-md-12">
                    <div class="w-50p position-relative flex flex-column">
                      @yield('content')
                    </div>
                  </div>
                  <div class="col col-alt bg-alt col-md-12 d-none d-md-none d-sm-none d-lg-flex flex justify-content-center align-items-center">
                    @yield('illustration')
                  </div>
                </div>
              </div>
            </main>
        @else
            <audio id="message_sound">
                <source src="{{ asset('sounds/light.mp3') }}"></source>
            </audio>

            <audio id="voice_call" :volume="states.voice.volume / 100">
                <source src=""></source>
            </audio>

            <audio v-for="(peer, index) in states.voice.peers" :id="'voice_channel_user-' + peer.user_id" :volume="peer.volume / 100">
                <source src=""></source>
            </audio>

            <audio id="incoming_call" loop>
                <source src="{{ asset('sounds/incoming.mp3') }}"></source>
            </audio>

            <transition name="fade">
                <div v-show="!states.loaded && false" :class="[ user_options.dark_mode ? 'bg-darker' : 'bg-alt' ]" class="w-100 h-100 position-fixed flex flex-column align-items-center justify-content-center" style="z-index: 99; top: 0; left: 0; right: 0; left: 0;">
                    <div :class="{ 'dark': user_options.dark_mode }" class="loader triangle">
                        <svg viewBox="0 0 86 80">
                            <polygon points="43 8 79 72 7 72"></polygon>
                        </svg>
                    </div>
                    <h1 :class="{ 'text-white': user_options.dark_mode }" class="font-weight-light mt-2" style="font-size: 2rem;">MirageChat</h1>
                    <p :class="{ 'text-light': user_options.dark_mode }">Loading</p>
                </div>
            </transition>

            <div v-show="states.upload_area" class="w-100 h-100 position-fixed" style="z-index: 9999; top: 0; left: 0; right: 0; left: 0;">
                <input class="d-none" type="file" name="file" id="file">
            </div>

            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                @csrf
            </form>

            <at-modal v-model="modals.incoming_call" title="Incoming call" ok-text="Answer" cancel-text="Decline" :mask-closable="false" :show-close="false">
                <div v-if="states.voice.peer_id" class="flex flex-row align-items-center">
                    <img height="70px" :class="{ 'avatar--active': states.voice.remoteStats.level > 0.2 }" class="avatar rounded-circle mx-1" :src="'{{ asset('storage/avatars') }}/' + states.voice.peer_id + '.png'" :data-user_id="states.voice.peer_id">
                    <h4>@{{ findUser(states.voice.peer_id).username + ' is calling you' }}</h4>
                </div>
                <div slot="footer">
                  <at-button type="error" @click="declineCall">Decline</at-button>
                  <at-button type="success" @click="acceptCall">Accept</at-button>
                </div>
            </at-modal>

            <at-modal v-model="modals.voice_call" title="Voice call">
                <div>
                    <at-button @click="startCall()" type="success">Start call</at-button>
                    <at-button @click="endCall" type="error">End call</at-button>
                    <at-button @click="muteCall" type="info">Mute call</at-button>
                    <at-button type="info" id="mute-self">Mute microphone</at-button>
                    <at-slider v-model="states.voice.volume"></at-slider>
                    <at-select placeholder="Peer" v-model="states.voice.peer_id" size="large">
                      <at-option v-for="(user, index) in other_users" :key="user.id" :value="user.id">@{{ user.username }}</at-option>
                    </at-select>
                </div>
            </at-modal>

            <at-modal v-model="modals.create_channel" title="Create channel" @on-confirm="createChannel">
              <at-input v-if="has(states.modal.content, 'name')" :class="{ 'darker': user_options.dark_mode }" size="large" type="text" placeholder="Channel name" v-model="states.modal.content.name" class="my-2"></at-input>
              <at-select v-if="has(states.modal.content, 'members')" placeholder="Channel members" v-model="states.modal.content.members" multiple size="large">
                <at-option v-for="(user, index) in other_users" :key="user.id" :value="user.id">@{{ user.username }}</at-option>
              </at-select>
            </at-modal>

            <at-modal v-model="modals.edit_message" title="Edit message" @on-confirm="editMessage">
                <div v-if="has(states.modal.item, 'content')">
                  <at-textarea v-model="states.modal.content" :class="{ 'darker': user_options.dark_mode }" autosize resize="none" max-rows="4"></at-textarea>
                </div>
            </at-modal>

            <at-modal v-model="modals.edit_channel" title="Edit channel" @on-confirm="editChannel">
              <at-input v-if="has(states.modal.item, 'name')" :class="{ 'darker': user_options.dark_mode }" size="large" type="text" v-model="states.modal.content.name" class="my-2"></at-input>
              <at-select v-if="has(states.modal.item, 'members')" v-model="states.modal.content.members" multiple size="large">
                <at-option v-for="(user, index) in other_users" :key="user.id" :value="user.id">@{{ user.username }}</at-option>
              </at-select>
              <div slot="footer" class="flex flex-row align-items-center">
                  <at-button @click="deleteChannel" type="error">Delete channel</at-button>
                  <div class="ml-auto">
                      <at-button @click.native="modals.edit_channel = false">Cancel</at-button>
                      <at-button type="primary" @click.native="editChannel(); modals.edit_channel = false">OK</at-button>
                  </div>
              </div>
            </at-modal>

            <at-modal v-model="modals.enable_2fa" title="Enable 2-Factor Authentication">
                <div v-if="has(states.modal.item, 'qr_img')">
                  <div>
                      <h6 class="text-uppercase font-weight-bold">Scan QR Code</h6>
                      <img :src="states.modal.item.qr_img"></img>
                      <p>Or enter this code into your authenticator app manually: @{{ states.modal.item.secret }}</p>
                  </div>
                  <div class="mt-2">
                      <h6 class="text-uppercase font-weight-bold">Enter 6-digit generated code</h6>
                      <at-input v-model="states.modal.item.code" :class="{ 'darker': user_options.dark_mode }" size="large" type="text" placeholder="Authenticator code" class="my-2" autofocus></at-input>
                  </div>
                </div>
                <div slot="footer" class="flex flex-row align-items-center">
                    <div class="ml-auto">
                        <at-button @click.native="modals.enable_2fa = false">Cancel</at-button>
                        <at-button type="primary" @click.native="confirm_2fa()">OK</at-button>
                    </div>
                </div>
            </at-modal>

            <at-modal v-model="modals.remove_2fa" title="Remove 2-Factor Authentication">
                <div v-if="has(states.modal.item, 'code')">
                  <div>
                      <h6 class="text-uppercase font-weight-bold">Enter 6-digit generated code</h6>
                      <at-input v-model="states.modal.item.code" :class="{ 'darker': user_options.dark_mode }" size="large" type="text" placeholder="Authenticator code" class="my-2" autofocus></at-input>
                  </div>
                </div>
                <div slot="footer" class="flex flex-row align-items-center">
                    <div class="ml-auto">
                        <at-button @click.native="modals.remove_2fa = false">Cancel</at-button>
                        <at-button type="primary" @click.native="remove_2fa()">OK</at-button>
                    </div>
                </div>
            </at-modal>

            <at-modal v-model="modals.files.drag" :styles="{top: '50%', transform: 'translateY(-50%)'}" :show-footer="false" :mask-closable="false" :show-close="false">
                <div class="flex flex-column align-items-center justify-content-center p-4">
                    <img height="200px" src="{{ asset('icons/drag_file.svg') }}">
                    <h1 class="mt-3">Drag and drop files</h1>
                    <p>You are able to add a message after uploading.</p>
                </div>
            </at-modal>

            <at-modal v-model="modals.files.upload" @on-confirm="sendFile" :styles="{top: '50%', transform: 'translateY(-50%)'}" ok-text="Upload" :show-close="false">
                <div v-if="has(states.modal.item, 'file_name')" class="flex flex-column align-items-center justify-content-center p-4">
                    <div class="flex flex-row align-items-center align-self-start">
                        <img height="100px" src="{{ asset('icons/upload_file.svg') }}">
                        <h1 class="mt-3 ml-3">@{{ states.modal.item.file_name }}</h1>
                    </div>
                    <div class="flex flex-column justify-content-center w-100 mt-5">
                        <h6 class="text-uppercase font-weight-bold">Add message (optional)</h6>
                        <at-input v-model="states.modal.item.message" :class="{ 'darker': user_options.dark_mode }" size="large" type="text" placeholder="Message" class="my-2" autofocus></at-input>
                    </div>
                </div>
            </at-modal>

            <!--<v-popover
              v-if="states.popover.open"
              :style="tooltip_location"
              :offset="8"
              :open="states.popover.open"
              :popover-class="'v-tooltip'"
              :auto-hide="false"
              :placement="states.popover.item.placement"
            >
              <template slot="popover">
                <div class="user-profile">
                    <user-frame :user="states.popover.item.user" :profile="true" :margin="false"></user-frame>
                    <h5>@{{ states.popover.item.user.username }}</h5>
                </div>
              </template>
          </v-popover>-->

            <transition name="scale">
                @yield('settings')
            </transition>

            <transition name="scale-under">
                <main v-show="!states.settings.display">
                    <div class="container-fluid px-0 h-100">
                      <div class="row h-100 w-100 flex-nowrap">
                          <div class="flex flex-column h-100" style="flex: 0 0 auto; flex-basis: 0;">
                              <div  :class="[ user_options.dark_mode ? 'bg-darkest' : 'bg-darker' ]" class="flex flex-row align-items-center p-3" :style="{ height: window.nav_height + 'px' }">
                                  <status-badge class="border-0" theme="darker" :status="current_user.status">
                                    <img height="30px" class="rounded-circle" src="{{ asset('storage/avatars/'.Auth::user()->id.'.png') }}" data-user_id="{{ Auth::user()->id }}">
                                  </status-badge>
                                  <h4 class="mx-2 text-white">@{{ current_user.username }}</h4>
                                  <div class="ml-auto text-white">
                                      <a class="mx-1" @click="modals.voice_call = true" data-toggle="tooltip" data-placement="top" title="Voice Call"><i class="icon icon-mic"></i></a>
                                      <a class="mx-1" @click="states.settings.display = true" data-toggle="tooltip" data-placement="top" title="User Settings"><i class="icon icon-settings"></i></a>
                                  </div>
                              </div>
                              <at-menu width="300px" theme="dark" :class="[ user_options.dark_mode ? 'bg-darker':'bg-dark' ]" class="flex-fill border-0 py-4" mode="vertical" :active-name="states.current_channel">
                                  <h5 class="text-white text-uppercase ls-1 my-2 pl-32">Channels - @{{ channels.length }}</h5>
                                  <at-menu-item v-for="(channel, index) in channels" :key="channel.channel_id" :name="channel.channel_id">
                                      <div @click="states.current_channel = channel.channel_id" class="position-relative flex align-items-center" style="overflow: hidden;">
                                          <i class="icon icon-hash"></i>
                                          @{{ channel.name }}
                                          <i v-if="!states.voice.connected" @click="joinVoiceChannel(channel.channel_id)" style="z-index: 2;" class="ml-auto hover icon icon-mic" data-toggle="tooltip" data-placement="top" data-original-title="Join voice chat"></i>
                                          <i v-else @click="leaveVoiceChannel(channel.channel_id)" style="z-index: 2;" class="ml-auto hover icon icon-mic-off" data-toggle="tooltip" data-placement="top" data-original-title="Leave voice chat"></i>
                                          <i v-if="channel.user_id == current_user.id" @click="editChannel(channel)" style="z-index: 2;" class="hover icon icon-settings" data-toggle="tooltip" data-placement="top" title="Edit channel"></i>
                                          <i v-else @click="leaveChannel(channel)" style="z-index: 2;" class="hover icon icon-user-minus" data-toggle="tooltip" data-placement="top" title="Leave channel"></i>
                                          <at-tag v-show="unread[channel.channel_id] > 0" color="error">@{{ unread[channel.channel_id] }}</at-tag>
                                      </div>
                                  </at-menu-item>
                                  <li class="at-menu__item">
                                    <div class="at-menu__item-link">
                                        <div @click="createChannel" class="position-relative flex align-items-center" style="overflow: hidden;">
                                            <i class="icon icon-plus"></i>
                                            <span>Create new channel</span>
                                        </div>
                                    </div>
                                  </li>
                                  <h5 class="text-white text-uppercase ls-1 my-2 mt-4 pl-32">Voice chat</h5>
                                  <div class="flex flex-column">
                                      <div class="" style="padding-left: 32px;">
                                          <span class="">Voice status: <b>@{{ states.voice.connected ? 'Connected' : 'Disconnected' }}</b></span>
                                      </div>
                                      <div v-if="states.voice.connected" class="flex flex-column align-items-start my-2" style="padding-left: 32px;">
                                          <div class="flex flex-row align-items-center">
                                              <img height="30px" width="30px" :class="{ 'avatar--active': states.voice.localStats.active }" class="avatar rounded-circle my-1" :src="'{{ asset('storage/avatars') }}/' + current_user.id + '.png'" :data-user_id="current_user.id">
                                              <span class="ml-2" :style="[states.voice.localStats.active ? { 'text-shadow': '0 0 0.05px white' } : { 'text-shadow': 'none' }]">@{{ current_user.username }}</span>
                                          </div>
                                          <div v-for="(peer, index) in states.voice.peers" class="flex flex-row align-items-center">
                                              <img height="30px" width="30px" class="avatar rounded-circle my-1" :src="'{{ asset('storage/avatars') }}/' + peer.user_id + '.png'" :data-user_id="peer.user_id">
                                              <span class="ml-2">@{{ findUser(peer.user_id).username }}</span>
                                          </div>
                                          <!--<div class="flex flex-row align-items-center">
                                              <img height="30px" width="30px" :class="{ 'avatar--active': states.voice.remoteStats.active }" class="avatar rounded-circle my-1" :src="'{{ asset('storage/avatars') }}/' + states.voice.peer_id  + '.png'" :data-user_id="states.voice.peer_id">
                                              <span class="ml-2" :style="[states.voice.remoteStats.active ? { 'text-shadow': '0 0 0.05px white' } : { 'text-shadow': 'none' }]">@{{ findUser(states.voice.peer_id).username }}</span>
                                          </div>-->
                                      </div>
                                  </div>
                              </at-menu>
                          </div>
                          <div :class="{ 'bg-dark': user_options.dark_mode }" class="flex flex-column flex-grow-1" style="flex: 0 0 auto; flex-basis: 0;">
                              <div class="border-bottom" :class="[ user_options.dark_mode ? 'border-darker' : 'border-light' ]" :style="{ height: window.nav_height + 'px' }">
                                  <div class="flex align-items-center w-100 h-100">
                                      <h4 :class="{ 'text-white': user_options.dark_mode }" class="mx-4">@{{ active_channel ? active_channel.name: '' }}</h4>
                                  </div>
                              </div>
                              <div class="flex-fill position-relative">
                                  @yield('chat')
                              </div>
                          </div>
                          <div class="flex flex-column mr-auto h-100" style="flex: 0 0 auto; flex-basis: 0;">
                              <at-menu width="300px" theme="dark" :class="[ user_options.dark_mode ? 'bg-darker':'bg-dark' ]" class="flex-fill pt-4 border-0" mode="vertical" :active-name="states.current_channel">
                                  <h5 v-if="usersStatus.online.length" class="text-white text-uppercase ls-1 mb-2 pl-32">Online - @{{ usersStatus.online.length }}</h5>
                                  <li class="at-menu__item" v-for="(user, index) in usersStatus.online" :key="user.id" :name="user.username" :class="{ 'mb-2': index == usersStatus.online.length - 1 }">
                                    <div class="at-menu__item-link">
                                        <div class="position-relative" style="overflow: hidden;">
                                            <status-badge offset="16px" class="border-0 mr-2" theme="dark" :status="user.status">
                                              <img height="30px" class="rounded-circle" :data-user_id="user.id" :src="'{{ asset('storage/avatars') }}/' + user.id + '.png'">
                                            </status-badge>
                                            <span class="mx-2">@{{ user.username }}</span>
                                        </div>
                                    </div>
                                  </li>
                                  <h5 v-if="usersStatus.offline.length" class="text-white text-uppercase ls-1 mb-2 pl-32">Offline - @{{ usersStatus.offline.length }}</h5>
                                  <li class="at-menu__item" v-for="(user, index) in usersStatus.offline" :key="user.id" :name="user.username" :class="{ 'mb-2': index == usersStatus.offline.length - 1 }">
                                    <div class="at-menu__item-link">
                                        <div class="position-relative" style="overflow: hidden;">
                                            <status-badge offset="16px" class="border-0 mr-2" theme="dark" :status="user.status">
                                              <img height="30px" class="rounded-circle" :data-user_id="user.id" :src="'{{ asset('storage/avatars') }}/' + user.id + '.png'">
                                            </status-badge>
                                            <span class="mx-2">@{{ user.username }}</span>
                                        </div>
                                    </div>
                                  </li>
                              </at-menu>
                          </div>
                      </div>
                    </div>
                </main>
            </transition>
            <div class="background dark-bg"></div>
        @endguest
    </div>
</body>
</html>
