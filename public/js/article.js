/**
 * News Junction - Article View Logic
 */

let currentArticle = null;
let isAudioPlaying = false;

document.addEventListener('DOMContentLoaded', async () => {
  checkAuth();
  const params = new URLSearchParams(window.location.search);
  const articleId = params.get('id') || 101;
  await loadArticle(articleId);
  initActions();
  initLeadModal();
});

async function checkAuth() {
  const container = document.getElementById('articleNavAuth');
  try {
    const res = await API.auth.checkSession();
    if (res.authenticated && res.user) {
      const initial = (res.user.fullName || 'U').charAt(0).toUpperCase();
      container.innerHTML = `
        <a href="/profile.html" class="nav-user-badge">
          <div class="nav-user-avatar">${initial}</div>
          <span>${res.user.fullName}</span>
        </a>
      `;
    }
  } catch {}
}

async function loadArticle(id) {
  const loading = document.getElementById('articleLoading');
  const wrapper = document.getElementById('articleWrapper');

  try {
    const art = await API.articles.getById(id);
    currentArticle = art;

    document.title = `${art.title} • News Junction`;
    document.getElementById('artCategory').textContent = art.category_name || 'Verified News';
    document.getElementById('artTitle').textContent = art.title;
    document.getElementById('artAuthor').textContent = `By ${art.author_name || art.publisher || 'Desk'}`;
    document.getElementById('artDate').textContent = formatIST(art.date);
    document.getElementById('artViews').textContent = `👁️ ${art.views || 1} views`;

    if (art.image) {
      document.getElementById('artImage').src = art.image;
      document.getElementById('artImage').alt = art.title;
    } else {
      document.getElementById('artMediaContainer').style.display = 'none';
    }

    document.getElementById('artContent').innerHTML = art.content || `<p>${art.description}</p>`;

    // Likes & Bookmarks State
    updateLikeState(art.isLiked, art.likes || 0);
    updateBookmarkState(art.isBookmarked);

    // Render Comments
    renderComments(art.comments || []);

    loading.style.display = 'none';
    wrapper.style.display = 'block';
  } catch (err) {
    loading.innerHTML = '<p style="color:#ef4444;">Could not load article. Please try returning to the News Feed.</p>';
  }
}

function formatIST(dateStr) {
  try {
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-IN', {
      timeZone: 'Asia/Kolkata',
      day: 'numeric',
      month: 'short',
      year: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  } catch {
    return dateStr;
  }
}

function updateLikeState(isLiked, count) {
  const icon = document.getElementById('artLikeIcon');
  const countEl = document.getElementById('artLikeCount');
  icon.textContent = isLiked ? '❤️' : '🤍';
  countEl.textContent = count;
}

function updateBookmarkState(isBookmarked) {
  const icon = document.getElementById('artBookmarkIcon');
  icon.textContent = isBookmarked ? '🔖' : '📑';
}

function initActions() {
  // Like button
  document.getElementById('artLikeBtn').addEventListener('click', async () => {
    if (!currentArticle) return;
    try {
      const res = await API.reader.toggleLike(currentArticle.id);
      currentArticle.isLiked = res.liked;
      currentArticle.likes = res.likes;
      updateLikeState(res.liked, res.likes);
    } catch (err) {
      console.error(err);
    }
  });

  // Bookmark button
  document.getElementById('artBookmarkBtn').addEventListener('click', async () => {
    if (!currentArticle) return;
    try {
      const res = await API.reader.toggleBookmark(currentArticle.id);
      currentArticle.isBookmarked = res.bookmarked;
      updateBookmarkState(res.bookmarked);
    } catch (err) {
      if (err.message.includes('sign in')) {
        window.location.assign('/sign-in.html');
      }
    }
  });

  // Text-To-Speech
  const audioBtn = document.getElementById('artAudioBtn');
  const statusText = document.getElementById('audioStatusText');
  audioBtn.addEventListener('click', () => {
    if (!('speechSynthesis' in window)) {
      alert('Speech synthesis is not supported on this browser.');
      return;
    }

    if (window.speechSynthesis.speaking) {
      window.speechSynthesis.cancel();
      isAudioPlaying = false;
      statusText.textContent = 'Listen';
      return;
    }

    const textToRead = `${currentArticle.title}. ${currentArticle.description}`;
    const utterance = new SpeechSynthesisUtterance(textToRead);
    utterance.rate = 1.0;
    utterance.pitch = 1.0;

    utterance.onend = () => {
      isAudioPlaying = false;
      statusText.textContent = 'Listen';
    };

    window.speechSynthesis.speak(utterance);
    isAudioPlaying = true;
    statusText.textContent = 'Pause';
  });

  // Social Sharing
  const pageUrl = window.location.href;
  const pageTitle = encodeURIComponent(document.title);

  document.getElementById('shareWhatsapp').onclick = () => {
    window.open(`https://api.whatsapp.com/send?text=${pageTitle}%20${encodeURIComponent(pageUrl)}`, '_blank');
  };

  document.getElementById('shareTwitter').onclick = () => {
    window.open(`https://twitter.com/intent/tweet?text=${pageTitle}&url=${encodeURIComponent(pageUrl)}`, '_blank');
  };

  document.getElementById('shareFacebook').onclick = () => {
    window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(pageUrl)}`, '_blank');
  };

  document.getElementById('shareCopy').onclick = () => {
    navigator.clipboard.writeText(pageUrl).then(() => {
      alert('Article link copied to clipboard!');
    });
  };

  // Comment Form Submit
  const commentForm = document.getElementById('commentForm');
  commentForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const commentInput = document.getElementById('commentText');
    const commentText = commentInput.value.trim();
    if (!commentText || !currentArticle) return;

    const postBtn = document.getElementById('postCommentBtn');
    postBtn.disabled = true;
    postBtn.textContent = 'Posting...';

    try {
      const res = await API.articles.addComment(currentArticle.id, commentText);
      commentInput.value = '';
      if (res.comment) {
        currentArticle.comments = currentArticle.comments || [];
        currentArticle.comments.unshift(res.comment);
        renderComments(currentArticle.comments);
      }
    } catch (err) {
      alert('Could not post comment. Please try again.');
    } finally {
      postBtn.disabled = false;
      postBtn.textContent = 'Post Comment';
    }
  });
}

function renderComments(comments) {
  const container = document.getElementById('commentsList');
  if (!comments || comments.length === 0) {
    container.innerHTML = '<p style="color:#94a3b8; font-size:0.95rem;">Be the first to share your thoughts on this story!</p>';
    return;
  }

  container.innerHTML = comments.map(c => `
    <div class="comment-item">
      <span class="comment-time">${formatIST(c.date)}</span>
      <div class="comment-author">${c.user_name || 'Reader'}</div>
      <p style="color:#334155; font-size:0.95rem;">${c.comment}</p>
    </div>
  `).join('');
}

// Lead Generation Modal
function initLeadModal() {
  const modal = document.getElementById('leadModal');
  const openBtn = document.getElementById('openLeadModalBtn');
  const closeBtn = document.getElementById('closeLeadModalBtn');
  const form = document.getElementById('leadForm');

  openBtn.addEventListener('click', () => modal.style.display = 'flex');
  closeBtn.addEventListener('click', () => modal.style.display = 'none');
  modal.addEventListener('click', (e) => {
    if (e.target === modal) modal.style.display = 'none';
  });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    if (!currentArticle) return;

    const submitBtn = document.getElementById('submitLeadBtn');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Sending...';

    const leadData = {
      leadName: document.getElementById('leadName').value.trim(),
      leadEmail: document.getElementById('leadEmail').value.trim(),
      leadMobile: document.getElementById('leadMobile').value.trim(),
      leadCompany: document.getElementById('leadCompany').value.trim()
    };

    try {
      await API.articles.submitLead(currentArticle.id, leadData);
      alert('Thank you! Your inquiry has been forwarded to the editorial desk.');
      modal.style.display = 'none';
      form.reset();
    } catch (err) {
      alert('Could not send enquiry. Please try again.');
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = 'Submit Enquiry';
    }
  });
}
