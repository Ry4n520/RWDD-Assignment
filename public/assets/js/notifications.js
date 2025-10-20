// notifications.js
// Fetch and render notifications; handle mark-read and trade accept/decline
(function () {
  function parsePayload(p) {
    if (!p) return {};
    if (typeof p === 'object') return p;
    try {
      return JSON.parse(p);
    } catch {
      return {};
    }
  }
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
    const payload = parsePayload(n.payload);
    const who =
      payload.from_username ||
      payload.username ||
      (payload.from_user ? 'User #' + payload.from_user : 'Someone');
    if (n.type === 'like') {
      const postName =
        payload.item_name || payload.post_title || payload.post_name || '';
      text = `${who} liked your post${postName ? ' "' + postName + '"' : ''}.`;
    } else if (n.type === 'trading_request' || n.type === 'trade_request') {
      // support both legacy and new naming
      const fromUser = payload.from_user || payload.from || null;
      const itemId = payload.item_id || payload.target_item_id || null;
      const itemName = payload.item_name || null;
      const baseText = `${
        payload.from_username
          ? payload.from_username
          : fromUser
          ? 'User #' + fromUser
          : 'Someone'
      } wants your item${
        itemName ? ' "' + itemName + '"' : itemId ? ' #' + itemId : ''
      }.`;
      // If server enriched with status, reflect persistent state
      if (payload.request_status === 'Accepted') {
        text = baseText;
      } else if (payload.request_status === 'Rejected') {
        text = baseText;
      } else {
        text = baseText;
      }
    } else if (n.type === 'trading_accepted' || n.type === 'trade_accepted') {
      const itemName = payload.item_name || '';
      text = `Your request${
        itemName ? ' for "' + itemName + '"' : ''
      } was accepted.`;
    } else if (n.type === 'trading_declined' || n.type === 'trade_declined') {
      const itemName = payload.item_name || '';
      text = `Your request${
        itemName ? ' for "' + itemName + '"' : ''
      } was declined.`;
    } else if (n.type === 'comment') {
      const postName =
        payload.item_name || payload.post_title || payload.post_name || '';
      const excerpt = payload.excerpt ? `: "${payload.excerpt}"` : '';
      text = `${who} commented on your post${
        postName ? ' "' + postName + '"' : ''
      }${excerpt}.`;
    } else {
      text = n.type + ' ' + JSON.stringify(n.payload);
    }
    body.textContent = text;

    const actions = document.createElement('div');
    actions.className = 'notif-actions';
    // helpers to move notification to Read list and update counts
    function moveToRead() {
      const unreadList = document.querySelector('.notifications-unread');
      const readList = document.querySelector('.notifications-read');
      if (!readList) return;
      // Remove empty state in read list if present
      const emptyRead = readList.querySelector('.empty');
      if (emptyRead) emptyRead.remove();
      // Move node
      readList.appendChild(div);
      // If unread becomes empty, show its empty state
      if (unreadList) {
        const hasUnread = unreadList.querySelector('.notification');
        if (!hasUnread) {
          const empty = document.createElement('div');
          empty.className = 'empty';
          empty.textContent = 'No unread notifications';
          unreadList.appendChild(empty);
        }
      }
      updateTabCounts();
    }
    function updateTabCounts() {
      const unreadList = document.querySelector('.notifications-unread');
      const readList = document.querySelector('.notifications-read');
      const unreadCount = unreadList
        ? unreadList.querySelectorAll('.notification').length
        : 0;
      const readCount = readList
        ? readList.querySelectorAll('.notification').length
        : 0;
      const headerUnread = document.getElementById('tabUnread');
      const headerRead = document.getElementById('tabRead');
      if (headerUnread) headerUnread.textContent = `Unread (${unreadCount})`;
      if (headerRead) headerRead.textContent = `Read (${readCount})`;
    }
    const markBtn = document.createElement('button');
    markBtn.textContent = 'Mark read';
    markBtn.className = 'btn btn-sm btn-outline';
    markBtn.addEventListener('click', async () => {
      await api('api/notifications.php', {
        method: 'POST',
        body: new URLSearchParams({ action: 'mark_read', id: n.id }),
      });
      // Reload list so this item moves from Unread to Read
      const container = document.getElementById('notificationsList');
      if (container) loadNotifications(container);
    });
    actions.appendChild(markBtn);

    // Accept/Decline for trading requests (only if still Pending)
    if (n.type === 'trading_request' || n.type === 'trade_request') {
      const accept = document.createElement('button');
      accept.textContent = 'Accept';
      accept.className = 'btn btn-sm btn-primary';
      const decline = document.createElement('button');
      decline.textContent = 'Decline';
      decline.className = 'btn btn-sm btn-danger';
      // carry data for later inline update
      const pl = parsePayload(n.payload);
      const meetLoc = pl.meetup_location || '';
      const displayItem = pl.item_name
        ? pl.item_name
        : pl.item_id
        ? 'Item #' + pl.item_id
        : '';
      accept.dataset.notif = n.id;
      decline.dataset.notif = n.id;
      accept.dataset.req = pl.request_id || '';
      decline.dataset.req = pl.request_id || '';
      accept.dataset.loc = meetLoc;
      decline.dataset.loc = meetLoc;
      accept.dataset.item = displayItem;
      decline.dataset.item = displayItem;
      // If already Accepted/Rejected (server-enriched), show final state and skip rendering buttons
      if (pl.request_status === 'Accepted') {
        const extra = document.createElement('div');
        extra.className = 'notif-extra';
        const itemText = displayItem ? ` for "${displayItem}"` : '';
        const locText = meetLoc ? ` • Meetup: ${meetLoc}` : '';
        extra.textContent = `Request accepted${itemText}.${locText}`.trim();
        actions.innerHTML = '';
        actions.appendChild(extra);
      } else if (pl.request_status === 'Rejected') {
        const extra = document.createElement('div');
        extra.className = 'notif-extra';
        const itemText = displayItem ? ` for "${displayItem}"` : '';
        extra.textContent = `Request declined${itemText}.`;
        actions.innerHTML = '';
        actions.appendChild(extra);
      } else {
        // Pending: render buttons
        actions.appendChild(accept);
        actions.appendChild(decline);
      }
      accept.addEventListener('click', async () => {
        const requestId = accept.dataset.req;
        if (!requestId) return;
        await api('api/trading_request_respond.php', {
          method: 'POST',
          body: new URLSearchParams({
            request_id: requestId,
            response: 'accept',
          }),
        });
        await api('api/notifications.php', {
          method: 'POST',
          body: new URLSearchParams({ action: 'mark_read', id: n.id }),
        });
        // inline update: mark read, show meetup location, remove action buttons
        div.classList.add('read');
        const extra = document.createElement('div');
        extra.className = 'notif-extra';
        const itemText = accept.dataset.item
          ? ` for "${accept.dataset.item}"`
          : '';
        const locText = accept.dataset.loc
          ? ` • Meetup: ${accept.dataset.loc}`
          : '';
        extra.textContent = `Request accepted${itemText}.${locText}`.trim();
        actions.innerHTML = '';
        actions.appendChild(extra);
        // Persist in UI: move to Read list and update counts
        moveToRead();
      });
      decline.addEventListener('click', async () => {
        const requestId = decline.dataset.req;
        if (!requestId) return;
        await api('api/trading_request_respond.php', {
          method: 'POST',
          body: new URLSearchParams({
            request_id: requestId,
            response: 'decline',
          }),
        });
        await api('api/notifications.php', {
          method: 'POST',
          body: new URLSearchParams({ action: 'mark_read', id: n.id }),
        });
        // inline update
        div.classList.add('read');
        const extra = document.createElement('div');
        extra.className = 'notif-extra';
        const itemText = decline.dataset.item
          ? ` for "${decline.dataset.item}"`
          : '';
        extra.textContent = `Request declined${itemText}.`;
        actions.innerHTML = '';
        actions.appendChild(extra);
        moveToRead();
      });
    }

    div.appendChild(head);
    div.appendChild(body);
    div.appendChild(actions);
    return div;
  }

  async function loadNotifications(container) {
    container.innerHTML = '<div class="loading">Loading...</div>';
    const res = await api('api/notifications.php');
    container.innerHTML = '';
    if (!res.ok) {
      container.textContent = 'Failed to load';
      return;
    }
    const list = Array.isArray(res.notifications) ? res.notifications : [];
    const unread = list.filter((n) => !n.is_read);
    const read = list.filter((n) => !!n.is_read);

    const unreadWrap = document.createElement('div');
    unreadWrap.className = 'notif-section-unread';
    const unreadList = document.createElement('div');
    unreadList.className = 'notifications-unread';
    unreadWrap.appendChild(unreadList);

    const readWrap = document.createElement('div');
    readWrap.className = 'notif-section-read';
    const readList = document.createElement('div');
    readList.className = 'notifications-read';
    readWrap.appendChild(readList);

    if (unread.length === 0) {
      const empty = document.createElement('div');
      empty.className = 'empty';
      empty.textContent = 'No unread notifications';
      unreadList.appendChild(empty);
    } else {
      unread.forEach((n) => unreadList.appendChild(renderNotification(n)));
    }
    if (read.length === 0) {
      const empty = document.createElement('div');
      empty.className = 'empty';
      empty.textContent = 'No read notifications';
      readList.appendChild(empty);
    } else {
      read.forEach((n) => readList.appendChild(renderNotification(n)));
    }

    container.appendChild(unreadWrap);
    container.appendChild(readWrap);

    // Update tab labels with counts
    const headerUnread = document.getElementById('tabUnread');
    const headerRead = document.getElementById('tabRead');
    if (headerUnread) headerUnread.textContent = `Unread (${unread.length})`;
    if (headerRead) headerRead.textContent = `Read (${read.length})`;

    // Toggle display based on selected tab
    const defaultTab = localStorage.getItem('notifTab') || 'unread';
    function applyTab(tab) {
      const showUnread = tab === 'unread';
      unreadWrap.style.display = showUnread ? '' : 'none';
      readWrap.style.display = showUnread ? 'none' : '';
      const linkUnread = document.getElementById('tabUnread');
      const linkRead = document.getElementById('tabRead');
      if (linkUnread && linkRead) {
        if (showUnread) {
          linkUnread.classList.add('active-link');
          linkRead.classList.remove('active-link');
        } else {
          linkRead.classList.add('active-link');
          linkUnread.classList.remove('active-link');
        }
      }
    }
    applyTab(defaultTab);

    const linkUnread = document.getElementById('tabUnread');
    const linkRead = document.getElementById('tabRead');
    if (linkUnread)
      linkUnread.onclick = (e) => {
        e.preventDefault();
        localStorage.setItem('notifTab', 'unread');
        applyTab('unread');
      };
    if (linkRead)
      linkRead.onclick = (e) => {
        e.preventDefault();
        localStorage.setItem('notifTab', 'read');
        applyTab('read');
      };
  }

  // attach to DOM when available
  document.addEventListener('DOMContentLoaded', () => {
    const container = document.querySelector('#notificationsList');
    if (!container) return;
    // Default to Unread on first visit if no stored preference
    if (!localStorage.getItem('notifTab'))
      localStorage.setItem('notifTab', 'unread');
    loadNotifications(container);
    // mark all read control
    const markAll = document.querySelector('#markAllNotifications');
    if (markAll)
      markAll.addEventListener('click', async () => {
        await api('api/notifications.php', {
          method: 'POST',
          body: new URLSearchParams({ action: 'mark_all_read' }),
        });
        loadNotifications(container);
      });
  });
})();
