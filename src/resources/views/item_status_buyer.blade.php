@extends('layouts.base')
@section('title', '商品ステータス')
@push('javascripts')
  @php
    $javascripts = ['change_status.js', 'rating.js'];
    $publicPath = asset('js');
  @endphp
  @foreach ($javascripts as $javascript)
    <script src="{{ "{$publicPath}/{$javascript}" }}" defer></script>
  @endforeach
@endpush
{{-- @section('modal')
  @include('components.modal')
@endsection --}}
@section('header')
  @include('components.header')
@endsection
@section('content')
  <div id="values"
    data-changestatustocompleted="{{ route('status.completed', $purchase->id) }}"
    data-type="{{ request()->routeIs('status.seller.show') ? 'seller' : 'buyer' }}"
    data-rating="{{ route('user.rating', $purchase->item->user->id) }}"
    ></div>
  <div id="item-status">
    <div class="detail">
      <dl>
        <div class="img-container">
          <dt class="detail-label">商品画像</dt>
          <dd class="detail-value">
            <a class="detail-value-img" href="{{ route('item.show', $purchase->item_id) }}">
              @if ($purchase->item->image && Storage::exists('item_images/'.$purchase->item->image))
                <img src="{{ Storage::url('item_images/').$purchase->item->image }}" width="80" height="80" alt="{{ $purchase->item->name }}の画像">
              @else
                <img class="c-no-image" src="{{ asset('img/'.'no_image.jpg') }}" width="80" height="80" alt="商品の画像がありません">
              @endif
            </a>
          </dd>
        </div>
        <div class="grid-container">
          <div class="grid-item grid-item-name">
            <dt class="detail-label">商品名</dt>
            <dd class="detail-value">{{ $purchase->item->name }}</dd>
          </div>
          <div class="grid-item grid-item-price">
            <dt class="detail-label price">価格</dt>
            <dd class="detail-value">{{ $purchase->item->price }}円</dd>
          </div>
          <div class="grid-item">
            <dt class="detail-label">販売者名</dt>
            <dd class="detail-value">{{ $purchase->item->user->name }}</dd>
          </div>
          <div class="grid-item">
            <dt class="detail-label">販売者メールアドレス</dt>
            <dd class="detail-value">{{ $purchase->item->user->email }}</dd>
          </div>
        </div>
      </dl>
    </div>
    <div class="status">
      <dl>
        <dt class="status-label">取引ステータス</dt>
        <dd id="status" class="status-value">{{ $purchase->status_text }}</dd>
        <dt class="status-label">購入日</dt>
        <dd class="status-value">{{ $purchase->formattedCreatedAt }} </dd>
        <dt class="status-label">発送日</dt>
        <dd id="shipped-at" class="status-value">{{ $purchase->formattedShippedAt }}</dd>
      </dl>
      @if ($purchase->isStatusChangeable)
        <button id="change-status-button" class="c-btn c-btn--red change-status-button">取引ステータス変更</button>
      @endif
    </div>
    <dialog id="status-dialog" class="item-status-dialog">
      <h2 class="item-status-dialog-title">取引ステータスを変更しますか？</h2>
      <div class="item-status-dialog-grid-container">
        <p>現在のステータス:</p>
        <p>{{ $purchase->status_text }}</p>
        <p>次のステータス:</p>
        <p>{{ $purchase->nextStatus() }}</p>
      </div>
      <form id="close-chat-form" method="POST">
        <label for="close-chat" class="item-status-dialog-checkbox">
          <input id="close-chat" type="checkbox" name="close_chat_checkbox" value="1"><span>取引完了と同時にチャットも終了する</span>
        </label>
      </form>
      <div class="item-status-dialog-buttons">
        <button id="change-status" class="c-btn c-btn--modal-edit item-status-dialog-buttons-confirm" type="button">変更</button>
        <a id="change-status-cancel" class="c-btn c-btn--modal-edit-cancel items-status-dialog-buttons-cancel">キャンセル</a>
      </div>
    </dialog>
    <dialog id="rating-dialog" class="modal">
      <form id="rating-form">
        <h2 class="modal-content-title">取引が完了しました</h2>
        <p class="modal-content-text">今回の取引相手はいかがでしたか？</p>
        <div id="stars" class="modal-content-stars">
          <div class="modal-content-stars-star" data-number="1"></div>
          <div class="modal-content-stars-star" data-number="2"></div>
          <div class="modal-content-stars-star" data-number="3"></div>
          <div class="modal-content-stars-star" data-number="4"></div>
          <div class="modal-content-stars-star" data-number="5"></div>
          {{-- 評価の値をvalueにいれる --}}
        </div>
        <input id="modal-input" type="hidden" name="rating" value="">
        <button id="modal-button" class="modal-content-btn c-btn c-btn--modal-send" type="button" disabled>送信する</button>
      </form>
    </dialog>
  </div>
@endsection