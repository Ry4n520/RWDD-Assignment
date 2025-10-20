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
          credentials: 'include',
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

  // live-preview: as user types in the modal, update the profile preview immediately
  const liveBind = () => {
    const usernameIn = document.getElementById('editUsername');
    const bioIn = document.getElementById('editBio');
    const emailIn = document.getElementById('editEmail');
    const phoneIn = document.getElementById('editPhone');

    if (usernameIn)
      usernameIn.addEventListener('input', (e) => {
        const v = e.target.value.trim();
        if (v.length) document.getElementById('profileName').textContent = v;
      });
    if (bioIn)
      bioIn.addEventListener('input', (e) => {
        const v = e.target.value;
        document.querySelector('.bio').innerHTML = (v || '').replace(
          /\n/g,
          '<br>'
        );
      });
    if (emailIn)
      emailIn.addEventListener('input', (e) => {
        const v = e.target.value.trim();
        document.getElementById('profileEmail').textContent = v;
      });
    if (phoneIn)
      phoneIn.addEventListener('input', (e) => {
        const v = e.target.value.trim();
        document.getElementById('profilePhone').textContent = v;
      });
  };

  liveBind();

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
        credentials: 'include',
        body: JSON.stringify(data),
      });
      const json = await res.json().catch(() => ({}));
      if (res.ok && json.success) {
        // if backend returned updated user, use it to update DOM
        const u = json.user || {};
        if (u.username)
          document.getElementById('profileName').textContent = u.username;
        if (u.bio)
          document.querySelector('.bio').innerHTML = (u.bio || '').replace(
            /\n/g,
            '<br>'
          );
        if (u.email)
          document.getElementById('profileEmail').textContent = u.email;
        if (u.phone)
          document.getElementById('profilePhone').textContent = u.phone;
        if (json.profile_picture)
          document.getElementById('profileAvatar').src =
            json.profile_picture + '?t=' + Date.now();
        closeModal();
      } else {
        const msg = json?.error || 'HTTP ' + res.status;
        alert('Save failed: ' + msg + (json?.detail ? '\n' + json.detail : ''));
      }
    } catch (err) {
      alert('Network error');
    } finally {
      save.disabled = false;
    }
  });
});
