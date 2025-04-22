const imgInput = document.getElementById('img-input');
const background = document.getElementById('background');
const resetBtn = document.getElementById('reset-btn');
const fileName = document.getElementById('file-name');
const fileBase64 = document.getElementById('file-base64');
const isChanged = document.getElementById('is-changed');
const isNoImage = document.getElementById('is-no-image');

// ------------------------------
// 関数
// ------------------------------

// eventはinput要素のchangeイベント
function showPreview(e) {
  // ファイル選択がキャンセルされた場合は何もせず終了
  if (!e.target.files || e.target.files.length === 0) {
    return;
  }

  const file = e.target.files[0]; // input要素が保持するファイル
  const fileNameText = file ? file.name : '';
  const noImage = document.getElementById('no-image');
  let preview = document.getElementById('preview');

  // 背景の初期化（灰色だった場合、白に戻す）
  background.style.backgroundColor = '#FFF';

  if (file && file.type.startsWith('image/')) {
    // 画像ファイルが選択されたことを示すフラグを立てる
    isNoImage.value = 'false';

    // ファイルをブラウザに読み込む
    const reader = new FileReader();

    // ファイル読込完了後の処理を定義
    reader.onload = function(e) {
      const imgError = document.getElementById('img-error');

      // 画像が変更されたことを示すフラグを立てる
      isChanged.value = 'true';

      if (!preview) {
        // 画像を表示するためのimg要素を生成
        preview = document.createElement('img');
        preview.id = 'preview';
        preview.className = 'c-profile-inner-frame';
        preview.alt = 'プロフィールの画像';

        // 背景要素に追加
        background.appendChild(preview);
      }

      // base64のデータを追加（e.targetから取っているだけ）
      preview.src = e.target.result; // 画面表示
      fileBase64.value = e.target.result; // inputのvalue

      preview.style.display = 'block'; // 選択された画像を表示

      if (noImage) {
        noImage.style.display = 'none';
      }

      // imageのバリデーションエラーが出力されていた場合は画像変更後に削除
      if (imgError !== null && imgError !== undefined) {
          imgError.style.display = 'none';
      }
    }

    reader.readAsDataURL(file);
    resetBtn.style.display = 'block';
  } else {
    // 画像ファイルが選択されたことを示すフラグを立てる
    isNoImage.value = 'true';

    // 画像ファイルが選択されていない場合、プレビューは灰色背景色になる
    if (preview) {
      preview.src = '';
      preview.style.display = 'none';
    }

    if (noImage) {
      noImage.style.display = 'none';
    }

    fileBase64.value = '';

    background.style.backgroundColor = '#D9D9D9';
    resetBtn.style.display = 'block';
  }

  fileName.textContent = `ファイル：${file.name}`;
}

function resetPreview() {
  const preview = document.getElementById('preview');
  // const fileName = document.getElementById('file-name');
  let noImage = document.getElementById('no-image');

  // 画像が変更されたことを示すフラグを立てる
  isChanged.value = 'true';

  // 画像が削除されたことを示すフラグを立てる
  isNoImage.value = 'true';

  if (preview) {
    preview.src = '';
    preview.style.display = 'none'; // 削除された画像のimg要素を非表示
  }

  resetBtn.style.display = 'none';
  fileBase64.value = ''; // ファイル入力をクリア（POSTされる値）
  fileName.textContent = ''; // ファイル名をクリア

  if (!noImage) {
    noImage = document.createElement('div');
    noImage.id = 'no-image';
    noImage.className = 'c-profile-no-image';
    noImage.innerHTML = '<p>NO</p><p>IMAGE</p>';
    background.appendChild(noImage);
  } else {
    noImage.style.display = 'block';
  }

  background.style.backgroundColor = '#FFF';
}

function switchResetBtn() {
  const preview = document.getElementById('preview');
  // ページが読み込まれたときにプレビュー画像がない場合、削除ボタンを非表示にする
  if (!preview) {
    resetBtn.style.display = 'none';
  }
}

// ------------------------------
// イベント
// ------------------------------

document.addEventListener('DOMContentLoaded', switchResetBtn);
imgInput.addEventListener('change', showPreview);
resetBtn.addEventListener('click', resetPreview);

// 出品取り下げ確認モーダル
const userDeactivationBtn = document.getElementById('user-deactivation-btn');
const userDeactivationModal = document.getElementById('user-deactivation-modal');
const closeUserDeactivationModal = document.getElementById('close-user-deactivation-modal');

console.log(userDeactivationBtn);
console.log(userDeactivationModal);
console.log(closeUserDeactivationModal);

userDeactivationBtn.addEventListener('click', function(event) {
    event.preventDefault();
    userDeactivationModal.showModal();
});

closeUserDeactivationModal.addEventListener('click', function(event) {
  event.preventDefault();
  userDeactivationModal.close();
});