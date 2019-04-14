@extends('layouts.app')

@section('settings')
<div v-show="states.settings.display" :class="[ user_options.dark_mode ? 'bg-dark':'bg-white' ]" class="flex position-fixed h-100 w-100" style="transform: scale(1); z-index: 4;">
  <div :class="[ user_options.dark_mode ? 'bg-darker':'bg-dark' ]" class="flex flex-row py-5 h-100 flex-shrink-0" style="width: 30%;">
    <at-menu theme="dark" :class="[ user_options.dark_mode ? 'bg-darker':'bg-dark' ]" class="border-0 bg-dark h-100 py-4 ml-auto" mode="vertical" :active-name="states.settings.active_tab">
        <h5 class="text-white mb-2" style="padding-left: 16px !important;">My Account</h5>
        <at-menu-item name="account_details">
          <div @click="states.settings.active_tab = 'account_details'" class="position-relative flex align-items-center" style="padding: 6px 16px; overflow: hidden;">
              <span class="">Account Details</span>
          </div>
        </at-menu-item>

        <h5 class="text-white mb-2 mt-4" style="padding-left: 16px !important;">App Settings</h5>
        <at-menu-item name="notifications">
          <div @click="states.settings.active_tab = 'notifications'" class="position-relative flex align-items-center" style="padding: 6px 16px; overflow: hidden;">
              <span class="">Notifications</span>
          </div>
        </at-menu-item>
        <at-menu-item name="appearance">
          <div @click="states.settings.active_tab = 'appearance'" class="position-relative flex align-items-center" style="padding: 6px 16px; overflow: hidden;">
              <span class="">Appearance</span>
          </div>
        </at-menu-item>

        <li class="at-menu__item mt-4">
          <div class="at-menu__item-link">
              <div onclick="document.getElementById('logout-form').submit()" class="position-relative flex align-items-center" style="padding: 6px 16px; overflow: hidden;">
                  <span class="text-danger">Sign Out</span>
              </div>
          </div>
        </li>
    </at-menu>
  </div>
  <div class="flex flex-fill py-5 h-100 position-relative" style="padding-left: 5%; padding-right: 10%;">
    <div v-show="states.settings.active_tab == 'account_details'" class="pt-4 w-75">
      <h1 :class="{ 'text-white': user_options.dark_mode }" class="mb-3">My Account</h1>
      <at-card :class="[ user_options.dark_mode ? 'bg-darker':'bg-dark', { 'border-darker': user_options.dark_mode } ]" class="w-100" :no-hover="true">
        <div v-show="!states.settings.account_edit" style="height: 100px;" class="flex flex-row">
          <img class="rounded-circle h-100" data-user_id="{{ Auth::user()->id }}" src="{{ asset('storage/avatars/'.Auth::user()->id.'.png') }}">
          <div class="flex flex-column ml-4 align-self-center">
            <div class="">
              <h6 class="text-white font-weight-bold text-uppercase ls-1">Username</h6>
              <p class="text-white">@{{ current_user.username }}</p>
            </div>
            <div class="mt-4">
              <h6 class="text-white font-weight-bold text-uppercase ls-1">Email</h6>
              <p class="text-white">@{{ current_user.email }}</p>
            </div>
          </div>
          <at-button @click="states.settings.account_edit = true" type="primary" class="ml-auto align-self-center">Edit</at-button>
        </div>
        <div v-show="states.settings.account_edit" class="flex flex-row">
          <form @submit.prevent="editAccount" class="flex flex-row w-100">
            <div class="position-relative avatar" style="height: 100px; width: 100px;">
              <div class="avatar__text bg-dark position-absolute flex align-items-center justify-content-center text-center text-uppercase text-white" style="height: 100px; width: 100px; cursor: pointer;" onclick="document.getElementsByName('avatar')[0].click()">Change Avatar</div>
              <input @change="editAvatar" class="position-absolute" style="top: -5000px" accept="image/x-png,image/jpeg,image/jpg" type="file" name="avatar">
              <input class="position-absolute" style="top: -5000px" type="text" class="form-control" readonly>
              <img :src="this.states.account.avatar_upload" data-user_id="{{ Auth::user()->id }}" class="rounded-circle avatar__img" style="height: 100px;">
            </div>
            <div class="flex flex-column w-100 ml-4">
              <div class="">
                <h5 class="text-white text-uppercase ls-1">Username</h5>
                <at-input name="username" class="darker mt-1" size="large" v-model="states.account.username"></at-input>
              </div>
              <div class="mt-4">
                <h5 class="text-white text-uppercase ls-1">Email</h5>
                <at-input name="email" class="darker mt-1" size="large" v-model="states.account.email"></at-input>
              </div>
              <div class="mt-4">
                <h5 class="text-white text-uppercase ls-1">Current password</h5>
                <at-input name="password_old" class="darker mt-1" size="large" type="password" v-model="states.account.password"></at-input>
              </div>
              <div class="mt-4">
                <at-button @click="states.settings.password_edit = true" v-show="!states.settings.password_edit" type="text" class="text-white p-0">Change password?</at-button>
                <div v-show="states.settings.password_edit">
                  <h5 class="text-white text-uppercase ls-1">New password</h5>
                  <at-input name="password" class="darker mt-1" size="large" type="password"></at-input>
                </div>
              </div>
              <div class="mt-4 pt-3 flex flex-row border-top">
                <at-button class="align-self-start" type="error" hollow class="">Delete Account</at-button>
                <div class="align-self-end ml-auto">
                  <at-button @click="states.settings.account_edit = false" type="text" class="text-white">Cancel</at-button>
                  <button type="submit" class="p-0 m-0" style="border: none; background: none;"><at-button type="success" class="">Save</at-button></button>
                </div>
              </div>
            </div>
          </form>
        </div>
      </at-card>
      <at-card :class="[ user_options.dark_mode ? 'bg-darker':'bg-alt', { 'border-darkest': user_options.dark_mode } ]" class="mt-5 w-100" :no-hover="true">
        <div class="">
          <h1 :class="{ 'text-white': user_options.dark_mode }" class="mb-3">2-Factor Authentication</h1>
          <at-button v-if="!current_user.google2fa_secret" class="" @click="enable_2fa" type="primary">Enable 2-Factor Authentication</at-button>
          <at-button v-else class="" @click="remove_2fa" type="error" hollow>Remove 2-Factor Authentication</at-button>
        </div>
      </at-card>
    </div>

    <div v-show="states.settings.active_tab == 'notifications'" class="pt-4 w-75">
      <at-card :class="[ user_options.dark_mode ? 'bg-darker':'bg-alt', { 'border-darker': user_options.dark_mode } ]" class="w-100" :no-hover="true">
        <h1 :class="{ 'text-white': user_options.dark_mode }" class="mb-3">Notifications</h1>
        <div class="flex flex-row align-items-center mb-5">
          <div class="flex flex-column">
            <h4 :class="{ 'text-white': user_options.dark_mode }" class="mb-1">Desktop Notifications</h4>
            <p :class="{ 'text-light': user_options.dark_mode }">Turn on desktop notifications to be alerted when a new message is recieved.</p>
          </div>
          <at-switch :value="states.settings.options.desktop_notifications" @change="changeOption('desktop_notifications')" size="large" class="ml-auto"></at-switch>
        </div>
        <div class="flex flex-row align-items-center mb-5">
          <div class="flex flex-column">
            <h4 :class="{ 'text-white': user_options.dark_mode }" class="mb-1">Message Sounds</h4>
            <p :class="{ 'text-light': user_options.dark_mode }">Recieve an alert sound when on new messages.</p>
          </div>
          <at-switch :value="states.settings.options.message_sounds" @change="changeOption('message_sounds')" size="large" class="ml-auto"></at-switch>
        </div>
      </at-card>
    </div>

    <div v-show="states.settings.active_tab == 'appearance'" class="pt-4 w-75">
      <at-card :class="[ user_options.dark_mode ? 'bg-darker':'bg-alt', { 'border-darker': user_options.dark_mode } ]" class="w-100" :no-hover="true">
        <h1 :class="{ 'text-white': user_options.dark_mode }" class="mb-3">Appearance</h1>
        <div class="flex flex-row align-items-center mb-5">
          <div class="flex flex-column">
            <h4 :class="{ 'text-white': user_options.dark_mode }" class="mb-1">Dark mode</h4>
            <p :class="{ 'text-light': user_options.dark_mode }">Enable dark mode. Easy on the eyes.</p>
          </div>
          <at-switch :value="states.settings.options.dark_mode" @change="changeOption('dark_mode')" size="large" class="ml-auto"></at-switch>
        </div>
      </at-card>
    </div>
  </div>
  <div class="flex flex-column align-items-center position-absolute" style="top: 5%; right: 15%;">
    <a :class="{ 'dark': user_options.dark_mode }" class="flex align-items-center justify-content-center rounded-circle border" @click="states.settings.display = false" style="height: 35px; width: 35px;" data-toggle="tooltip" data-placement="top" data-offset-tooltip="14px" title="Close">
      <i :class="{ 'text-white': user_options.dark_mode }" style="font-size: 1.25rem;" class="icon icon-x"></i>
    </a>
    <p :class="{ 'text-white': user_options.dark_mode }" class="" style="margin-top: 2px;">ESC</p>
  </div>
</div>
@endsection
