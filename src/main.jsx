import '@fontsource/dm-sans/latin-400.css';
import '@fontsource/dm-sans/latin-500.css';
import '@fontsource/dm-sans/latin-600.css';
import '@fontsource/dm-sans/latin-700.css';
import '@fontsource/noto-sans-kannada/kannada-400.css';
import React, { createContext, useContext, useEffect, useState } from 'react';
import { createRoot } from 'react-dom/client';
import {
  BrowserRouter,
  Routes,
  Route,
  NavLink,
  Link,
  useNavigate,
  useLocation,
} from 'react-router-dom';
import {
  Newspaper,
  Search,
  MapPin,
  ArrowUpRight,
  ArrowRight,
  Bookmark,
  Radio,
  BookOpen,
  Video,
  Users,
  ChartNoAxesCombined,
  PenLine,
  Menu,
  X,
  LogOut,
  ShieldCheck,
  Globe,
  MessageSquare,
  Settings,
  ChevronDown,
} from 'lucide-react';
import { api, session } from './api';
import {
  Feed,
  Story,
  Auth,
  Channels,
  Channel,
  Resources,
  Complaints,
  Editor,
  Profile,
  Analytics,
  Admin,
  Reels,
  LeadBuilder,
  LeadPage,
  Info,
  Markets,
} from './pages';
import './styles.css';
import './original.css';
import { Landing } from './original-ui';
import Social from './social';
export const AppContext = createContext();
export const useApp = () => useContext(AppContext);
export const districts = [
  'Bengaluru Urban',
  'Udupi',
  'Mysuru',
  'Hassan',
  'Belagavi',
  'Dakshina Kannada',
  'Dharwad',
  'Shivamogga',
];
export const dateLabel = (d) =>
  !d || new Date(d).getTime() <= 0
    ? 'Date unavailable'
    : new Date(d).toLocaleDateString('en-IN', { day: 'numeric', month: 'short', year: 'numeric' });
export function useLoad(url) {
  const [state, set] = useState({ loading: true, data: null, error: '' });
  const [version, bump] = useState(0);
  useEffect(() => {
    let active = true;
    set((s) => ({ ...s, loading: true, error: '' }));
    api(url)
      .then((data) => active && set({ data, loading: false, error: '' }))
      .catch((e) => active && set({ data: null, loading: false, error: e.message }));
    return () => {
      active = false;
    };
  }, [url, version]);
  return { ...state, reload: () => bump((v) => v + 1) };
}
export function State({ loading, error, retry }) {
  return loading ? (
    <div className="loading" role="status">
      <span /> Loading...
    </div>
  ) : error ? (
    <div className="empty" role="alert">
      <h2>We couldn’t load this page.</h2>
      <p>{error}</p>
      {retry && (
        <button className="button" onClick={retry}>
          Try again
        </button>
      )}
    </div>
  ) : null;
}
export function Cover({ src, alt = '', className = '' }) {
  return (
    <img
      className={className}
      src={src || '/media/placeholder.svg'}
      alt={alt}
      loading="lazy"
      onError={(e) => {
        e.currentTarget.onerror = null;
        e.currentTarget.src = '/media/placeholder.svg';
      }}
    />
  );
}
export function Brand() {
  return (
    <Link to="/" className="brand" aria-label="News Junction home">
      <img src="/legacy-media/grfx/images/newsjunction.png" alt="News Junction" />
    </Link>
  );
}
export function Gate({ children, editor = false, admin = false }) {
  const { user, ready } = useApp();
  if (!ready) return <State loading />;
  if (!user)
    return (
      <div className="empty">
        <ShieldCheck size={40} />
        <h1>Please log in.</h1>
        <p>Sign in to save stories, join your community, and make your voice heard.</p>
        <Link className="button" to="/sign-in">
          Sign in <ArrowRight size={16} />
        </Link>
      </div>
    );
  if ((editor && !['reporter', 'admin'].includes(user.role)) || (admin && user.role !== 'admin'))
    return (
      <div className="empty">
        <h1>Editorial access required</h1>
        <p>This workspace is for News Junction reporters and editors.</p>
        <Link to="/" className="button">
          Explore the news
        </Link>
      </div>
    );
  return children;
}
function Shell() {
  const { user, setUser, toast } = useApp();
  const [open, setOpen] = useState(false);
  const [q, setQ] = useState('');
  const navigate = useNavigate();
  const location = useLocation();
  useEffect(() => {
    setOpen(false);
    window.scrollTo(0, 0);
  }, [location.pathname]);
  if (location.pathname === '/') return <Landing />;
  if (location.pathname === '/sign-in')
    return (
      <main id="main" className="auth-page">
        <Auth />
      </main>
    );
  const groups = [
    [
      'Social',
      [
        ['/community', 'Social', Globe],
        ['/community?pincode=1', 'Pincode', MapPin],
        ['/community?mode=saved', 'Bookmarks', Bookmark],
        ['/community?mode=following', 'Following', Users],
      ],
    ],
    ['Shop', [['https://newsjunction.net/manikya_market/index.php?p=home', 'Manikya Market', BookOpen]]],
    [
      'Reader',
      [
        ['/reader', 'Reader', BookOpen],
        ['/channels', 'Featured Channels', Radio],
        ['/reader?topics=1', 'Featured Topics', Bookmark],
        ['/newspapers', 'Curated Feeds', Newspaper],
        ['/magazines', 'Magazines', BookOpen],
      ],
    ],
    [
      'Creator',
      [
        ['/my-articles', 'My Collection', Newspaper],
        ['/create', 'Reporter', PenLine],
        ['/analytics', 'Analytics', ChartNoAxesCombined],
      ],
    ],
    [
      '',
      [
        ['/profile', 'My Account', Users],
        ['/complaints', 'My Complaints', MessageSquare],
        ['/profile?settings=1', 'Settings', Settings],
        ['/reader?search=1', 'Search', Search],
        ['/saved', 'Saved stories', Bookmark],
        ...(user?.role === 'admin' ? [['/admin', 'Administration', Settings]] : []),
      ],
    ],
  ];
  return (
    <>
      <header className="reader-header">
        <button
          className="icon-button mobile-menu"
          aria-label="Open navigation"
          onClick={() => setOpen(!open)}
        >
          {open ? <X /> : <Menu />}
        </button>
        <Brand />
        <div className="header-actions">
          <Link className="citizen-link" to="/community">
            <Users size={20} />
            Citizen Connect
          </Link>
          <Link
            className="avatar"
            to={user ? '/profile' : '/sign-in'}
            aria-label={user ? 'My Account' : 'Login'}
          >
            {user ? user.full_name[0] : <Users size={23} />}
          </Link>
        </div>
      </header>
      <div className="app-layout">
        {open && (
          <button
            className="nav-backdrop"
            aria-label="Close navigation"
            onClick={() => setOpen(false)}
          />
        )}
        <aside className={'sidebar ' + (open ? 'is-open' : '')}>
          {groups.map(([title, links], i) => (
            <section className="nav-group" key={i}>
              {title && <h2>{title}</h2>}
              <nav>
                {links.map(([url, label, Icon]) => (
                  <Link
                    key={url}
                    to={url}
                    onClick={() => setOpen(false)}
                    className={location.pathname + location.search === url ? 'active' : ''}
                  >
                    <Icon size={22} />
                    {label}
                  </Link>
                ))}
              </nav>
            </section>
          ))}
          {user ? (
            <button
              className="logout"
              onClick={async () => {
                try {
                  await api('/auth/logout', { method: 'POST' });
                  setUser(null);
                  await session();
                  navigate('/');
                } catch (e) {
                  toast(e.message);
                }
              }}
            >
              <LogOut size={20} />
              Logout
            </button>
          ) : (
            <Link className="logout" to="/sign-in">
              <LogOut size={20} />
              Login
            </Link>
          )}
        </aside>
        <main id="main">
          <Routes>
            <Route path="/reader" element={<Feed />} />
            <Route path="/community" element={<Social />} />
            <Route
              path="/saved"
              element={
                <Gate>
                  <Feed saved />
                </Gate>
              }
            />
            <Route
              path="/my-articles"
              element={
                <Gate editor>
                  <Feed mine />
                </Gate>
              }
            />
            <Route path="/article/:id" element={<Story />} />
            <Route path="/sign-in" element={<Auth />} />
            <Route path="/channels" element={<Channels />} />
            <Route path="/channels/:id" element={<Channel />} />
            <Route path="/newspapers" element={<Resources kind="newspapers" />} />
            <Route path="/magazines" element={<Resources kind="magazines" />} />
            <Route path="/influencers" element={<Resources kind="influencers" />} />
            <Route
              path="/complaints"
              element={
                <Gate>
                  <Complaints />
                </Gate>
              }
            />
            <Route
              path="/create"
              element={
                <Gate editor>
                  <Editor />
                </Gate>
              }
            />
            <Route
              path="/edit/:id"
              element={
                <Gate editor>
                  <Editor />
                </Gate>
              }
            />
            <Route
              path="/profile"
              element={
                <Gate>
                  <Profile />
                </Gate>
              }
            />
            <Route
              path="/analytics"
              element={
                <Gate editor>
                  <Analytics />
                </Gate>
              }
            />
            <Route
              path="/admin"
              element={
                <Gate admin>
                  <Admin />
                </Gate>
              }
            />
            <Route path="/reels" element={<Reels />} />
            <Route
              path="/lead-builder"
              element={
                <Gate editor>
                  <LeadBuilder />
                </Gate>
              }
            />
            <Route path="/pages/:id" element={<LeadPage />} />
            <Route path="/markets" element={<Markets />} />
            {['about', 'privacy', 'contact'].map((p) => (
              <Route key={p} path={'/' + p} element={<Info page={p} />} />
            ))}
            <Route
              path="*"
              element={
                <div className="empty">
                  <h1>A little off the beaten path.</h1>
                  <p>We couldn’t find that page.</p>
                  <Link className="button" to="/">
                    Back to the news
                  </Link>
                </div>
              }
            />
          </Routes>
          <footer>
            <Brand />
            <span>© {new Date().getFullYear()} News Junction</span>
            <div>
              <Link to="/about">About</Link>
              <Link to="/privacy">Privacy</Link>
              <Link to="/contact">Contact</Link>
            </div>
          </footer>
        </main>
      </div>
    </>
  );
}
function App() {
  const [user, setUser] = useState(null),
    [ready, setReady] = useState(false),
    [notice, setNotice] = useState('');
  useEffect(() => {
    session()
      .then((d) => setUser(d.user))
      .catch(() => {})
      .finally(() => setReady(true));
  }, []);
  useEffect(() => {
    if (notice) {
      const t = setTimeout(() => setNotice(''), 5000);
      return () => clearTimeout(t);
    }
  }, [notice]);
  return (
    <AppContext.Provider value={{ user, setUser, ready, toast: setNotice }}>
      <a className="skip-link" href="#main">
        Skip to content
      </a>
      <Shell />
      {notice && (
        <div className="toast" role="status">
          {notice}
          <button aria-label="Dismiss notification" onClick={() => setNotice('')}>
            <X size={16} />
          </button>
        </div>
      )}
    </AppContext.Provider>
  );
}
createRoot(document.getElementById('root')).render(
  <BrowserRouter>
    <App />
  </BrowserRouter>,
);
