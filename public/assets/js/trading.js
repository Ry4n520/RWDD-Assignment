// trading-popup.js
// Handles trading card popup logic (similar to events)

document.addEventListener('DOMContentLoaded', function () {
  // Lightweight toast popout
  function showToast(message, type = 'success') {
    const el = document.createElement('div');
    el.textContent = message;
    el.setAttribute('role', 'status');
    el.style.position = 'fixed';
    el.style.left = '50%';
    el.style.bottom = '24px';
    el.style.transform = 'translateX(-50%)';
    el.style.padding = '10px 16px';
    el.style.borderRadius = '8px';
    el.style.color = '#fff';
    el.style.fontSize = '14px';
    el.style.boxShadow = '0 6px 20px rgba(0,0,0,0.25)';
    el.style.zIndex = '20000';
    el.style.opacity = '0';
    el.style.transition = 'opacity 200ms ease, transform 200ms ease';
    if (type === 'error') {
      el.style.background = 'rgba(220, 53, 69, 0.95)'; // red
    } else if (type === 'info') {
      el.style.background = 'rgba(0, 123, 255, 0.95)'; // blue
    } else {
      el.style.background = 'rgba(40, 167, 69, 0.95)'; // green
    }
    document.body.appendChild(el);
    // animate in
    requestAnimationFrame(() => {
      el.style.opacity = '1';
      el.style.transform = 'translateX(-50%) translateY(-6px)';
    });
    // auto-dismiss
    setTimeout(() => {
      el.style.opacity = '0';
      el.style.transform = 'translateX(-50%) translateY(0)';
      setTimeout(() => el.remove(), 250);
    }, 1400);
  }
  // Ensure popup is hidden on load (defensive against stale CSS cache)
  (function () {
    const p = document.getElementById('trading-popup');
    if (p) {
      p.style.display = 'none';
      if (p.classList) p.classList.remove('open');
    }
    const btn = document.getElementById('trading-popup-action-btn');
    if (btn) btn.style.display = 'none';
  })();
  document.querySelectorAll('.trading-card').forEach((card) => {
    card.style.cursor = 'pointer';
    card.addEventListener('click', function (e) {
      // Prevent button click from triggering popup
      if (e.target.classList.contains('request-btn')) return;
      const img = card.querySelector('img').src;
      const title = card.querySelector('h2').textContent;
      const owner = card.querySelector('p').textContent;
      document.getElementById('trading-popup-img').src = img;
      document.getElementById('trading-popup-title').textContent = title;
      document.getElementById('trading-popup-owner').textContent = owner;
      const popup = document.getElementById('trading-popup');
      // store target info on popup for request action
      if (card.dataset) {
        popup.dataset.ownerid = card.dataset.ownerid || '';
        popup.dataset.itemid = card.dataset.itemid || '';
      }
      // Ensure popup is direct child of body to avoid transformed/stacking ancestors
      if (popup.parentElement !== document.body) {
        document.body.appendChild(popup);
      }
      // Ensure overlay shows and is fixed-centered even if CSS fails/cached
      popup.style.display = 'flex';
      popup.style.position = 'fixed';
      popup.style.top = '0';
      popup.style.left = '0';
      popup.style.right = '0';
      popup.style.bottom = '0';
      popup.style.alignItems = 'center';
      popup.style.justifyContent = 'center';
      popup.style.background = 'rgba(0, 0, 0, 0.6)';
      popup.style.zIndex = '10000';
      // Style the modal card content inline as a fallback
      const content = popup.querySelector('.trading-popup-content');
      if (content) {
        content.style.background = '#fff';
        content.style.color = '#222';
        content.style.borderRadius = '10px';
        content.style.padding = '50px 20px 20px 20px';
        content.style.maxWidth = '700px';
        content.style.width = '90%';
        content.style.boxShadow = '0 8px 32px rgba(0, 0, 0, 0.3)';
        content.style.position = 'relative';

        const imgEl = content.querySelector('img');
        if (imgEl) {
          imgEl.style.width = '95%';
          imgEl.style.height = '250px';
          imgEl.style.objectFit = 'cover';
          imgEl.style.borderRadius = '8px';
          imgEl.style.display = 'block';
          imgEl.style.margin = '0 auto 20px auto';
        }
      }
      // Prevent background scroll while popup is open
      document.body.style.overflow = 'hidden';
      // Show the popup button when popup opens
      var popupBtn = document.getElementById('trading-popup-action-btn');
      if (popupBtn) {
        popupBtn.style.display = 'inline-block';
        // reflect requested state in popup button
        const requested = card.dataset.requested === '1';
        popupBtn.textContent = requested ? 'Request sent' : 'I want this';
        popupBtn.disabled = requested;
      }
    });

    // Card button behavior handled by meetup modal logic below
  });
  // Close popup
  document.querySelector('.trading-popup-close').onclick = function () {
    const popup = document.getElementById('trading-popup');
    popup.style.display = 'none';
    // Restore background scroll
    document.body.style.overflow = '';
    // Hide the button again after closing
    var popupBtn = document.getElementById('trading-popup-action-btn');
    if (popupBtn) popupBtn.style.display = 'none';
  };
  // Close popup when clicking outside content
  document.getElementById('trading-popup').onclick = function (e) {
    if (e.target === this) {
      this.style.display = 'none';
      // Restore background scroll
      document.body.style.overflow = '';
      // Hide the button again after closing
      var popupBtn = document.getElementById('trading-popup-action-btn');
      if (popupBtn) popupBtn.style.display = 'none';
    }
  };

  // --- Direct request flow (no meetup prompt) ---
  (function () {
    const grid = document.querySelector('.trading-grid');
    const viewPopup = document.getElementById('trading-popup');
    const viewClose = viewPopup?.querySelector('.trading-popup-close');
    const viewBtn = document.getElementById('trading-popup-action-btn');

    // toast (reuse top one)
    function toast(msg, type = 'success') {
      showToast(msg, type);
    }

    async function sendRequest(itemId, cardEl) {
      const fd = new FormData();
      fd.append('item_id', String(itemId));
      // no meetup_location here

      const resp = await fetch('api/trading_request.php', {
        method: 'POST',
        body: fd,
        credentials: 'include',
      });
      if (resp.status === 401) {
        toast('Please log in first', 'error');
        // window.location.href = 'login.php';
        return;
      }
      const data = await resp.json().catch(() => ({}));
      if (resp.ok && data.ok) {
        toast('Request sent', 'success');
        // close view popup if open
        if (viewPopup) viewPopup.style.display = 'none';
        document.body.style.overflow = '';
        // update UI: mark card/button as requested and persist on dataset
        if (cardEl) {
          cardEl.dataset.requested = '1';
          const btn = cardEl.querySelector('.request-btn');
          if (btn) {
            btn.textContent = 'Request sent';
            btn.disabled = true;
          }
        }
      } else {
        toast(data.error || 'Failed to send request', 'error');
      }
    }

    // Card button → send directly
    if (grid) {
      grid.addEventListener('click', (e) => {
        const btn = e.target.closest('.request-btn');
        if (!btn) return;
        const card = e.target.closest('.trading-card');
        if (!card) return;
        if (card.dataset.requested === '1') return; // already requested
        const itemId = card.dataset.itemid;
        if (!itemId) return;
        sendRequest(itemId, card);
      });
    }

    // Popup button → send directly
    viewBtn?.addEventListener('click', () => {
      const itemId = viewPopup?.dataset.itemid;
      if (!itemId) return;
      // find the matching card to update state after send
      const card = document.querySelector(
        `.trading-card[data-itemid="${CSS.escape(String(itemId))}"]`
      );
      sendRequest(itemId, card);
    });

    // Close view popup interactions remain
    viewClose?.addEventListener('click', () => {
      if (viewPopup) viewPopup.style.display = 'none';
      document.body.style.overflow = '';
    });
    viewPopup?.addEventListener('click', (e) => {
      if (e.target === viewPopup) {
        viewPopup.style.display = 'none';
        document.body.style.overflow = '';
      }
    });
  })();

  // --- Post Item modal wiring ---
  (function () {
    const openBtn = document.getElementById('post-item-open');
    const modal = document.getElementById('post-item-modal');
    const closeIcon = document.getElementById('post-item-close');
    const cancelBtn = document.getElementById('post-item-cancel');
    const submitBtn = document.getElementById('post-item-submit');
    const nameEl = document.getElementById('post-name');
    const descEl = document.getElementById('post-desc');
    const catEl = document.getElementById('post-category');
    const locEl = document.getElementById('post-location');
    const imagesEl = document.getElementById('post-images');
    const previewEl = document.getElementById('post-images-preview');

    function open() {
      if (!modal) return;
      modal.style.display = 'flex';
      modal.style.position = 'fixed';
      modal.style.top = '0';
      modal.style.left = '0';
      modal.style.right = '0';
      modal.style.bottom = '0';
      modal.style.alignItems = 'center';
      modal.style.justifyContent = 'center';
      modal.style.background = 'rgba(0,0,0,.6)';
      modal.style.zIndex = '10000';
      document.body.style.overflow = 'hidden';
    }
    function close() {
      if (!modal) return;
      modal.style.display = 'none';
      document.body.style.overflow = '';
    }

    openBtn?.addEventListener('click', open);
    closeIcon?.addEventListener('click', close);
    cancelBtn?.addEventListener('click', close);
    modal?.addEventListener('click', (e) => {
      if (e.target === modal) close();
    });

    // Image previews (up to 6)
    imagesEl?.addEventListener('change', () => {
      if (!previewEl) return;
      previewEl.innerHTML = '';
      const files = Array.from(imagesEl.files || []);
      files.slice(0, 6).forEach((file) => {
        if (!file.type || !file.type.startsWith('image/')) return;
        const url = URL.createObjectURL(file);
        const img = document.createElement('img');
        img.src = url;
        img.style.width = '86px';
        img.style.height = '86px';
        img.style.objectFit = 'cover';
        img.style.borderRadius = '6px';
        img.style.boxShadow = '0 1px 4px rgba(0,0,0,.15)';
        previewEl.appendChild(img);
      });
    });

    submitBtn?.addEventListener('click', async () => {
      const name = nameEl?.value.trim() || '';
      const category = catEl?.value.trim() || '';
      const description = descEl?.value.trim() || '';
      const meetup_location = locEl?.value || '';
      if (!name) {
        showToast('Title is required', 'error');
        return;
      }

      // The existing API expects: name, category, description
      const fd = new FormData();
      fd.append('name', name);
      fd.append('category', category);
      fd.append('description', description);
      // meetup_location not stored yet in API; include for future compatibility
      fd.append('meetup_location', meetup_location);
      // Append selected images (API accepts 'image' or 'image[]')
      const files = Array.from(imagesEl?.files || []);
      files.forEach((f) => fd.append('image[]', f));

      try {
        const resp = await fetch('api/trading_item.php', {
          method: 'POST',
          body: fd,
          credentials: 'include',
        });
        if (resp.status === 401) {
          showToast('Please log in first', 'error');
          return;
        }
        const data = await resp.json().catch(() => ({}));
        if (resp.ok && data.ok) {
          showToast('Item posted', 'success');
          close();
          // Optional: refresh to show the new item
          setTimeout(() => window.location.reload(), 700);
        } else {
          showToast(data.error || 'Failed to post item', 'error');
        }
      } catch {
        showToast('Network error', 'error');
      }
    });
  })();
});
