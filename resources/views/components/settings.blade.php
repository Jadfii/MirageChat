@extends('layouts.app')

@section('settings')
<div v-show="states.settings.display" class="flex position-fixed bg-white h-100 w-100" style="transform: scale(1); z-index: 4;">
  <div class="flex flex-row py-5 h-100 bg-dark" style="width: 20%;">
    <at-menu theme="dark" class="border-0 bg-dark h-100 py-4 ml-auto" mode="vertical" active-name="account_details">
        <h5 class="text-white my-2" style="padding-left: 16px !important;">My Account</h5>
        <at-menu-item name="account_details">
          <div class="position-relative flex align-items-center" style="padding: 6px 16px; overflow: hidden;">
              <span class="">Account Details</span>
          </div>
        </at-menu-item>
    </at-menu>
  </div>
  <div class="flex flex-fill py-5 h-100 position-relative" style="padding-left: 5%;">
    <div class="pt-4">
      <h1>Account Details</h1>
      <at-card class="bg-dark mt-3" style="width: 900px;" :no-hover="true">
        <div v-show="!states.settings.account_edit" style="height: 100px;" class="flex flex-row">
          <img class="rounded-circle h-100" data-user_id="{{ Auth::user()->id }}" src="{{ asset('storage/avatars/'.Auth::user()->id.'.png') }}">
          <div class="flex flex-column ml-4 align-self-center">
            <div class="">
              <h5 class="text-white text-uppercase ls-1">Username</h5>
              <p class="text-white">@{{ current_user.username }}</p>
            </div>
            <div class="mt-4">
              <h5 class="text-white text-uppercase ls-1">Email</h5>
              <p class="text-white">@{{ current_user.email }}</p>
            </div>
          </div>
          <at-button @click="states.settings.account_edit = true" type="primary" class="ml-auto align-self-center">Edit</at-button>
        </div>
        <div v-show="states.settings.account_edit" style="height: 200px;" class="flex flex-row">
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
                <at-input name="username" class="mt-1" size="large" :value="current_user.username"></at-input>
              </div>
              <div class="mt-4">
                <h5 class="text-white text-uppercase ls-1">Email</h5>
                <at-input name="email" class="mt-1" size="large" :value="current_user.email"></at-input>
              </div>
              <div class="mt-auto flex flex-row">
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
    </div>
  </div>
  <a class="flex align-items-center justify-content-center position-absolute" @click="states.settings.display = false" style="height: 35px; width: 35px; top: 5%; right: 5%;">
    <i style="font-size: 2rem;" class="icon icon-x-circle"></i>
  </a>
</div>
@endsection
