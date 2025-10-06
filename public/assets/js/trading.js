// trading-popup.js
// Handles trading card popup logic (similar to events)

document.addEventListener('DOMContentLoaded', function () {
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
      if (popupBtn) popupBtn.style.display = 'inline-block';
    });
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

  // Popup button action
  var popupBtn = document.getElementById('trading-popup-action-btn');
  if (popupBtn) {
    popupBtn.onclick = function () {
      alert('Trade request sent!');
      const popup = document.getElementById('trading-popup');
      popup.style.display = 'none';
      // Restore background scroll
      document.body.style.overflow = '';
    };
  }
});
