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
    <div class="app-container" :class="{ 'dark-mode': user_options && user_options.dark_mode }" id="app">
        @if (Auth::guest() or !Auth()->user()->email_verified_at)
            <main>
              <div class="container-fluid px-0 h-100">
                <div class="row justify-content-center h-100 w-100">
                  <div class="col col-md-12">
                    <div class="w-50p position-relative flex flex-column">
                      @yield('content')
                    </div>
                  </div>
                  <div class="col col-alt col-md-12 d-none d-md-none d-sm-none d-lg-flex flex justify-content-center align-items-center">
                    @yield('illustration')
                  </div>
                </div>
              </div>
            </main>
        @else
            <audio id="message_sound">
                <source src="{{ asset('sounds/light.mp3') }}"></source>
            </audio>

            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                @csrf
            </form>

            <at-modal v-model="modals.create_channel" title="Create channel" @on-confirm="createChannel">
              <at-input size="large" type="text" placeholder="Channel name" v-model="states.modal.content.name" class="my-2"></at-input>
              <at-select placeholder="Channel members" v-model="states.modal.content.members" multiple size="large">
                <at-option v-for="(user, index) in other_users" :key="user.id" :value="user.id">@{{ user.username }}</at-option>
              </at-select>
            </at-modal>

            <at-modal v-model="modals.edit_message" title="Edit message" @on-confirm="editMessage">
                <div v-if="has(states.modal.item, 'content')">
                  <at-textarea v-model="states.modal.content" autosize resize="none" max-rows="4"></at-textarea>
                </div>
            </at-modal>

            <at-modal v-model="modals.edit_channel" title="Edit channel" @on-confirm="editChannel">
              <at-input v-if="has(states.modal.item, 'name')" size="large" type="text" v-model="states.modal.content.name" class="my-2"></at-input>
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

            <v-popover
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
            </v-popover>

            <transition name="scale">
                @yield('settings')
            </transition>

            <transition name="scale-under">
                <main v-show="!states.settings.display">
                    <div class="container-fluid px-0 h-100">
                      <div class="row h-100 w-100 flex-nowrap">
                          <div class="flex flex-column h-100" style="flex: 0 0 auto; flex-basis: 0;">
                              <div class="flex flex-row align-items-center bg-darker p-3" :style="{ height: window.nav_height + 'px' }">
                                  <status-badge class="border-0" theme="darker" :status="current_user.status">
                                    <img height="30px" class="rounded-circle" src="{{ asset('storage/avatars/'.Auth::user()->id.'.png') }}" data-user_id="{{ Auth::user()->id }}">
                                  </status-badge>
                                  <h4 class="mx-2 text-white">@{{ current_user.username }}</h4>
                                  <div class="ml-auto text-white">
                                      <a class="mx-1" @click="states.settings.display = true"><i class="icon icon-settings"></i></a>
                                      <a class="mx-1" onclick="document.getElementById('logout-form').submit()"><i class="icon icon-log-out"></i></a>
                                  </div>
                              </div>
                              <at-menu theme="dark" class="flex-fill border-0 bg-dark py-4" mode="vertical" :active-name="states.current_channel">
                                  <h5 class="text-white text-uppercase ls-1 my-2 pl-32">Channels - @{{ channels.length }}</h5>
                                  <at-menu-item v-for="(channel, index) in channels" :key="channel.channel_id" :name="channel.channel_id">
                                      <div @click="states.current_channel = channel.channel_id" class="position-relative flex align-items-center" style="overflow: hidden;">
                                          <i class="icon icon-hash"></i>
                                          @{{ channel.name }}
                                          <i @click="editChannel(channel)" style="z-index: 2;" class="ml-auto hover icon icon-settings"></i>
                                          <at-tag v-show="unread[channel.channel_id] > 0" class="ml-auto" color="error">@{{ unread[channel.channel_id] }}</at-tag>
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
                              </at-menu>
                          </div>
                          <div class="flex flex-column flex-grow-1" style="flex: 0 0 auto; flex-basis: 0;">
                              <div class="border-bottom" :style="{ height: window.nav_height + 'px' }">
                                  <div class="flex align-items-center w-100 h-100">
                                      <h4 class="mx-4">@{{ active_channel.name }}</h4>
                                  </div>
                              </div>
                              <div class="flex-fill position-relative">
                                  @yield('chat')
                              </div>
                          </div>
                          <div class="flex flex-column mr-auto h-100" style="flex: 0 0 auto; flex-basis: 0;">
                              <at-menu theme="dark" class="flex-fill pt-4 border-0 bg-dark" mode="vertical" :active-name="states.current_channel">
                                  <h5 v-if="usersStatus.online.length" class="text-white text-uppercase ls-1 mb-2 pl-32">Online - @{{ usersStatus.online.length }}</h5>
                                  <li class="at-menu__item" v-for="(user, index) in usersStatus.online" :key="user.id" :name="user.username" :class="{ 'mb-2': index == usersStatus.online.length - 1 }">
                                    <div class="at-menu__item-link">
                                        <div class="position-relative" style="overflow: hidden;">
                                            <status-badge class="border-0 mr-2" theme="dark" :status="user.status">
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
                                            <status-badge class="border-0 mr-2" theme="dark" :status="user.status">
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
