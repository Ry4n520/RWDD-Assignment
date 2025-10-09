document.addEventListener("DOMContentLoaded", () => {
  const cards = document.querySelectorAll(".event-card");
  const popup = document.getElementById("event-popup");
  const closeBtn = document.querySelector(".event-popup-close");

  const popupImg = document.getElementById("event-popup-img");
  const popupTitle = document.getElementById("event-popup-title");
  const popupCommunity = document.getElementById("event-popup-community");
  const popupOrganizer = document.getElementById("event-popup-organizer");
  const popupAddress = document.getElementById("event-popup-address");
  const popupDate = document.getElementById("event-popup-date");

  // Make whole card clickable except join button
  cards.forEach(card => {
    card.addEventListener("click", (e) => {
      if (e.target.classList.contains("join-btn")) {
        // Stop popup if user clicked "Join Event"
        return;
      }

      popupImg.src = card.dataset.img;
      popupTitle.textContent = card.dataset.title;
      popupCommunity.textContent = "Community: " + card.dataset.community;
      popupOrganizer.textContent = "Organizer: " + card.dataset.organizer;
      popupAddress.textContent = "Location: " + card.dataset.address;
      popupDate.textContent = "Date: " + card.dataset.date;

      popup.style.display = "flex";
    });
  });

  closeBtn.addEventListener("click", () => {
    popup.style.display = "none";
  });

  popup.addEventListener("click", (e) => {
    if (e.target === popup) {
      popup.style.display = "none";
    }
  });
});
