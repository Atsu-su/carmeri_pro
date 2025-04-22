@extends('layouts.base')
@section('title', 'プロフィール入力')
@push('javascripts')
  @php
    $publicPath = asset('js');
    $javascripts = ['preview.js']
  @endphp
  @foreach ($javascripts as $javascript)
    <script src="{{ "{$publicPath}/{$javascript}" }}" defer></script>
  @endforeach
@endpush
@section('modal')
  @include('components.modal')
@endsection
@section('header')
  @include('components.header')
@endsection
@section('content')
  <div id="profile" class="c-default-form">
    @error('is_changed')
      <p class="c-error-message">{{ $message }}</p>
    @enderror
    <h1 class="title">プロフィール編集</h1>
    <form class="form" action="{{ route('profile.update') }}" method="post" enctype="multipart/form-data">
      @csrf
      {{-- c-default影響範囲外 ここから --}}
      <div class="img-upload">
        <div id="background" class="c-profile-outer-frame img-upload-preview">
          @php
            // 通常のプロフィール画像表示（画面遷移時など）
            $showImage = !$errors->any() && $user->image && Storage::exists('profile_images/'.$user->image);
            // バリデーションエラー時の表示
            $validationError = $errors->any() && old('file_base64') && old('is_no_image') == 'false';
          @endphp
          @if ($showImage || $validationError)
            <img id="preview" class="c-profile-inner-frame" src="{{ old('file_base64', Storage::url('profile_images/').$user->image) }}" alt="プロフィールの画像">
          @else
            <div id="no-image" class="c-profile-no-image">
              <p>NO</p>
              <p>IMAGE</p>
            </div>
          @endif
        </div>
        <label class="c-btn-img-select c-btn-img-select--profile" for="img-input">
          画像を選択する
        </label>
        <input class="img-upload-input" id="img-input" type="file" accept="image/*" style="display: none"/>
				<input id="file-base64" type="hidden" name="file_base64" value=""/>
        <input id="is-changed" type="hidden" name="is_changed" value="false"/>
        <input id="is-no-image" type="hidden" name="is_no_image" value="{{ is_null($user->image) ? 'true' : 'false' }}">
        <button class="img-upload-reset c-btn-img-reset c-btn-img-reset--profile" id="reset-btn" type="button">画像を削除</button>
      </div>
      {{-- ここまで c-default影響範囲外 --}}
      <p class="img-upload-file-name" id="file-name"></p>
      @error('image')
        <p id="img-error" class="c-error-message">{{ $message }}</p>
      @enderror
      <label class="form-title form-title-name">ユーザー名</label>
      <input class="form-input" type="text" name="name" value="{{ old('name', $user->name) }}">
      @error('name')
        <p class="c-error-message">{{ $message }}</p>
      @enderror
      <label class="form-title">郵便番号</label>
      <input class="form-input" type="text" name="postal_code" value="{{ old('postal_code', $user->postal_code) }}">
      @error('postal_code')
        <p class="c-error-message">{{ $message }}</p>
      @enderror
      <label class="form-title">住所</label>
      <input class="form-input" type="text" name="address" value="{{ old('address', $user->address) }}">
      @error('address')
        <p class="c-error-message">{{ $message }}</p>
      @enderror
      <label class="form-title">建物名</label>
      <input class="form-input" type="text" name="building_name" value="{{ old('building_name', $user->building_name) }}">
      @error('building_name')
        <p class="c-error-message">{{ $message }}</p>
      @enderror
      <button class="form-btn c-btn c-btn--red" type="submit">更新する</button>
      <button id="user-deactivation-btn" class="c-btn c-btn--delete">退会する</button>
    </form>
    <dialog id="user-deactivation-modal" class="c-confirm-modal">
      <form action="{{ route('user.deactivate')}}" method="POST">
        @csrf
        @method('PUT')
        <p>退会しますか？</p>
        <button class="c-confirm-modal-btn c-btn c-btn--red" type="submit">はい</button>
        <a id="close-user-deactivation-modal" class="c-confirm-modal-cancel c-cancel-btn">いいえ</a>
      </form>
    </dialog>
  </div>
@endsection