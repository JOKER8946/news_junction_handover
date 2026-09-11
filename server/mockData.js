/**
 * Initial seed data matching the MySQL database tables
 * Used both for fallback when MySQL is inactive and for initial population.
 */

const categories = [
  { id: 1, category: 'All News' },
  { id: 2, category: 'District News' },
  { id: 3, category: 'Karnataka' },
  { id: 4, category: 'National' },
  { id: 5, category: 'Business' },
  { id: 6, category: 'Technology' },
  { id: 7, category: 'Entertainment' },
  { id: 8, category: 'Sports' }
];

const users = [
  {
    id: 1,
    full_name: 'Editor In Chief',
    company: 'News Junction Media',
    email: 'editor@newsjunction.net',
    phone_no: '9876543210',
    country_code: '+91',
    pincode: '560001',
    password: '$2a$10$YourHashedPasswordHereOrPlaintextForTest', // will match 'admin123'
    website: 'https://newsjunction.net',
    bio: 'Chief Editor overseeing verified state and district news operations.',
    profile_pic: 'default.png',
    subdomain: 'editor',
    plan: 'Pro',
    role: 'admin',
    is_influencer: 0,
    is_activated: 1,
    num_visits: 42,
    date_created: '2026-01-01 10:00:00'
  },
  {
    id: 2,
    full_name: 'Ramesh Kulkarni',
    company: 'Udupi District Desk',
    email: 'ramesh.udupi@newsjunction.net',
    phone_no: '9845012345',
    country_code: '+91',
    pincode: '576101',
    password: 'password123',
    website: '',
    bio: 'Senior journalist covering coastal Karnataka and Udupi affairs.',
    profile_pic: 'default.png',
    subdomain: 'ramesh',
    plan: 'Pro',
    role: 'reporter',
    is_influencer: 1,
    is_activated: 1,
    num_visits: 28,
    date_created: '2026-01-15 11:30:00'
  },
  {
    id: 3,
    full_name: 'Demo Reader',
    company: 'Citizen Journalist',
    email: 'demo@newsjunction.net',
    phone_no: '9844000000',
    country_code: '+91',
    pincode: '560034',
    password: 'demo',
    website: '',
    bio: 'Avid reader and community contributor from Koramangala.',
    profile_pic: 'default.png',
    subdomain: '',
    plan: 'Free',
    role: 'user',
    is_influencer: 0,
    is_activated: 1,
    num_visits: 12,
    date_created: '2026-02-01 09:15:00'
  }
];

const articles = [
  {
    id: 101,
    title: 'Bengaluru Suburban Rail Corridor 2 Progress Ahead of Schedule',
    description: 'The Baiyappanahalli to Chikkabanavara corridor reaches major milestone with 65% viaduct construction completed. K-RIDE confirms testing will begin by late 2026 with ultra-modern air-conditioned rakes.',
    content: `<p>The long-awaited Bengaluru Suburban Rail Project (BSRP) has achieved a significant milestone along Corridor 2 (Kanaka Line), which connects Baiyappanahalli to Chikkabanavara.</p>
    <p>K-RIDE officials announced on Wednesday that over 65% of the elevated viaducts and track-laying groundwork have been concluded ahead of the targeted quarterly timeline.</p>
    <h3>Enhanced Urban Connectivity</h3>
    <p>The 25.2 km stretch will feature 14 stations providing crucial interchanges with the Namma Metro Purple and Green lines. The initiative aims to relieve traffic congestion across Yeshwanthpur, Hebbal, and the North Bengaluru tech corridor.</p>
    <p>Citizen welfare associations have welcomed the rapid execution and urged the authorities to prioritize non-motorized last-mile connectivity at each hub.</p>`,
    image: 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?w=800&auto=format&fit=crop&q=80',
    category_id: 3,
    category_name: 'Karnataka',
    publisher: 'Deccan Herald',
    url: 'https://deccanherald.com',
    user_id: 1,
    author_name: 'News Junction Bureau',
    district: 'Bengaluru Urban',
    pincode: '560001',
    likes: 42,
    views: 1250,
    date: '2026-03-10 11:30:00'
  },
  {
    id: 102,
    title: 'Udupi Coastal Tourism Boost: New Eco-Walkway Inaugurated at Malpe Beach',
    description: 'District administration launches state-of-the-art mangrove walkway and zero-emission electric buggy facility to encourage sustainable coastal tourism while protecting fragile marine ecology.',
    content: `<p>Malpe Beach in Udupi witnessed the grand opening of the Coastal Eco-Walkway today, attended by local representatives and environmental scholars.</p>
    <p>The project, developed under the Swadesh Darshan initiative, includes a 1.2-kilometer wooden walkway winding through coastal mangrove thickets without disturbing local flora and marine birds.</p>
    <h3>Eco-Friendly Amenities</h3>
    <p>Battery-operated electric buggies will provide accessibility for senior citizens and differently-abled visitors. Automated waste segregation points and solar-powered lighting have been integrated across the stretch.</p>`,
    image: 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=800&auto=format&fit=crop&q=80',
    category_id: 2,
    category_name: 'District News',
    publisher: 'Udayavani',
    url: 'https://udayavani.com',
    user_id: 2,
    author_name: 'Ramesh Kulkarni',
    district: 'Udupi',
    pincode: '576101',
    likes: 38,
    views: 980,
    date: '2026-03-10 09:45:00'
  },
  {
    id: 103,
    title: 'Karnataka Agri-Tech Expo 2026 Opens in Dharwad with Focus on AI Irrigation',
    description: 'Over 200 startups and research scientists showcase smart sensor drones, precision soil moisture monitors, and drought-resilient millets to thousands of regional farmers in North Karnataka.',
    content: `<p>The University of Agricultural Sciences (UAS) campus in Dharwad became the epicentre of farming innovation as the 4-day Agri-Tech Expo 2026 kicked off today.</p>
    <p>Key highlights include autonomous soil-scanning drones, solar pump automation kits, and predictive pest management models tuned specifically to Karnataka climatic zones.</p>`,
    image: 'https://images.unsplash.com/photo-1625246333195-78d9c38ad449?w=800&auto=format&fit=crop&q=80',
    category_id: 2,
    category_name: 'District News',
    publisher: 'Prajavani',
    url: 'https://prajavani.net',
    user_id: 1,
    author_name: 'North Karnataka Bureau',
    district: 'Dharwad',
    pincode: '580005',
    likes: 29,
    views: 810,
    date: '2026-03-09 14:15:00'
  },
  {
    id: 104,
    title: 'Renewable Energy: Pavagada Solar Park Expands with Grid-Scale Battery Storage',
    description: 'Tumakuru Pavagada solar facility integrates 250 MWh battery energy storage systems, ensuring round-the-clock clean power stability for state industrial grids.',
    content: `<p>One of the world\'s premier photovoltaic installations, the Pavagada Solar Park in Tumakuru district, has officially synchronized a next-generation 250 MWh battery energy storage system (BESS).</p>
    <p>This addition ensures that surplus mid-day solar energy can be smoothly dispatched during peak evening industrial demand hours across Bengaluru and surrounding manufacturing belts.</p>`,
    image: 'https://images.unsplash.com/photo-1509391365360-2e959784a276?w=800&auto=format&fit=crop&q=80',
    category_id: 5,
    category_name: 'Business',
    publisher: 'The Hindu',
    url: 'https://thehindu.com',
    user_id: 1,
    author_name: 'Energy Desk',
    district: 'Tumakuru',
    pincode: '572101',
    likes: 54,
    views: 1420,
    date: '2026-03-09 10:00:00'
  },
  {
    id: 105,
    title: 'Hassan Heritage Preservation: Hoysala Temples Receive UNESCO Digital Archiving',
    description: 'High-resolution 3D lidar scanning and digital twin creation underway for Belur, Halebidu, and Somanathapura temple complexes to preserve architectural genius for future generations.',
    content: `<p>Following the UNESCO World Heritage inscription of the Sacred Ensembles of the Hoysalas, the Archeological Survey of India has initiated comprehensive millimeter-precise 3D laser photogrammetry scanning.</p>
    <p>The interactive 3D models will soon be made available through the News Junction Virtual Heritage reader and national digital archives.</p>`,
    image: 'https://images.unsplash.com/photo-1590050752117-238cb0fb12b1?w=800&auto=format&fit=crop&q=80',
    category_id: 3,
    category_name: 'Karnataka',
    publisher: 'Vijaya Karnataka',
    url: 'https://vijaykarnataka.com',
    user_id: 2,
    author_name: 'Heritage Bureau',
    district: 'Hassan',
    pincode: '573201',
    likes: 67,
    views: 1890,
    date: '2026-03-08 16:30:00'
  },
  {
    id: 106,
    title: 'India Wins T20 International Series with Stellar All-Round Performance in Bengaluru',
    description: 'Chinnaswamy stadium erupts in celebration as thrilling final-over defense seals the series 3-1. Sensational fielding and death bowling praised by cricket experts.',
    content: `<p>A packed M. Chinnaswamy Stadium witnessed top-tier cricketing drama as India successfully defended 185 against Australia in the series finale.</p>
    <p>Disciplined bowling in the death overs and two breathtaking boundary catches secured an 11-run victory and clinched the bilateral cup.</p>`,
    image: 'https://images.unsplash.com/photo-1531415074968-036ba1b575da?w=800&auto=format&fit=crop&q=80',
    category_id: 8,
    category_name: 'Sports',
    publisher: 'Times of India',
    url: 'https://timesofindia.indiatimes.com',
    user_id: 1,
    author_name: 'Sports Desk',
    district: 'Bengaluru Urban',
    pincode: '560001',
    likes: 112,
    views: 3400,
    date: '2026-03-08 22:45:00'
  }
];

const channels = [
  {
    id: 1,
    name: 'District Reporters Network',
    profilePic: 'images/district.png',
    visibility: 'public',
    featured_channel: 'Y',
    created_by: 1,
    subscribers: 1420
  },
  {
    id: 2,
    name: 'Coastal Watch & Udupi News',
    profilePic: 'images/udupi.png',
    visibility: 'public',
    featured_channel: 'Y',
    created_by: 2,
    subscribers: 890
  },
  {
    id: 3,
    name: 'Bengaluru Tech & Infrastructure',
    profilePic: 'grfx/images/bengaluru.png',
    visibility: 'public',
    featured_channel: 'Y',
    created_by: 1,
    subscribers: 2310
  },
  {
    id: 4,
    name: 'Citizen Grievance Forum',
    profilePic: 'images/verified.png',
    visibility: 'public',
    featured_channel: 'N',
    created_by: 1,
    subscribers: 670
  }
];

const influencers = [
  {
    id: 2,
    full_name: 'Ramesh Kulkarni',
    profile_pic: 'default.png',
    title: 'Senior Columnist & Coastal Analyst',
    topics: 'Rural Economics, Civic Infrastructure, Heritage'
  },
  {
    id: 4,
    full_name: 'Dr. Ananya Sharma',
    profile_pic: 'default.png',
    title: 'Public Health Policy Advisor',
    topics: 'Community Wellness, Primary Healthcare'
  },
  {
    id: 5,
    full_name: 'Vikram Joshi',
    profile_pic: 'default.png',
    title: 'Tech & Economy Commentator',
    topics: 'Startups, Digital Public Infrastructure'
  }
];

const ads = [
  {
    id: 1,
    title: 'Manikya Market - Artisanal Karnataka Products',
    description: 'Authentic GI-tagged handlooms, sandalwood crafts, and pure spices straight from local artisans.',
    image: 'images/1(1).png',
    ad_link: 'https://newsjunction.net/market',
    position: 'top,feed',
    is_active: 1,
    clicks: 184
  },
  {
    id: 2,
    title: 'News Junction Pro - Digital Publishing Suite',
    description: 'Create, publish, and monetize newsletters with AI assistance and district-level reach.',
    image: 'images/ethical.png',
    ad_link: 'sign-in.html?type=signup',
    position: 'feed',
    is_active: 1,
    clicks: 96
  }
];

const complaints = [
  {
    id: 1,
    pincode: '560034',
    location: 'Koramangala 4th Block, Bengaluru',
    title: 'Water pipe leakage on 17th Main Road',
    description: 'Continuous drinking water leakage from underground pipeline for 3 days causing road erosion and water loss.',
    status: 'In Review',
    submitted_by: 'Demo Reader',
    date: '2026-03-09 10:20:00'
  },
  {
    id: 2,
    pincode: '576101',
    location: 'Near Old Bus Stand, Udupi',
    title: 'Street light failure near public high school',
    description: 'Three consecutive street lights have been dark for a week, posing safety concerns for evening students.',
    status: 'Resolved',
    submitted_by: 'Citizen Reporter',
    date: '2026-03-05 19:10:00'
  }
];

const comments = [
  {
    id: 1,
    article_id: 101,
    user_name: 'Karthik Rao',
    comment: 'Great to see Corridor 2 ahead of schedule. Connecting Baiyappanahalli will ease the ring road load tremendously.',
    date: '2026-03-10 12:15:00'
  },
  {
    id: 2,
    article_id: 101,
    user_name: 'Suma Hegde',
    comment: 'Hope the last-mile bus feeder routes are planned simultaneously before commercial operations begin.',
    date: '2026-03-10 13:40:00'
  },
  {
    id: 3,
    article_id: 102,
    user_name: 'Prashanth Kamath',
    comment: 'Excellent work on Malpe beach. The electric buggy facility is very helpful for elders.',
    date: '2026-03-10 10:30:00'
  }
];

const userCollections = [
  { user_id: 3, article_id: 101 },
  { user_id: 3, article_id: 102 }
];

const userLikes = [
  { user_id: 3, article_id: 101 }
];

const channelPosts = [
  {
    id: 1,
    channel_id: 1,
    user_id: 1,
    user_name: 'Editor In Chief',
    chat: 'Welcome to the District Reporters Network! Ground correspondents across all 31 districts can post verified regional updates and ground dispatches directly here.',
    postedOn: '2026-03-10 14:00:00',
    mediaPath: 'images/district.png',
    likes: 18
  },
  {
    id: 2,
    channel_id: 2,
    user_id: 2,
    user_name: 'Ramesh Kulkarni',
    chat: 'High tide alert along Kaup and Malpe coastlines issued for fishermen over the next 48 hours. District Disaster Management Authority advises caution.',
    postedOn: '2026-03-10 16:30:00',
    mediaPath: 'images/udupi.png',
    likes: 24
  },
  {
    id: 3,
    channel_id: 3,
    user_id: 1,
    user_name: 'Editor In Chief',
    chat: 'BMRCL initiates trial runs for the Pink Line underground segment between Dairy Circle and Nagawara. Commercial readiness slated for Q4 2026.',
    postedOn: '2026-03-09 18:20:00',
    mediaPath: 'grfx/images/bengaluru.png',
    likes: 31
  }
];

const newspapers = [
  {
    id: 1,
    title: 'Bengaluru Express Morning Digest',
    district: 'Bengaluru Urban',
    publisher: 'News Junction Metro',
    edition_date: '2026-03-10',
    pages: 12,
    thumbnail: 'images/belagavi.png',
    pdf_url: '/data/papers/bengaluru_morning.pdf',
    uploaded_by: 1
  },
  {
    id: 2,
    title: 'Karavali Coastal Chronicle',
    district: 'Udupi',
    publisher: 'Coastal Media Trust',
    edition_date: '2026-03-10',
    pages: 8,
    thumbnail: 'images/udupi.png',
    pdf_url: '/data/papers/udupi_chronicle.pdf',
    uploaded_by: 2
  },
  {
    id: 3,
    title: 'Mysuru Heritage Times',
    district: 'Mysuru',
    publisher: 'Royal City News',
    edition_date: '2026-03-09',
    pages: 10,
    thumbnail: 'images/mysore.png',
    pdf_url: '/data/papers/mysuru_times.pdf',
    uploaded_by: 1
  },
  {
    id: 4,
    title: 'Hassan Malnad Bulletin',
    district: 'Hassan',
    publisher: 'Malnad Publications',
    edition_date: '2026-03-09',
    pages: 6,
    thumbnail: 'images/hassan.png',
    pdf_url: '/data/papers/hassan_bulletin.pdf',
    uploaded_by: 1
  },
  {
    id: 5,
    title: 'Kittur Karnataka Vani',
    district: 'Belagavi',
    publisher: 'Belagavi Press Syndicate',
    edition_date: '2026-03-08',
    pages: 8,
    thumbnail: 'images/bellari.png',
    pdf_url: '/data/papers/belagavi_vani.pdf',
    uploaded_by: 2
  }
];

const leads = [
  {
    id: 1,
    article_id: 104,
    article_title: 'Renewable Energy: Pavagada Solar Park Expands with Grid-Scale Battery Storage',
    lead_name: 'Naveen Rao',
    lead_email: 'naveen.rao@cleantech.in',
    lead_company: 'Karnataka CleanTech Solutions',
    lead_mobile: '9845098765',
    date_captured: '2026-03-09 15:40:00'
  },
  {
    id: 2,
    article_id: 101,
    article_title: 'Bengaluru Suburban Rail Corridor 2 Progress Ahead of Schedule',
    lead_name: 'Priya Sundaram',
    lead_email: 'priya.sundaram@urbaninfra.org',
    lead_company: 'Urban Mobility Forum',
    lead_mobile: '9880123456',
    date_captured: '2026-03-10 11:55:00'
  }
];

const templates = [
  {
    id: 1,
    name: 'Modern News Lead Page',
    heroTitle: 'Get Verified Karnataka News Direct to Your Inbox',
    heroSubtitle: 'Join 50,000+ conscious readers who rely on uncompromised grassroots journalism every morning.',
    ctaText: 'Subscribe Now For Free',
    primaryColor: '#b00000',
    theme: 'light',
    date_created: '2026-02-20'
  },
  {
    id: 2,
    name: 'Business & Agriculture Expo Lead',
    heroTitle: 'Karnataka Agri-Tech & Innovation Report 2026',
    heroSubtitle: 'Download our comprehensive 40-page dossier on precision farming, soil sensing, and agri subsidies.',
    ctaText: 'Download Free Whitepaper',
    primaryColor: '#10b981',
    theme: 'corporate',
    date_created: '2026-03-01'
  }
];

const publishers = [
  'Prajavani',
  'Udayavani',
  'Deccan Herald',
  'The Hindu',
  'Vijaya Karnataka',
  'Kannada Prabha',
  'Times of India',
  'Samyuktha Karnataka'
];

module.exports = {
  categories,
  users,
  articles,
  channels,
  channelPosts,
  influencers,
  ads,
  complaints,
  comments,
  userCollections,
  userLikes,
  newspapers,
  leads,
  templates,
  publishers
};
