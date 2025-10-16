// trading-popup.js
// Robust trading UI with event delegation and null checks

document.addEventListener('DOMContentLoaded', function () {
  const grid = document.querySelector('.trading-grid');
  const popup = document.getElementById('trading-popup');
  const popupImg = popup ? popup.querySelector('#trading-popup-img') : null;
  const popupTitle = popup ? popup.querySelector('#trading-popup-title') : null;
  const popupOwner = popup ? popup.querySelector('#trading-popup-owner') : null;
  const popupAction = document.getElementById('trading-popup-action-btn');

  function showPopupForCard(card) {
    if (!popup || !card) return;
    const img = card.querySelector('img') ? card.querySelector('img').src : '';
    const title = card.querySelector('h2')
      ? card.querySelector('h2').textContent
      : '';
    const ownerText = card.querySelector('.trading-details p')
      ? card.querySelector('.trading-details p').textContent
      : '';
    if (popupImg) popupImg.src = img;
    if (popupTitle) popupTitle.textContent = title;
    if (popupOwner) popupOwner.textContent = ownerText;
    // ensure modal is attached to body
    if (popup.parentElement !== document.body) document.body.appendChild(popup);
    popup.classList.add('open');
    document.body.style.overflow = 'hidden';
    if (popupAction) popupAction.style.display = 'inline-block';
  }

  // close handlers (safe)
  const closeBtn = document.querySelector('.trading-popup-close');
  if (closeBtn && popup)
    closeBtn.addEventListener('click', () => {
      popup.classList.remove('open');
      document.body.style.overflow = '';
      if (popupAction) popupAction.style.display = 'none';
    });
  if (popup)
    popup.addEventListener('click', (e) => {
      // close when clicking the overlay itself
      if (e.target === popup) {
        popup.classList.remove('open');
        document.body.style.overflow = '';
        if (popupAction) popupAction.style.display = 'none';
      }
    });

  // simple POST helper
  async function apiPost(path, params) {
    try {
      const res = await fetch(path, {
        method: 'POST',
        credentials: 'include',
        body: new URLSearchParams(params),
      });
      const txt = await res.text();
      try {
        return JSON.parse(txt);
      } catch (e) {
        return { ok: false, raw: txt };
      }
    } catch (err) {
      return { ok: false, error: err.message };
    }
  }

  // Delegated click handler for grid
  if (grid) {
    grid.addEventListener('click', async (e) => {
      const requestBtn = e.target.closest('.request-btn');
      const card = e.target.closest('.trading-card');
      if (!card) return;

      // If click was on Request Trade button -> show a simple transient 'Request sent' popup
      if (requestBtn) {
        e.stopPropagation();
        // transient message element
        const showTransient = (msg) => {
          let el = document.createElement('div');
          el.className = 'request-sent-transient';
          el.textContent = msg;
          // small centered toast-like box
          el.style.position = 'fixed';
          el.style.left = '50%';
          el.style.top = '20%';
          el.style.transform = 'translateX(-50%)';
          el.style.background = 'rgba(0,0,0,0.85)';
          el.style.color = '#fff';
          el.style.padding = '10px 16px';
          el.style.borderRadius = '8px';
          el.style.zIndex = 2000;
          document.body.appendChild(el);
          setTimeout(() => {
            el.style.transition = 'opacity 300ms ease';
            el.style.opacity = '0';
          }, 900);
          setTimeout(() => el.remove(), 1300);
        };
        showTransient('Request sent');
        return;
      }

      // Otherwise, clicking the card should open popup details
      showPopupForCard(card);
    });
  }

  // Post Trade feature removed
});
