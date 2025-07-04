@extends('layouts.base')
@section('title', '商品ステータス')
@push('javascripts')
  @php
    $javascripts = ['change_status.js'];
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
      <div class="item-status-dialog-buttons">
        <button id="change-status" class="c-btn c-btn--modal-edit item-status-dialog-buttons-confirm" type="button">変更</button>
        <a id="change-status-cancel" class="c-btn c-btn--modal-edit-cancel items-status-dialog-buttons-cancel">キャンセル</a>
      </div>
    </dialog>
  </div>
@endsection