"use strict";

// =======================================
// 共通変数
// =======================================
const itemStatusDialog = document.getElementById('status-dialog');
const type = document.getElementById('values').dataset.type;
const ratingDialog = document.getElementById('rating-dialog');

// =======================================
// 関数
// =======================================
async function changeStatus() {
  document.getElementById('values').dataset.changestatustoshipped;
  let url;
  let response;
  let formData = null;

  if (type == 'seller') {
    // 出品者が閲覧している場合
    url = document.getElementById('values').dataset.changestatustoshipped;
  } else if ( type == 'buyer') {
    // 購入者が閲覧している場合
    url = document.getElementById('values').dataset.changestatustocompleted;
    const form = document.getElementById('close-chat-form')
    formData = new FormData(form);
  }

  try {
    response = await fetch(url, {
      method: 'POST',
      body: formData,
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      }
    });

    if (!response.ok) {
      throw new Error('サーバエラーが発生。');
    }

  } catch (error) {
    console.log('Error:', error);
  }
  const data = await response.json();
  return data;
}

function updateStatusText(data) {
  // ステータスと発送日を変更する
  if (type === 'seller') document.getElementById('shipped-at').textContent = data.shipped_at;
  document.getElementById('status').textContent = data.status;

  // 取引ステータス変更ボタンを削除
  const button = document.getElementById('change-status-button').remove();
}

// DOM読み込み後のイベント設定（document）
document.addEventListener('DOMContentLoaded', function() {
  const statusChangeButton = document.getElementById('change-status-button');

  if (statusChangeButton) {
    document.getElementById('change-status-button').addEventListener('click', function() {
      itemStatusDialog.showModal();
    });

    document.getElementById('change-status-cancel').addEventListener('click', function() {
      itemStatusDialog.close();
    });

    document.getElementById('change-status').addEventListener('click', async function() {
      const data = await changeStatus();
      if (data.success) {
        updateStatusText(data);
        alert('取引ステータスを変更しました。');
        itemStatusDialog.close();

        // 購入者がステータス変更が成功した場合（完了へ変更）、評価を実施
        if (type === 'buyer') {
          ratingDialog.showModal();
        }
      } else {
        alert(`取引ステータスの変更に失敗しました。\n${data.message || 'しばらくしてから再度お試しください。'}`);
        itemStatusDialog.close();
      }
    });
  }
});