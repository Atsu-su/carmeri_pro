"use strict";

// タブの切り替え処理
const titles = document.querySelectorAll('.title');
const tabs = document.querySelectorAll('.tab');

titles.forEach(title => {
  title.addEventListener('click', (e) => {
    if (! e.target.classList.contains('js-active-title')) {
      e.target.classList.add('js-active-title');

      titles.forEach(title => {
        if (e.target !== title) {
          title.classList.remove('js-active-title');
        }
      })

      tabs.forEach(tab => {
        if (tab.classList.contains(e.target.dataset.tab)) {
          tab.classList.remove('js-hidden');
        } else {
          tab.classList.add('js-hidden');
        }
      });
    }
  })
})