// home.js - Admin toggle functionality

document.addEventListener('DOMContentLoaded', () => {
  // Admin toggle functionality
  document.querySelectorAll('.admin-toggle').forEach((checkbox) => {
    checkbox.addEventListener('change', async function () {
      const userId = this.dataset.userid;
      const isAdmin = this.checked;

      try {
        const response = await fetch('api/toggle_admin.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ user_id: userId, is_admin: isAdmin }),
        });

        const data = await response.json();

        if (data.ok) {
          // Show success message
          showSuccessMessage(
            isAdmin ? 'User promoted to admin' : 'Admin privileges removed'
          );
        } else {
          alert(data.error || 'Failed to update admin status');
          this.checked = !isAdmin; // Revert checkbox
        }
      } catch (err) {
        console.error('Error:', err);
        alert('Network error. Please try again.');
        this.checked = !isAdmin; // Revert checkbox
      }
    });
  });
});

function showSuccessMessage(text) {
  const msg = document.createElement('div');
  msg.className = 'success-toast';
  msg.textContent = text;
  document.body.appendChild(msg);

  // Trigger animation
  requestAnimationFrame(() => {
    msg.classList.add('show');
  });

  setTimeout(() => {
    msg.classList.remove('show');
    setTimeout(() => msg.remove(), 300);
  }, 3000);
}
