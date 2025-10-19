document.addEventListener('DOMContentLoaded', () => {
  // Lightweight toast popout (same as trading)
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
      el.style.background = 'rgba(220, 53, 69, 0.95)';
    } else if (type === 'info') {
      el.style.background = 'rgba(0, 123, 255, 0.95)';
    } else {
      el.style.background = 'rgba(40, 167, 69, 0.95)'; // green
    }
    document.body.appendChild(el);
    requestAnimationFrame(() => {
      el.style.opacity = '1';
      el.style.transform = 'translateX(-50%) translateY(-6px)';
    });
    setTimeout(() => {
      el.style.opacity = '0';
      el.style.transform = 'translateX(-50%) translateY(0)';
      setTimeout(() => el.remove(), 250);
    }, 1400);
  }
  const grid = document.querySelector('.events-grid');
  const cards = document.querySelectorAll('.event-card');
  const popup = document.getElementById('event-popup');
  const closeBtn = document.querySelector('.event-popup-close');

  const popupImg = document.getElementById('event-popup-img');
  const popupTitle = document.getElementById('event-popup-title');
  const popupCommunity = document.getElementById('event-popup-community');
  const popupOrganizer = document.getElementById('event-popup-organizer');
  const popupAddress = document.getElementById('event-popup-address');
  const popupDate = document.getElementById('event-popup-date');
  const popupAction = document.getElementById('event-popup-action-btn');

  // Post Event modal wires
  const postOpen = document.getElementById('event-post-open');
  const postModal = document.getElementById('post-event-modal');
  const postClose = document.getElementById('event-post-close');
  const postCancel = document.getElementById('event-post-cancel');
  const postSubmit = document.getElementById('event-post-submit');
  const inputName = document.getElementById('post-event-name');
  const inputAddress = document.getElementById('post-event-address');
  const inputDate = document.getElementById('post-event-date');
  const inputOrganizer = document.getElementById('post-event-organizer');
  const inputImages = document.getElementById('post-event-images');
  const imagesPreview = document.getElementById('post-event-images-preview');

  function openPostModal() {
    if (!postModal) return;
    postModal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
  }
  function closePostModal() {
    if (!postModal) return;
    postModal.style.display = 'none';
    document.body.style.overflow = '';
    if (inputName) inputName.value = '';
    if (inputAddress) inputAddress.value = '';
    if (inputDate) inputDate.value = '';
    if (inputOrganizer) inputOrganizer.value = '';
    if (inputImages) inputImages.value = '';
    if (imagesPreview) imagesPreview.innerHTML = '';
  }
  // Preview selected images
  if (inputImages && imagesPreview) {
    inputImages.addEventListener('change', () => {
      imagesPreview.innerHTML = '';
      const files = Array.from(inputImages.files || []);
      files.slice(0, 6).forEach((file) => {
        if (!file.type.startsWith('image/')) return;
        const url = URL.createObjectURL(file);
        const img = document.createElement('img');
        img.src = url;
        img.alt = 'preview';
        img.style.width = '80px';
        img.style.height = '80px';
        img.style.objectFit = 'cover';
        img.style.borderRadius = '6px';
        img.style.border = '1px solid #00000022';
        imagesPreview.appendChild(img);
      });
    });
  }
  if (postOpen) postOpen.addEventListener('click', openPostModal);
  if (postClose) postClose.addEventListener('click', closePostModal);
  if (postCancel) postCancel.addEventListener('click', closePostModal);

  async function postEvent() {
    const name = (inputName?.value || '').trim();
    const address = (inputAddress?.value || '').trim();
    const date = (inputDate?.value || '').trim();
    const organizer = (inputOrganizer?.value || '').trim();
    if (!name || !address || !date || !organizer) {
      showToast('Please fill in all fields', 'error');
      return;
    }
    const form = new FormData();
    form.set('name', name);
    form.set('address', address);
    form.set('date', date);
    form.set('organizer', organizer);
    const files = Array.from(inputImages?.files || []);
    files.slice(0, 6).forEach((f) => form.append('images[]', f));
    try {
      const res = await fetch('api/event_post.php', {
        method: 'POST',
        credentials: 'include',
        body: form,
      });
      const data = await res.json().catch(() => ({ ok: false }));
      if (!data.ok) {
        showToast(data.error || 'Failed to create event', 'error');
        return;
      }
      showToast('Event published', 'success');
      // insert a new card at top
      if (grid) {
        const card = document.createElement('div');
        card.className = 'event-card';
        card.dataset.eventId = String(data.id || '');
        const firstImg =
          data.images && data.images.length
            ? data.images[0]
            : 'https://placehold.co/600x400';
        card.dataset.img = firstImg;
        card.dataset.title = name;
        card.dataset.organizer = organizer;
        card.dataset.address = address;
        card.dataset.date = date;
        card.dataset.joined = '0';
        card.dataset.participants = '0';
        card.innerHTML = `
          <img src="${firstImg.replace(/"/g, '&quot;')}" alt="Event Image" />
          <div class="event-details">
            <h2>${name.replace(/</g, '&lt;')}</h2>
            <p><strong>Date:</strong> ${date.replace(/</g, '&lt;')}</p>
            <p><strong>Address:</strong> ${address.replace(/</g, '&lt;')}</p>
            <p><strong>Participants:</strong> <span class="participants-count">0</span></p>
            <button class="join-btn">Join Event</button>
          </div>
        `;
        // Prepend so it appears first
        if (grid.firstChild) grid.insertBefore(card, grid.firstChild);
        else grid.appendChild(card);
        // Attach the same click handler behavior as others
        card.addEventListener('click', (e) => {
          if (e.target.classList.contains('join-btn')) {
            e.stopPropagation();
            const btn = e.target;
            const eventId = card.dataset.eventId || '';
            const participantsEl = card.querySelector('.participants-count');
            const currentCount = parseInt(
              participantsEl?.textContent || '0',
              10
            );
            const isJoined = card.dataset.joined === '1';
            if (isJoined) {
              leaveEvent(eventId, btn).then((ok) => {
                if (ok) {
                  card.dataset.joined = '0';
                  btn.classList.remove('leave');
                  if (participantsEl)
                    participantsEl.textContent = String(
                      Math.max(0, currentCount - 1)
                    );
                }
              });
            } else {
              joinEvent(eventId, btn).then((ok) => {
                if (ok) {
                  card.dataset.joined = '1';
                  btn.classList.add('leave');
                  if (participantsEl)
                    participantsEl.textContent = String(currentCount + 1);
                }
              });
            }
            return;
          }
          // open popup with new card details
          popupImg.src = card.dataset.img;
          popupTitle.textContent = card.dataset.title;
          popupCommunity.textContent = '';
          popupOrganizer.textContent = 'Organizer: ' + card.dataset.organizer;
          popupAddress.textContent = 'Location: ' + card.dataset.address;
          popupDate.textContent = 'Date: ' + card.dataset.date;
          const alreadyJoined = card.dataset.joined === '1';
          popupAction.style.display = 'inline-block';
          popupAction.textContent = alreadyJoined
            ? 'Leave Event'
            : 'Join Event';
          popupAction.disabled = false;
          if (alreadyJoined) popupAction.classList.add('leave');
          else popupAction.classList.remove('leave');
          popupAction.dataset.eventId = card.dataset.eventId || '';
          popup.style.display = 'flex';
          document.body.style.overflow = 'hidden';
          const popupCountEl = document.getElementById(
            'event-popup-participants-count'
          );
          if (popupCountEl) popupCountEl.textContent = '0';
        });
      }
      closePostModal();
    } catch (e) {
      showToast('Network error', 'error');
    }
  }
  if (postSubmit) postSubmit.addEventListener('click', postEvent);

  async function joinEvent(eventId, buttonToDisable) {
    if (!eventId) return false;
    try {
      const form = new URLSearchParams();
      form.set('event_id', eventId);
      const res = await fetch('api/event_join.php', {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: form.toString(),
      });
      const data = await res.json().catch(() => ({ ok: false }));
      if (data.ok) {
        if (buttonToDisable) {
          buttonToDisable.textContent = 'Leave Event';
          buttonToDisable.disabled = false;
        }
        showToast(data.already ? 'Already joined' : 'Event Joined', 'success');
        return true;
      }
      showToast(data.error || 'Failed to join event', 'error');
      return false;
    } catch (e) {
      showToast('Network error', 'error');
      return false;
    }
  }

  async function leaveEvent(eventId, buttonToDisable) {
    if (!eventId) return false;
    try {
      const form = new URLSearchParams();
      form.set('event_id', eventId);
      const res = await fetch('api/event_leave.php', {
        method: 'POST',
        credentials: 'include',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: form.toString(),
      });
      const data = await res.json().catch(() => ({ ok: false }));
      if (data.ok) {
        if (buttonToDisable) {
          buttonToDisable.textContent = 'Join Event';
          buttonToDisable.disabled = false;
        }
        showToast('Left event', 'info');
        return true;
      }
      showToast(data.error || 'Failed to leave event', 'error');
      return false;
    } catch (e) {
      showToast('Network error', 'error');
      return false;
    }
  }

  // Make whole card clickable; if Join/Leave clicked on the card, toggle and update counts
  cards.forEach((card) => {
    card.addEventListener('click', (e) => {
      if (e.target.classList.contains('join-btn')) {
        e.stopPropagation();
        const btn = e.target;
        const eventId = card.dataset.eventId || '';
        const participantsEl = card.querySelector('.participants-count');
        const currentCount = parseInt(participantsEl?.textContent || '0', 10);
        const isJoined = card.dataset.joined === '1';
        if (isJoined) {
          leaveEvent(eventId, btn).then((ok) => {
            if (ok) {
              card.dataset.joined = '0';
              btn.classList.remove('leave');
              if (participantsEl)
                participantsEl.textContent = String(
                  Math.max(0, currentCount - 1)
                );
            }
          });
        } else {
          joinEvent(eventId, btn).then((ok) => {
            if (ok) {
              card.dataset.joined = '1';
              btn.classList.add('leave');
              if (participantsEl)
                participantsEl.textContent = String(currentCount + 1);
            }
          });
        }
        return;
      }
      popupImg.src = card.dataset.img;
      popupTitle.textContent = card.dataset.title;
      if (card.dataset.community) {
        popupCommunity.textContent = 'Community: ' + card.dataset.community;
      } else {
        popupCommunity.textContent = '';
      }
      popupOrganizer.textContent = 'Organizer: ' + card.dataset.organizer;
      popupAddress.textContent = 'Location: ' + card.dataset.address;
      popupDate.textContent = 'Date: ' + card.dataset.date;

      // Show join/leave button in popup and wire handler
      const alreadyJoined = card.dataset.joined === '1';
      popupAction.style.display = 'inline-block';
      popupAction.textContent = alreadyJoined ? 'Leave Event' : 'Join Event';
      popupAction.disabled = false;
      if (alreadyJoined) popupAction.classList.add('leave');
      else popupAction.classList.remove('leave');
      popupAction.dataset.eventId = card.dataset.eventId || '';
      popupAction.onclick = async () => {
        const eventId = popupAction.dataset.eventId;
        if (!eventId) return;
        const isJoined = card.dataset.joined === '1';
        const participantsEl = card.querySelector('.participants-count');
        const popupCountEl = document.getElementById(
          'event-popup-participants-count'
        );
        const currentCount = parseInt(participantsEl?.textContent || '0', 10);
        if (isJoined) {
          const ok = await leaveEvent(eventId, popupAction);
          if (ok) {
            card.dataset.joined = '0';
            const cardBtn = card.querySelector('.join-btn');
            if (cardBtn) {
              cardBtn.textContent = 'Join Event';
              cardBtn.disabled = false;
              cardBtn.classList.remove('leave');
            }
            const newCount = Math.max(0, currentCount - 1);
            if (participantsEl) participantsEl.textContent = String(newCount);
            if (popupCountEl) popupCountEl.textContent = String(newCount);
            popupAction.textContent = 'Join Event';
            popupAction.classList.remove('leave');
          }
        } else {
          const ok = await joinEvent(eventId, popupAction);
          if (ok) {
            card.dataset.joined = '1';
            const cardBtn = card.querySelector('.join-btn');
            if (cardBtn) {
              cardBtn.textContent = 'Leave Event';
              cardBtn.disabled = false;
              cardBtn.classList.add('leave');
            }
            const newCount = currentCount + 1;
            if (participantsEl) participantsEl.textContent = String(newCount);
            if (popupCountEl) popupCountEl.textContent = String(newCount);
            popupAction.textContent = 'Leave Event';
            popupAction.classList.add('leave');
          }
        }
      };

      popup.style.display = 'flex';
      // Prevent background scroll while popup is open
      document.body.style.overflow = 'hidden';

      // Sync popup participants count from card on open
      const popupCountEl = document.getElementById(
        'event-popup-participants-count'
      );
      if (popupCountEl) {
        const participantsEl = card.querySelector('.participants-count');
        popupCountEl.textContent = participantsEl
          ? participantsEl.textContent
          : '';
      }
    });
  });

  closeBtn.addEventListener('click', () => {
    popup.style.display = 'none';
    // Restore background scroll
    document.body.style.overflow = '';
  });

  popup.addEventListener('click', (e) => {
    if (e.target === popup) {
      popup.style.display = 'none';
      // Restore background scroll
      document.body.style.overflow = '';
    }
  });
});
