import React, { useEffect, useRef, useState } from 'react';
import { Link, useNavigate, useSearchParams } from 'react-router-dom';
import { Heart, Share2, Volume2, VolumeX, ChevronDown, ChevronUp, Play, Pause } from 'lucide-react';
import { api, uploadReel } from './api';
import { useApp, useLoad, State } from './main';
import './reels.css';

function Reel({ reel, muted, setMuted, onLike }) {
  const video = useRef(null),
    card = useRef(null);
  const [playing, setPlaying] = useState(false),
    [liking, setLiking] = useState(false),
    [failed, setFailed] = useState(false);
  const { user, toast } = useApp();
  const navigate = useNavigate();
  useEffect(() => {
    const el = video.current;
    const observer = new IntersectionObserver(
      (entries) => {
        const visible = entries[0].isIntersecting && entries[0].intersectionRatio >= 0.65;
        if (visible && !document.hidden && !matchMedia('(prefers-reduced-motion: reduce)').matches)
          el.play().catch(() => {});
        else el.pause();
      },
      { threshold: [0, 0.65] },
    );
    observer.observe(card.current);
    const hide = () => {
      if (document.hidden) el.pause();
    };
    document.addEventListener('visibilitychange', hide);
    return () => {
      observer.disconnect();
      el.pause();
      document.removeEventListener('visibilitychange', hide);
    };
  }, []);
  return (
    <article className="reel-slide" ref={card} id={'reel-' + reel.id} aria-label={reel.title}>
      <video
        ref={video}
        src={reel.video_url}
        loop
        playsInline
        muted={muted}
        preload="metadata"
        onPlay={() => setPlaying(true)}
        onPause={() => setPlaying(false)}
        onError={() => setFailed(true)}
      />
      <button
        className="reel-play"
        aria-label={playing ? 'Pause reel' : 'Play reel'}
        onClick={() =>
          playing
            ? video.current.pause()
            : video.current.play().catch(() => toast('This video could not be played.'))
        }
      >
        {!playing && <Play size={48} />}
      </button>
      <div className="reel-top">
        <span>Reels</span>
        <button aria-label={muted ? 'Unmute reel' : 'Mute reel'} onClick={() => setMuted(!muted)}>
          {muted ? <VolumeX /> : <Volume2 />}
        </button>
      </div>
      {failed && (
        <p className="reel-error" role="alert">
          This video could not be loaded. Try the next reel.
        </p>
      )}
      <div className="reel-caption">
        <b>{reel.author_name || 'News Junction'}</b>
        <h2>{reel.title}</h2>
        {reel.caption && (
          <details>
            <summary>Caption</summary>
            <p>{reel.caption}</p>
          </details>
        )}
      </div>
      <div className="reel-actions">
        <button
          aria-label={reel.liked ? 'Unlike reel' : 'Like reel'}
          aria-pressed={reel.liked}
          disabled={liking}
          onClick={async () => {
            if (!user) return navigate('/sign-in');
            setLiking(true);
            try {
              await api('/reels/' + reel.id + '/like', {
                method: 'PUT',
                body: { active: !reel.liked },
              });
              onLike(reel.id, !reel.liked);
            } catch (e) {
              toast(e.message);
            } finally {
              setLiking(false);
            }
          }}
        >
          <Heart fill={reel.liked ? '#ff563d' : 'none'} />
          <span>{reel.likes}</span>
        </button>
        <button
          aria-label="Share reel"
          onClick={async () => {
            const url = location.origin + '/reels?id=' + reel.id;
            try {
              if (navigator.share) await navigator.share({ title: reel.title, url });
              else {
                await navigator.clipboard.writeText(url);
                toast('Reel link copied.');
              }
            } catch (e) {
              if (e.name !== 'AbortError') toast('Copy the page link to share this reel.');
            }
          }}
        >
          <Share2 />
          <span>Share</span>
        </button>
      </div>
    </article>
  );
}
export function Reels() {
  const [params] = useSearchParams();
  const [page, setPage] = useState(1),
    [items, setItems] = useState([]),
    [muted, setMuted] = useState(true);
  const { data, loading, error, reload } = useLoad(
    '/reels?page=' + page + (params.get('id') ? '&id=' + encodeURIComponent(params.get('id')) : ''),
  );
  const { user } = useApp();
  const feed = useRef(null);
  useEffect(() => {
    if (data)
      setItems((previous) => {
        const map = new Map((page === 1 ? [] : previous).map((x) => [x.id, x]));
        data.reels.forEach((x) => map.set(x.id, x));
        return [...map.values()];
      });
  }, [data, page]);
  useEffect(() => {
    if (location.hash)
      document.getElementById(location.hash.slice(1))?.scrollIntoView({ block: 'nearest' });
  }, [items.length]);
  const move = (direction) => {
    const el = feed.current;
    if (el) el.scrollBy({ top: direction * el.clientHeight, behavior: 'smooth' });
  };
  return (
    <div className="reels-page">
      <div className="page-title">
        <h1>Reels</h1>
        {params.has('id') && <Link to="/reels">All reels</Link>}
        {user?.role === 'admin' && (
          <Link className="button" to="/admin?tab=reels">
            Upload reel
          </Link>
        )}
      </div>
      <State loading={loading} error={error} retry={reload} />
      {!loading && !error && !items.length && (
        <div className="empty">
          <h2>No reels yet</h2>
          <p>Published reels will appear here.</p>
        </div>
      )}
      {!!items.length && (
        <div className="reels-stage">
          <div
            className="reels-feed"
            ref={feed}
            tabIndex={0}
            aria-label="Reels feed"
            onKeyDown={(e) => {
              if (e.target !== e.currentTarget) return;
              if (['ArrowDown', 'ArrowUp'].includes(e.key)) {
                e.preventDefault();
                move(e.key === 'ArrowDown' ? 1 : -1);
              }
            }}
          >
            {items.map((r) => (
              <Reel
                key={r.id}
                reel={r}
                muted={muted}
                setMuted={setMuted}
                onLike={(id, active) =>
                  setItems((all) =>
                    all.map((x) =>
                      x.id === id ? { ...x, liked: active, likes: x.likes + (active ? 1 : -1) } : x,
                    ),
                  )
                }
              />
            ))}
            {data?.hasMore && (
              <div className="reels-more">
                <button className="button" disabled={loading} onClick={() => setPage((p) => p + 1)}>
                  Load more reels
                </button>
              </div>
            )}
          </div>
          <div className="reel-navigation">
            <button aria-label="Previous reel" onClick={() => move(-1)}>
              <ChevronUp />
            </button>
            <button aria-label="Next reel" onClick={() => move(1)}>
              <ChevronDown />
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
export function AdminReels() {
  const [page, setPage] = useState(1),
    [file, setFile] = useState(null),
    [preview, setPreview] = useState(''),
    [busy, setBusy] = useState(false),
    [progress, setProgress] = useState(0),
    [error, setError] = useState(''),
    [editing, setEditing] = useState(null);
  const [form, setForm] = useState({ title: '', caption: '', published: true });
  const input = useRef(null);
  const { data, loading, error: loadError, reload } = useLoad('/reels?manage=true&page=' + page);
  const { toast } = useApp();
  useEffect(() => {
    if (!file) {
      setPreview('');
      return;
    }
    const url = URL.createObjectURL(file);
    setPreview(url);
    return () => URL.revokeObjectURL(url);
  }, [file]);
  const reset = () => {
    setEditing(null);
    setFile(null);
    setForm({ title: '', caption: '', published: true });
    if (input.current) input.current.value = '';
    setError('');
  };
  return (
    <section className="admin-reels">
      <div className="section-heading">
        <h2>Manage reels</h2>
        <Link to="/reels">View reels</Link>
      </div>
      <form
        className="panel reel-upload"
        onSubmit={async (e) => {
          e.preventDefault();
          setError('');
          if (!editing && !file) {
            setError('Choose a video.');
            return;
          }
          setBusy(true);
          setProgress(0);
          try {
            if (editing) await api('/reels/' + editing, { method: 'PUT', body: form });
            else {
              const body = new FormData();
              body.append('video', file);
              Object.entries(form).forEach(([k, v]) => body.append(k, String(v)));
              await uploadReel(body, setProgress);
            }
            reset();
            reload();
            toast('Reel saved.');
          } catch (e) {
            setError(e.message);
          } finally {
            setBusy(false);
          }
        }}
      >
        <h3>{editing ? 'Edit reel' : 'Upload reel'}</h3>
        <fieldset disabled={busy}>
          {!editing && (
            <label>
              Video
              <input
                ref={input}
                type="file"
                aria-label="Video"
                accept="video/mp4,video/webm"
                required
                onChange={(e) => {
                  const f = e.target.files?.[0];
                  setError('');
                  if (f && f.size > 100 * 1024 * 1024) {
                    setError('Videos must be 100 MB or smaller.');
                    e.target.value = '';
                    setFile(null);
                    return;
                  }
                  setFile(f || null);
                }}
              />
              <small>MP4 or WebM, up to 100 MB. Vertical 9:16 videos work best.</small>
            </label>
          )}
          {preview && <video className="reel-upload-preview" src={preview} controls playsInline />}
          <label>
            Reel title
            <input
              required
              maxLength={150}
              value={form.title}
              onChange={(e) => setForm({ ...form, title: e.target.value })}
            />
          </label>
          <label>
            Reel caption
            <textarea
              maxLength={2200}
              value={form.caption}
              onChange={(e) => setForm({ ...form, caption: e.target.value })}
            />
          </label>
          <label>
            Visibility
            <select
              value={String(form.published)}
              onChange={(e) => setForm({ ...form, published: e.target.value === 'true' })}
            >
              <option value="true">Published</option>
              <option value="false">Draft (admins only)</option>
            </select>
          </label>
          <div className="row">
            <button className="button">
              {busy ? 'Saving...' : editing ? 'Save reel' : 'Upload reel'}
            </button>
            {editing && (
              <button type="button" className="button secondary" onClick={reset}>
                Cancel editing
              </button>
            )}
          </div>
        </fieldset>
        {busy && !editing && (
          <div role="status">
            <progress max="100" value={progress} />
            <span>{progress < 100 ? `Uploading ${progress}%` : 'Processing video...'}</span>
          </div>
        )}
        {error && (
          <p role="alert" className="form-error">
            {error}
          </p>
        )}
      </form>
      <State loading={loading} error={loadError} retry={reload} />
      {data?.reels.map((r) => (
        <article className="panel admin-reel-row" key={r.id}>
          <video src={r.video_url} controls playsInline preload="none" />
          <div>
            <h3>{r.title}</h3>
            <p>
              {r.published ? 'Published' : 'Draft'} · {r.likes} likes
            </p>
            <div className="row">
              <button
                className="button secondary"
                disabled={busy}
                onClick={() => {
                  setEditing(r.id);
                  setForm({ title: r.title, caption: r.caption, published: r.published });
                  setFile(null);
                  setError('');
                  document.querySelector('.reel-upload')?.scrollIntoView({ behavior: 'smooth' });
                }}
              >
                Edit reel
              </button>
              <button
                className="button danger"
                disabled={busy}
                onClick={async () => {
                  if (!confirm('Delete this reel and its video permanently?')) return;
                  try {
                    await api('/reels/' + r.id, { method: 'DELETE' });
                    if (editing === r.id) reset();
                    reload();
                    toast('Reel deleted.');
                  } catch (e) {
                    toast(e.message);
                  }
                }}
              >
                Delete reel
              </button>
            </div>
          </div>
        </article>
      ))}
      {!loading && data && !data.reels.length && <p>No reels uploaded yet.</p>}
      <div className="pagination">
        {page > 1 && (
          <button className="button secondary" onClick={() => setPage((p) => p - 1)}>
            Previous
          </button>
        )}
        {data?.hasMore && (
          <button className="button secondary" onClick={() => setPage((p) => p + 1)}>
            Next
          </button>
        )}
      </div>
    </section>
  );
}
