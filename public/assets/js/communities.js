// communities.js - handles AJAX posting, comments fetch, comment posting, and likes

async function apiFetch(path, opts = {}) {
  // default options: include cookies so session is sent
  const res = await fetch(
    path,
    Object.assign({ credentials: 'include' }, opts)
  );
  let data = null;
  try {
    data = await res.json();
  } catch (e) {
    // JSON parse failed; capture raw text for better error message and attach it to thrown error
    let text = null;
    try {
      text = await res.text();
    } catch (inner) {
      // ignore
    }
    const msg =
      'Invalid JSON response from server' +
      (text ? ': ' + text.slice(0, 2000) : '');
    const err = new Error(msg);
    err.raw = text;
    throw err;
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

// client-side limit for number of files to upload
const MAX_UPLOAD_FILES = 6;
// accumulate selected files so users can pick files multiple times
let selectedFiles = [];

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
      <h3 class="post-title">${escapeHtml(post.username)}</h3>
      <div class="post-meta"><span class="time">${escapeHtml(
        post.Created_at
      )}</span></div>
    </div>
    <p class="post-content">${escapeHtml(post.Content || '').replace(
      /\n/g,
      '<br>'
    )}</p>
    <div class="post-images">${imagesHtml}</div>
      <div class="post-actions">
        ${(() => {
          const liked = post.liked || false;
          const count = post.like_count || 0;
          return `<button type="button" class="like-btn ${
            liked ? 'liked' : ''
          }" data-type="post" data-id="${post.PostID}" data-liked="${
            liked ? '1' : '0'
          }" aria-pressed="${liked ? 'true' : 'false'}">👍 ${count}</button>`;
        })()}
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
          if (window.isLoggedIn) {
            const formHtml = `<form class="add-comment-ajax" data-postid="${postId}"><input type="text" name="content" placeholder="Write a comment..."><button type="submit">Reply</button></form>`;
            target.insertAdjacentHTML('beforeend', formHtml);
          } else {
            target.insertAdjacentHTML(
              'beforeend',
              '<div class="comment-login-prompt"><a href="login.php">Log in to comment</a></div>'
            );
          }
          target.dataset.loaded = '1';
        }
      } catch (err) {
        console.error(err);
      }
    }
    target.setAttribute('aria-hidden', isHidden ? 'false' : 'true');
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
      // disable button while request in-flight
      lb.disabled = true;
      const json = await apiFetch('api/like.php', {
        method: 'POST',
        body: JSON.stringify({ type, id }),
      });
      if (json.success) {
        const count = json.count || json.like_count || 0;
        lb.textContent = `👍 ${count}`;
        // update visual state
        if (json.action === 'liked') {
          lb.classList.add('liked');
          lb.setAttribute('data-liked', '1');
          lb.setAttribute('aria-pressed', 'true');
        } else if (json.action === 'unliked') {
          lb.classList.remove('liked');
          lb.setAttribute('data-liked', '0');
          lb.setAttribute('aria-pressed', 'false');
        }
      } else {
        console.warn('Like API returned success=false', json);
      }
    } catch (err) {
      console.error(err);
      // show helpful message when auth is required
      const apiErr = err?.response?.error || err?.message || 'Failed to like';
      if (/auth|authentication/i.test(apiErr)) {
        showMessage('Log in to like items', 'error');
      } else {
        showMessage(apiErr, 'error');
      }
    } finally {
      lb.disabled = false;
    }
    return;
  }
});

// handle ajax comment submit (delegated)
document.addEventListener('submit', async function (e) {
  const form = e.target.closest('.add-comment-ajax');
  if (form) {
    e.preventDefault();
    const postId = form.dataset.postid || form.closest('.post')?.dataset.postid;
    const input = form.querySelector('input[name="content"]');
    if (!postId || !input) return;
    const content = input.value.trim();
    if (!content) return;
    try {
      const json = await apiFetch('api/comments.php', {
        method: 'POST',
        body: JSON.stringify({ postId: parseInt(postId), content }),
      });
      if (json.success && json.comment) {
        // insert into comments container above the form
        const postEl = document.querySelector(`.post[data-postid="${postId}"]`);
        const commentsContainer = postEl
          ? postEl.querySelector('.comments')
          : null;
        const commentLiked = json.comment.liked || false;
        const commentCountVal = json.comment.like_count || 0;
        const commentHtml = `<div class="comment"><span class="comment-user">${escapeHtml(
          json.comment.username
        )}:</span><p>${escapeHtml(
          json.comment.Content
        )}</p><button type="button" class="like-btn ${
          commentLiked ? 'liked' : ''
        }" data-type="comment" data-id="${
          json.comment.CommentID
        }" data-liked="${commentLiked ? '1' : '0'}" aria-pressed="${
          commentLiked ? 'true' : 'false'
        }">👍 ${commentCountVal}</button></div>`;
        if (commentsContainer) {
          // ensure comments container is visible and mark loaded
          commentsContainer.insertAdjacentHTML('afterbegin', commentHtml);
          commentsContainer.dataset.loaded = '1';
          commentsContainer.setAttribute('aria-hidden', 'false');
        }
        // increment comment count in the post-actions button
        const ctBtn = postEl ? postEl.querySelector('.comment-toggle') : null;
        if (ctBtn) {
          const parts = ctBtn.textContent.split(' ');
          const last = parseInt(parts[parts.length - 1] || '0') + 1;
          ctBtn.textContent = `💬 ${last}`;
        }
        input.value = '';
      }
    } catch (err) {
      console.error('comment POST error', err);
      // try to show JSON error message from the API
      const apiErr =
        err?.response?.error || err?.message || 'Failed to post comment';
      showMessage(apiErr, 'error');
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
        )}</p><button type="button" class="like-btn ${
          c.liked ? 'liked' : ''
        }" data-type="comment" data-id="${c.CommentID}" data-liked="${
          c.liked ? '1' : '0'
        }" aria-pressed="${c.liked ? 'true' : 'false'}">👍 ${
          c.like_count || 0
        }</button></div>`
    )
    .join('');
}

document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('postForm');
  const content = document.getElementById('postContent');
  const filesInput = document.getElementById('postFiles');
  const preview = document.getElementById('filePreview');
  const postButton = document.getElementById('postButton');

  // remove preview/file handlers if you prefer they are hidden
  filesInput?.addEventListener('change', () => {
    const files = Array.from(filesInput.files || []);
    if (!files.length) {
      // no new files selected
      return;
    }

    const remaining = Math.max(0, MAX_UPLOAD_FILES - selectedFiles.length);
    if (remaining <= 0) {
      showMessage(
        `You can upload up to ${MAX_UPLOAD_FILES} images. Remove some to add more.`,
        'error'
      );
      filesInput.value = '';
      return;
    }

    const toAdd = files
      .filter((f) => f.type && f.type.startsWith('image/'))
      .slice(0, remaining);
    for (const f of toAdd) selectedFiles.push(f);

    if (
      toAdd.length <
      files.filter((f) => f.type && f.type.startsWith('image/')).length
    ) {
      showMessage(
        `You can upload up to ${MAX_UPLOAD_FILES} images. Extra files were ignored.`,
        'error'
      );
    }

    // refresh preview from selectedFiles (with remove buttons)
    function renderPreview() {
      preview.innerHTML = '';
      if (!selectedFiles.length) {
        preview.setAttribute('aria-hidden', 'true');
        return;
      }
      preview.removeAttribute('aria-hidden');
      selectedFiles.forEach((f, idx) => {
        const thumb = document.createElement('span');
        thumb.className = 'thumb';
        const img = document.createElement('img');
        img.src = URL.createObjectURL(f);
        img.onload = () => URL.revokeObjectURL(img.src);
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'remove-btn';
        btn.innerText = '✕';
        btn.addEventListener('click', () => {
          // remove this file from selectedFiles
          selectedFiles.splice(idx, 1);
          renderPreview();
        });
        thumb.appendChild(img);
        thumb.appendChild(btn);
        preview.appendChild(thumb);
      });
    }
    renderPreview();

    // clear file input so the user can select more files in a subsequent selection
    filesInput.value = '';
  });

  // Prevent double submissions and ensure proper async flow
  form?.addEventListener('submit', async (ev) => {
    ev.preventDefault();
    if (!postButton) return;

    // prevent double-clicks/submissions
    if (postButton.dataset.busy === '1') return;
    postButton.dataset.busy = '1';
    postButton.disabled = true;

    const contentVal = content.value.trim();

    // require content
    if (!contentVal) {
      showMessage('Please add some content before posting.', 'error');
      postButton.dataset.busy = '0';
      postButton.disabled = false;
      return;
    }

    const fd = new FormData();
    fd.append('content', contentVal);

    // use the accumulated selectedFiles array when submitting
    if (selectedFiles.length) {
      for (const f of selectedFiles) fd.append('images[]', f);
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
          const newPostEl = wrapper.firstElementChild;
          postsContainer.prepend(newPostEl);
          // initialize carousel (if post has multiple images)
          if (window.initPostCarousels) window.initPostCarousels(newPostEl);
        }
      } else {
        location.reload();
      }

      // clear form and selected files
      content.value = '';
      filesInput.value = '';
      selectedFiles = [];
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

// --- Simple in-post carousel for posts with multiple images ---
function imagesLoaded(imgElements, timeout = 2000) {
  return new Promise((resolve) => {
    if (!imgElements || imgElements.length === 0) return resolve();
    let remaining = imgElements.length;
    let done = false;
    const onLoad = () => {
      if (done) return;
      remaining -= 1;
      if (remaining <= 0) {
        done = true;
        resolve();
      }
    };
    imgElements.forEach((img) => {
      if (img.complete && img.naturalWidth !== 0) {
        onLoad();
      } else {
        img.addEventListener('load', onLoad);
        img.addEventListener('error', onLoad);
      }
    });
    setTimeout(() => {
      if (!done) {
        done = true;
        resolve();
      }
    }, timeout);
  });
}

function setupCarouselForContainer(container) {
  if (!container) return;
  container.classList.add('carousel');
  // wrap images in a slides container
  let slides = container.querySelector('.slides');
  if (!slides) {
    slides = document.createElement('div');
    slides.className = 'slides';
    // move existing images into slides
    while (container.firstChild) {
      const node = container.firstChild;
      if (node.tagName && node.tagName.toLowerCase() === 'img') {
        slides.appendChild(node);
      } else {
        container.removeChild(node);
      }
    }
    container.appendChild(slides);
  }

  // add controls if missing
  if (!container.querySelector('.carousel-prev')) {
    const prev = document.createElement('button');
    prev.className = 'carousel-prev';
    prev.type = 'button';
    prev.innerText = '◀';
    const next = document.createElement('button');
    next.className = 'carousel-next';
    next.type = 'button';
    next.innerText = '▶';
    container.appendChild(prev);
    container.appendChild(next);

    // set initial state
    slides.dataset.index = '0';

    prev.addEventListener('click', () => {
      const idx = parseInt(slides.dataset.index || '0', 10);
      const total = slides.querySelectorAll('img').length;
      const nextIdx = (idx - 1 + total) % total;
      setSlideIndex(slides, nextIdx);
    });

    next.addEventListener('click', () => {
      const idx = parseInt(slides.dataset.index || '0', 10);
      const total = slides.querySelectorAll('img').length;
      const nextIdx = (idx + 1) % total;
      setSlideIndex(slides, nextIdx);
    });
  }

  // ensure only the active image is visible
  setSlideIndex(container.querySelector('.slides'), 0);
}

function initPostCarousels(root = document) {
  let posts;
  if (root instanceof Element) {
    posts = root.matches('.post') ? [root] : root.querySelectorAll('.post');
  } else {
    posts = root.querySelectorAll('.post');
  }

  Array.from(posts).forEach((post) => {
    const imgs = post.querySelectorAll('.post-images img');
    if (!imgs || imgs.length <= 1) return;
    const container = post.querySelector('.post-images');
    // wait for images to load then setup carousel (fallback to timeout)
    imagesLoaded(Array.from(imgs), 1500).then(() =>
      setupCarouselForContainer(container)
    );
  });
}

function setSlideIndex(slidesEl, idx) {
  if (!slidesEl) return;
  const imgs = slidesEl.querySelectorAll('img');
  imgs.forEach((img, i) => {
    if (i === idx) {
      img.style.display = 'block';
    } else {
      img.style.display = 'none';
    }
  });
  slidesEl.dataset.index = String(idx);
}

// initialize carousels on initial load
document.addEventListener('DOMContentLoaded', () => initPostCarousels());

// Also provide a helper for newly-inserted posts (used after posting)
window.initPostCarousels = initPostCarousels;

// --- Three-dot menu functionality ---
document.addEventListener('DOMContentLoaded', () => {
  // Toggle menu dropdown
  document.addEventListener('click', (e) => {
    const menuBtn = e.target.closest('.post-menu-btn');

    if (menuBtn) {
      e.stopPropagation();
      const menu = menuBtn.closest('.post-menu');
      const dropdown = menu.querySelector('.post-menu-dropdown');
      const isHidden = dropdown.getAttribute('aria-hidden') === 'true';

      // Close all other dropdowns
      document.querySelectorAll('.post-menu-dropdown').forEach((d) => {
        d.setAttribute('aria-hidden', 'true');
      });

      // Toggle current dropdown
      dropdown.setAttribute('aria-hidden', isHidden ? 'false' : 'true');
    } else {
      // Close all dropdowns when clicking outside
      document.querySelectorAll('.post-menu-dropdown').forEach((d) => {
        d.setAttribute('aria-hidden', 'true');
      });
    }
  });

  // Edit post
  document.addEventListener('click', async (e) => {
    const editBtn = e.target.closest('.edit-post');
    if (!editBtn) return;

    const postId = editBtn.dataset.postid;
    const post = document.querySelector(`.post[data-postid="${postId}"]`);
    if (!post) return;

    const contentEl = post.querySelector('.post-content');
    const currentContent = contentEl.textContent.trim();

    // Show prompt to edit
    const newContent = prompt('Edit your post:', currentContent);
    if (newContent === null || newContent.trim() === '') return;

    try {
      const data = await apiFetch('api/posts.php', {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ post_id: postId, content: newContent.trim() }),
      });

      if (data.ok) {
        contentEl.textContent = newContent.trim();
        alert('Post updated successfully');
      }
    } catch (err) {
      alert('Failed to update post: ' + err.message);
    }
  });

  // Delete post
  document.addEventListener('click', async (e) => {
    const deleteBtn = e.target.closest('.delete-post');
    if (!deleteBtn) return;

    const postId = deleteBtn.dataset.postid;

    if (!confirm('Are you sure you want to delete this post?')) return;

    try {
      const data = await apiFetch('api/posts.php', {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ post_id: postId }),
      });

      if (data.ok) {
        const post = document.querySelector(`.post[data-postid="${postId}"]`);
        if (post) {
          post.remove();
          alert('Post deleted successfully');
        }
      }
    } catch (err) {
      alert('Failed to delete post: ' + err.message);
    }
  });
});
