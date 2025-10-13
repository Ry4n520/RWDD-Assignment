// communities.js - handles AJAX posting, comments fetch, comment posting, and likes

async function apiFetch(path, opts = {}) {
  const res = await fetch(
    path,
    Object.assign(
      {
        credentials: 'same-origin',
        headers: { 'Content-Type': 'application/json' },
      },
      opts
    )
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

// Top composer submit
const composerForm = document.querySelector('.composer-form');
if (composerForm) {
  async function submitComposer() {
    const ta = composerForm.querySelector('.composer-input');
    const content = ta.value.trim();
    if (!content) return;
    try {
      const json = await apiFetch('api/posts.php', {
        method: 'POST',
        body: JSON.stringify({ content }),
      });
      if (json.success && json.post) {
        // Prepend the new post to .posts
        const postsEl = document.querySelector('.posts');
        const html = buildPostHtml(json.post, 0);
        postsEl.insertAdjacentHTML('afterbegin', html);
        ta.value = '';
      }
    } catch (err) {
      console.error(err);
      alert('Failed to post: ' + (err.message || err));
    }
  }

  // bind button click
  const postBtn = composerForm.querySelector('.composer-actions button');
  if (postBtn) postBtn.addEventListener('click', submitComposer);
  // also allow pressing Enter in the textarea to submit (Ctrl+Enter to allow newline)
  const ta = composerForm.querySelector('.composer-input');
  if (ta)
    ta.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) submitComposer();
    });
}

// Build post HTML (server returns post with PostID, Content, Created_at, username)
function buildPostHtml(post, commentCount) {
  return `
  <article class="post" data-postid="${post.PostID}">
    <div class="post-header">
      <h2>${escapeHtml(post.username)}</h2>
      <span class="time">${escapeHtml(post.Created_at)}</span>
    </div>
    <p class="post-content">${escapeHtml(post.Content).replace(
      /\n/g,
      '<br>'
    )}</p>
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
