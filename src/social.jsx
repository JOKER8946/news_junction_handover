import React, { useState } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import { Heart, Bookmark, MessageSquare, Trash2, ArrowUpRight, MapPin } from 'lucide-react';
import { useApp, useLoad, State, Cover, dateLabel } from './main';
import { Adverts, AllNews } from './original-ui';
import { api } from './api';
function Discussion({ id }) {
  const { data, reload } = useLoad(`/community/${id}/comments`),
    { user, toast } = useApp();
  return (
    <div className="social-discussion">
      {data?.map((c) => (
        <div className="comment" key={c.id}>
          <b>{c.full_name}</b>
          <p>{c.body}</p>
        </div>
      ))}
      {user && (
        <form
          onSubmit={async (e) => {
            e.preventDefault();
            const form = e.currentTarget;
            try {
              await api(`/community/${id}/comments`, {
                method: 'POST',
                body: { body: new FormData(form).get('body') },
              });
              form.reset();
              reload();
            } catch (e) {
              toast(e.message);
            }
          }}
        >
          <label>
            Join the conversation
            <input name="body" required maxLength={3000} placeholder="A thoughtful reply…" />
          </label>
          <button className="button small">Post reply</button>
        </form>
      )}
    </div>
  );
}
export default function Social() {
  const [params, setParams] = useSearchParams();
  const mode = params.get('mode') || 'all';
  const setMode = (value) => setParams(value === 'all' ? {} : { mode: value });
  const [composing, setComposing] = useState(false);
  const [pin, setPin] = useState(''),
    [page, setPage] = useState(1),
    [open, setOpen] = useState(null),
    [busy, setBusy] = useState(false);
  const { user, toast, ready } = useApp(),
    navigate = useNavigate();
  const { data, loading, error, reload } = useLoad(
    '/community?' + new URLSearchParams({ mode, pincode: pin, page }),
  );
  const action = async (url, body) => {
    if (!user) return navigate('/sign-in');
    try {
      await api(url, { method: 'PUT', body });
      reload();
    } catch (e) {
      toast(e.message);
    }
  };
  return (
    <>
      <div className="social-layout">
        <div className="social-feed">
          <Adverts />
          <Adverts strip />
          <div className="category-bar">
            {[
              ['all', 'Social'],
              ['following', 'Following'],
              ['saved', 'Bookmarks'],
            ].map(([value, label]) => (
              <button
                className={mode === value ? 'active' : ''}
                key={value}
                onClick={() => {
                  setMode(value);
                  setPage(1);
                }}
              >
                {label}
              </button>
            ))}
          </div>
          {composing &&
            (user ? (
              <form
                className="panel social-composer"
                onSubmit={async (e) => {
                  e.preventDefault();
                  setBusy(true);
                  const form = e.currentTarget;
                  try {
                    await api('/community', {
                      method: 'POST',
                      body: Object.fromEntries(new FormData(form)),
                    });
                    form.reset();
                    setPage(1);
                    reload();
                    setComposing(false);
                    toast('Post published.');
                  } catch (e) {
                    toast(e.message);
                  } finally {
                    setBusy(false);
                  }
                }}
              >
                <label>
                  What’s happening in your neighbourhood?
                  <textarea
                    name="body"
                    required
                    maxLength={5000}
                    placeholder="A local update, a moment, a new perspective…"
                  />
                </label>
                <div className="form-grid">
                  <label>
                    PIN code (optional)
                    <input
                      name="pincode"
                      inputMode="numeric"
                      pattern="[0-9]{6}"
                      maxLength={6}
                      placeholder="560001"
                    />
                  </label>
                  <label>
                    Image URL (optional)
                    <input type="url" name="image" placeholder="https://…" />
                  </label>
                </div>
                <button className="button" disabled={busy}>
                  Post <ArrowUpRight size={16} />
                </button>
              </form>
            ) : (
              <div className="panel social-composer">
                <p>Every community has a story. Be part of yours.</p>
                <Link to="/sign-in" className="button">
                  Sign in to join in
                </Link>
              </div>
            ))}
          <State loading={loading} error={error} retry={reload} />
          {data?.posts.map((p) => (
            <article key={p.id} className="panel social-post">
              <div className="row">
                <div className="row">
                  <span className="avatar">{p.full_name[0]}</span>
                  <div>
                    <b>{p.full_name}</b>
                    <small className="block">
                      {dateLabel(p.created_at)} {p.pincode && ' · ' + p.pincode}
                    </small>
                  </div>
                </div>
                {String(user?.id) !== String(p.user_id) && (
                  <button
                    className="button small secondary"
                    onClick={() =>
                      action(`/community/people/${p.user_id}/follow`, { active: !p.followed })
                    }
                  >
                    {p.followed ? 'Following' : '+ Follow'}
                  </button>
                )}
              </div>
              <p className="preserve-lines">{p.body}</p>
              {p.image && (
                <Cover className="social-cover" src={p.image} alt="Community contribution" />
              )}
              <div className="social-actions">
                <button
                  className={'icon-button ' + (p.liked ? 'selected' : '')}
                  aria-label="Appreciate post"
                  onClick={() => action(`/community/${p.id}/like`, { active: !p.liked })}
                >
                  <Heart size={18} />
                </button>
                <small>{p.likes}</small>
                <button
                  className="icon-button"
                  aria-label="Discuss post"
                  onClick={() => setOpen(open === p.id ? null : p.id)}
                >
                  <MessageSquare size={18} />
                </button>
                <button
                  className={'icon-button ' + (p.saved ? 'selected' : '')}
                  aria-label="Bookmark post"
                  onClick={() => action(`/community/${p.id}/save`, { active: !p.saved })}
                >
                  <Bookmark size={18} />
                </button>
                {user && (String(user.id) === String(p.user_id) || user.role === 'admin') && (
                  <button
                    className="icon-button"
                    aria-label="Delete post"
                    onClick={async () => {
                      if (!confirm('Delete this post?')) return;
                      try {
                        await api('/community/' + p.id, { method: 'DELETE' });
                        reload();
                      } catch (e) {
                        toast(e.message);
                      }
                    }}
                  >
                    <Trash2 size={17} />
                  </button>
                )}
              </div>
              {open === p.id && <Discussion id={p.id} />}
            </article>
          ))}
          {!loading && data && !data.posts.length && (
            <div className="empty">
              <MessageSquare size={30} />
              <h2>No posts found.</h2>
              <p>No updates here yet. Share a moment from your community.</p>
            </div>
          )}
          <div className="pagination">
            {page > 1 && (
              <button className="button secondary" onClick={() => setPage(page - 1)}>
                Previous
              </button>
            )}
            {data?.hasMore && (
              <button className="button secondary" onClick={() => setPage(page + 1)}>
                More posts
              </button>
            )}
          </div>
        </div>
        <aside className="social-news">
          <AllNews />
          {params.has('pincode') && (
            <div className="panel">
              <MapPin size={24} />
              <h2>Pincode</h2>
              <p>Filter posts by PIN code.</p>
              <label>
                Filter by PIN code
                <input
                  aria-label="Filter by PIN code"
                  inputMode="numeric"
                  maxLength={6}
                  value={pin}
                  onChange={(e) => {
                    setPin(e.target.value.replace(/\D/g, ''));
                    setPage(1);
                  }}
                  placeholder="Enter 6-digit PIN"
                />
              </label>
              {pin && (
                <button className="text-link" onClick={() => setPin('')}>
                  Show all neighbourhoods
                </button>
              )}
            </div>
          )}
        </aside>
      </div>
      <button
        className="compose-fab"
        disabled={!ready}
        aria-label="Create post"
        onClick={() => {
          if (!user) return navigate('/sign-in');
          setComposing(true);
          setTimeout(
            () =>
              document
                .querySelector('.social-composer')
                ?.scrollIntoView({ behavior: 'smooth', block: 'center' }),
            0,
          );
        }}
      >
        +
      </button>
    </>
  );
}
