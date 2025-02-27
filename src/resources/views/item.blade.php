@extends('layouts.base')
@section('title', '商品詳細')
@section('modal')
  @include('components.modal')
@endsection
@section('header')
  @include('components.header')
@endsection
@section('content')
  <div id="item">
    <div class="item-img">
      @if ($item->image && Storage::disk('public')->exists('item_images/'.$item->image))
        <img src="{{ asset('storage/item_images/'.$item->image) }}" width="600" height="600" alt="{{ $item->name }}の画像">
      @else
        <img class="c-no-image" src="{{ asset('img/'.'no_image.jpg') }}">
      @endif
    </div>
    <div class="item-detail">
      @if ($item->isOnSale())
        <h1 class="item-detail-title">{{ $item->name }}</h1>
      @else
        <h1 class="sold item-detail-title">{{ $item->name }}</h1>
      @endif
      <p class="item-detail-brand">{{ $item->brand ?? '' }}</p>
      <p class="item-detail-price">¥<span>{{ number_format($item->price) }}</span> (税込)</p>
      <div class="item-detail-icons">
        @if (auth()->check() && !$item->isOwnItem())
          <div id="like-icon" class="item-detail-icons-icon item-detail-icons-like {{ $like ? 'filled' : '' }} pointer" onclick="toggleLike({{ $item->id }}, '{{ route('like', $item->id) }}')">
        @else
          <div id="like-icon" class="item-detail-icons-icon item-detail-icons-like {{ $like ? 'filled' : '' }}">
        @endif
          <span id="number-of-likes">{{ $item->likes_count }}</span>
        </div>
        <div class="item-detail-icons-icon item-detail-icons-comment">
          <span>{{ $item->comments_count }}</span>
        </div>
      </div>
      @if ($item->isOnSale() && !$item->isOwnItem())
        <a class="item-detail-btn c-btn c-btn--item" href="{{ route('purchase', $item->id) }}">購入手続きへ</a>
      @endif
      <h2 class="item-detail-title-about">商品説明</h2>
      <pre class="c-pre item-detail-about">{{ $item->description }}</pre>
      <h2 class="item-detail-title-general">商品の情報</h2>
      <table class="item-detail-general">
        <tr>
          <th>カテゴリ</th>
          <td>
            @foreach ($item->categoryItems as $categoryItem)
              <span class="c-label-category c-label-category--gray">{{ $categoryItem->category->category }}</span>
            @endforeach
          </td>
        </tr>
        <tr>
          <th>商品の状態</th>
          <td>{{ $item->condition->condition}}</td>
        </tr>
      </table>
      <div class="item-detail-comment">
        <h2 class="item-detail-comment-title">コメント({{ $item->comments_count}})</h2>

        {{-- ここからコメント表示 --}}

        {{--
        ・もしログインしているユーザがこの商品にコメントしていればそれを表示
        ・ログインしているユーザのコメントは他のコメントとは別の変数とする
        ・コメントは$itemに含まず、別の変数で渡す
        --}}

        {{-- ログインユーザのコメント表示 --}}
        @if (isset($myComment))
          <div class="item-detail-comment-commenter">
            <div class="item-detail-comment-commenter-frame">
              @if ($myComment->user->image && Storage::disk('public')->exists('profile_images/'.$myComment->user->image))
                <img src="{{ asset('storage/profile_images/'.$myComment->user->image) }}" alt="プロフィールの画像">
              @else
                <p>NO</p>
                <p>IMAGE</p>
              @endif
            </div>
            <p class="item-detail-comment-commenter-user">{{ $myComment->user->name }}</p>
          </div>
          <div class="item-detail-comment-body">
            <pre id="comment-preview" class="c-pre">{{ $myComment->comment }}</pre>
            <div class="item-detail-comment-body-container">
              @if ($item->isOnSale())
                <a id="update-comment" class="item-detail-comment-body-edit">編集</a>
                <a id="delete-comment" class="item-detail-comment-body-delete">削除</a>
                {{-- モーダル（dialog） --}}
                {{-- 編集 --}}
                <dialog id="edit-modal" class="item-detail-comment-edit-modal">
                  <form action="{{ route('comment.update', ['item_id' => $item->id, 'comment_id' => $myComment->id ])}}" method="post">
                    @csrf
                    <textarea id="edit-textarea" name="comment" cols="30" rows="10">{{ old('comment') }}</textarea>
                    <button class="c-btn c-btn--item" type="submit">コメントを更新する</button>
                    <a id="close-edit-modal" class="c-cancel-btn" class="">キャンセル</a>
                  </form>
                </dialog>
                {{-- 削除 --}}
                <dialog id="delete-modal" class="item-detail-comment-delete-modal">
                  <form action="{{ route('comment.delete', ['item_id' => $item->id, 'comment_id' => $myComment->id ])}}" method="post">
                    @csrf
                    <button class="c-btn c-btn--item" type="submit">コメントを削除する</button>
                    <a id="close-delete-modal" class="c-cancel-btn">キャンセル</a>
                  </form>
                </dialog>
              @endif
            </div>
            @error('comment')
              <p class="c-error-message item-detail-comment-body-error">{{ $message }}</p>
            @enderror
          </div>
        @endif
        @if (isset($comments))
          @foreach ($comments as $comment)
            <div class="item-detail-comment-commenter">
              <div class="item-detail-comment-commenter-frame">
                @if ($comment->user->image && Storage::disk('public')->exists('profile_images/'.$comment->user->image))
                  <img src="{{ asset('storage/profile_images/'.$comment->user->image) }}" alt="プロフィールの画像">
                @else
                  <p>NO</p>
                  <p>IMAGE</p>
                @endif
              </div>
              <p class="item-detail-comment-commenter-user">{{ $comment->user->name }}</p>
            </div>
            <div class="item-detail-comment-body">
              <pre class="c-pre">{{ $comment->comment }}</pre>
            </div>
          @endforeach
        @endif
        {{-- ここまでコメント表示 --}}

        {{-- ここからコメント作成 --}}
        {{-- 自身が出品している商品の場合コメント不可 --}}
        @if (!$item->isOwnItem())
          {{-- ログインしていない場合、タイトルだけ必要（!auth()->check()） --}}
          @if ((auth()->check() && !isset($myComment)) || !auth()->check())
            <h3 class="item-detail-comment-title-form">商品へのコメント</h3>
          @endif
          {{-- ログインしていてコメントを投稿していない場合に可能 --}}
          @if (auth()->check() && !isset($myComment))
            <div class="item-detail-comment-form">
              <form action="{{ route('comment.store', $item->id)}}" method="post">
                @csrf
                <textarea name="comment" id="comment" cols="30" rows="10"></textarea>
                @error('comment')
                  <p class="c-error-message">{{ $message }}</p>
                @enderror
                <button id="submit-comment-btn" class="c-btn c-btn--item" type="submit">コメントを送信する</button>
              </form>
            </div>
          @elseif (!auth()->check())
            <p class="item-detail-comment-login">コメントをするには<a href="{{route('login')}}">ログイン</a>が必要です。</p>
          @endif
        @endif
        {{-- ここまでコメント作成 --}}
      </div>
    </div>
  </div>
  @if (auth()->check())
    <script>
      // ------------------------------
      // 関数
      // ------------------------------

      // ------------------
      // いいね数の増減
      // ------------------
      // いいねの状態を変更するのみで、その時点でのいいねの数は取得していない
      // その時点のいいねの数を取得するには画面のリロードが必要
      async function toggleLike(itemId, url) {
        const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const likeIcon = document.getElementById('like-icon');
        const likes = document.getElementById('number-of-likes');

        console.log(url)

        // 重複処理抑止用1
        if (likeIcon.classList.contains('js-processing')) {
          console.log('処理中です');
          return;
        }

        likeIcon.classList.add('js-processing');

        try {
          const response = await fetch(url, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrf
            }
          });

          if (!response.ok) {
            throw new Error('Network response was not OK');
          }

          const data = await response.json();

          if (data.likeIt) {
            likes.textContent = parseInt(likes.textContent) + 1;  // いいねの数を増やす
            likeIcon.classList.add('filled'); // 星の色を黄色に変更
          } else {
            likes.textContent = parseInt(likes.textContent) - 1;  // いいねの数を減らす
            likeIcon.classList.remove('filled'); // 星の色を白色に変更
          }
        } catch (error) {
          console.error('There has been a problem with your fetch operation:', error);
        } finally {
          likeIcon.classList.remove('js-processing');
        }
      }
    </script>
    {{-- $myComment変数は定義はされていて値がない場合nullが入る --}}
    @if (!empty($myComment))
      <script>
        // ------------------
        // コメント編集モーダル
        // ------------------
        // 変数名の修正から（削除が発生したため重複する）
        const updateComment = document.getElementById('update-comment');
        const closeEditModal = document.getElementById('close-edit-modal');
        const editDialog = document.getElementById('edit-modal');

        closeEditModal.addEventListener('click', function(event) {
          event.preventDefault();
          editDialog.close();
        });

        updateComment.addEventListener('click', function(event) {
            event.preventDefault();
            editDialog.showModal();

            // textareaが空の場合、編集対象のコメントを取得
            const preview = document.getElementById('comment-preview');
            const editTextarea = document.getElementById('edit-textarea');
            editTextarea.textContent ? null : editTextarea.textContent = preview.textContent;
        });

        // ------------------
        // コメント削除モーダル
        // ------------------
        const deleteComment = document.getElementById('delete-comment');
        const closeDeleteModal = document.getElementById('close-delete-modal');
        const deleteDialog = document.getElementById('delete-modal');

        closeDeleteModal.addEventListener('click', function(event) {
          event.preventDefault();
          deleteDialog.close();
        });

        deleteComment.addEventListener('click', function(event) {
            event.preventDefault();
            deleteDialog.showModal();
        });
      </script>

    @else
      <script>
        // ------------------
        // コメント入力チェック
        // ------------------
        const textarea = document.getElementById('comment');
        const submitButton = document.getElementById('submit-comment-btn');

        // inputイベントは文字が入力されるたびに発火します
        textarea.addEventListener('input', function() {
            // 空白を除去した値の長さをチェック
            if (this.value.trim().length > 0) {
                submitButton.disabled = false;  // ボタンを有効化
            } else {
                submitButton.disabled = true;   // ボタンを無効化
            }
        });

        // 初期状態では無効化しておく
        submitButton.disabled = true;
      </script>
    @endif
  @endif
  <script>
        window.addEventListener('pageshow', function(event) {
          console.log(event.persisted);
          if (event.persisted) {
            console.log('キャッシュから表示')
          } else {
            console.log('サーバにアクセスした')
          }
        });
  </script>
@endsection