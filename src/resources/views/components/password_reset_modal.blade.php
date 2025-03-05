@if (session('status'))
  <div id="modal" class="c-modal">
    <div class="message">
      <div class="message-wrapper">
        <h1 class="title">{{ session('status') }}</h1>
        <a id="modal-btn" class="btn c-btn c-btn--modal-close">閉じる</a>
      </div>
    </div>
  </div>
  <script>
    const modal = document.getElementById('modal');
    const btn = document.getElementById('modal-btn');
    btn.addEventListener('click', () => {
      modal.classList.add('js-hidden');
    });
  </script>
@endif