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
      // If delete button clicked, don't open the view popup
      if (e.target.closest && e.target.closest('.trading-delete-btn')) {
        return;
      }
      // If edit button clicked, don't open the view popup
      if (e.target.closest && e.target.closest('.trading-edit-btn')) {
        return;
      }
      // Prevent button click from triggering popup
      if (e.target.classList.contains('request-btn')) return;
      const img = card.querySelector('img').src;
      const title = card.querySelector('h2').textContent;
      const owner = card.querySelector('p').textContent;
      const description =
        card.dataset.description || 'No description available';
      const dateAdded = card.dataset.dateadded || '';

      document.getElementById('trading-popup-img').src = img;
      document.getElementById('trading-popup-title').textContent = title;
      document.getElementById('trading-popup-content').textContent =
        description;
      document.getElementById('trading-popup-owner').textContent = owner;
      document.getElementById('trading-popup-time').textContent = dateAdded
        ? new Date(dateAdded).toLocaleString()
        : 'Time Posted';
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
      // Don't override the inline flex layout styles
      const content = popup.querySelector('.trading-popup-content');
      if (content) {
        content.style.boxShadow = '0 8px 32px rgba(0, 0, 0, 0.3)';
        content.style.position = 'relative';
        content.style.borderRadius = '10px';
      }
      // Prevent background scroll while popup is open
      document.body.style.overflow = 'hidden';
      // Show the popup button when popup opens
      var popupBtn = document.getElementById('trading-popup-action-btn');
      if (popupBtn) {
        popupBtn.style.display = 'inline-block';
        // reflect requested state in popup button
        const requested = card.dataset.requested === '1';
        popupBtn.textContent = requested ? 'Request sent' : 'Request';
        popupBtn.disabled = requested;
      }
    });

    // Card-level request button removed; actions are handled in the popup only
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

  // --- My Trades modal controls ---
  (function () {
    const modal = document.getElementById('my-trades-modal');
    const closeBtn = document.getElementById('my-trades-close');

    closeBtn?.addEventListener('click', () => {
      if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
      }
    });

    modal?.addEventListener('click', (e) => {
      if (e.target === modal) {
        modal.style.display = 'none';
        document.body.style.overflow = '';
      }
    });
  })();

  // --- Filter and sort controls ---
  (function () {
    const sortBtn = document.getElementById('sort-btn');
    const ascendingBtn = document.getElementById('ascending-btn');
    const latestBtn = document.getElementById('latest-btn');
    const viewTradesBtn = document.getElementById('view-my-trades');
    const filterBtns = [sortBtn, ascendingBtn, latestBtn];

    // Toggle active state
    filterBtns.forEach((btn) => {
      if (!btn) return;
      btn.addEventListener('click', () => {
        // Remove active from all
        filterBtns.forEach((b) => b && b.classList.remove('active'));
        // Add active to clicked
        btn.classList.add('active');

        // Apply sorting logic based on which button was clicked
        if (btn === sortBtn) {
          showToast('Sort options coming soon', 'info');
        } else if (btn === ascendingBtn) {
          sortCards('ascending');
        } else if (btn === latestBtn) {
          sortCards('latest');
        }
      });
    });

    // View current trades button
    viewTradesBtn?.addEventListener('click', async () => {
      await loadMyTrades();
    });

    async function loadMyTrades() {
      const modal = document.getElementById('my-trades-modal');
      const list = document.getElementById('my-trades-list');

      if (!modal || !list) return;

      // Show loading
      list.innerHTML =
        '<p style="text-align:center;color:#666;padding:20px">Loading your trades...</p>';
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

      try {
        const resp = await fetch('api/my_trades.php', {
          credentials: 'include',
        });

        if (resp.status === 401) {
          showToast('Please log in first', 'error');
          modal.style.display = 'none';
          document.body.style.overflow = '';
          return;
        }

        const data = await resp.json();

        if (data.ok && data.trades) {
          if (data.trades.length === 0) {
            list.innerHTML =
              '<p style="text-align:center;color:#666;padding:20px">You haven\'t made any trade requests yet.</p>';
          } else {
            list.innerHTML = data.trades
              .map((trade) => {
                const statusColor =
                  trade.Status === 'Pending'
                    ? '#f0ad4e'
                    : trade.Status === 'Accepted'
                    ? '#5cb85c'
                    : trade.Status === 'Declined'
                    ? '#d9534f'
                    : '#999';
                const imgSrc =
                  trade.ImagePath || 'https://placehold.co/600x400';
                const desc = trade.Description || 'No description';

                return `
                <div style="display:flex;gap:12px;padding:12px;background:#f8f8f8;border-radius:8px;border-left:4px solid ${statusColor}">
                  <img src="${imgSrc}" alt="Item" style="width:80px;height:80px;object-fit:cover;border-radius:6px;flex-shrink:0" />
                  <div style="flex:1;min-width:0">
                    <h4 style="margin:0 0 4px;font-size:1.1rem;color:#222">${trade.ItemName}</h4>
                    <p style="margin:2px 0;font-size:0.85rem;color:#666">${desc}</p>
                    <p style="margin:2px 0;font-size:0.85rem;color:#666">Owner: ${trade.OwnerName}</p>
                    <p style="margin:6px 0 0;font-size:0.85rem;font-weight:600;color:${statusColor}">Status: ${trade.Status}</p>
                  </div>
                </div>
              `;
              })
              .join('');
          }
        } else {
          list.innerHTML =
            '<p style="text-align:center;color:#d9534f;padding:20px">Failed to load trades.</p>';
        }
      } catch (err) {
        list.innerHTML =
          '<p style="text-align:center;color:#d9534f;padding:20px">Network error. Please try again.</p>';
      }
    }

    function sortCards(type) {
      const grid = document.querySelector('.trading-grid');
      if (!grid) return;

      const cards = Array.from(grid.querySelectorAll('.trading-card'));

      if (type === 'latest') {
        // Already sorted by DateAdded DESC from server
        showToast('Showing latest items', 'success');
      } else if (type === 'ascending') {
        // Sort alphabetically by title
        cards.sort((a, b) => {
          const titleA = a.querySelector('h2')?.textContent.toLowerCase() || '';
          const titleB = b.querySelector('h2')?.textContent.toLowerCase() || '';
          return titleA.localeCompare(titleB);
        });

        // Re-append in new order
        cards.forEach((card) => grid.appendChild(card));
        showToast('Sorted A-Z', 'success');
      }
    }
  })();

  // Delete button handler for trading items
  document.addEventListener('click', (e) => {
    const deleteBtn = e.target.closest('.trading-delete-btn');
    if (deleteBtn) {
      e.stopPropagation();
      const itemId = deleteBtn.dataset.itemid;

      if (confirm('Are you sure you want to delete this trading item?')) {
        fetch('api/trading_item.php', {
          method: 'DELETE',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ item_id: itemId }),
        })
          .then((res) => res.json())
          .then((data) => {
            if (data.ok) {
              const card = deleteBtn.closest('.trading-card');
              if (card) card.remove();
              showToast('Item deleted successfully', 'success');
            } else {
              showToast(data.error || 'Failed to delete item', 'error');
            }
          })
          .catch((err) => {
            console.error('Delete error:', err);
            showToast('Error deleting item', 'error');
          });
      }
    }

    // Handle edit trading item
    const editBtn = e.target.closest('.trading-edit-btn');
    if (editBtn) {
      e.stopPropagation();
      const itemId = editBtn.dataset.itemid;
      const card = editBtn.closest('.trading-card');
      const currentName = card.querySelector('h2').textContent;
      const currentDesc = card.dataset.description || '';
      const currentCategory = card.dataset.category || '';
      const currentLocation = card.dataset.meetuplocation || '';

      // Open edit modal
      openEditModal(
        itemId,
        currentName,
        currentDesc,
        currentCategory,
        currentLocation
      );
    }
  });

  // Edit Item modal wires
  const editModal = document.getElementById('edit-item-modal');
  const editClose = document.getElementById('edit-item-close');
  const editCancel = document.getElementById('edit-item-cancel');
  const editSubmit = document.getElementById('edit-item-submit');
  const editItemId = document.getElementById('edit-item-id');
  const editName = document.getElementById('edit-name');
  const editDesc = document.getElementById('edit-desc');
  const editCategory = document.getElementById('edit-category');
  const editLocation = document.getElementById('edit-location');
  const editError = document.getElementById('edit-item-error');

  function openEditModal(itemId, name, description, category, location) {
    if (!editModal) return;
    if (editItemId) editItemId.value = itemId;
    if (editName) editName.value = name;
    if (editDesc) editDesc.value = description;
    if (editCategory) editCategory.value = category;
    if (editLocation) editLocation.value = location;
    if (editError) editError.style.display = 'none';
    editModal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
  }

  function closeEditModal() {
    if (!editModal) return;
    editModal.style.display = 'none';
    document.body.style.overflow = '';
    if (editItemId) editItemId.value = '';
    if (editName) editName.value = '';
    if (editDesc) editDesc.value = '';
    if (editCategory) editCategory.value = '';
    if (editLocation) editLocation.value = '';
    if (editError) editError.style.display = 'none';
  }

  if (editClose) editClose.addEventListener('click', closeEditModal);
  if (editCancel) editCancel.addEventListener('click', closeEditModal);

  async function updateItem() {
    const itemId = editItemId?.value || '';
    const name = (editName?.value || '').trim();
    const description = (editDesc?.value || '').trim();
    const category = (editCategory?.value || '').trim();
    const meetupLocation = (editLocation?.value || '').trim();

    if (!itemId || !name || !description || !meetupLocation) {
      if (editError) {
        editError.textContent = 'Please fill in all required fields';
        editError.style.display = 'block';
      }
      return;
    }

    try {
      const res = await fetch('api/trading_item.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          item_id: itemId,
          name: name,
          description: description,
          category: category,
          meetup_location: meetupLocation,
        }),
      });
      const data = await res.json();

      if (!data.ok) {
        if (editError) {
          editError.textContent = data.error || 'Failed to update item';
          editError.style.display = 'block';
        }
        return;
      }

      showToast('Item updated successfully', 'success');
      closeEditModal();

      // Reload page to show updated data
      setTimeout(() => location.reload(), 500);
    } catch (err) {
      if (editError) {
        editError.textContent = 'Network error';
        editError.style.display = 'block';
      }
    }
  }

  if (editSubmit) editSubmit.addEventListener('click', updateItem);
});
