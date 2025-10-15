// communities.js - handles AJAX posting, comments fetch, comment posting, and likes

async function apiFetch(path, opts = {}) {
  // default options: keep same-origin cookies; caller may override headers/body
  const res = await fetch(
    path,
    Object.assign({ credentials: 'same-origin' }, opts)
  );
  let data = null;
  try {
    data = await res.json();
  } catch (e) {
    // JSON parse failed; capture raw text for better error message
    try {
      const text = await res.text();
      throw new Error(
        'Invalid JSON response from server: ' +
          (text ? text.slice(0, 2000) : '[empty]')
      );
    } catch (inner) {
      throw new Error('Invalid JSON response from server');
    }
  }
  if (!res.ok) {
    const msg =
      data && data.error
        ? data.error + (data.detail ? ' - ' + data.detail : '')
        : 'Network error: ' + res.status;
    const err = new Error(msg);
    err.response = data;
    throw err;
  }
  return data;
}

// Composer submit is handled in the DOMContentLoaded form submit handler below.

// Build post HTML (server returns post with PostID, Content, Created_at, username)
function buildPostHtml(post, commentCount) {
  const imagesHtml = (post.images || [])
    .map(
      (src) =>
        `<img src="${escapeHtml(src)}" class="post-image" alt="post image">`
    )
    .join('');
  return `
  <article class="post" data-postid="${post.PostID}">
    <div class="post-header">
      ${
        post.Title
          ? `<h3 class="post-title">${escapeHtml(post.Title)}</h3>`
          : ''
      }
      <div class="post-meta"><strong>${escapeHtml(
        post.username
      )}</strong> <span class="time">${escapeHtml(post.Created_at)}</span></div>
    </div>
    <p class="post-content">${escapeHtml(post.Content || '').replace(
      /\n/g,
      '<br>'
    )}</p>
    <div class="post-images">${imagesHtml}</div>
    <div class="post-actions">
      <a class="like-btn" href="#" data-type="post" data-id="${
        post.PostID
      }">👍 0</a>
      <button type="button" class="comment-toggle" data-target="#comments-${
        post.PostID
      }">💬 ${commentCount}</button>
    </div>
    <div class="comments" id="comments-${post.PostID}" aria-hidden="true"></div>
  </article>
  `;
}

function escapeHtml(s) {
  return String(s)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

// Event delegation for comment toggle, submit comment, and likes
document.addEventListener('click', async function (e) {
  // comment toggle
  const ct = e.target.closest('.comment-toggle');
  if (ct) {
    const target = document.querySelector(ct.dataset.target);
    if (!target) return;
    const isHidden = target.getAttribute('aria-hidden') === 'true';
    if (isHidden && !target.dataset.loaded) {
      // fetch comments
      const postId = ct.dataset.target.replace('#comments-', '');
      try {
        const json = await apiFetch(
          'api/comments.php?postId=' + encodeURIComponent(postId)
        );
        if (json.success) {
          target.innerHTML = renderCommentsHtml(json.comments);
          // append comment form
          const formHtml = `<form class="add-comment-ajax"><input type="text" name="content" placeholder="Write a comment..."><button type="submit">Reply</button></form>`;
          target.insertAdjacentHTML('beforeend', formHtml);
          target.dataset.loaded = '1';
        }
      } catch (err) {
        console.error(err);
      }
    }
    target.setAttribute('aria-hidden', isHidden ? 'false' : 'true');
    if (isHidden) target.scrollIntoView({ behavior: 'smooth', block: 'start' });
    return;
  }

  // like buttons
  const lb = e.target.closest('.like-btn');
  if (lb) {
    e.preventDefault();
    const type = lb.dataset.type;
    const id = lb.dataset.id;
    if (!type || !id) return;
    try {
      const json = await apiFetch('api/like.php', {
        method: 'POST',
        body: JSON.stringify({ type, id }),
      });
      if (json.success) {
        lb.textContent = `👍 ${json.count}`;
      }
    } catch (err) {
      console.error(err);
    }
    return;
  }
});

// handle ajax comment submit (delegated)
document.addEventListener('submit', async function (e) {
  const a = e.target.closest('.add-comment-ajax');
  if (a) {
    e.preventDefault();
    const input = a.querySelector('input[name="content"]');
    const targetDiv = a.closest('.comments');
    const postId = targetDiv ? targetDiv.id.replace('comments-', '') : null;
    if (!postId || !input) return;
    const content = input.value.trim();
    if (!content) return;
    try {
      const json = await apiFetch('api/comments.php', {
        method: 'POST',
        body: JSON.stringify({ postId: parseInt(postId), content }),
      });
      if (json.success && json.comment) {
        // insert into comments before the form
        const html = `<div class="comment"><span class="comment-user">${escapeHtml(
          json.comment.username
        )}:</span><p>${escapeHtml(
          json.comment.Content
        )}</p><a href="#" class="like-btn" data-type="comment" data-id="${
          json.comment.CommentID
        }">👍 0</a></div>`;
        a.insertAdjacentHTML('beforebegin', html);
        input.value = '';
      }
    } catch (err) {
      console.error(err);
    }
  }
});

function renderCommentsHtml(comments) {
  return comments
    .map(
      (c) =>
        `<div class="comment"><span class="comment-user">${escapeHtml(
          c.username
        )}:</span><p>${escapeHtml(
          c.Content
        )}</p><a href="#" class="like-btn" data-type="comment" data-id="${
          c.CommentID
        }">👍 0</a></div>`
    )
    .join('');
}

document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('postForm');
  const title = document.getElementById('postTitle');
  const content = document.getElementById('postContent');
  const filesInput = document.getElementById('postFiles');
  const preview = document.getElementById('filePreview');
  const postButton = document.getElementById('postButton');

  // remove preview/file handlers if you prefer they are hidden
  filesInput?.addEventListener('change', () => {
    preview.innerHTML = '';
    const files = Array.from(filesInput.files || []);
    if (!files.length) {
      preview.setAttribute('aria-hidden', 'true');
      return;
    }
    preview.removeAttribute('aria-hidden');
    for (const f of files.slice(0, 6)) {
      if (!f.type.startsWith('image/')) continue;
      const img = document.createElement('img');
      img.src = URL.createObjectURL(f);
      img.onload = () => URL.revokeObjectURL(img.src);
      preview.appendChild(img);
    }
  });

  // Prevent double submissions and ensure proper async flow
  form?.addEventListener('submit', async (ev) => {
    ev.preventDefault();
    if (!postButton) return;

    // prevent double-clicks/submissions
    if (postButton.dataset.busy === '1') return;
    postButton.dataset.busy = '1';
    postButton.disabled = true;

    const titleVal = title.value.trim();
    const contentVal = content.value.trim();

    // require both title and content
    if (!titleVal || !contentVal) {
      showMessage('Please add both a title and content.', 'error');
      postButton.dataset.busy = '0';
      postButton.disabled = false;
      return;
    }

    const fd = new FormData();
    fd.append('title', titleVal);
    fd.append('content', contentVal);

    const files = Array.from(filesInput.files || []);
    if (files.length) {
      for (const f of files) fd.append('images[]', f);
    }

    try {
      const res = await fetch('api/posts.php', {
        method: 'POST',
        body: fd,
        credentials: 'include',
      });

      const text = await res.text();
      let json;
      try {
        json = JSON.parse(text);
      } catch (e) {
        alert('Save failed: HTTP ' + res.status + '\n' + text);
        console.error('Unexpected non-json response from api/posts.php:', text);
        return;
      }

      if (!json.success) {
        showMessage('Save failed: ' + (json.error || 'unknown'), 'error');
        console.error('API error response:', json);
        return;
      }

      // render new post or reload if builder not present
      if (typeof buildPostHtml === 'function') {
        const postHtml = buildPostHtml(json.post, json.commentCount || 0);
        const postsContainer = document.querySelector('.posts');
        if (postsContainer) {
          const wrapper = document.createElement('div');
          wrapper.innerHTML = postHtml;
          postsContainer.prepend(wrapper.firstElementChild);
        }
      } else {
        location.reload();
      }

      // clear form
      title.value = '';
      content.value = '';
      filesInput.value = '';
      preview.innerHTML = '';
      preview.setAttribute('aria-hidden', 'true');
      showMessage('Post created', 'success');
    } catch (err) {
      console.error('Fetch error posting:', err);
      showMessage(
        'Failed to post: ' + (err.message || 'Network error'),
        'error'
      );
    } finally {
      postButton.dataset.busy = '0';
      postButton.disabled = false;
    }
  });
});

// inline message helpers
function showMessage(text, type = 'error') {
  const el = document.getElementById('composerMessage');
  if (!el) return;
  el.textContent = text;
  el.classList.remove('error', 'success');
  el.classList.add(type === 'success' ? 'success' : 'error');
  el.style.display = 'block';
  el.setAttribute('aria-hidden', 'false');
  // auto-hide after 5s for success messages
  if (type === 'success') setTimeout(() => hideMessage(), 4000);
}

function hideMessage() {
  const el = document.getElementById('composerMessage');
  if (!el) return;
  el.textContent = '';
  el.classList.remove('error', 'success');
  el.style.display = 'none';
  el.setAttribute('aria-hidden', 'true');
}
