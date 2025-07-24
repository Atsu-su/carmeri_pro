"use strict";

let isRating = false;

async function rating(){
  isRating = true;
  const url = document.getElementById('values').dataset.rating;
  const formData = new FormData(document.getElementById('rating-form'));

  try {
    const response = await fetch(url, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
      },
      body: formData
    });

    if (!response.ok) {
      throw new Error('Network response was not ok');
    }
    const data = await response.json();

    alert('評価が完了しました。');
  } catch (error) {
    console.log(error);
    alert('評価の送信に失敗しました。\nしばらくしてから再度お試しください。');
  }

  isRating = false;
  document.getElementById('rating-dialog').close();
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
// DOM読み込み後の処理
// ================================================

// DOM読み込み後のイベント設定（document）
document.addEventListener('DOMContentLoaded', function() {
  // 評価関連のイベント
  const stars = document.querySelectorAll('.modal-content-stars-star');
  evaluationAddEvents(stars);
});

document.getElementById('modal-button').addEventListener('click', function() {
  rating();
  document.getElementById('rating-dialog').close();

});