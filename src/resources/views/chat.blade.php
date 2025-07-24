@extends('layouts.base')
@section('title', 'チャット')
@push('javascripts')
  @php
    $publicPath = asset('js');
    $javascripts = ['app.js', 'chat.js']
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
  <div id="values"
    {{-- $isSeller:true => 自分が出品者 --}}
    {{-- $isSeller:false => 自分が購入者 --}}
    {{-- チャット相手の情報を取得したい --}}
    {{-- $purchase->userは購入者の情報／$purchase->item->userは出品者の情報 --}}
    @php $receiverId = $isSeller ? $purchase->user->id : $purchase->item->user->id; @endphp
    data-purchaseid="{{ $purchase->id }}"
    data-senderid="{{ auth()->id() }}"
    data-receiverid="{{ $receiverId }}"
    data-storage="{{ Storage::url('profile_images/') }}"
    data-chatsend="{{ route('chat.send', ['purchase_id' => $purchase->id, 'receiver_id' => $receiverId]) }}"
    data-chatread="{{ route("chat.read", ['chat_id' => ':chatid', 'purchase_id' => $purchase->id, 'receiver_id' => $receiverId]) }}"
    data-chatupdate="{{ route("chat.update", ['receiver_id' => $receiverId, 'chat_id' => ':chatid']) }}"
    data-chatdelete="{{ route("chat.delete", ['receiver_id' => $receiverId, 'chat_id' => ':chatid']) }}"
    data-closechat="{{ route('chat.close', ['purchase_id' => $purchase->id]) }}"
    data-chatsendimage="{{ route('chat.send.image', ['purchase_id' => $purchase->id, 'receiver_id' => $receiverId]) }}"
    ></div>
  <div id="chat" data-purchaseid="{{ $purchase->id }}" data-receiverid="{{ $receiverId }}">
    <div class="container">
      <aside class="sidebar">
        <h3 class="sidebar-title">取引チャット</h3>
        <div class="sidebar-container">
          <div class="sidebar-listed-container">
            <h4 class="sidebar-title-listed">出品</h4>
            <ul class="sidebar-items">
              @foreach ($sellingItems as $item)
                <li class="sidebar-items-name"><a href="{{ route('chat', $item->id)}}">{{ $item->item->name }}</a></li>
              @endforeach
            </ul>
          </div>
          <div class="sidebar-title-processing-container">
            <h4 class="sidebar-title-processing">購入</h4>
            <ul class="sidebar-items">
              @foreach ($purchasingItems as $item)
                <li class="sidebar-items-name"><a href="{{ route('chat', $item->id)}}">{{ $item->item->name }}</a></li>
              @endforeach
            </ul>
          </div>
        </div>
      </aside>
      <div class="chat">
        <div class="chat-title">
          <div class="chat-title-profile-outer-frame">
            @php
              $profileImage = $isSeller ? $purchase->user->image : $purchase->item->user->image;
            @endphp
            @if ($profileImage && Storage::exists('profile_images/'.$profileImage))
              <img class="chat-title-profile-inner-frame"
                src="{{ Storage::url('profile_images/').$profileImage }}" alt="プロフィールの画像">
            @else
              <div class="chat-title-profile-no-image">
                <p>NO</p>
                <p>IMAGE</p>
              </div>
            @endif
          </div>
          <h1 class="chat-title-content"><span>{{ $isSeller ? $purchase->user->name : $purchase->item->user->name }}さんとの</span>取引画面</h1>
          @if (!$isSeller && $purchase->status === 'completed' && $purchase->is_chat_enabled)
            <button id="close-chat-button" class="c-btn c-btn--chat-close">チャットを終了</button>
            <dialog id="close-chat-dialog" class="chat-close-dialog">
              <p>チャットを終了しますか？</p>
              <p>※ 終了後、メッセージの送受信はできなくなります。</p>
              <p>※ 終了後もチャットの閲覧は可能です。</p>
              <div class="chat-close-dialog-flex-container">
                <button id="close-chat-confirmed" class="c-btn c-btn--modal-edit" type="button">終了</button>
                <a id="close-chat-cancelled" class="c-btn c-btn--modal-edit-cancel">キャンセル</a>
              </div>
            </dialog>
          @endif
        </div>
        <div class="chat-item">
          @if ($purchase->item->image && Storage::exists('item_images/'.$purchase->item->image))
            <img src="{{ Storage::url('item_images/'.$purchase->item->image) }}" width="200" height="200" alt="">
          @else
            <img class="c-no-image" src="{{ asset('img/'.'no_image.jpg') }}" width="200" height="200" alt="">
          @endif
          <div class="chat-item-info">
            <h2 class="chat-item-info-name">{{ $purchase->item->name}}</h2>
            <p class="chat-item-info-price">¥{{ number_format($purchase->item->price) }}</p>
          </div>
        </div>
        <div class="chat-content">
          <ul>
            {{-- class="chat-content-list right"にすると右に寄る --}}
            @foreach ($chats as $chat)
              <li class="chat-content-list {{ $chat->sender_id != auth()->id() ? 'left' : 'right'}}">
                <div class="chat-content-list-profile">
                  <div class="chat-content-list-profile-outer-frame">
                    @if ($chat->user->image && Storage::exists('profile_images/'.$chat->user->image))
                      <img class="chat-content-list-profile-inner-frame" src="{{ Storage::url('profile_images/').$chat->user->image }}" alt="プロフィールの画像">
                    @else
                      <div class="chat-content-list-profile-no-image">
                        <p>NO</p>
                        <p>IMAGE</p>
                      </div>
                    @endif
                  </div>
                  <p class="chat-content-list-profile-name">{{ $chat->user->name }}</p>
                </div>
                <div class="chat-content-list-container">
                  @if ($chat->is_text && !$chat->is_deleted)
                    <p class="chat-content-list-message" data-chatid="{{ $chat->id }}">{{ $chat->message }}</p>
                  @elseif ($chat->is_text && $chat->is_deleted)
                    <p class="chat-content-list-message deleted" data-chatid="{{ $chat->id }}">このメッセージは削除されました</p>
                  @elseif (!$chat->is_text && !$chat->is_deleted)
                    <img class="chat-content-list-image" src="{{ Storage::url('chat_images/').$chat->message }}" alt="チャットの画像" data-chatid="{{ $chat->id }}">
                  @elseif (!$chat->is_text && $chat->is_deleted)
                    <p class="chat-content-list-image deleted" data-chatid="{{ $chat->id }}">この画像は削除されました</p>
                  @endif
                  <div class="chat-content-list-information">
                    @if ($chat->sender_id == auth()->id() && $chat->is_read == 1)
                      <p class="chat-content-list-read">既読</p>
                    @endif
                    <p class="chat-content-list-datetime">{{ $chat->created_at->format('Y/m/d H:i') }}</p>
                  </div>
                  @if ($chat->sender_id == auth()->id() && !$chat->is_deleted)
                    <div class="chat-content-list-edit" data-istext="{{ $chat->is_text }}">
                      @if ($chat->is_text)
                        <a class="chat-content-list-edit-update">編集</a>
                      @endif
                      <a class="chat-content-list-edit-delete">削除</a>
                    </div>
                  @endif
                </div>
              </li>
            @endforeach
          </ul>
          {{-- メッセージ編集 --}}
          <dialog id="update" class="chat-content-modal-update">
            <form id="update-form">
              @csrf
              <textarea id="update-textarea" name="updated-message" placeholder="メッセージを入力してください"></textarea>
              <div class="chat-content-modal-update-buttons">
                <button id="update-submit" class="c-btn c-btn--modal-edit" type="button">編集</button>
                <a id="update-cancel" class="c-btn c-btn--modal-edit-cancel">キャンセル</a>
              </div>
            </form>
          </dialog>
          {{-- メッセージ削除 --}}
          <dialog id="delete" class="chat-content-modal-delete">
            <div id="delete-container" class="chat-content-modal-delete-container">
              @csrf
              <p id="delete-p"></p>
              <div class="chat-content-modal-delete-buttons">
                <button id="delete-submit" class="c-btn c-btn--modal-edit" type="button">削除</button>
                <a id="delete-cancel" class="c-btn c-btn--modal-edit-cancel">キャンセル</a>
              </div>
            </div>
          </dialog>
          {{-- 画像削除 --}}
          <dialog id="delete-img" class="chat-content-modal-delete-img">
            <div id="delete-container-image" class="chat-content-modal-delete-container">
              @csrf
              <img id="delete-img-img" src="">
              <div class="chat-content-modal-delete-buttons">
                <button id="delete-submit-image" class="c-btn c-btn--modal-edit" type="button">削除</button>
                <a id="delete-cancel-image" class="c-btn c-btn--modal-edit-cancel">キャンセル</a>
              </div>
            </div>
          </dialog>
          {{-- 画像プレビュー --}}
          <dialog id="enlarge-img" class="chat-content-modal-enlarge-img">
            <img src="">
            <a id="enlarge-img-close" class="c-btn c-btn--modal-enlarge-img-close">閉じる</a>
          </dialog>
          {{-- データ送信用操作盤 --}}
          @if ($purchase->is_chat_enabled)
            <div class="chat-content-send">
              <p id="validation-error" class="c-error-message-top"></p>
              <form id="form">
                @csrf
                <div id="input-container" class="chat-content-send-textarea c-flex">
                  <div id="input-dummy" class="c-flex-dummy"></div>
                  <textarea id="input" class="chat-content-send-input c-flex-textarea" type="text" name="message" value="" placeholder="取引メッセージを入力してください（Enter+Ctrlで送信）"></textarea>
                </div>
                <label id="label" class="c-btn c-btn--chat-add-image" for="img-input">画像を追加</label>
                <input id="img-input" class="chat-content-send-img-input" type="file" name="image" accept="image/*"/>
                <button id="submit-text" class="chat-content-send-submit" type="button"></button>
              </form>
              <dialog id="preview-img" class="chat-content-send-modal">
                <form id="preview-img-form">
                  <img id="preview-img-img" src="">
                  <input id="preview-img-input" type="hidden" name="base64" value=""/>
                  <div class="chat-content-send-modal-buttons">
                    <button id="submit-image" class="c-btn c-btn--modal-preview-img-send" type="button">送信</button>
                    <button id="preview-img-cancel" class="c-btn c-btn--modal-preview-img-cancel" type="button">キャンセル</button>
                  </div>
                </form>
              </dialog>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
@endsection