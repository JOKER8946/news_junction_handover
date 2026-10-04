import React, { useEffect, useState } from 'react';
import { Link, useNavigate, useParams, useSearchParams } from 'react-router-dom';
import {
  ArrowRight,
  ArrowUpRight,
  Bookmark,
  MapPin,
  Clock,
  Heart,
  Share2,
  ArrowLeft,
  Check,
  Mail,
  Plus,
  Search,
  ShieldCheck,
  Eye,
  EyeOff,
  PenLine,
  Trash2,
  Radio,
  Play,
  TrendingUp,
} from 'lucide-react';
import { api } from './api';
import ImageUpload from './upload';
import { useApp, useLoad, State, Cover, Gate, districts, dateLabel } from './main';

function PageTitle({ eyebrow, title, description, action }) {
  return (
    <div className="page-title">
      <div>
        <div className="eyebrow">{eyebrow || ''}</div>
        <h1>{title}</h1>
        {description && <p>{description}</p>}
      </div>
      {action}
    </div>
  );
}
function Empty({ title = 'Nothing here just yet.', children }) {
  return (
    <div className="empty">
      <BookIcon />
      <h2>{title}</h2>
      <p>{children || 'Try another filter or come back for the next update.'}</p>
    </div>
  );
}
function BookIcon() {
  return <Radio size={30} strokeWidth={1.3} />;
}
function FormError({ error }) {
  return error ? (
    <p className="form-error" role="alert">
      {error}
    </p>
  ) : null;
}
function CategorySelect({ value, onChange }) {
  const { data } = useLoad('/categories');
  return (
    <select aria-label="Category" required value={value} onChange={onChange}>
      <option value="">Choose a category</option>
      {data?.map((c) => (
        <option key={c.id} value={c.id}>
          {c.name}
        </option>
      ))}
    </select>
  );
}
function DistrictSelect({ value, onChange, all = true }) {
  return (
    <select aria-label="District" value={value} onChange={onChange}>
      <option value="">{all ? 'All districts' : 'Choose your district'}</option>
      {districts.map((d) => (
        <option key={d}>{d}</option>
      ))}
    </select>
  );
}
function SaveButton({ article, onChange }) {
  const { user, toast } = useApp();
  const navigate = useNavigate();
  const [saved, setSaved] = useState(article.bookmarked),
    [busy, setBusy] = useState(false);
  useEffect(() => setSaved(article.bookmarked), [article.bookmarked]);
  return (
    <button
      className={'icon-button bookmark ' + (saved ? 'selected' : '')}
      aria-label={saved ? 'Unsave story' : 'Save story'}
      aria-pressed={!!saved}
      disabled={busy}
      onClick={async () => {
        if (!user) return navigate('/sign-in');
        setBusy(true);
        try {
          await api(`/articles/${article.id}/bookmark`, {
            method: 'PUT',
            body: { active: !saved },
          });
          setSaved(!saved);
          toast(saved ? 'Story removed from your collection.' : 'Story saved.');
          onChange?.();
        } catch (e) {
          toast(e.message);
        } finally {
          setBusy(false);
        }
      }}
    >
      <Bookmark size={18} fill={saved ? 'currentColor' : 'none'} />
    </button>
  );
}
function StoryCard({ article, compact = false, onChange }) {
  return (
    <article
      className={'story-card ' + (compact ? 'compact ' : '') + (!article.image ? 'text-story' : '')}
    >
      {article.image ? (
        <Link to={'/article/' + article.id} className="story-image">
          <Cover src={article.image} alt={article.title} />
          <span className="image-tag">{article.category_name || 'Community'}</span>
        </Link>
      ) : (
        <span className="eyebrow">{article.category_name || 'Community'}</span>
      )}
      <div className="story-card-body">
        <div className="story-kicker">
          {article.district || article.publisher}
          <span> • </span>
          {dateLabel(article.published_at)}
        </div>
        <Link to={'/article/' + article.id}>
          <h3>{article.title}</h3>
        </Link>
        {!compact && <p>{article.description}</p>}
        <div className="story-meta">
          <span>{article.author_name}</span>
          <SaveButton article={article} onChange={onChange} />
        </div>
        {article.status !== 'published' && <span className="badge">{article.status}</span>}
      </div>
    </article>
  );
}
export function Feed({ saved = false, mine = false }) {
  const [params, setParams] = useSearchParams();
  const category = params.get('category') || '',
    district = params.get('district') || '',
    q = params.get('q') || '',
    page = Number(params.get('page')) || 1;
  const { data, loading, error, reload } = useLoad(
    '/articles?' + new URLSearchParams({ category, district, q, page, saved, mine }),
  );
  const categories = useLoad('/categories');
  let items = data?.articles || [];
  const update = (key, value) =>
    setParams((p) => {
      const n = new URLSearchParams(p);
      value ? n.set(key, value) : n.delete(key);
      n.delete('page');
      return n;
    });
  return (
    <>
      <PageTitle
        title={
          saved
            ? 'Saved stories'
            : mine
              ? 'My Collection'
              : q
                ? `Search results for "${q}"`
                : 'Reader'
        }
        action={
          <DistrictSelect value={district} onChange={(e) => update('district', e.target.value)} />
        }
      />
      <form
        className="reader-search"
        onSubmit={(e) => {
          e.preventDefault();
          update('q', new FormData(e.currentTarget).get('q'));
        }}
      >
        <input name="q" aria-label="Search news" placeholder="Search..." defaultValue={q} key={q} />
        <button aria-label="Search">
          <Search size={20} />
        </button>
      </form>
      {!saved && !mine && (
        <div className="category-bar">
          <button className={!category ? 'active' : ''} onClick={() => update('category', '')}>
            All News
          </button>
          {categories.data?.map((c) => (
            <button
              key={c.id}
              className={category === c.name ? 'active' : ''}
              onClick={() => update('category', c.name)}
            >
              {c.name}
            </button>
          ))}
        </div>
      )}
      <State loading={loading} error={error} retry={reload} />
      {!loading &&
        !error &&
        (items.length ? (
          <div className="reader-stories">
            {items.map((a) => (
              <StoryCard key={a.id} article={a} onChange={saved ? reload : undefined} />
            ))}
          </div>
        ) : (
          <Empty title="No stories found." />
        ))}
      <div className="pagination">
        {page > 1 && (
          <button
            className="button secondary"
            onClick={() => {
              params.set('page', page - 1);
              setParams(params);
            }}
          >
            Previous page
          </button>
        )}
        {data?.hasMore && (
          <button
            className="button"
            onClick={() => {
              params.set('page', page + 1);
              setParams(params);
              window.scrollTo(0, 0);
            }}
          >
            More stories
          </button>
        )}
      </div>
    </>
  );
}
export function Story() {
  const { id } = useParams(),
    { data: a, loading, error, reload } = useLoad('/articles/' + id),
    { user, toast } = useApp();
  const [comment, setComment] = useState(''),
    [busy, setBusy] = useState(false);
  const navigate = useNavigate();
  if (loading || error) return <State loading={loading} error={error} retry={reload} />;
  return (
    <div className="article-page">
      <Link to="/" className="back-link">
        <ArrowLeft size={16} /> Back to the stories
      </Link>
      <div className="eyebrow">
        {a.category_name} <span> / </span> {a.district || 'News Junction'}
      </div>
      <h1>{a.title}</h1>
      <p className="article-deck">{a.description}</p>
      <div className="article-byline">
        <span className="avatar">{a.author_name[0]}</span>
        <div>
          <b>{a.author_name}</b>
          <small>
            {dateLabel(a.published_at)} ·{' '}
            {Math.max(1, Math.ceil(a.content.split(/\s+/).length / 200))} min read
          </small>
        </div>
        <div className="article-actions">
          <SaveButton article={a} />
          <button
            className="icon-button"
            aria-label="Share story"
            onClick={async () => {
              try {
                await navigator.clipboard.writeText(location.href);
                toast('Story link copied.');
              } catch {
                toast('Copy the URL from your address bar to share.');
              }
            }}
          >
            <Share2 size={18} />
          </button>
        </div>
      </div>
      <Cover className="article-cover" src={a.image} alt={a.title} />
      <div className="article-content" dangerouslySetInnerHTML={{ __html: a.content }} />
      <div className="article-bottom">
        {a.source_url && (
          <a className="button secondary" href={a.source_url} target="_blank" rel="noreferrer">
            Read original reporting <ArrowUpRight size={16} />
          </a>
        )}
        <button
          className={'button secondary ' + (a.liked ? 'selected' : '')}
          onClick={async () => {
            if (!user) return navigate('/sign-in');
            try {
              await api('/articles/' + id + '/like', { method: 'PUT', body: { active: !a.liked } });
              reload();
            } catch (e) {
              toast(e.message);
            }
          }}
        >
          <Heart size={17} fill={a.liked ? 'currentColor' : 'none'} />
          {a.likes} appreciations
        </button>
        {user && (String(user.id) === String(a.user_id) || user.role === 'admin') && (
          <>
            <Link className="button secondary" to={'/edit/' + a.id}>
              <PenLine size={16} />
              Edit story
            </Link>
            <button
              className="button danger"
              onClick={async () => {
                if (!confirm('Delete this story permanently?')) return;
                try {
                  await api('/articles/' + id, { method: 'DELETE' });
                  navigate('/my-articles');
                } catch (e) {
                  toast(e.message);
                }
              }}
            >
              <Trash2 size={16} />
              Delete
            </button>
          </>
        )}
      </div>
      <section className="comments">
        <h2>Keep the conversation going.</h2>
        {user ? (
          <form
            onSubmit={async (e) => {
              e.preventDefault();
              setBusy(true);
              try {
                await api('/articles/' + id + '/comments', {
                  method: 'POST',
                  body: { body: comment },
                });
                setComment('');
                reload();
              } catch (e) {
                toast(e.message);
              } finally {
                setBusy(false);
              }
            }}
          >
            <textarea
              aria-label="Your comment"
              required
              maxLength={3000}
              placeholder="Add a thoughtful perspective…"
              value={comment}
              onChange={(e) => setComment(e.target.value)}
            />
            <button className="button" disabled={busy}>
              Post comment <ArrowRight size={16} />
            </button>
          </form>
        ) : (
          <p>
            <Link to="/sign-in">Sign in</Link> to join the discussion.
          </p>
        )}
        {a.comments.map((c) => (
          <article className="comment" key={c.id}>
            <div>
              <b>{c.full_name}</b>
              <small>{dateLabel(c.created_at)}</small>
            </div>
            <p>{c.body}</p>
          </article>
        ))}
      </section>
    </div>
  );
}
export function Auth() {
  const [register, setRegister] = useState(
      new URLSearchParams(window.location.search).get('type') === 'signup',
    ),
    [show, setShow] = useState(false),
    [error, setError] = useState(''),
    [busy, setBusy] = useState(false);
  const { setUser, user } = useApp(),
    navigate = useNavigate();
  if (user)
    return (
      <div className="empty">
        <h1>Welcome back, {user.full_name.split(' ')[0]}.</h1>
        <Link to="/community" className="button">
          Open Social <ArrowRight size={16} />
        </Link>
      </div>
    );
  return (
    <div className="auth-layout">
      <div className="auth-art">
        <Link to="/" className="auth-logo">
          <img src="/legacy-media/grfx/images/newsjunction.png" alt="News Junction" />
        </Link>
        <h1>
          Your Voice,
          <br />
          Your Platform
        </h1>
        <p>
          Hyperlocal news, citizen journalism and community stories — straight from the ground,
          across the region.
        </p>
        <div className="auth-promises">
          {[
            'Real-time local coverage you can trust',
            'Become a verified citizen reporter',
            'Your community, your stories',
          ].map((t) => (
            <span key={t}>
              <Check size={17} />
              {t}
            </span>
          ))}
        </div>
      </div>
      <div className="auth-form">
        <div className="auth-tabs">
          <button
            className={!register ? 'active' : ''}
            onClick={() => {
              setRegister(false);
              setError('');
            }}
          >
            Login
          </button>
          <button
            className={register ? 'active' : ''}
            onClick={() => {
              setRegister(true);
              setError('');
            }}
          >
            Sign up
          </button>
        </div>
        <form
          onSubmit={async (e) => {
            e.preventDefault();
            setBusy(true);
            setError('');
            const body = Object.fromEntries(new FormData(e.currentTarget));
            try {
              const d = await api('/auth/' + (register ? 'register' : 'login'), {
                method: 'POST',
                body,
              });
              setUser(d.user);
              navigate('/community');
            } catch (e) {
              setError(e.message);
            } finally {
              setBusy(false);
            }
          }}
        >
          {register && (
            <label>
              Your name
              <input
                name="full_name"
                required
                maxLength={100}
                autoComplete="name"
                placeholder="How should we call you?"
              />
            </label>
          )}
          <label>
            Email address
            <input
              name="email"
              type="email"
              required
              autoComplete="email"
              placeholder="Enter Email"
            />
          </label>
          <label>
            Password
            <div className="password-field">
              <input
                name="password"
                type={show ? 'text' : 'password'}
                required
                minLength={register ? 10 : 1}
                maxLength={128}
                autoComplete={register ? 'new-password' : 'current-password'}
                placeholder={register ? 'At least 10 characters' : 'Enter Password'}
              />
              <button
                type="button"
                className="icon-button"
                aria-label={show ? 'Hide password' : 'Show password'}
                onClick={() => setShow(!show)}
              >
                {show ? <EyeOff size={18} /> : <Eye size={18} />}
              </button>
            </div>
          </label>
          <FormError error={error} />
          <button className="button" disabled={busy}>
            {busy ? 'Just a moment…' : register ? 'Sign up' : 'Log in'}
            <ArrowRight size={17} />
          </button>
        </form>
        <small>
          By continuing, you agree to our <Link to="/privacy">privacy policy</Link>.
        </small>
        <Link className="back-link" to="/">
          <ArrowLeft size={14} /> Just looking? Explore the news
        </Link>
      </div>
    </div>
  );
}
export function Channels() {
  const { data, loading, error, reload } = useLoad('/channels'),
    { user, toast } = useApp(),
    navigate = useNavigate();
  const [q, setQ] = useState(''),
    [create, setCreate] = useState(false);
  return (
    <>
      <PageTitle
        title="Featured Channels"
        eyebrow="NEWS CHANNELS"
        description="Follow the conversations that bring your community closer."
        action={
          ['reporter', 'admin'].includes(user?.role) && (
            <button className="button" onClick={() => setCreate(!create)}>
              <Plus size={16} />
              New channel
            </button>
          )
        }
      />
      {create && (
        <form
          className="panel inline-form"
          onSubmit={async (e) => {
            e.preventDefault();
            try {
              await api('/channels', {
                method: 'POST',
                body: Object.fromEntries(new FormData(e.currentTarget)),
              });
              setCreate(false);
              reload();
            } catch (e) {
              toast(e.message);
            }
          }}
        >
          <label>
            Channel name
            <input required name="name" maxLength={120} />
          </label>
          <label>
            About the channel
            <textarea name="bio" maxLength={1000} />
          </label>
          <button className="button">Create channel</button>
        </form>
      )}
      <div className="search-box">
        <Search size={18} />
        <input
          aria-label="Find a channel"
          placeholder="Find a channel or community…"
          value={q}
          onChange={(e) => setQ(e.target.value)}
        />
      </div>
      <State loading={loading} error={error} retry={reload} />
      <div className="directory-grid">
        {data
          ?.filter((c) => (c.name + ' ' + c.bio).toLowerCase().includes(q.toLowerCase()))
          .map((c, i) => (
            <article className="directory-card" key={c.id}>
              <div className={'channel-banner tone-' + (i % 3)}>
                <Radio size={46} strokeWidth={1} />
                <span>THE COMMUNITY NETWORK</span>
              </div>
              <div className="directory-body">
                <span className="eyebrow">{c.followers} FOLLOWERS</span>
                <h2>
                  <Link to={'/channels/' + c.id}>{c.name}</Link>
                </h2>
                <p>{c.bio}</p>
                <div className="row">
                  <Link className="text-link" to={'/channels/' + c.id}>
                    Explore channel <ArrowUpRight size={16} />
                  </Link>
                  <button
                    className={'button small ' + (c.followed ? 'secondary' : '')}
                    onClick={async () => {
                      if (!user) return navigate('/sign-in');
                      try {
                        await api(`/channels/${c.id}/follow`, {
                          method: 'PUT',
                          body: { active: !c.followed },
                        });
                        reload();
                      } catch (e) {
                        toast(e.message);
                      }
                    }}
                  >
                    {c.followed ? (
                      <>
                        <Check size={14} />
                        Following
                      </>
                    ) : (
                      <>
                        <Plus size={14} />
                        Follow
                      </>
                    )}
                  </button>
                </div>
              </div>
            </article>
          ))}
      </div>
      {data &&
        !data.filter((c) => (c.name + ' ' + c.bio).toLowerCase().includes(q.toLowerCase()))
          .length && <Empty title="No channels found." />}
    </>
  );
}
export function Channel() {
  const { id } = useParams(),
    { data, loading, error, reload } = useLoad('/channels/' + id),
    { user, toast } = useApp();
  if (loading || error) return <State loading={loading} error={error} retry={reload} />;
  return (
    <>
      <PageTitle eyebrow="COMMUNITY CHANNEL" title={data.name} description={data.bio} />
      {user && (String(user.id) === String(data.user_id) || user.role === 'admin') && (
        <form
          className="panel inline-form"
          onSubmit={async (e) => {
            e.preventDefault();
            const form = e.currentTarget;
            try {
              await api('/channels/' + id + '/posts', {
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
            Share an update
            <textarea name="body" required maxLength={5000} />
          </label>
          <button className="button">Publish update</button>
        </form>
      )}
      <div className="post-list">
        {data.posts.map((p) => (
          <article key={p.id} className="panel">
            <div className="row">
              <b>{p.full_name || 'News Junction'}</b>
              <small>{dateLabel(p.created_at)}</small>
            </div>
            <p className="preserve-lines">{p.body}</p>
          </article>
        ))}
      </div>
      {!data.posts.length && <Empty title="The conversation starts here." />}
    </>
  );
}
export function Resources({ kind }) {
  const { data, loading, error, reload } = useLoad('/resources/' + kind),
    { user, toast } = useApp();
  const [district, setDistrict] = useState(''),
    [adding, setAdding] = useState(false);
  const titles = {
    newspapers: [
      'Your district. In print.',
      'Explore local editions and the people behind the headlines.',
    ],
    magazines: [
      'Take the longer view.',
      'Ideas, culture, and stories worth spending a little time with.',
    ],
    influencers: ['The voices among us.', 'Meet independent voices and community storytellers.'],
  };
  const list = data?.filter((r) => !district || r.data.district === district) || [];
  return (
    <>
      <PageTitle
        eyebrow={kind.toUpperCase()}
        title={titles[kind][0]}
        description={titles[kind][1]}
        action={
          kind !== 'influencers' &&
          ['reporter', 'admin'].includes(user?.role) && (
            <button className="button" onClick={() => setAdding(!adding)}>
              <Plus size={16} />
              Add edition
            </button>
          )
        }
      />
      {adding && (
        <ResourceForm
          kind={kind}
          onDone={() => {
            setAdding(false);
            reload();
          }}
        />
      )}
      {kind === 'newspapers' && (
        <div className="filter-line">
          <MapPin size={17} />
          <DistrictSelect value={district} onChange={(e) => setDistrict(e.target.value)} />
        </div>
      )}
      <State loading={loading} error={error} retry={reload} />
      <div className="directory-grid">
        {list.map((r) => (
          <article className="directory-card" key={r.id}>
            <Cover className="resource-cover" src={r.data.image} alt={r.title} />
            <div className="directory-body">
              <span className="eyebrow">{r.data.district || kind}</span>
              <h2>{r.title}</h2>
              <p>{r.data.description}</p>
              {r.data.url ? (
                <a className="text-link" href={r.data.url} target="_blank" rel="noreferrer">
                  {kind === 'influencers' ? 'Visit profile' : 'Read edition'}{' '}
                  <ArrowUpRight size={16} />
                </a>
              ) : (
                <span className="muted">Edition link not yet available</span>
              )}
              {user && (String(r.user_id) === String(user.id) || user.role === 'admin') && (
                <button
                  className="icon-button"
                  aria-label={'Delete ' + r.title}
                  onClick={async () => {
                    if (!confirm('Remove this resource?')) return;
                    try {
                      await api('/resources/' + r.id, { method: 'DELETE' });
                      reload();
                    } catch (e) {
                      toast(e.message);
                    }
                  }}
                >
                  <Trash2 size={15} />
                </button>
              )}
            </div>
          </article>
        ))}
      </div>
      {!loading && !error && !list.length && <Empty title="The next edition is on its way." />}
    </>
  );
}
function ResourceForm({ kind, onDone }) {
  const [busy, setBusy] = useState(false),
    [error, setError] = useState('');
  return (
    <form
      className="panel inline-form"
      onSubmit={async (e) => {
        e.preventDefault();
        setBusy(true);
        setError('');
        const { title, ...data } = Object.fromEntries(new FormData(e.currentTarget));
        try {
          await api('/resources/' + kind, { method: 'POST', body: { title, data } });
          onDone();
        } catch (e) {
          setError(e.message);
        } finally {
          setBusy(false);
        }
      }}
    >
      <label>
        Title
        <input name="title" required maxLength={200} />
      </label>
      <label>
        Description
        <textarea name="description" maxLength={2000} />
      </label>
      <label>
        Cover image URL
        <input name="image" type="url" placeholder="https://…" />
      </label>
      <label>
        {kind === 'ads' ? 'Destination' : 'Edition'} URL
        <input name="url" type="url" required placeholder="https://…" />
      </label>
      <label>
        District
        <select name="district">
          <option value="">All districts</option>
          {districts.map((d) => (
            <option key={d}>{d}</option>
          ))}
        </select>
      </label>
      <FormError error={error} />
      <button className="button" disabled={busy}>
        Save {kind === 'ads' ? 'advertisement' : 'edition'}
      </button>
    </form>
  );
}
export function Complaints() {
  const { data, loading, error, reload } = useLoad('/complaints'),
    { user, toast } = useApp();
  const [adding, setAdding] = useState(false),
    [busy, setBusy] = useState(false);
  return (
    <>
      <PageTitle
        eyebrow="CIVIC REPORTS"
        title="My Complaints"
        description="Raise a local concern and follow its progress. Your reports are shared with the editorial team."
        action={
          <button className="button" onClick={() => setAdding(!adding)}>
            <Plus size={16} />
            Raise a concern
          </button>
        }
      />
      {adding && (
        <form
          className="panel inline-form"
          onSubmit={async (e) => {
            e.preventDefault();
            setBusy(true);
            try {
              await api('/complaints', {
                method: 'POST',
                body: Object.fromEntries(new FormData(e.currentTarget)),
              });
              setAdding(false);
              reload();
              toast('Your concern has been submitted.');
            } catch (e) {
              toast(e.message);
            } finally {
              setBusy(false);
            }
          }}
        >
          <label>
            What needs attention?
            <input name="title" required maxLength={200} />
          </label>
          <label>
            Tell us more
            <textarea name="description" required maxLength={5000} />
          </label>
          <div className="form-grid">
            <label>
              Location
              <input name="location" required maxLength={200} />
            </label>
            <label>
              PIN code
              <input name="pincode" required pattern="[0-9]{6}" maxLength={6} inputMode="numeric" />
            </label>
          </div>
          <button className="button" disabled={busy}>
            Submit report <ArrowRight size={16} />
          </button>
        </form>
      )}
      <State loading={loading} error={error} retry={reload} />
      <div className="post-list">
        {data?.map((c) => (
          <article className="panel" key={c.id}>
            <div className="row">
              <span className={'badge ' + (c.status === 'Resolved' ? 'green' : '')}>
                {c.status}
              </span>
              <small>
                #{c.id} · {dateLabel(c.created_at)}
              </small>
            </div>
            <h2>{c.title}</h2>
            <p>{c.description}</p>
            <small>
              <MapPin size={13} /> {c.location} · {c.pincode}
            </small>
            {c.response && <blockquote>{c.response}</blockquote>}
            {user.role === 'admin' && (
              <form
                className="inline-form"
                onSubmit={async (e) => {
                  e.preventDefault();
                  try {
                    await api('/complaints/' + c.id, {
                      method: 'PUT',
                      body: Object.fromEntries(new FormData(e.currentTarget)),
                    });
                    reload();
                  } catch (e) {
                    toast(e.message);
                  }
                }}
              >
                <label>
                  Status
                  <select name="status" defaultValue={c.status}>
                    {['Submitted', 'In Review', 'Resolved'].map((s) => (
                      <option key={s}>{s}</option>
                    ))}
                  </select>
                </label>
                <label>
                  Editorial response
                  <textarea name="response" defaultValue={c.response} />
                </label>
                <button className="button small">Update report</button>
              </form>
            )}
          </article>
        ))}
      </div>
      {data && !data.length && (
        <Empty title="A voice for your neighbourhood.">
          Report an issue to start tracking it here.
        </Empty>
      )}
    </>
  );
}
export function Editor() {
  const { id } = useParams();
  const { data, loading, error } = useLoad(id ? '/articles/' + id : '/categories');
  const [form, setForm] = useState({
      title: '',
      description: '',
      content: '',
      image: '',
      category_id: '',
      district: '',
      status: 'draft',
      published_at: '',
    }),
    [saving, setSaving] = useState(false),
    [failure, setFailure] = useState('');
  const navigate = useNavigate(),
    { toast } = useApp();
  useEffect(() => {
    if (id && data)
      setForm({
        ...data,
        published_at: data.published_at
          ? new Date(
              new Date(data.published_at) - new Date(data.published_at).getTimezoneOffset() * 60000,
            )
              .toISOString()
              .slice(0, 16)
          : '',
      });
  }, [id, data]);
  const field = (key) => ({
    value: form[key],
    onChange: (e) => setForm({ ...form, [key]: e.target.value }),
  });
  return (
    <>
      <PageTitle
        eyebrow="THE NEWSROOM"
        title={id ? 'Edit Article' : 'Create Article'}
        description="Write with care. Add context. Help your community understand."
      />
      <State loading={loading} error={error} />
      {!loading && !error && (
        <form
          className="editor-form panel"
          onSubmit={async (e) => {
            e.preventDefault();
            setSaving(true);
            setFailure('');
            try {
              const body = { ...form, category_id: Number(form.category_id) };
              if (body.published_at) body.published_at = new Date(body.published_at).toISOString();
              else delete body.published_at;
              const a = await api('/articles' + (id ? '/' + id : ''), {
                method: id ? 'PUT' : 'POST',
                body,
              });
              toast('Your story has been saved.');
              navigate('/article/' + a.id);
            } catch (e) {
              setFailure(e.message);
            } finally {
              setSaving(false);
            }
          }}
        >
          <label>
            Headline
            <input
              className="headline-input"
              placeholder="A headline that says something."
              required
              maxLength={220}
              {...field('title')}
            />
          </label>
          <label>
            A short introduction
            <textarea
              rows={2}
              maxLength={600}
              placeholder="Give your readers a reason to read on…"
              {...field('description')}
            />
          </label>
          <div className="form-grid">
            <label>
              Category
              <CategorySelect
                {...{ value: form.category_id, onChange: field('category_id').onChange }}
              />
            </label>
            <label>
              District
              <DistrictSelect
                all={false}
                {...{ value: form.district, onChange: field('district').onChange }}
              />
            </label>
          </div>
          <label>
            Cover image
            <input placeholder="https://… or upload below" {...field('image')} />
          </label>
          <ImageUpload onUpload={(image) => setForm({ ...form, image })} />
          <label>
            Your story <small>Basic HTML formatting is supported; unsafe markup is removed.</small>
            <textarea
              className="content-input"
              rows={15}
              required
              maxLength={100000}
              placeholder="Start with what matters…"
              {...field('content')}
            />
          </label>
          <div className="form-grid">
            <label>
              Publication status
              <select {...field('status')}>
                <option value="draft">Save as draft</option>
                <option value="published">Publish now</option>
                <option value="scheduled">Schedule for later</option>
              </select>
            </label>
            {form.status === 'scheduled' && (
              <label>
                Publish at
                <input type="datetime-local" required {...field('published_at')} />
              </label>
            )}
          </div>
          <FormError error={failure} />
          <button className="button" disabled={saving}>
            {saving
              ? 'Saving…'
              : form.status === 'draft'
                ? 'Save draft'
                : form.status === 'scheduled'
                  ? 'Schedule story'
                  : 'Publish story'}
            <ArrowUpRight size={17} />
          </button>
        </form>
      )}
    </>
  );
}
export function Profile() {
  const { user, setUser, toast } = useApp(),
    [busy, setBusy] = useState(false);
  return (
    <>
      <PageTitle
        title="My Account"
        eyebrow="YOUR PROFILE"
        description="Make this corner of News Junction your own."
      />
      <form
        className="panel inline-form"
        onSubmit={async (e) => {
          e.preventDefault();
          setBusy(true);
          try {
            const u = await api('/profile', {
              method: 'PUT',
              body: Object.fromEntries(new FormData(e.currentTarget)),
            });
            setUser(u);
            toast('Profile updated.');
          } catch (e) {
            toast(e.message);
          } finally {
            setBusy(false);
          }
        }}
      >
        <div className="row">
          <span className="avatar large">{user.full_name[0]}</span>
          <span>
            {user.email}
            <small className="block">{user.role}</small>
          </span>
        </div>
        <label>
          Full name
          <input name="full_name" defaultValue={user.full_name} required maxLength={100} />
        </label>
        <label>
          Bio
          <textarea name="bio" defaultValue={user.bio} maxLength={1000} />
        </label>
        <div className="form-grid">
          <label>
            District
            <select name="district" defaultValue={user.district}>
              <option value="">Choose your district</option>
              {districts.map((d) => (
                <option key={d}>{d}</option>
              ))}
            </select>
          </label>
          <label>
            Phone
            <input name="phone" defaultValue={user.phone} maxLength={30} />
          </label>
        </div>
        <button className="button" disabled={busy}>
          Save profile
        </button>
      </form>
      <form
        className="panel inline-form"
        onSubmit={async (e) => {
          e.preventDefault();
          const f = e.currentTarget;
          try {
            await api('/profile/password', {
              method: 'PUT',
              body: Object.fromEntries(new FormData(f)),
            });
            f.reset();
            toast('Password changed. Other sessions have been signed out.');
          } catch (e) {
            toast(e.message);
          }
        }}
      >
        <h2>Change your password</h2>
        <label>
          Current password
          <input type="password" name="current" required autoComplete="current-password" />
        </label>
        <label>
          New password
          <input
            type="password"
            name="password"
            required
            minLength={10}
            maxLength={128}
            autoComplete="new-password"
          />
        </label>
        <button className="button secondary">Update password</button>
      </form>
    </>
  );
}
export function Analytics() {
  const { data, loading, error, reload } = useLoad('/analytics');
  return (
    <>
      <PageTitle
        eyebrow="NEWSROOM ANALYTICS"
        title="Analytics"
        description="A clear view of readership, publishing, and the people you reach."
      />
      <State loading={loading} error={error} retry={reload} />
      {data && (
        <>
          <div className="stats-grid">
            {[
              ['Stories published & drafted', data.stats.stories],
              ['Story views', data.stats.views],
              ['Drafts in progress', data.stats.drafts],
              ['Leads received', data.leads.length],
            ].map(([label, n]) => (
              <div className="stat panel" key={label}>
                <small>{label}</small>
                <strong>{Number(n).toLocaleString()}</strong>
              </div>
            ))}
          </div>
          <div className="panel">
            <h2>Your stories at a glance</h2>
            <div className="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>Story</th>
                    <th>Status</th>
                    <th>Views</th>
                  </tr>
                </thead>
                <tbody>
                  {data.stories.map((s) => (
                    <tr key={s.id}>
                      <td>
                        <Link to={'/article/' + s.id}>{s.title}</Link>
                      </td>
                      <td>
                        <span className="badge">{s.status}</span>
                      </td>
                      <td>{s.views}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          </div>
          <div className="panel">
            <h2>Recent leads</h2>
            <div className="table-wrap">
              <table>
                <thead>
                  <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Page</th>
                  </tr>
                </thead>
                <tbody>
                  {data.leads.map((l) => (
                    <tr key={l.id}>
                      <td>{l.name}</td>
                      <td>{l.email}</td>
                      <td>{l.title}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            {!data.leads.length && <p>No leads received yet.</p>}
          </div>
        </>
      )}
    </>
  );
}
export function Admin() {
  const { data, loading, error, reload } = useLoad('/admin/users'),
    { user, toast } = useApp();
  const ads = useLoad('/resources/ads');
  return (
    <>
      <PageTitle
        eyebrow="ADMINISTRATION"
        title="Administration"
        description="Manage newsroom access, advertisements, and civic reports."
      />
      <State loading={loading} error={error} retry={reload} />
      <div className="panel">
        <h2>People & permissions</h2>
        <div className="table-wrap">
          <table>
            <thead>
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
              </tr>
            </thead>
            <tbody>
              {data?.map((u) => (
                <tr key={u.id}>
                  <td>{u.full_name}</td>
                  <td>{u.email}</td>
                  <td>
                    <select
                      aria-label={'Role for ' + u.full_name}
                      value={u.role}
                      disabled={u.id === user.id}
                      onChange={async (e) => {
                        try {
                          await api('/admin/users/' + u.id + '/role', {
                            method: 'PUT',
                            body: { role: e.target.value },
                          });
                          reload();
                        } catch (e) {
                          toast(e.message);
                        }
                      }}
                    >
                      {['reader', 'reporter', 'admin'].map((r) => (
                        <option key={r}>{r}</option>
                      ))}
                    </select>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
      <div className="section-heading">
        <h2>Advertisements</h2>
        <Link to="/complaints" className="text-link">
          Review civic reports <ArrowUpRight size={16} />
        </Link>
      </div>
      <ResourceForm
        kind="ads"
        onDone={() => {
          ads.reload();
          toast('Advertisement saved.');
        }}
      />
      {ads.data?.map((a) => (
        <div className="panel row" key={a.id}>
          <span>{a.title}</span>
          <button
            className="button secondary"
            onClick={async () => {
              if (!confirm('Remove this advertisement?')) return;
              try {
                await api('/resources/' + a.id, { method: 'DELETE' });
                ads.reload();
              } catch (e) {
                toast(e.message);
              }
            }}
          >
            Remove
          </button>
        </div>
      ))}
    </>
  );
}
export function Reels() {
  return (
    <>
      <PageTitle
        eyebrow="IN THE FRAME"
        title="Videos"
        description="Short films from the News Junction media library."
      />
      <div className="reel-grid">
        {[1, 2, 3, 4, 5].map((n) => (
          <article key={n}>
            <video controls playsInline preload="metadata" src={'/media/reel' + n + '.mp4'} />
            <span className="eyebrow">FROM OUR VIDEO LIBRARY</span>
            <h3>Community perspectives · {String(n).padStart(2, '0')}</h3>
          </article>
        ))}
      </div>
    </>
  );
}
export function LeadBuilder() {
  const { data, reload } = useLoad('/resources/templates'),
    { toast } = useApp();
  const [form, setForm] = useState({
      title: 'A little perspective, in your inbox.',
      description: 'Join our community of curious, connected readers.',
      cta: 'Count me in',
    }),
    [busy, setBusy] = useState(false);
  return (
    <>
      <PageTitle
        eyebrow="LEAD PAGE STUDIO"
        title="Create Page"
        description="Create a simple, shareable signup page. New leads appear in your analytics."
      />
      <div className="builder-grid">
        <form
          className="panel inline-form"
          onSubmit={async (e) => {
            e.preventDefault();
            setBusy(true);
            try {
              await api('/resources/templates', {
                method: 'POST',
                body: { title: form.title, data: { description: form.description, cta: form.cta } },
              });
              reload();
              toast('Your page is ready to share.');
            } catch (e) {
              toast(e.message);
            } finally {
              setBusy(false);
            }
          }}
        >
          {[
            ['title', 'Headline'],
            ['description', 'Description'],
            ['cta', 'Button text'],
          ].map(([key, label]) => (
            <label key={key}>
              {label}
              <input
                required
                maxLength={key === 'title' ? 200 : key === 'cta' ? 100 : 2000}
                value={form[key]}
                onChange={(e) => setForm({ ...form, [key]: e.target.value })}
              />
            </label>
          ))}
          <button className="button" disabled={busy}>
            Create page <ArrowUpRight size={16} />
          </button>
        </form>
        <div className="newsletter builder-preview">
          <span className="eyebrow">PAGE PREVIEW</span>
          <h2>{form.title}</h2>
          <p>{form.description}</p>
          <div className="preview-input">Your name</div>
          <div className="preview-input">Your email address</div>
          <span className="button">{form.cta}</span>
        </div>
      </div>
      <h2>Your published pages</h2>
      {data?.map((r) => (
        <div className="panel row" key={r.id}>
          <h3>{r.title}</h3>
          <Link to={'/pages/' + r.id} className="text-link">
            Open page <ArrowUpRight size={16} />
          </Link>
        </div>
      ))}
    </>
  );
}
export function LeadPage() {
  const { id } = useParams(),
    { data, loading, error } = useLoad('/lead-pages/' + id),
    [done, setDone] = useState(false),
    [failure, setFailure] = useState('');
  if (loading || error) return <State loading={loading} error={error} />;
  return (
    <div className="lead-page newsletter">
      <span className="eyebrow">THE NEWS JUNCTION COMMUNITY</span>
      <h1>{data.title}</h1>
      <p>{data.data.description}</p>
      {done ? (
        <h2>Thanks for being part of the story.</h2>
      ) : (
        <form
          onSubmit={async (e) => {
            e.preventDefault();
            try {
              await api('/lead-pages/' + id + '/leads', {
                method: 'POST',
                body: Object.fromEntries(new FormData(e.currentTarget)),
              });
              setDone(true);
            } catch (e) {
              setFailure(e.message);
            }
          }}
        >
          <label>
            Your name
            <input required name="name" maxLength={100} />
          </label>
          <label>
            Email address
            <input required type="email" name="email" />
          </label>
          <FormError error={failure} />
          <button className="button">
            {data.data.cta || 'Join us'}
            <ArrowRight size={16} />
          </button>
        </form>
      )}
    </div>
  );
}
export function Markets() {
  return (
    <>
      <PageTitle
        eyebrow="BUSINESS & MARKETS"
        title="Markets"
        description="Business reporting and direct links to the exchanges."
      />
      <div className="directory-grid">
        {[
          ['NSE India', 'National Stock Exchange', 'https://www.nseindia.com/'],
          ['BSE India', 'Bombay Stock Exchange', 'https://www.bseindia.com/'],
        ].map(([title, desc, url]) => (
          <article className="panel" key={title}>
            <TrendingUp size={28} />
            <h2>{title}</h2>
            <p>{desc}</p>
            <a className="text-link" href={url} target="_blank" rel="noreferrer">
              View exchange data <ArrowUpRight size={16} />
            </a>
          </article>
        ))}
      </div>
      <p className="muted">
        Live prices are available on the exchanges. News Junction does not currently provide a live
        market feed.
      </p>
      <Link to="/?category=Business" className="button secondary">
        Read business stories <ArrowRight size={16} />
      </Link>
    </>
  );
}
export function Info({ page }) {
  const content = {
    about: [
      'Stories that bring us together.',
      'News Junction is a digital news platform for local reporting, community voices, and wider perspectives. Explore district stories, follow a channel, and take part in thoughtful conversations.',
    ],
    privacy: [
      'Your trust matters.',
      'We store account details, reading interactions you choose to save, comments, and civic reports in our database. A secure session cookie keeps you signed in. Newsletter signup stores your email address. Civic reports are visible to their author and the editorial team. Contact us to request access to or deletion of your data.',
    ],
    contact: [
      'Let’s start a conversation.',
      'For editorial enquiries, account assistance, or privacy requests, contact the News Junction team.',
    ],
  };
  return (
    <div className="info-page">
      <PageTitle eyebrow="NEWS JUNCTION" title={content[page][0]} />
      <p>{content[page][1]}</p>
      {page === 'contact' && (
        <a className="button" href="mailto:newsjunction@gmail.com">
          <Mail size={17} />
          newsjunction@gmail.com
        </a>
      )}
      <Link className="back-link" to="/">
        <ArrowLeft size={16} />
        Back to the stories
      </Link>
    </div>
  );
}
