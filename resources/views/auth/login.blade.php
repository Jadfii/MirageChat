@extends('layouts.app')
@section('content')
<div class="my-2 align-self-center w-100">
  <h1 class="my-2">Login</h1>
  <h4>Sign in by entering your information below</h4>
</div>
<form class="w-100 flex flex-column justify-content-center align-self-center" method="POST" action="{{ route('login') }}">
  @csrf
  <at-alert message="{{ $errors->first() }}" class="{{ $errors->all() ? '' : 'd-none' }}" type="error"></at-alert>
  <at-input size="large" name="email" type="email" placeholder="Email" value="{{ old('email') }}" status="{{ $errors->all() ? 'error' : '' }}" class="my-2" required {{ old('email') ? '' : 'autofocus' }}></at-input>
  <at-input size="large" name="password" type="password" placeholder="Password" status="{{ $errors->all() ? 'error' : '' }}" class="my-2" required {{ old('email') ? 'autofocus' : '' }}></at-input>
  <div class="my-2 mx-0 row flex-column-sm">
    <input type="submit" style="display: none;"></input>
    <at-button type="primary" onclick="this.form.submit();">Log in to your account</at-button>
    <a href="{{ route('password.request') }}" class="align-self-center ml-none-sm ml-auto"><at-button type="text">Forgot Your Password?</at-button></a>
  </div>
  <div class="my-2">Don't have an account? <a href="/register">Sign up</a></div>
</form>
@endsection

@section('illustration')
<img height="250px" src="{{ asset('icons/login.svg') }}">
@endsection
