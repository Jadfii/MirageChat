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
                @yield('content')
            </main>
        @else
            <transition name="fade">
                <div class="overlay" onclick="return false;" v-show="!states.loaded && !states.offline">
                  <svg class="spinner" viewBox="0 0 50 50">
                    <circle class="path" cx="25" cy="25" r="20" fill="none" stroke-width="5"></circle>
                  </svg>
                </div>
            </transition>

            <audio id="message_sound">
                <source src="{{ asset('sounds/light.mp3') }}"></source>
            </audio>

            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                @csrf

            </form>

            <form-modal data-reset="true" :title="'Create channel'" :message="''" :id="'channel-create-modal'" :func="createChannel">
                <template slot="body">
                  <div class="form-group form-group-alt">
                      <label for="channel_name">Channel Name</label>
                      <input name="channel_name" type="text" class="form-control form-field" autocomplete="off" required>
                      <div class="form-error"></div>
                  </div>
                  <div class="form-group form-group-alt">
                      <label for="members">Members</label>
                      <select name="members" class="custom-select" :size="users.length - 1" multiple>
                        <option v-for="user in users" v-if="user.id !== current_user.id" :value="user.id">@{{ user.username }}</option>
                      </select>
                  </div>
                </template>
                <template slot="footer">
                    <button type="submit" class="btn btn-primary btn-confirm">Confirm</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                </template>
            </form-modal>

            <form-modal :title="'Edit channel'" :message="''" :id="'channel-edit-modal'" :func="editChannel">
                <template v-if="states.modal.item && states.modal.item.name" slot="body">
                  <div class="form-group form-group-alt">
                      <label for="channel_name">Channel Name</label>
                      <input :value="states.modal.item.name" name="channel_name" type="text" class="form-control form-field" autocomplete="off" required>
                      <div class="form-error"></div>
                  </div>
                  <div class="form-group form-group-alt">
                      <label for="members">Members</label>
                      <select name="members" class="custom-select" :size="users.length - 1" multiple>
                        <option v-for="user in users" v-if="user.id !== current_user.id" :value="user.id" :selected="isMember(user.id, states.modal.item)">@{{ user.username }}</option>
                      </select>
                  </div>
                </template>
                <template slot="footer">
                    <button type="submit" class="btn btn-primary btn-confirm">Confirm</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button @click="deleteChannel" type="button" class="btn btn-primary btn-delete mr-auto">Delete channel</button>
                </template>
            </form-modal>

            <form-modal :title="'Edit message'" :message="''" :id="'message-edit-modal'" :func="editMessage">
                <template slot="body">
                  <div class="form-group form-group-alt">
                      <label for="message_content">Message</label>
                      <textarea v-if="states.modal.item" :value="states.modal.item.content" rows="3" style="height: auto;" name="message_content" class="inline-scroll scroll-light form-control form-field" autocomplete="off"></textarea>
                      <div class="form-error"></div>
                  </div>
                </template>
                <template slot="footer">
                    <button type="submit" class="btn btn-primary btn-confirm">Confirm</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                </template>
            </form-modal>

            <form-modal data-reset="true" :title="'Enable 2FA'" :message="''" :id="'enable-2fa-modal'" :func="confirm_2fa">
                <template v-if="states.modal.item" slot="body">
                    <div class="form-group form-group-alt qr-wrapper">
                        <label>Scan QR Code</label>
                        <img :src="states.modal.item.qr_img"></img>
                        <p>Or enter this code into your authenticator app manually: @{{ states.modal.item.secret }}</p>
                    </div>
                    <div class="form-group form-group-alt">
                        <label for="verify_code">Authenticator code</label>
                        <input name="verify_code" type="text" class="form-control form-field" autocomplete="off" autofocus required>
                        <div class="form-error"></div>
                    </div>
                </template>
                <template slot="footer">
                    <button type="submit" class="btn btn-primary btn-confirm">Confirm</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                </template>
            </form-modal>

            <form-modal data-reset="true" :title="'Remove 2FA'" :message="''" :id="'remove-2fa-modal'" :func="remove_2fa">
                <template slot="body">
                    <div class="form-group form-group-alt">
                        <label for="verify_code">Authenticator code</label>
                        <input name="verify_code" type="text" class="form-control form-field" autocomplete="off" autofocus required>
                        <div class="form-error"></div>
                    </div>
                </template>
                <template slot="footer">
                    <button type="submit" class="btn btn-primary btn-confirm">Confirm</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                </template>
            </form-modal>

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
                <div v-show="states.settings.display" class="card-wrapper settings-wrapper" tabindex="0">
                    <div class="settings-sidebar">
                        <ul class="sidebar-nav settings-nav">
                            <div class="settings-nav-heading">
                              <h3>My Account</h3>
                              <li data-setting="account_details" @click="changeSetting" :class="{ active: states.settings.active_tab == 'account_details' }"><a>Account Details</a></li>
                            </div>
                            <div class="settings-nav-heading">
                              <h3>App Settings</h3>
                              <li data-setting="notifications" @click="changeSetting" :class="{ active: states.settings.active_tab == 'notifications' }"><a>Notifications</a></li>
                              <li data-setting="appearance" @click="changeSetting" :class="{ active: states.settings.active_tab == 'appearance' }"><a>Appearance</a></li>
                            </div>
                            <div class="settings-nav-heading">
                              <li data-setting="app_information" @click="changeSetting" :class="{ active: states.settings.active_tab == 'app_information' }"><a>App Information</a></li>
                            </div>
                            <div class="settings-nav-heading">
                              <li onclick="event.preventDefault();document.getElementById('logout-form').submit();"><a class="bad">Sign Out</a></li>
                            </div>
                        </ul>
                    </div>
                  <div class="settings-content-wrapper cards-scroll inline-scroll scroll-light">
                    <div class="settings-content">
                        <div>
                            <div v-show="isActiveTab('account_details')" class="settings-body">
                                <div class="heading-body">
                                  <h4 class="heading-title">Account Details</h4>
                                  <settings-frame>
                                      <div class="user-settings">
                                        <div class="user-settings-box">
                                            <div v-if="!states.settings.account_edit" class="user-settings-inner viewing user">
                                                <img :src="states.account.avatar" class="avatar avatar-xl">
                                                <div class="user-settings-child">
                                                    <div class="user-settings-info mb-20">
                                                        <h3>{{ __('Username') }}</h3>
                                                        <h6 class="username">@{{ current_user.username }}</h6>
                                                    </div>
                                                    <div class="user-settings-info">
                                                        <h3>{{ __('Email') }}</h3>
                                                        <h6 class="username">@{{ current_user.email }}</h6>
                                                    </div>
                                                </div>
                                                <button v-on:click="states.settings.account_edit = true" type="button" class="btn btn-primary ml-auto user-settings-edit">{{ __('Edit') }}</button>
                                            </div>
                                            <div v-else class="user-settings-inner editing user">
                                                <form @submit.prevent="editAccount">
                                                  <div class="avatar-wrapper">
                                                      <a class="avatar avatar-xl input-group">
                                                        <div class="avatar avatar-xl" onclick="document.getElementById('avatar').click()">{{ __('Change Avatar') }}</div>
                                                        <input @change="editAvatar" accept="image/x-png,image/jpeg,image/jpg" type="file" id="avatar" name="avatar">
                                                        <input type="text" class="form-control" readonly>
                                                        <img :src="this.states.account.avatar_upload" id="avatar-upload" class="avatar avatar-xl">
                                                      </a>
                                                  </div>

                                                  <div class="info-wrapper">
                                                      <div class="form-group">
                                                          <input name="username" :value="current_user.username" type="text" class="form-control form-field">
                                                          <label for="username" :class="{ active: current_user.username }">{{ __('Username') }}</label>
                                                          <div class="form-error"></div>
                                                      </div>

                                                      <div class="form-group">
                                                          <input name="email" :value="current_user.email" type="text" class="form-control form-field">
                                                          <label for="email" :class="{ active: current_user.email }">{{ __('Email') }}</label>
                                                          <div class="form-error"></div>
                                                      </div>

                                                      <h6 class="heading-subtitle">{{ __('Change password') }}</h6>

                                                      <div class="form-group">
                                                        <input id="password_old" type="password" class="form-control form-field" name="password_old" value="">
                                                        <label for="password_old">{{ __('Current password') }}</label>
                                                      </div>

                                                      <div class="form-row">
                                                        <div class="col">
                                                          <div class="form-group">
                                                            <input id="password" type="password" class="form-control form-field" name="password" value="">
                                                            <label for="password">{{ __('New password') }}</label>
                                                          </div>
                                                        </div>

                                                        <div class="col">
                                                          <div class="form-group">
                                                            <input id="password_confirmation" type="password" class="form-control form-field" name="password_confirmation" value="">
                                                            <label for="password_confirmation">{{ __('Confirm new password') }}</label>
                                                          </div>
                                                        </div>
                                                      </div>

                                                      <div class="form-group form-submit">
                                                          <div class="form-group no-margin">
                                                            <button id="delete-account" type="button" class="btn btn-primary btn-delete">{{ __('Delete Account') }}</button>
                                                          </div>

                                                          <div class="form-group no-margin form-submit">
                                                            <button v-on:click="states.settings.account_edit = false; states.account.avatar_upload = states.account.avatar" type="button" class="btn btn-secondary user-settings-close">{{ __('Cancel') }}</button>
                                                            <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
                                                          </div>
                                                      </div>
                                                  </div>
                                                </form>
                                            </div>
                                        </div>
                                      </div>
                                  </settings-frame>
                                  <h4 class="heading-title">2 Factor Authentication</h4>
                                  <settings-frame>
                                      <div class="form-group no-margin form-submit">
                                        <button v-if="!current_user.google2fa_secret" @click="enable_2fa" data-toggle="modal" data-target="#enable-2fa-modal" class="btn btn-primary">{{ __('Enable 2FA') }}</button>
                                        <button v-else data-toggle="modal" data-target="#remove-2fa-modal" class="btn btn-primary btn-delete">{{ __('Remove 2FA') }}</button>
                                      </div>
                                  </settings-frame>
                                </div>
                            </div>
                            <div v-show="isActiveTab('appearance')" class="settings-body">
                                <div class="heading-body">
                                  <h4 class="heading-title">Appearance</h4>
                                  <settings-frame>
                                      <div class="heading-desc">
                                          <h6 class="heading-subtitle">Dark mode</h6>
                                          <p class="heading-text">Enable dark mode. Easy on the eyes.</p>
                                      </div>
                                      <settings-toggle :name="'dark_mode'" :user_options="user_options" :func="changeOption"></settings-toggle>
                                  </settings-frame>
                                </div>
                            </div>
                            <div v-show="isActiveTab('notifications')" class="settings-body">
                                <div class="heading-body">
                                  <h4 class="heading-title">Notifications</h4>
                                  <settings-frame>
                                      <div class="heading-desc">
                                          <h6 class="heading-subtitle">Desktop Notifications</h6>
                                          <p class="heading-text">Turn on desktop notifications to be alerted when a new message is recieved.</p>
                                      </div>
                                      <settings-toggle :name="'desktop_notifications'" :user_options="user_options" :func="changeOption"></settings-toggle>
                                  </settings-frame>
                                  <settings-frame>
                                      <div class="heading-desc">
                                          <h6 class="heading-subtitle">Message Sounds</h6>
                                          <p class="heading-text">Recieve an alert sound when on new messages.</p>
                                      </div>
                                      <settings-toggle :name="'message_sounds'" :user_options="user_options" :func="changeOption"></settings-toggle>
                                  </settings-frame>
                                </div>
                            </div>
                            <div v-show="isActiveTab('app_information')" class="settings-body">
                                <div class="heading-body">
                                  <h4 class="heading-title">App Information</h4>
                                  <settings-frame class="flex-column">
                                      <p><b>Version: </b>{{ Version::version() }}</p>
                                      <p><b>Build: </b>{{ Version::build() }}</p>
                                  </settings-frame>
                                </div>
                            </div>
                        </div>
                    </div>
                    <a v-on:click="states.settings.display = false" class="close icon settings-close" data-toggle="tooltip" data-placement="top" title="Close"><i class="material-icons">{{ __('close') }}</i></a>
                  </div>
                </div>
            </transition>

            <transition name="scale-under">
                <main v-show="!states.settings.display">
                    <nav class="navbar fixed-top" v-bind:class="{ 'userlist-hide': !states.userlist, 'sidebar-hide': !states.sidebar}">
                      <div class="navbar-nav">
                        <a v-on:click="states.sidebar= !states.sidebar" class="menu icon" data-toggle="tooltip" data-placement="top" title="Sidebar"><i class="material-icons">menu</i></a>
                        <h5 v-if="active_channel">@{{ active_channel.name }}</h5>
                      </div>

                      <div class="navbar-nav ml-auto">
                          <div class="ui-actions ml-auto">
                            <a v-on:click="states.userlist = !states.userlist" class="icon" id="user-list" data-toggle="tooltip" data-placement="top" title="User List"><i class="material-icons">supervised_user_circle</i></a>
                          </div>
                      </div>

                      <div v-show="states.sidebar" class="sidebar sidebar-wrapper sidebar-toggle">
                        <div class="sidebar-inner">
                            <div class="brand">
                                  <user-frame :user="current_user" :text="true"></user-frame>
                                  <div class="user-actions ml-auto">
                                    <span data-toggle="modal" data-target="#channel-create-modal">
                                        <a class="icon" id="channel-create" data-toggle="tooltip" data-placement="top" title="Create Channel"><i class="material-icons">add</i></a>
                                    </span>
                                    <a v-on:click="states.settings.display = true" class="icon" id="settings" data-toggle="tooltip" data-placement="top" title="Account Settings"><i class="material-icons">settings</i></a>
                                  </div>
                            </div>
                            <ul class="sidebar-nav" id="channels">
                              <h3>Channels</h3>
                              <li v-for="(channel, key, index) in channels" v-bind:data-channel_id="channel.channel_id" v-bind:class="{ active: channel.channel_id == states.current_channel }">
                                <a v-on:click="states.current_channel = channel.channel_id">
                                    <i class="material-icons">people</i>
                                    <div class="channel-name">@{{ channel.name }}</div>
                                </a>
                                <div class="channel-actions justify-content-end">
                                    <div v-if="channel.user_id == current_user.id" @click="states.modal.item = channel" class="icon" id="channel-settings" data-toggle="modal" data-target="#channel-edit-modal"><i data-toggle="tooltip" data-placement="top" title="Edit channel" class="material-icons">settings</i></div>
                                    <span v-show="unread[channel.channel_id] !== 0" class="badge new-message">@{{ unread[channel.channel_id] }}</span>
                                </div>
                              </li>
                              <li class="create-channel">
                                <a data-toggle="modal" data-target="#channel-create-modal">
                                    <i class="material-icons">add</i>
                                    <div class="channel-name">Create new channel</div>
                                </a>
                              </li>
                            </ul>
                        </div>
                      </div>

                      <div v-show="states.userlist" class="sidebar sidebar-wrapper sidebar-toggle sidebar-left inline-scroll scroll-dark">
                        <ul class="sidebar-nav" id="users">
                            <a @click="states.userlist = false" class="close icon"><i class="material-icons">close</i></a>
                              <h3 v-if="usersStatus.online.length">Online</h3>
                              <li v-for="(user, index) in usersStatus.online" :class="{ bottom: isLast(index, usersStatus.online) }">
                                <a data-toggle="popover" data-placement="left" :data-user_id="user.id">
                                    <div class="channel-icon">
                                        <user-frame :user="user"></user-frame>
                                    </div>
                                    @{{ user.username }}
                                </a>
                              </li>
                              <h3 v-if="usersStatus.offline.length">Offline</h3>
                              <li v-for="(user, index) in usersStatus.offline" :class="{ bottom: isLast(index, usersStatus.offline) }">
                                <a data-toggle="popover" data-placement="left" :data-user_id="user.id">
                                    <div class="channel-icon">
                                        <user-frame :user="user"></user-frame>
                                    </div>
                                    @{{ user.username }}
                                </a>
                              </li>
                        </ul>
                      </div>
                    </nav>
                    @yield('content')
                </main>
            </transition>
            <div class="background dark-bg"></div>
        @endguest
    </div>
</body>
</html>
