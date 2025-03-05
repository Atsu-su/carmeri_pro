@extends('layouts.base')
@section('title', 'アカウント有効化')
@section('header')
  @include('components.header')
@endsection
@section('content')
  <div class="c-default-form" id="register">
    <h1 class="title">パスワード変更</h1>
    <form class="form" action="{{ route('password.update') }}" method="POST">
      @csrf
      <label class="form-title">パスワード</label>
      <input class="form-input" type="password" name="password">
      @error('password')
        <p class="c-error-message">{{ $message }}</p>
      @enderror
      <label class="form-title">確認用パスワード</label>
      <input class="form-input" type="password" name="password_confirmation">
      @error('confirm_password')
        <p class="c-error-message">{{ $message }}</p>
      @enderror
      <input type="hidden" name="email" value="{{ request()->query('email') }}">
      <input type="hidden" name="token" value="{{ request()->route('token') }}">
      <button class="form-btn c-btn c-btn--red" type="submit">送信</button>
    </form>
  </div>
@endsection