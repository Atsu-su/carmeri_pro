@extends('layouts.base')
@section('title', 'パスワード再設定')
@section('header')
  @include('components.header')
@endsection
@section('content')
  <div class="c-default-form" id="forgot-password">
    <h1 class="title">パスワードの再設定</h1>
    <form class="form" action="{{ route('password.email') }}" method="POST">
      @csrf
      <label class="form-title">メールアドレスを入力してください</label>
      <input class="form-input" type="text" name="email">
      @if (session('status'))
        <p class="form-status">{{ session('status') }}</p>
      @endif
      <button class="form-btn c-btn c-btn--red" type="submit">送信</button>
    </form>
  </div>
@endsection