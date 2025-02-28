@extends('layouts.base')
@section('title', '商品の出品')
@section('header')
  @include('components.header')
@endsection
@section('content')
  <div id="item-input">
    @if (!isset($item))
      <h1 class="title">商品の出品</h1>
      <form class="form" action="{{ route('sell.store') }}" method="post" enctype="multipart/form-data">
    @else
      <h1 class="title">商品情報の変更</h1>
      <form class="form" action="{{ route('sell.update', $item->id) }}" method="post" enctype="multipart/form-data">
    @endif
      @csrf
      <div class="img-upload">
        <h2 class="img-upload-title">商品の画像</h2>
        <div class="img-upload-container">
          <div id="background" class="img-upload-background">
            <img id="preview" src="{{ !isset($item) ? old('file_base64') : old('file_base64') ?? Storage::url('item_images/').$item->image }}" width="100" height="100">
          </div>
          <label id="label" class="img-upload-img-select c-btn-img-select c-btn-img-select--profile" for="img-input">
            画像を選択する
          </label>
          <input class="img-upload-input" id="img-input" type="file" accept="image/*"/>
          <input id="file-base64" type="hidden" name="file_base64" value="{{ old('file_base64') }}"/>
          <input id="is-changed" type="hidden" name="is_changed" value="{{ old('is_changed', 'false') }}"/>
        </div>
        @error('image')
          <p id="img-error" class="c-error-message">{{ $message }}</p>
        @enderror
        <p class="img-upload-file-name" id="file-name"></p>
        <button class="img-upload-reset c-btn-img-reset c-btn-img-reset--profile" id="reset-btn" type="button">画像を削除</button>
      </div>
      <h2 class="form-title">商品の詳細</h2>
      <label class="form-name form-name-category">カテゴリ</label>
      <div class="form-category">
        @foreach ($categories as $category)
          <input type="checkbox" id="{{ $loop->iteration }}" value="{{ $category->id }}" name="category_id[]" {{in_array($category->id, old('category_id', $categoryIdArray ?? [])) ? 'checked' : '' }}>
          <label for="{{ $loop->iteration }}">{{ $category->category }}</label>
        @endforeach
      </div>
      @error('category_id')
        <p class="c-error-message">{{ $message }}</p>
      @enderror
      <label class="form-name form-name-condition">商品の状態</label>
      <div class="form-condition-wrapper">
        <select class="form-condition" name="condition_id">
          <option value="">選択してください</option>
          @foreach ($conditions as $condition)
              <option value="{{ $condition->id }}" {{ old('condition_id', $item->condition_id ?? '') == $condition->id ? 'selected' : '' }}>{{ $condition->condition}}</option>
          @endforeach
        </select>
        @error('condition_id')
          <p class="c-error-message">{{ $message }}</p>
        @enderror
      </div>
      <h2 class="form-title form-title-detail">商品名と説明</h2>
      <label class="form-name">商品名</label>
      <input class="form-input" type="text" name="name" value="{{ old('name', $item->name ?? '') }}">
      @error('name')
        <p class="c-error-message">{{ $message }}</p>
      @enderror
      <label class="form-name form-name-brand">ブランド</label>
      <input class="form-input" type="text" name="brand" value="{{ old('brand', $item->brand ?? '') }}">
      @error('brand')
        <p class="c-error-message">{{ $message }}</p>
      @enderror
      <label class="form-name form-name-description">商品の説明</label>
      <textarea class="form-textarea" name="description"> {{ old('description', $item->description ?? '') }}</textarea>
      @error('description')
        <p class="c-error-message">{{ $message }}</p>
      @enderror
      <label class="form-name form-name-price">販売価格</label>
      <div class="form-price-wrapper">
        <input class="form-input form-input-price" type="text" name="price" value="{{ old('price', $item->price ?? '')}}">
      </div>
      @error('price')
        <p class="c-error-message">{{ $message }}</p>
      @enderror
      @if (!isset($item))
        <button class="form-btn c-btn c-btn--red" type="submit">登録する</button>
      @else
        <button class="form-btn c-btn c-btn--red" type="submit">変更する</button>
        <button id="withdraw-btn" class="c-btn c-btn--delete">出品を取り下げる</button>
      @endif
    </form>
    @if (isset($item))
    <dialog id="withdraw-modal" class="form-modal">
      <form action="{{ route('sell.delete', ['item_id' => $item->id]) }}" method="POST">
        @csrf
        @method('DELETE')
        <p>出品を取り下げますか？</p>
        <button class="form-modal-btn c-btn c-btn--red" type="submit">はい</button>
        <a id="close-withdraw-modal" class="form-modal-cancel c-cancel-btn">いいえ</a>
      </form>
    </dialog>
    @endif
  </div>

  {{-- 画像プレビュー --}}
  <script>
    const imgInput = document.getElementById('img-input');
    const preview = document.getElementById('preview');
    const background = document.querySelector('.img-upload-background');
    const resetBtn = document.getElementById('reset-btn');
    const fileBase64 = document.getElementById('file-base64');
    const fileName = document.getElementById('file-name');
    const label = document.getElementById('label');
    const imgError = document.getElementById('img-error');
    const imagePath = ({{ Js::from(Storage::url('item_images/')) }});
    // 新しく画像が追加された場合、または画像が削除された場合にtrueになる
    const isChanged = document.getElementById('is-changed');

    // ------------------------------
    // 関数
    // ------------------------------

    function showPreview(e) {
      const file = e.target.files[0];

      if (file && file.type.startsWith('image/')) {
        const reader = new FileReader();

        // ロード後の処理
        reader.onload = function(e) {
          preview.src = e.target.result;
          preview.style.display = 'block';
          fileBase64.value = e.target.result;
          background.style.display = 'block';
          resetBtn.style.display = 'block';
          label.style.display = 'none';

          // 画像が変更されたことを示すフラグを立てる
          isChanged.value = 'true';
        }
        reader.readAsDataURL(file);
      }

      // バリデーションエラーメッセージ削除
      if (imgError !== null && imgError !== undefined) {
        imgError.style.display = 'none';
      }

      // ファイル名の表示
      fileName.textContent = `ファイル名：${file.name}`;
    }

    function resetPreview() {
      preview.src = '';
      preview.style.display = 'none';
      fileBase64.value = '';
      background.style.display = 'none';
      resetBtn.style.display = 'none';
      imgInput.value = ''; // ファイル入力をクリア（POSTされる値）
      fileName.textContent = ''; // ファイル名をクリア
      label.style.display = 'grid';

      // 画像が削除されたことを示すフラグを立てる
      isChanged.value = 'true';
    }

    function switchResetBtn() {
			// ページが読み込まれた時、base64のデータがある場合はプレビューを表示
      if (preview.src.includes('data:image') || preview.src.includes(imagePath)) {
        preview.style.display = 'block';
        background.style.display = 'block';
        resetBtn.style.display = 'block';
        label.style.display = 'none';
      }
    }

    // ------------------------------
    // イベント
    // ------------------------------

    document.addEventListener('DOMContentLoaded', switchResetBtn);
    imgInput.addEventListener('change', showPreview);
    resetBtn.addEventListener('click', resetPreview);

    // 出品取り下げ確認モーダル
    const withdrawBtn = document.getElementById('withdraw-btn');
    const withdrawModal = document.getElementById('withdraw-modal');
    const closeWithdrawModal = document.getElementById('close-withdraw-modal');

    withdrawBtn.addEventListener('click', function(event) {
        event.preventDefault();
        withdrawModal.showModal();
    });

    closeWithdrawModal.addEventListener('click', function(event) {
      event.preventDefault();
      withdrawModal.close();
    });
  </script>
@endsection