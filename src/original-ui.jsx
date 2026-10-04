import React from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { Search, ArrowRight } from 'lucide-react';
import { Cover, useLoad } from './main';

export function Adverts({ strip = false, offset = 0 }) {
  const { data } = useLoad('/resources/ads');
  const ads = data?.filter((a) => a.data.image) || [];
  const ordered = [
    ...ads.filter((a) => /mahindra/i.test(a.title)),
    ...ads.filter((a) => !/mahindra/i.test(a.title)),
  ];
  const selected = strip ? ordered.slice(0, 3) : ordered.slice(offset, offset + 1);
  if (!selected.length) return null;
  return (
    <div className={strip ? 'original-ad-strip' : 'original-ad-wrap'}>
      {strip && <small>SPONSORED</small>}
      {selected.map((ad) => (
        <article className="original-ad" key={ad.id} aria-label="Advertisement">
          <div className="ad-picture">
            <Cover src={ad.data.image} alt={ad.title} />
            <span>AD</span>
          </div>
          <div className="ad-copy">
            <h3>{ad.title}</h3>
            <p>{ad.data.description}</p>
            {ad.data.url && (
              <a className="button" href={ad.data.url} target="_blank" rel="noreferrer">
                Learn More <ArrowRight size={14} />
              </a>
            )}
          </div>
        </article>
      ))}
    </div>
  );
}
export function AllNews() {
  const { data } = useLoad('/articles?page=1');
  const navigate = useNavigate();
  return (
    <>
      <form
        className="reader-search"
        onSubmit={(e) => {
          e.preventDefault();
          navigate('/reader?q=' + encodeURIComponent(new FormData(e.currentTarget).get('q')));
        }}
      >
        <input name="q" aria-label="Search news" placeholder="Search..." />
        <button aria-label="Search">
          <Search size={20} />
        </button>
      </form>
      <section className="all-news panel">
        <h2>All News</h2>
        {data?.articles.slice(0, 7).map((a) => (
          <Link key={a.id} to={'/article/' + a.id}>
            {a.image && <Cover src={a.image} alt="" />}
            <span>{a.title}</span>
          </Link>
        ))}
        <Link className="see-news" to="/reader">
          See all news
        </Link>
      </section>
    </>
  );
}
export function Landing() {
  const features = [
    [
      'ಪರಿಶೀಲಿತ ವರದಿ',
      'verified.png',
      'ಪ್ರಕಟನೆಯ ಮೊದಲು ಪ್ರತಿಯೊಂದು ಸುದ್ದಿಯೂ ಜಿಲ್ಲಾ ಸಂಪಾದಕರಿಂದ ಪರಿಶೀಲಿಸಲಾಗುತ್ತದೆ.',
    ],
    ['ಜಿಲ್ಲಾ ವರದಿ', 'district.png', 'ಪ್ರತಿ ಜಿಲ್ಲೆಯ ಸ್ಥಳೀಯ ಘಟನೆಗಳು, ಜನರಿಗೆ ಸಂಬಂಧಿಸಿದ ವರದಿ.'],
    [
      'ತಕ್ಷಣದ ವರದಿ',
      'instatnt.png',
      'ಬ್ರೇಕಿಂಗ್ ನ್ಯೂಸ್ ಅಲರ್ಟ್‌ಗಳು ಮತ್ತು ಲೈವ್ ಅಪ್‌ಡೇಟ್‌ಗಳು ಕನ್ನಡದಲ್ಲಿ.',
    ],
    ['ನೈತಿಕ ವರದಿ', 'ethical.png', 'ಜವಾಬ್ದಾರಿಯುತ, ಪಕ್ಷಪಾತರಹಿತ ಮತ್ತು ಪಾರದರ್ಶಕ ವರದಿ ಮಾನದಂಡಗಳು.'],
  ];
  return (
    <div className="original-landing">
      <section className="landing-hero">
        <img src="/legacy-media/grfx/images/newsjunction.png" alt="News Junction" />
        <div>
          <p>
            A professional digital news platform delivering verified district, state, national and
            breaking news with journalistic integrity.
          </p>
          <Link to="/reader">Explore Latest News</Link>
        </div>
      </section>
      <nav className="landing-nav">
        <Link to="/" aria-label="News Junction home">
          <img src="/media/original-logo.png" alt="News Junction" />
        </Link>
        <div>
          <Link to="/sign-in">Login</Link>
          <Link to="/sign-in?type=signup">Sign Up</Link>
        </div>
      </nav>
      <main id="main" className="landing-content">
        <h1>News Junction ಯಾಕೆ?</h1>
        <section className="landing-features">
          {features.map(([title, img, body]) => (
            <article key={img}>
              <h2>{title}</h2>
              <img src={'/legacy-media/images/' + img} alt={title} />
              <p>{body}</p>
            </article>
          ))}
        </section>
        <Adverts />
        <section className="landing-districts" aria-label="District news">
          {[
            ['Belagavi', 0],
            ['Mysuru', 2],
            ['Hassan', 3],
            ['Udupi', 1],
          ].map(([name, i]) => (
            <Link
              key={name}
              to={'/reader?district=' + encodeURIComponent(name)}
              aria-label={name + ' news'}
            >
              <img src={'/media/district-' + i + '.png'} alt={name + ' News'} />
            </Link>
          ))}
        </section>
        <Adverts offset={1} />
        <section className="landing-stats">
          <div>
            <b>30+</b>
            <span>District Reporters</span>
          </div>
          <div>
            <b>24x7</b>
            <span>News Coverage</span>
          </div>
          <div>
            <b>100%</b>
            <span>Verified Sources</span>
          </div>
        </section>
      </main>
      <footer>
        <p>© {new Date().getFullYear()} News Junction</p>
        <div>
          <Link to="/about">About</Link>
          <Link to="/privacy">Privacy Policy</Link>
          <Link to="/contact">Contact</Link>
        </div>
      </footer>
    </div>
  );
}
