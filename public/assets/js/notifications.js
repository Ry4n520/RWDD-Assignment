// notifications.js
// Fetch and render notifications; handle mark-read and trade accept/decline
(function () {
  async function api(path, opts = {}) {
    opts.credentials = 'include';
    const res = await fetch(path, opts);
    const text = await res.text();
    try {
      return JSON.parse(text);
    } catch (e) {
      return { ok: false, raw: text };
    }
  }

  function renderNotification(n) {
    const div = document.createElement('div');
    div.className = 'notification' + (n.is_read ? ' read' : '');
    div.dataset.id = n.id;
    const head = document.createElement('div');
    head.className = 'notif-head';
    head.textContent = new Date(n.created_at).toLocaleString();
    const body = document.createElement('div');
    body.className = 'notif-body';
    let text = '';
    if (n.type === 'like') {
      text = `Someone liked your post/comments.`;
    } else if (n.type === 'trade_request') {
      const from =
        n.payload && n.payload.from ? `User #${n.payload.from}` : 'Someone';
      text = `${from} sent you a trade request.`;
    } else if (n.type === 'trade_accepted') {
      text = `Your trade request was accepted.`;
    } else if (n.type === 'trade_declined') {
      text = `Your trade request was declined.`;
    } else {
      text = n.type + ' ' + JSON.stringify(n.payload);
    }
    body.textContent = text;

    const actions = document.createElement('div');
    actions.className = 'notif-actions';
    const markBtn = document.createElement('button');
    markBtn.textContent = 'Mark read';
    markBtn.addEventListener('click', async () => {
      await api('/public/api/notifications.php', {
        method: 'POST',
        body: new URLSearchParams({ action: 'mark_read', id: n.id }),
      });
      div.classList.add('read');
    });
    actions.appendChild(markBtn);

    if (n.type === 'trade_request') {
      const accept = document.createElement('button');
      accept.textContent = 'Accept';
      const decline = document.createElement('button');
      decline.textContent = 'Decline';
      accept.addEventListener('click', async () => {
        // call trade respond
        await api('/public/api/trade.php', {
          method: 'POST',
          body: new URLSearchParams({
            action: 'respond',
            trade_id: n.payload.trade_id,
            response: 'accept',
          }),
        });
        await api('/public/api/notifications.php', {
          method: 'POST',
          body: new URLSearchParams({ action: 'mark_read', id: n.id }),
        });
        div.classList.add('read');
      });
      decline.addEventListener('click', async () => {
        await api('/public/api/trade.php', {
          method: 'POST',
          body: new URLSearchParams({
            action: 'respond',
            trade_id: n.payload.trade_id,
            response: 'decline',
          }),
        });
        await api('/public/api/notifications.php', {
          method: 'POST',
          body: new URLSearchParams({ action: 'mark_read', id: n.id }),
        });
        div.classList.add('read');
      });
      actions.appendChild(accept);
      actions.appendChild(decline);
    }

    div.appendChild(head);
    div.appendChild(body);
    div.appendChild(actions);
    return div;
  }

  async function loadNotifications(container) {
    container.innerHTML = '<div class="loading">Loading...</div>';
    const res = await api('/public/api/notifications.php');
    container.innerHTML = '';
    if (!res.ok) {
      container.textContent = 'Failed to load';
      return;
    }
    if (res.notifications.length === 0) {
      container.textContent = 'No notifications.';
      return;
    }
    res.notifications.forEach((n) => {
      const el = renderNotification(n);
      container.appendChild(el);
    });
  }

  // attach to DOM when available
  document.addEventListener('DOMContentLoaded', () => {
    const container = document.querySelector('#notificationsList');
    if (!container) return;
    loadNotifications(container);
    // mark all read control
    const markAll = document.querySelector('#markAllNotifications');
    if (markAll)
      markAll.addEventListener('click', async () => {
        await api('/public/api/notifications.php', {
          method: 'POST',
          body: new URLSearchParams({ action: 'mark_all_read' }),
        });
        loadNotifications(container);
      });
  });
})();
