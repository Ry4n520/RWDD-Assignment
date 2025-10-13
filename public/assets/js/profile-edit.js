// profile-edit.js: handles opening modal, populating form and submitting to /api/profile.php
document.addEventListener('DOMContentLoaded', () => {
  const modal = document.getElementById('editProfileModal');
  const form = document.getElementById('editProfileForm');
  const cancel = document.getElementById('cancelEdit');
  const save = document.getElementById('saveEdit');
  const openBtn = document.getElementById('openEditProfile');
  const avatarInput = document.getElementById('avatarInput');
  const avatarImg = document.getElementById('profileAvatar');

  function openModal() {
    // populate fields from DOM
    document.getElementById('editUsername').value =
      document.getElementById('profileName')?.textContent?.trim() || '';
    document.getElementById('editBio').value =
      document.querySelector('.bio')?.textContent?.trim() || '';
    document.getElementById('editEmail').value =
      document.getElementById('profileEmail')?.textContent?.trim() || '';
    document.getElementById('editPhone').value =
      document.getElementById('profilePhone')?.textContent?.trim() || '';

    modal.classList.add('open');
    modal.setAttribute('aria-hidden', 'false');
  }
  function closeModal() {
    modal.classList.remove('open');
    modal.setAttribute('aria-hidden', 'true');
  }

  // single edit button
  if (openBtn)
    openBtn.addEventListener('click', (e) => {
      e.preventDefault();
      openModal();
    });

  // avatar upload handling
  if (avatarInput && avatarImg) {
    avatarInput.addEventListener('change', async (e) => {
      const file = avatarInput.files[0];
      if (!file) return;
      const formData = new FormData();
      formData.append('avatar', file);
      try {
        const res = await fetch('api/profile-avatar.php', {
          method: 'POST',
          credentials: 'same-origin',
          body: formData,
        });
        const json = await res.json();
        if (json.success && json.url) {
          // update preview and optionally reload
          avatarImg.src = json.url + '?t=' + Date.now();
        } else {
          alert('Upload failed: ' + (json.error || 'unknown'));
        }
      } catch (err) {
        alert('Network error during upload');
      }
    });
  }

  // backdrop close
  modal
    .querySelectorAll('[data-close]')
    .forEach((el) => el.addEventListener('click', closeModal));
  cancel.addEventListener('click', (e) => {
    e.preventDefault();
    closeModal();
  });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    save.disabled = true;
    const data = {
      username: document.getElementById('editUsername').value.trim(),
      bio: document.getElementById('editBio').value.trim(),
      email: document.getElementById('editEmail').value.trim(),
      phone: document.getElementById('editPhone').value.trim(),
    };

    try {
      const res = await fetch('api/profile.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify(data),
      });
      const json = await res.json();
      if (json.success) {
        // update DOM values without a full reload
        document.getElementById('profileName').textContent =
          data.username || document.getElementById('profileName').textContent;
        document.querySelector('.bio').textContent =
          data.bio || document.querySelector('.bio').textContent;
        document.getElementById('profileEmail').textContent =
          data.email || document.getElementById('profileEmail').textContent;
        document.getElementById('profilePhone').textContent =
          data.phone || document.getElementById('profilePhone').textContent;
        closeModal();
      } else {
        alert('Save failed: ' + (json.error || 'unknown'));
      }
    } catch (err) {
      alert('Network error');
    } finally {
      save.disabled = false;
    }
  });
});
