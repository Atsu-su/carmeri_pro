// ================================================
// 共通変数（複数の機能で利用）
// ================================================

const cookieName = 'savedText';
const purchaseId = document.getElementById('values').dataset.purchaseid;
let isSending = false;
let isSendingImage = false;

// ================================================
// 共通関数（複数の機能で利用）
// ================================================

function createUpdateLink(event, link, updateForm, updateDialog) {
  event.preventDefault(); // リンクのデフォルト動作を防止

  // クリックされた編集リンクの親要素（chat-content-list-edit）を取得
  const messageContainer = link.parentElement;
  // 親のchat-content-list-containerを取得
  const contentContainer = messageContainer.parentElement;
  // その中のメッセージ要素を取得
  const messageElement = contentContainer.querySelector('.chat-content-list-message');
  // メッセージのテキストを取得
  const messageText = messageElement.textContent;

  // dialogのformにdata-chatidをセット
  const chatId = messageElement.dataset.chatid;
  updateForm.dataset.chatid = chatId;

  // 編集エリアにメッセージを表示
  const textarea = document.getElementById('update-textarea');
  textarea.value = messageText;

  // ダイアログを表示
  updateDialog.showModal();
}

function createDeleteLink(event, link, deleteContainer, deleteDialog, isText = 1) {
  event.preventDefault(); // リンクのデフォルト動作を防止

  // クリックされた編集リンクの親要素（chat-content-list-edit）を取得
  const messageContainer = link.parentElement;
  // 親のchat-content-list-containerを取得
  const contentContainer = messageContainer.parentElement;

  let messageElement;
  if (parseInt(isText)) {
    // contentContainerのメッセージ要素を取得
    messageElement = contentContainer.querySelector('.chat-content-list-message');
    const messageText = messageElement.textContent;
    const pragraph = document.getElementById('delete-p');
    pragraph.textContent = messageText;
  } else {

    // contentContainerの画像要素を取得
    messageElement = contentContainer.querySelector('.chat-content-list-image');
    const messageSrc = messageElement.src;
    const image = document.getElementById('delete-img-img');
    image.src = messageSrc;
  }

  // dialogのformにdata-chatidをセット
  const chatId = messageElement.dataset.chatid;
  deleteContainer.dataset.chatid = chatId;

  // ダイアログを表示
  deleteDialog.showModal();
}

function scrollToBottom() {
  const chatContent = document.querySelector('.chat-content ul');
  chatContent.scrollTop = chatContent.scrollHeight;
}

function scrollToBottomWrapper(isText){
  if (isText) {
    // テキストの場合
    scrollToBottom();
  } else {
    const image = document.querySelector('.chat-content ul li:last-child .chat-content-list-image');
    if (image.complete) {
      // 画像がすでに読み込まれている場合はすぐにスクロール
      scrollToBottom();
    } else {
      // 画像の読み込みを待ってからスクロール
      image.onload = function() {
        scrollToBottom();
      };
      // 画像が読み込めなかった場合のフォールバック
      image.onerror = function() {
        scrollToBottom();
      };
    }
  }
}

function renderChat(isLeft, data) {
  const isText = data.isText;
  const ul = document.querySelector('.chat-content ul');
  const li = document.createElement('li');
  li.className = `chat-content-list ${isLeft ? 'left' : 'right'}`;

  // プロフィール部分を作成
  const profileDiv = document.createElement('div');
  profileDiv.className = 'chat-content-list-profile';

  const profileFrameOuter = document.createElement('div');
  profileFrameOuter.className = 'chat-content-list-profile-outer-frame';

  const profileImg = document.createElement('img');
  profileImg.className = 'chat-content-list-profile-inner-frame';

  const storage = document.getElementById('values').dataset.storage;
  profileImg.src = `${storage}${data.image}`;
  profileImg.alt = 'プロフィールの画像';

  const profileName = document.createElement('p');
  profileName.className = 'chat-content-list-profile-name';
  profileName.textContent = data.username;

  // コンテンツ部分を作成
  const contentDiv = document.createElement('div');
  contentDiv.className = 'chat-content-list-container';

  let message;
  // テキストか画像かで分岐
  if (isText) {
    // テキストの場合
    message = document.createElement('p');
    message.className = 'chat-content-list-message';
    message.textContent = data.message;
    message.dataset.chatid = data.chatId;
  } else {
    // 画像の場合
    message = document.createElement('img');
    message.className = 'chat-content-list-image';
    message.src = data.message;
    message.dataset.chatid = data.chatId;
  }

  const informationDiv =  document.createElement('div');
  informationDiv.className = 'chat-content-list-information';
  const datetimeP = document.createElement('p');
  datetimeP.className = 'chat-content-list-datetime';
  datetimeP.textContent = data.datetime;
  informationDiv.appendChild(datetimeP);

  // if文に入れるとeditDivは外では使えない
  const editDiv =document.createElement('div');
  editDiv.className = 'chat-content-list-edit';
  if (!isLeft && isText) {
    const editA1 = document.createElement('a');
    editA1.textContent = '編集';
    const editA2 = document.createElement('a');
    editA2.textContent = '削除';
    editDiv.appendChild(editA1);
    editDiv.appendChild(editA2);

    // 編集ボタンにイベントリスナーを付与
    const updateForm = document.getElementById('update-form');
    const updateDialog = document.getElementById('update');
    editA1.addEventListener('click', function(e) {
      createUpdateLink(e, editA1, updateForm, updateDialog);
    });

    // 削除ボタンにイベントリスナーを付与
    const deleteContainer = document.getElementById('delete-container');
    const deleteDialog = document.getElementById('delete');
    editA2.addEventListener('click', function(e) {
      createDeleteLink(e, editA2, deleteContainer, deleteDialog);
    });
  } else if (!isLeft && !isText) {
    const editA2 = document.createElement('a');
    editA2.textContent = '削除';
    editDiv.appendChild(editA2);

    // 削除ボタンにイベントリスナーを付与
    const deleteContainer = document.getElementById('delete-container-image');
    const deleteDialog = document.getElementById('delete-img');
    editA2.addEventListener('click', function(e) {
      createDeleteLink(e, editA2, deleteContainer, deleteDialog, false);
    });
  }

  if (!isText) {
    // messageにイベントリスナーを追加
    message.addEventListener('click', function() {
      displayImageModal(this.src);
    });
  }

  // 要素を組み立てる
  profileFrameOuter.appendChild(profileImg);
  profileDiv.appendChild(profileFrameOuter);
  profileDiv.appendChild(profileName);

  contentDiv.appendChild(message);
  contentDiv.appendChild(informationDiv);
  if (!isLeft) {
    contentDiv.appendChild(editDiv);
  }

  li.appendChild(profileDiv);
  li.appendChild(contentDiv);

  ul.appendChild(li);
}

function renderRead(chatId, isText = 1) {
  let target;
  if (parseInt(isText)) {
    target = document.querySelector(`.chat-content-list-message[data-chatid="${chatId}"]`);
  } else {
    target = document.querySelector(`.chat-content-list-image[data-chatid="${chatId}"]`);
  }
  const informationDiv =  target.parentElement.querySelector('.chat-content-list-information');
  const readP = document.createElement('p');
  readP.className = 'chat-content-list-read';
  readP.textContent = '既読';
  informationDiv.prepend(readP);
}

// ================================================
// メッセージ送信
// ================================================

async function sendMessage() {
  isSending = true;

  const socketId = Echo.socketId();
  const form = document.getElementById('form');
  const input = document.getElementById('input');
  const formData = new FormData(form);
  const url = document.getElementById('values').dataset.chatsend;

  try {
    const response = await fetch(url, {
      method: 'POST',
      body: formData,
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        'X-Socket-ID': socketId
      }
    });

    if (!response.ok) {
      errorData = await response.json();
      const error = new Error('Network response was not ok or validation error');
      error.data = errorData;
      throw error;
    }

    const data = await response.json();
    // 送信したチャットの表示
    renderChat(false, data);
    // 入力フィールドをクリア
    input.value = '';
    hideError();
    // 入力フィールドの幅を初期化
    initializeHeight();
    // 保存された入力値の削除
    deleteSavedTextCookie(cookieName, purchaseId);
    // 送信中フラグを変更（送信可能）
  } catch (error) {
    // バリデーションエラーメッセージを表示
    if (error?.data?.errors?.message) {
      displayError(error.data.errors.message[0]);
    } else {
      displayError('メッセージを送信できませんでした');
    }
  }

  // 一番下までスクロール（バリデーションエラー出力で位置がずれるため）
  scrollToBottom();
  isSending = false;
}

async function sendImage(){
  isSendingImage = true;

  const socketId = Echo.socketId();
  const previewDialog = document.getElementById('preview-img');
  const imgPreview = document.getElementById('preview-img-img');
  const imgPreviewInput = document.getElementById('preview-img-input');
  const form = document.getElementById('preview-img-form');
  const input = document.getElementById('img-input');
  const formData = new FormData(form);
  const url = document.getElementById('values').dataset.chatsendimage;

  try {
    const response = await fetch(url, {
      method: 'POST',
      body: formData,
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        'X-Socket-ID': socketId
      }
    })

    if (!response.ok) {
      errorData = await response.json();
      const error = new Error('Network response was not ok or validation error');
      error.data = errorData;
      throw error;
    }

    const data = await response.json()
    // 送信したチャットの表示
    renderChat(false, data);
    // バリデーションエラーメッセージをクリア
    hideError();
    // 画像が読み込まれたらスクロール
    scrollToBottomWrapper(data.isText);
  } catch (error) {
    if (error?.data?.errors?.image) {
      displayError(error.data.errors.image[0]);
    } else {
      displayError('画像を送信できませんでした');
    }
  }

  imgPreview.src = '';
  imgPreviewInput.value = '';
  input.value = '';             // inputタグのvalueを空にする
  previewDialog.close();
  isSendingImage = false;
}

// バリデーションエラーメッセージの表示
function displayError(message) {
  document.getElementById('validation-error').textContent = message;
}

// バリデーションエラーメッセージの削除
function hideError() {
  const error = document.getElementById('validation-error');
  error.textContent = '';
}

// 既読処理（ChatControllerのreadメソッドを実行する）
async function read(chatId) {
  const baseUrl = document.getElementById('values').dataset.chatread;
  const url = baseUrl.replace(':chatid', chatId);

  try {
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      }
    })

    if (!response.ok) {
      throw new Error('Network response was not ok');
    }

  } catch (error) {
    console.log(error);
  }
}

function adjustHeight() {
  const dummy = document.getElementById('input-dummy');
  document.getElementById('input').addEventListener('input', function() {
    dummy.textContent = this.value + '\u200b';
  });
}

function initializeHeight() {
  const dummy =  document.querySelector('.c-flex-dummy');
  dummy.textContent = '';
}

function deleteSavedTextCookie(cookieName, id) {
  document.cookie = `${cookieName}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/chat/${id}`;
}

// ================================================
// メッセージ修正
// ================================================

let isUpdating = false;

async function updateMessage(chatId) {
  isUpdating = true;
  // 文字列は参照ではない
  let baseUrl = document.getElementById('values').dataset.chatupdate;
  let url = baseUrl.replace(':chatid', chatId);
  const form = document.getElementById('update-form');
  const textarea = document.getElementById('update-textarea');
  const formData = new FormData(form);

  // フォームの内容を送信する処理
  if (!textarea.value) {
    isUpdating = false;
  }

  // async/awaitを使う
  try {
    const response = await fetch(url, {
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      },
      method: 'POST',
      body: formData,
    })

    if (!response.ok) {
      throw new Error('Network response was not ok');
    }

    const data = await response.json();
    updateChatContent(data.chatId, data.updatedMessage);
  } catch (error) {
    console.log(error);
  }

  // 送信中フラグを下げる
  isUpdating = false;
}

function updateChatContent(chatId, updatedMessage) {
  console.log(chatId);
  console.log(updatedMessage);
  const target = document.querySelector(`.chat-content-list-message[data-chatid="${chatId}"]`);
  target.textContent = updatedMessage;
}

function updateAddEvents() {
  const updateDialog = document.getElementById('update');
  const updateForm = document.getElementById('update-form');

  // dialog内の編集ボタンのイベント
  const updateSubmit = document.getElementById('update-submit');
  updateSubmit.addEventListener('click', function() {
    // id="values"にはdata-chatidとして入れられない（$chats as $chat）
    const chatId = updateForm.dataset.chatid;
    if (!isUpdating) {
      updateMessage(chatId);
    }
    updateDialog.close();
  });

  // dialog内のキャンセルボタンのイベント
  const cancel = document.getElementById('update-cancel');
  cancel.addEventListener('click', function() {
    updateDialog.close();
  });

  // すべての編集リンクを取得
  const updateLinks = document.querySelectorAll('.chat-content-list-edit-update');
  updateLinks.forEach(function(link) {
    link.addEventListener('click', function(e) {
      createUpdateLink(e, link, updateForm, updateDialog);
    });
  });
}

// ================================================
// メッセージ削除
// ================================================

let isDeleting = false;

async function deleteMessage(chatId) {
  isDeleting = true;
  const baseUrl = document.getElementById('values').dataset.chatdelete;
  const url = baseUrl.replace(':chatid', chatId);

  try {
  // DELETEとして処理してほしいPOSTリクエストを送信
  const response = await fetch(url, {
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
    },
    method: 'POST',
  });

  if (!response.ok) {
    throw new Error('Network response was not ok');
  }

  const data = await response.json();
  // 削除したチャットの表示を変更
  deleteChatContent(data.chatId, data.isText);
  } catch (error) {
    console.log(error);
  }

  // 送信中フラグを下げる
  isDeleting = false;
}

// 削除されたメッセージの表示を変更
function deleteChatContent(chatId, isText = 1) {
  let targetMessage;
  if (parseInt(isText) || isText) {
    // テキストの場合
    targetMessage = document.querySelector(`.chat-content-list-message[data-chatid="${chatId}"]`);
  } else {
    // 画像の場合
    targetMessage = document.querySelector(`.chat-content-list-image[data-chatid="${chatId}"]`);
  }

  // 対象の要素の親要素を取得
  const parentElement = targetMessage.parentElement;

  // 削除後のメッセージを作成
  const deletedMessage = document.createElement('p');
  deletedMessage.className = 'chat-content-list-message deleted';
  deletedMessage.setAttribute('data-chatid', chatId);

  if (parseInt(isText)) {
    deletedMessage.textContent = 'このメッセージは削除されました';
  } else {
    deletedMessage.textContent = 'この画像は削除されました';
  }

  // 削除対象のメッセージを置き換える
  parentElement.replaceChild(deletedMessage, targetMessage);

  // 編集・削除ボタンを削除
  const targetEditors = parentElement.querySelector('.chat-content-list-edit');
  if (targetEditors) {    // 受信側には編集・削除ボタンがないため
    parentElement.removeChild(targetEditors);
  }
}

// イベント設定用の関数
function deleteAddEvents() {
  const deleteDialog = document.getElementById('delete');
  const deleteImageDialog = document.getElementById('delete-img');
  const deleteContainer = document.getElementById('delete-container');
  const deleteContainerImage = document.getElementById('delete-container-image');

  // dialog内の削除ボタンのイベント（テキスト）
  const deleteSubmit = document.getElementById('delete-submit');
  deleteSubmit.addEventListener('click', function() {
    const chatId = deleteContainer.dataset.chatid;
    if (!isDeleting) {
      deleteMessage(chatId);
    }
    deleteDialog.close();
  });

  // dialog内の削除ボタンのイベント（画像）
  const deleteSubmitImage = document.getElementById('delete-submit-image');
  deleteSubmitImage.addEventListener('click', function() {
    const chatId = deleteContainerImage.dataset.chatid;
    if (!isDeleting) {
      deleteMessage(chatId);
    }
    deleteImageDialog.close();
  });

  // dialog内のキャンセルボタンのイベント
  const cancel = document.getElementById('delete-cancel');
  cancel.addEventListener('click', function() {
    deleteDialog.close();
  });

  const cancelImage = document.getElementById('delete-cancel-image');
  cancelImage.addEventListener('click', function() {
    deleteImageDialog.close();
  });

  const deleteLinks = document.querySelectorAll('.chat-content-list-edit-delete');
  deleteLinks.forEach(function(link) {
    link.addEventListener('click', function(e) {
      linkParent = link.parentElement;
      const isText = link.parentElement.dataset.istext;
      if (parseInt(isText)) {
        // テキストの場合
        createDeleteLink(e, link, deleteContainer, deleteDialog, isText);
      } else {
        // 画像の場合
        createDeleteLink(e, link, deleteContainerImage, deleteImageDialog, isText);
      }
    });
  });
}

// ================================================
// 入力したメッセージのクッキーへの保存
// ================================================

// textareaの値をCookieに保存する関数
function saveTextAreaToCookie(textarea, cookieName, id, expirationHours = 1) {
    // 入力値を取得
    const value = textarea.value;

    // Cookieの有効期限を設定
    const date = new Date();
    date.setTime(date.getTime() + (expirationHours * 60 * 60 * 1000));
    const expires = "expires=" + date.toUTCString();

    // Cookieに保存（エスケープして保存）
    document.cookie = `${cookieName}=${encodeURIComponent(value)};${expires};path=/chat/${id}`;
}

// Cookieからtextareaの値を取得して設定する関数
function loadTextAreaFromCookie(textarea, cookieName) {
    // Cookieから値を取得
    const value = getCookie(cookieName);

    // textareaに値をセット
    if (value) {
        textarea.value = decodeURIComponent(value);
    }
}

// 指定したCookieの値を取得する関数
function getCookie(name) {
    const cookieName = name + "=";
    const cookies = document.cookie.split(';');

    for (let i = 0; i < cookies.length; i++) {
        let cookie = cookies[i].trim();
        if (cookie.indexOf(cookieName) === 0) {
            return cookie.substring(cookieName.length, cookie.length);
        }
    }
    return "";
}

// イベント設定用の関数
function cookieAddEvents() {
  // cookieName, purchaseIdは共通変数として定義
  const textarea = document.getElementById('input');

  // ページ読み込み時にCookieから値を復元
  loadTextAreaFromCookie(textarea, cookieName);

  // textareaの入力内容が変更されたらCookieに保存
  textarea.addEventListener('input', function() {
      saveTextAreaToCookie(textarea, cookieName, purchaseId);
  });
}

// ================================================
// 評価
// ================================================

let isChangingStatus = false;

function showRatingModal() {
  const modal = document.getElementById('modal');
  modal.classList.remove('js-hidden');
}

async function changeStatus(){
  isChangingStatus = true;
  const url = document.getElementById('values').dataset.transactioncomplete;

  try {
    const response = await fetch(url, {
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      },
      method: 'POST',
    })

    if (!response.ok) {
      throw new Error('Network response was not ok');
    }

    showRatingModal();
  } catch (error) {
    console.log(error);
  }

  isChangingStatus = false;
}

// イベント設定用の関数
function evaluationAddEvents(stars){
  stars.forEach(star => {
    // マウスオーバー時の処理
    star.addEventListener('mouseover', function(e) {
      const number = parseInt(this.dataset.number);
      for (let i = 0; i < number; i++) {
        stars[i].classList.add('filled');
      }
    });

    // マウスクリック時の処理
    star.addEventListener('click', function(e) {
      const rating = parseInt(this.dataset.number);
      const input = document.getElementById('modal-input');

      // 一旦全て削除
      stars.forEach(star => {
        star.classList.remove('clicked');
      });

      for (let i = 0; i < rating; i++) {
        stars[i].classList.add('clicked');
      }

      input.value = rating;

      const button = document.getElementById('modal-button');
      button.disabled = false;
    });

    // マウスアウト時の処理
    star.addEventListener('mouseout', (e) => {
      stars.forEach(star => {
        star.classList.remove('filled');
      });
    });
  });
}

// ================================================
// 画像プレビュー
// ================================================

// DOM読み込み後に実行する
function previewImage(e){
  const previewDialog = document.getElementById('preview-img');
  const imgPreview = document.getElementById('preview-img-img');
  const imgPreviewInput = document.getElementById('preview-img-input');
  const file = e.target.files[0];

  if (file && file.type.startsWith('image/')) {
    const reader = new FileReader();

    // ロード後の処理
    reader.onload = function(e) {
      imgPreview.src = e.target.result;
      imgPreviewInput.value = e.target.result;
      previewDialog.showModal();
    }
    reader.readAsDataURL(file);
  }
}

function closeModal() {
  const imgPreview = document.getElementById('preview-img-img');
  const imgPreviewInput = document.getElementById('preview-img-input');
  const previewDialog = document.getElementById('preview-img');
  const input = document.getElementById('img-input');

  // データを削除後モーダルを閉じる
  imgPreview.src = '';
  imgPreviewInput.value = '';
  previewDialog.close();
  input.value = '';
}

// ================================================
// 画像拡大表示
// ================================================

function displayImageModal(src) {
  const modal = document.getElementById('enlarge-img');
  const img = modal.querySelector('img');
  img.src = src;
  modal.showModal();
}

function closeImageModal() {
  const modal = document.getElementById('enlarge-img');
  const img = modal.querySelector('img');
  img.src = '';
  modal.close();
}

// ================================================
// DOM読み込み後の処理
// ================================================

// DOM読み込み後のイベント設定（window）
window.addEventListener("DOMContentLoaded", () =>{
  const userId = document.getElementById('values').dataset.senderid;

  // purchaseIdは共通変数で定義
  window.Echo.private(`channel.${userId}.${purchaseId}`)
    // .chat-nameでドットが必要
    .listen('.carmeri-chat', (e) => {
      const message = e.message;
      switch (message.method) {
        case 'create':
          // メッセージをレンダリング
          renderChat(true, e.message);
          // チャットの既読処理
          read(message.chatId);
          // 最下部にスクロール（テキストと画像）
          scrollToBottomWrapper(e.message.isText);
          break;
        case 'update':
          // 受信側のメッセージ更新処理
          updateChatContent(message.chatId, message.message);
          break;
        case 'delete':
          // 受信側のメッセージ（テキスト・画像）削除処理
          deleteChatContent(message.chatId, message.isText);
          break;
        case 'read':
          message.chatId.forEach ((chatId, index) => {
            renderRead(chatId, message.isText[index]);
          });
          break;
        default:
          console.log('Unknown method');
      }
    });
});

// DOM読み込み後のイベント設定（document）
document.addEventListener('DOMContentLoaded', function() {
  // 画面読み込み後チャットの最下部へ移動
  scrollToBottom();

  // textareaの高さを初期化
  adjustHeight();

  // 編集ボタン関連のイベント
  updateAddEvents();

  // 削除ボタン関連のイベント
  deleteAddEvents();

  // メッセージ送信のイベント
  document.getElementById('submit-text').addEventListener('click', function(){
    if (isSending) return
    sendMessage();
  });

  document.addEventListener('keydown', function(event) {
    if (event.key === 'Enter' && !event.ctrKey) {
      event.preventDefault(); // Enterキーで改行しない
      if (isSending) return
      sendMessage();
    }
  });

  // 画像プレビュー関連のイベント
  document.getElementById('img-input').addEventListener('change', previewImage);
  document.getElementById('preview-img-cancel').addEventListener('click', closeModal);

  // 画像送信のイベント
  document.getElementById('submit-image').addEventListener('click', function(){
    if (isSendingImage) return
    sendImage();
  });

  // クッキー関連のイベント
  cookieAddEvents();

  // 取引完了ボタンのイベント
  const transactionComplete = document.getElementById('transaction-complete');
  if (transactionComplete) {
    transactionComplete.addEventListener('click', function() {
      if (isChangingStatus) return
      changeStatus();
    });
  }

  // 評価関連のイベント
  const stars = document.querySelectorAll('.modal-content-stars-star');
  evaluationAddEvents(stars);

  // 画像拡大関連のイベント
  document.querySelectorAll('.chat-content-list-image').forEach(image => {
    image.addEventListener('click', function() {
      displayImageModal(this.src);
    });
  });

  document.getElementById('enlarge-img-close').addEventListener('click', function() {
    closeImageModal();
  });
});