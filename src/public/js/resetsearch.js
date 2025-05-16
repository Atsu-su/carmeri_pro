'use strict';

const keyword = document.getElementById('search-keyword');
const category = document.getElementById('search-category');
const brand = document.getElementById('search-brand');
const condition = document.getElementById('search-condition');
const minPrice = document.getElementById('search-min-price');
const maxPrice = document.getElementById('search-max-price');
const onSale = document.getElementById('search-on-sale');
const resetButton = document.getElementById('reset-search');

resetButton.addEventListener('click', () => {
  keyword.value = '';
  category.value = '';
  brand.value = '';
  condition.value = '';
  minPrice.value = '';
  maxPrice.value = '';
  onSale.checked = false;
  onSale.removeAttribute('checked');
});