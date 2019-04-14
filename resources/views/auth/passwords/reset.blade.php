@extends('layouts.app')
@section('content')
<div class="my-2">
  <h1 class="my-2">Reset password</h1>
  <h4>Enter your new password below</h4>
</div>
<form class="w-100 flex flex-column justify-content-center align-self-center" method="POST" action="{{ route('password.update') }}">
  @csrf
  <at-alert message="{{ $errors->first() }}" class="{{ $errors->all() ? '' : 'd-none' }}" type="error"></at-alert>
  <at-input name="email" placeholder="Email" status="{{ $errors->has('email') ? 'error' : '' }}" type="email" size="large" class="my-2" required autofocus></at-input>
  <at-input name="password" placeholder="Password" status="{{ $errors->has('password') ? 'error' : '' }}" type="password" size="large" class="my-2" required></at-input>
  <at-input name="password_confirmation" placeholder="Repeat password" status="{{ $errors->has('password_confirmation') ? 'error' : '' }}" type="password" size="large" class="my-2" required></at-input>
  <div class="my-2 mx-0 row">
    <input type="submit" style="display: none;"></input>
    <at-button type="primary" onclick="this.form.submit();">Change password</at-button>
  </div>
  <div class="my-2"><a href="/">Go back</a></div>
</form>
@endsection

@section('illustration')
<img height="250px" src="{{ asset('icons/change_password.svg') }}">
@endsection
