// events-popup.js
// Handles event card popup logic

document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.event-card').forEach((card) => {
    card.style.cursor = 'pointer';
    card.addEventListener('click', function (e) {
      // Prevent button click from triggering popup
      if (e.target.classList.contains('join-btn')) return;
      const img = card.querySelector('img').src;
      const title = card.querySelector('h2').textContent;
      const [date, location] = Array.from(card.querySelectorAll('p')).map(
        (p) => p.textContent
      );
      document.getElementById('popup-img').src = img;
      document.getElementById('popup-title').textContent = title;
      document.getElementById('popup-date').textContent = date;
      document.getElementById('popup-location').textContent = location;
      document.getElementById('event-popup').style.display = 'flex';
      // Show the popup button when popup opens
      var popupBtn = document.getElementById('popup-action-btn');
      if (popupBtn) popupBtn.style.display = 'inline-block';
    });
  });
  // Close popup
  document.querySelector('.event-popup-close').onclick = function () {
    document.getElementById('event-popup').style.display = 'none';
    // Hide the button again after closing
    var popupBtn = document.getElementById('popup-action-btn');
    if (popupBtn) popupBtn.style.display = 'none';
  };
  // Close popup when clicking outside content
  document.getElementById('event-popup').onclick = function (e) {
    if (e.target === this) {
      this.style.display = 'none';
      // Hide the button again after closing
      var popupBtn = document.getElementById('popup-action-btn');
      if (popupBtn) popupBtn.style.display = 'none';
    }
  };

  // Popup button action
  var popupBtn = document.getElementById('popup-action-btn');
  if (popupBtn) {
    popupBtn.onclick = function () {
      alert('You have joined the event!');
      document.getElementById('event-popup').style.display = 'none';
    };
  }
});
