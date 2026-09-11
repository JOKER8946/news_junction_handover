# News Junction (Digital News & Citizen Journalism Platform)

[![Node.js](https://img.shields.io/badge/Node.js-v18+-green.svg)](https://nodejs.org/)
[![Express](https://img.shields.io/badge/Express-4.21-lightgrey.svg)](https://expressjs.com/)
[![Frontend](https://img.shields.io/badge/Frontend-Vanilla%20HTML5%20%7C%20CSS3%20%7C%20ES6-orange.svg)]()
[![Database](https://img.shields.io/badge/Database-MySQL%20%2F%20In--Memory%20Fallback-blue.svg)]()
[![License](https://img.shields.io/badge/License-ISC-purple.svg)]()

> A modern, responsive digital news and citizen journalism platform focused on grassroots reporting, district e-papers, verified columnist opinions, and citizen grievance escalation. Converted from a legacy PHP architecture to **vanilla HTML5, CSS3, JavaScript, and Node.js/Express**, preserving all business logic and editorial workflows.

---

## 🚀 Key Features & Modules

### 1. 📰 Editorial & Reader Operations
* **News Feed & Discovery (`/reader.html`)**: Real-time article feed with search, category filtering (Karnataka, District, Business, Technology, Sports, Entertainment), and district-level geo-filtering.
* **Single Article Reader (`/article.html`)**: Clean distraction-free reading experience, page view tracking, like counters, user comments, bookmarking, and **"Read More" Lead Capture Gating**.
* **Story Composer & Editor (`/create.html`)**: Rich post authoring with tags, cover image preview, customizable lead capture gates (mandatory email, mobile, company fields, custom autoresponders), and future release scheduling.
* **Author Dashboard (`/my-articles.html`)**: View published articles vs. scheduled queue, monitor readership performance, edit, and delete stories.

### 2. 📡 Community & Regional Hubs
* **Channels & Streams (`/channels.html`, `/channel.html`)**: Regional reporter bureaus and community channels with live stream updates, post composition, and follower subscriptions.
* **District E-Papers (`/district-newspapers.html`)**: Digitized morning editions and PDF archives across Karnataka districts (Bengaluru Urban, Udupi, Mysuru, Belagavi, Hassan, Dharwad, Tumakuru), complete with upload modal.
* **Verified Columnists Hub (`/influencers.html`)**: Directory of respected journalists and columnists with beat expertise, published opinion counts, and follow actions.

### 3. 📖 Curation & Commerce
* **Personalized Digital Magazine (`/magazine.html`)**: Multi-publisher curation wizard (Prajavani, Udayavani, Deccan Herald, The Hindu, etc.) that compiles customized, print-ready digital editions (`window.print()` support).
* **Reading Vault & Collections (`/collections.html`)**: Personal reading library with tags, offline study, and one-click **eSamudaay sharing** integration.
* **Visual Landing Page & Lead Builder (`/lead-builder.html`)**: Interactive drag-and-drop/controls studio with real-time canvas preview, customizable themes (light/dark), brand color pickers, and **standalone HTML export**.
* **Live Indian Share Market Board (`/sharemarket.html`)**: Real-time ticker for NIFTY 50, SENSEX, and blue-chip equities (Reliance, TCS, Infosys, HDFC Bank, ICICI Bank, Tata Motors) with 30s auto-refresh.

### 4. ⚖️ Civic Action & Administration
* **Citizen Grievance Desk (`/complaints.html`)**: Local civic and infrastructure reporting mapped to pincodes with verified reporter follow-up.
* **Executive Analytics & Leads (`/analytics.html`)**: Performance dashboard showing readership leaderboards, complaint resolution rates, and **captured business leads table with CSV export**.
* **Administrative Control Center (`/admin.html`)**: Full campaign control for sponsor ads, grievance triage with status assignment ("In Review", "Resolved", "Rejected") and official notes, plus user/reporter directory.
* **Identity & Profiles (`/sign-in.html`, `/profile.html`)**: Secure authentication, session cookies, user registration, password recovery, and profile editing.

---

## 🛠️ Technology Stack

* **Frontend**: Vanilla HTML5, semantic CSS3 (no bulky frameworks), ES6 JavaScript modules with clean `Fetch` API client (`public/js/api.js`).
* **Backend**: Node.js & Express (`server.js`).
* **Session & Security**: `express-session`, `cookie-parser`, `cors`, `bcryptjs`.
* **Database & Persistence**: MySQL 2 (`mysql2/promise`) with a resilient, zero-config in-memory database engine for seamless operation when MySQL is offline.
* **Backward Compatibility**: Preserved legacy PHP routes (`/create.php`, `/channel.php`, `/district_newspapers.php`, `/magazine.php`, `/signInProcess.php`, etc.) via HTTP 302 redirects and POST dispatchers.

---

## 📂 Project Structure

```
news_junction_handover/
├── public/                     # Converted Frontend (HTML, CSS, JS)
│   ├── css/
│   │   └── style.css           # Vanilla CSS design system & responsive layout
│   ├── js/
│   │   ├── api.js              # Universal REST API client
│   │   ├── index.js            # Home page interactions & tickers
│   │   ├── reader.js           # News feed filtering & infinite scrolling
│   │   ├── article.js          # Article view, comments, and lead gate
│   │   ├── create.js           # Story authoring & schedule logic
│   │   ├── my-articles.js      # Author stories & drafts management
│   │   ├── channels.js         # Channel directory & stream composer
│   │   ├── district-newspapers.js # E-paper archive & upload
│   │   ├── magazine.js         # Digital magazine compilation & print
│   │   ├── collections.js      # Reading library & eSamudaay share
│   │   ├── analytics.js        # Analytics charts & CSV lead export
│   │   ├── sharemarket.js      # Stock market quotes & auto-refresh
│   │   ├── influencers.js      # Columnists directory & follow logic
│   │   ├── lead-builder.js     # Visual template editor & HTML export
│   │   ├── admin.js            # Ads manager & grievance triage
│   │   ├── complaints.js       # Citizen grievance submission
│   │   ├── profile.js          # User profile management
│   │   └── auth.js             # Login, register, and password reset
│   ├── index.html              # Home portal & district headline ticker
│   ├── reader.html             # News feed & filter dashboard
│   ├── article.html            # Single story view with lead capture
│   ├── create.html             # Story composer & lead gate config
│   ├── my-articles.html        # Published & scheduled queue
│   ├── channels.html           # Channels directory
│   ├── channel.html            # Single channel stream & post composer
│   ├── district-newspapers.html# Regional newspaper archive
│   ├── magazine.html           # Digital magazine curation
│   ├── collections.html        # Saved articles & eSamudaay
│   ├── analytics.html          # Executive analytics & leads
│   ├── sharemarket.html        # Live stock market board
│   ├── influencers.html        # Columnists & editorial voices
│   ├── lead-builder.html       # Landing page studio
│   ├── admin.html              # Admin ads & complaint triage
│   ├── complaints.html         # Citizen grievance portal
│   ├── profile.html            # User account settings
│   ├── sign-in.html            # Authentication hub
│   ├── about.html              # About News Junction
│   ├── contact.html            # Contact & newsroom desk
│   ├── privacy.html            # Privacy policy & terms
│   └── 404.html                # Error page
│
├── server/                     # Node.js Server Architecture
│   ├── db.js                   # Unified data access layer (MySQL + In-Memory)
│   ├── mockData.js             # Initial dataset & seed models
│   ├── crypto.js               # Password hashing & verification
│   └── routes/
│       ├── auth.js             # /api/auth (login, register, forgot-pwd)
│       ├── reader.js           # /api/reader (feeds, categories, bookmarks)
│       ├── articles.js         # /api/articles (CRUD, comments, leads)
│       ├── channels.js         # /api/channels (channels, streams, follow)
│       ├── newspapers.js       # /api/newspapers (district papers, upload)
│       ├── magazine.js         # /api/magazine (publishers, digest generator)
│       ├── collections.js      # /api/collections (saved list, eSamudaay)
│       ├── analytics.js        # /api/analytics (stats, leads, newsletter)
│       ├── sharemarket.js      # /api/sharemarket (live stock quotes)
│       ├── influencers.js      # /api/influencers (columnists & opinions)
│       ├── templates.js        # /api/templates (landing pages, export)
│       ├── admin.js            # /api/admin (ads CRUD, grievance triage)
│       ├── ads.js              # /api/ads (sponsor delivery & click tracker)
│       ├── complaints.js       # /api/complaints (citizen submissions)
│       └── profile.js          # /api/profile (user bio, settings)
│
├── app/                        # Original PHP code & static media assets
│   ├── images/                 # District logos & UI graphics
│   ├── grfx/                   # Brand logos & artwork
│   └── ...                     # Legacy reference scripts
├── database/                   # Database backup dumps (nj_cream, nj_reader)
├── .env.example                # Sample environment configuration
├── .gitignore                  # Git ignore rules
├── package.json                # Project dependencies & scripts
├── server.js                   # Express application entry point
└── README.md                   # Project documentation
```

---

## ⚙️ Getting Started

### Prerequisites
* **Node.js**: Version 18.0 or higher
* **npm**: Version 8.0 or higher
* *(Optional)* **MySQL**: Version 8.0 (if connecting to local/remote database)

### Installation
1. Clone the repository:
   ```bash
   git clone https://github.com/YOUR_USERNAME/news-junction.git
   cd news-junction
   ```
2. Install npm dependencies:
   ```bash
   npm install
   ```
3. Create your environment configuration:
   ```bash
   cp .env.example .env
   ```
4. Start the server:
   ```bash
   # Production mode
   npm start

   # Development mode with hot-reload
   npm run dev
   ```
5. Open your browser and navigate to:
   ```
   http://localhost:3000
   ```

> **Note on Database**: If MySQL is not running on your machine, the application will automatically start in **resilient in-memory mode** using preloaded seed data. All features (publishing, bookmarking, channels, complaints, analytics) will function normally in memory!

---

## 📡 REST API Summary

| Route Prefix | Resource Description | Key Endpoints |
| :--- | :--- | :--- |
| `/api/auth` | Authentication & Sessions | `POST /login`, `POST /register`, `GET /me`, `POST /logout` |
| `/api/reader` | Feeds & Categorization | `GET /feeds`, `GET /categories`, `POST /bookmark`, `POST /like` |
| `/api/articles` | Story Publishing & Leads | `POST /create`, `GET /:id`, `PUT /:id`, `DELETE /:id`, `POST /:id/lead` |
| `/api/channels` | Community Channels | `GET /`, `POST /`, `GET /:id`, `POST /:id/posts`, `POST /:id/follow` |
| `/api/newspapers` | District E-Papers | `GET /?district=...`, `POST /` |
| `/api/magazine` | Digital Digest Generator | `GET /publishers`, `POST /generate`, `POST /blast` |
| `/api/collections` | Reading Vault & Sharing | `GET /`, `POST /tag`, `POST /esamudaay`, `DELETE /:id` |
| `/api/analytics` | Editorial & Conversion Stats| `GET /overview`, `GET /top-articles`, `GET /leads` |
| `/api/sharemarket` | Indian Stock Quotes | `GET /quotes?live=true` |
| `/api/influencers` | Verified Columnists | `GET /`, `GET /:id`, `POST /:id/follow` |
| `/api/templates` | Lead Builder Templates | `GET /`, `POST /save`, `POST /export` |
| `/api/admin` | Management & Triage | `GET /stats`, `GET /ads`, `POST /ads`, `PUT /complaints/:id` |
| `/api/ads` | Public Sponsored Banners | `GET /?position=...`, `GET /click/:id` |
| `/api/complaints` | Citizen Grievances | `GET /?pincode=...`, `POST /` |

---

## 📜 License
This project is licensed under the ISC License.
