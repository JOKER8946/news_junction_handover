const mysql = require('mysql2/promise');
const mock = require('./mockData');
const { verifyPassword, hashPassword } = require('./crypto');
require('dotenv').config();

// Attempt to configure MySQL pools
let pool = null;
let useDatabase = false;

const dbConfig = {
  host: process.env.DB_HOST || 'localhost',
  port: parseInt(process.env.DB_PORT || '3306', 10),
  user: process.env.DB_USERNAME || 'YOUR_DB_USER',
  password: process.env.DB_PASSWORD || 'YOUR_DB_PASSWORD',
  database: 'nj_cream',
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0
};

async function initDb() {
  try {
    const testConn = await mysql.createConnection({
      host: dbConfig.host,
      port: dbConfig.port,
      user: dbConfig.user,
      password: dbConfig.password,
      database: dbConfig.database,
      connectTimeout: 2000
    });
    await testConn.ping();
    await testConn.end();

    pool = mysql.createPool(dbConfig);
    useDatabase = true;
    console.log('[DB] Connected successfully to MySQL database nj_cream.');
  } catch (err) {
    useDatabase = false;
    console.log('[DB] MySQL not available (' + err.code + '). Using resilient in-memory database engine.');
  }
}

// In-memory data store for fallback
const store = {
  users: [...mock.users],
  articles: [...mock.articles],
  categories: [...mock.categories],
  channels: [...mock.channels],
  channelPosts: [...mock.channelPosts],
  influencers: [...mock.influencers],
  ads: [...mock.ads],
  complaints: [...mock.complaints],
  comments: [...mock.comments],
  userCollections: [...mock.userCollections],
  userLikes: [...mock.userLikes],
  newspapers: [...mock.newspapers],
  leads: [...mock.leads],
  templates: [...mock.templates],
  publishers: [...mock.publishers],
  channelFollows: [],
  userLogins: [],
  sessionLogs: []
};

// Database queries abstraction
const db = {
  isUsingDatabase() {
    return useDatabase;
  },

  async findUserByEmail(email) {
    if (useDatabase && pool) {
      try {
        const [rows] = await pool.query(
          'SELECT id, full_name, subdomain, plan, is_activated, pincode, password, company, role, email FROM user WHERE email = ? AND is_deleted IS NULL LIMIT 1',
          [email]
        );
        return rows[0] || null;
      } catch (err) {
        console.error('findUserByEmail MySQL error:', err.message);
      }
    }
    return store.users.find(u => u.email.toLowerCase() === email.toLowerCase()) || null;
  },

  async findUserById(id) {
    const numId = parseInt(id, 10);
    if (useDatabase && pool) {
      try {
        const [rows] = await pool.query(
          'SELECT id, full_name, company, email, phone_no, country_code, pincode, website, bio, profile_pic, subdomain, plan, role, is_influencer, is_activated FROM user WHERE id = ? LIMIT 1',
          [numId]
        );
        return rows[0] || null;
      } catch (err) {
        console.error('findUserById MySQL error:', err.message);
      }
    }
    return store.users.find(u => u.id === numId) || null;
  },

  async createUser(userData) {
    const {
      fullName,
      company = '',
      email,
      phone = '',
      countryCode = '+91',
      pincode = '',
      password,
      website = '',
      businessType = 0
    } = userData;

    const hashedPassword = hashPassword(password);

    if (useDatabase && pool) {
      try {
        const [result] = await pool.query(
          `INSERT INTO user(full_name, company, email, phone_no, country_code, pincode, password, website, num_visits, date_created) 
           VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())`,
          [fullName, company, email, phone, countryCode, pincode, hashedPassword, website]
        );
        return {
          id: result.insertId,
          full_name: fullName,
          email,
          company,
          pincode,
          plan: 'Free'
        };
      } catch (err) {
        console.error('createUser MySQL error:', err.message);
        throw err;
      }
    }

    const newId = store.users.length ? Math.max(...store.users.map(u => u.id)) + 1 : 1;
    const newUser = {
      id: newId,
      full_name: fullName,
      company,
      email,
      phone_no: phone,
      country_code: countryCode,
      pincode,
      password: hashedPassword,
      website,
      bio: '',
      profile_pic: 'default.png',
      subdomain: '',
      plan: 'Free',
      role: 'user',
      is_influencer: 0,
      is_activated: 1,
      num_visits: 1,
      date_created: new Date().toISOString()
    };
    store.users.push(newUser);
    return newUser;
  },

  async updateUser(id, updates) {
    const numId = parseInt(id, 10);
    if (useDatabase && pool) {
      try {
        await pool.query(
          `UPDATE user 
           SET full_name = ?, email = ?, phone_no = ?, country_code = ?, company = ?, website = ?, bio = ?, pincode = ?, date_modified = NOW()
           WHERE id = ?`,
          [
            updates.fullName,
            updates.email,
            updates.phone,
            updates.countryCode,
            updates.company,
            updates.website,
            updates.bio,
            updates.pincode,
            numId
          ]
        );
      } catch (err) {
        console.error('updateUser MySQL error:', err.message);
      }
    }

    const user = store.users.find(u => u.id === numId);
    if (user) {
      if (updates.fullName !== undefined) user.full_name = updates.fullName;
      if (updates.email !== undefined) user.email = updates.email;
      if (updates.phone !== undefined) user.phone_no = updates.phone;
      if (updates.countryCode !== undefined) user.country_code = updates.countryCode;
      if (updates.company !== undefined) user.company = updates.company;
      if (updates.website !== undefined) user.website = updates.website;
      if (updates.bio !== undefined) user.bio = updates.bio;
      if (updates.pincode !== undefined) user.pincode = updates.pincode;
      return user;
    }
    return null;
  },

  async recordLogin(userId, ip, sessionId) {
    const numId = parseInt(userId, 10);
    if (useDatabase && pool) {
      try {
        await pool.query('UPDATE user SET num_visits = num_visits + 1 WHERE id = ?', [numId]);
        await pool.query('INSERT INTO user_login(user_id, ip, date_login) VALUES(?, ?, NOW())', [numId, ip]);
        if (sessionId) {
          await pool.query('INSERT INTO session_log(userId, sessionId, startTime) VALUES(?, ?, NOW())', [numId, sessionId]);
        }
      } catch (err) {
        console.error('recordLogin MySQL error:', err.message);
      }
    }

    const user = store.users.find(u => u.id === numId);
    if (user) user.num_visits = (user.num_visits || 0) + 1;
    store.userLogins.push({ userId: numId, ip, date: new Date().toISOString() });
    if (sessionId) {
      store.sessionLogs.push({ userId: numId, sessionId, startTime: new Date().toISOString() });
    }
  },

  async updatePassword(email, newPlainPassword) {
    const hashed = hashPassword(newPlainPassword);
    if (useDatabase && pool) {
      try {
        await pool.query('UPDATE user SET password = ? WHERE email = ?', [hashed, email]);
      } catch (err) {
        console.error('updatePassword MySQL error:', err.message);
      }
    }
    const user = store.users.find(u => u.email.toLowerCase() === email.toLowerCase());
    if (user) {
      user.password = hashed;
      return true;
    }
    return false;
  },

  async getArticles({ categoryId, district, search, limit = 20, offset = 0 } = {}) {
    let list = [...store.articles];

    if (categoryId && parseInt(categoryId, 10) > 1) {
      const catId = parseInt(categoryId, 10);
      list = list.filter(a => a.category_id === catId);
    }

    if (district) {
      const d = district.toLowerCase();
      list = list.filter(a => a.district && a.district.toLowerCase().includes(d));
    }

    if (search) {
      const s = search.toLowerCase();
      list = list.filter(a =>
        (a.title && a.title.toLowerCase().includes(s)) ||
        (a.description && a.description.toLowerCase().includes(s)) ||
        (a.author_name && a.author_name.toLowerCase().includes(s)) ||
        (a.publisher && a.publisher.toLowerCase().includes(s))
      );
    }

    list.sort((a, b) => new Date(b.date) - new Date(a.date));
    const total = list.length;
    const paginated = list.slice(offset, offset + limit);

    return { total, articles: paginated };
  },

  async getArticleById(id) {
    const numId = parseInt(id, 10);
    const article = store.articles.find(a => a.id === numId);
    if (article) {
      article.views = (article.views || 0) + 1;
    }
    return article || null;
  },

  async toggleLike(userId, articleId) {
    const uId = parseInt(userId, 10);
    const aId = parseInt(articleId, 10);

    const existingIndex = store.userLikes.findIndex(l => l.user_id === uId && l.article_id === aId);
    const article = store.articles.find(a => a.id === aId);

    if (existingIndex > -1) {
      store.userLikes.splice(existingIndex, 1);
      if (article && article.likes > 0) article.likes -= 1;
      return { liked: false, likes: article ? article.likes : 0 };
    } else {
      store.userLikes.push({ user_id: uId, article_id: aId });
      if (article) article.likes = (article.likes || 0) + 1;
      return { liked: true, likes: article ? article.likes : 1 };
    }
  },

  async toggleBookmark(userId, articleId) {
    const uId = parseInt(userId, 10);
    const aId = parseInt(articleId, 10);

    const existingIndex = store.userCollections.findIndex(c => c.user_id === uId && c.article_id === aId);
    if (existingIndex > -1) {
      store.userCollections.splice(existingIndex, 1);
      return { bookmarked: false };
    } else {
      store.userCollections.push({ user_id: uId, article_id: aId, date_created: new Date().toISOString() });
      return { bookmarked: true };
    }
  },

  async getUserCollections(userId) {
    const uId = parseInt(userId, 10);
    const savedIds = store.userCollections.filter(c => c.user_id === uId).map(c => c.article_id);
    return store.articles.filter(a => savedIds.includes(a.id));
  },

  async isArticleBookmarked(userId, articleId) {
    const uId = parseInt(userId, 10);
    const aId = parseInt(articleId, 10);
    return store.userCollections.some(c => c.user_id === uId && c.article_id === aId);
  },

  async isArticleLiked(userId, articleId) {
    const uId = parseInt(userId, 10);
    const aId = parseInt(articleId, 10);
    return store.userLikes.some(l => l.user_id === uId && l.article_id === aId);
  },

  async getComments(articleId) {
    const aId = parseInt(articleId, 10);
    return store.comments.filter(c => c.article_id === aId).sort((a, b) => new Date(b.date) - new Date(a.date));
  },

  async addComment(articleId, userName, comment) {
    const aId = parseInt(articleId, 10);
    const newComment = {
      id: store.comments.length + 1,
      article_id: aId,
      user_name: userName || 'Reader',
      comment,
      date: new Date().toISOString().slice(0, 19).replace('T', ' ')
    };
    store.comments.unshift(newComment);
    return newComment;
  },

  async getChannels() {
    return store.channels;
  },

  async getInfluencers() {
    return store.influencers;
  },

  async getCategories() {
    return store.categories;
  },

  async getAds(position = 'feed') {
    return store.ads.filter(a => a.is_active && a.position.includes(position));
  },

  async recordAdClick(adId, userId = null) {
    const ad = store.ads.find(a => a.id === parseInt(adId, 10));
    if (ad) {
      ad.clicks = (ad.clicks || 0) + 1;
      return ad.ad_link;
    }
    return '/';
  },

  async getComplaints(pincode = null) {
    let list = [...store.complaints];
    if (pincode) {
      list = list.filter(c => c.pincode === pincode);
    }
    return list;
  },

  async addComplaint({ pincode, location, title, description, submittedBy }) {
    const newComplaint = {
      id: store.complaints.length + 1,
      pincode,
      location,
      title,
      description,
      status: 'Submitted',
      submitted_by: submittedBy || 'Citizen',
      date: new Date().toISOString().slice(0, 19).replace('T', ' ')
    };
    store.complaints.unshift(newComplaint);
    return newComplaint;
  },

  async updateComplaintStatus(id, { status, adminResponse }) {
    const complaint = store.complaints.find(c => c.id === parseInt(id, 10));
    if (complaint) {
      if (status) complaint.status = status;
      if (adminResponse) complaint.admin_response = adminResponse;
      complaint.updated_at = new Date().toISOString().slice(0, 19).replace('T', ' ');
      return complaint;
    }
    return null;
  },

  // Article creation and management
  async createArticle(data) {
    const newId = store.articles.length ? Math.max(...store.articles.map(a => a.id)) + 1 : 101;
    const newArticle = {
      id: newId,
      title: data.title,
      description: data.description || (data.content ? data.content.replace(/<[^>]*>/g, '').slice(0, 150) + '...' : ''),
      content: data.content,
      image: data.image || '/images/default-news.jpg',
      category_id: parseInt(data.category_id || data.categoryId || 1, 10),
      category_name: data.category_name || 'General',
      publisher: data.publisher || 'News Junction Direct',
      url: data.url || `/article.html?id=${newId}`,
      user_id: parseInt(data.user_id || data.userId || 1, 10),
      author_name: data.author_name || data.author || 'Contributor',
      district: data.district || 'Karnataka',
      pincode: data.pincode || '',
      likes: 0,
      views: 0,
      is_archive: data.isArchive ? 1 : 0,
      is_read_more: data.isReadMore ? 1 : 0,
      read_more_txt: data.readMoreTxt || '',
      read_more_response: data.readMoreResponse || '',
      read_more_email: data.readMoreEmail || '',
      is_mandatory_company: data.isMandatoryCompany ? 1 : 0,
      is_mandatory_email: data.isMandatoryEmail ? 1 : 0,
      is_mandatory_mobile: data.isMandatoryMobile ? 1 : 0,
      scheduled_for: data.scheduledFor || null,
      status: data.scheduledFor ? 'scheduled' : 'published',
      tags: Array.isArray(data.tags) ? data.tags : (data.tags ? data.tags.split(',').map(t => t.trim()) : []),
      date: data.scheduledFor || new Date().toISOString().slice(0, 19).replace('T', ' ')
    };
    store.articles.unshift(newArticle);
    return newArticle;
  },

  async updateArticle(id, data) {
    const numId = parseInt(id, 10);
    const index = store.articles.findIndex(a => a.id === numId);
    if (index === -1) return null;
    const existing = store.articles[index];
    const updated = {
      ...existing,
      ...data,
      id: numId,
      date_modified: new Date().toISOString()
    };
    store.articles[index] = updated;
    return updated;
  },

  async deleteArticle(id) {
    const numId = parseInt(id, 10);
    const index = store.articles.findIndex(a => a.id === numId);
    if (index !== -1) {
      const deleted = store.articles.splice(index, 1);
      return deleted[0];
    }
    return null;
  },

  async getMyArticles(userId) {
    const numId = parseInt(userId, 10);
    return store.articles.filter(a => a.user_id === numId);
  },

  async getScheduledArticles(userId = null) {
    let list = store.articles.filter(a => a.status === 'scheduled');
    if (userId) {
      list = list.filter(a => a.user_id === parseInt(userId, 10));
    }
    return list;
  },

  // Channels
  async getChannelById(id) {
    return store.channels.find(c => c.id === parseInt(id, 10)) || null;
  },

  async createChannel(channelData) {
    const newId = store.channels.length ? Math.max(...store.channels.map(c => c.id)) + 1 : 1;
    const newChannel = {
      id: newId,
      name: channelData.name,
      profilePic: channelData.profilePic || 'images/district.png',
      bio: channelData.bio || '',
      visibility: channelData.visibility || 'public',
      featured_channel: channelData.featured || 'N',
      created_by: parseInt(channelData.created_by || 1, 10),
      subscribers: 1
    };
    store.channels.push(newChannel);
    return newChannel;
  },

  async toggleFollowChannel(userId, channelId) {
    const uId = parseInt(userId, 10);
    const cId = parseInt(channelId, 10);
    const followIndex = store.channelFollows.findIndex(f => f.user_id === uId && f.channel_id === cId);
    const channel = store.channels.find(c => c.id === cId);

    if (followIndex > -1) {
      store.channelFollows.splice(followIndex, 1);
      if (channel && channel.subscribers > 0) channel.subscribers -= 1;
      return { following: false, subscribers: channel ? channel.subscribers : 0 };
    } else {
      store.channelFollows.push({ user_id: uId, channel_id: cId });
      if (channel) channel.subscribers = (channel.subscribers || 0) + 1;
      return { following: true, subscribers: channel ? channel.subscribers : 1 };
    }
  },

  async isChannelFollowed(userId, channelId) {
    const uId = parseInt(userId, 10);
    const cId = parseInt(channelId, 10);
    return store.channelFollows.some(f => f.user_id === uId && f.channel_id === cId);
  },

  async getChannelPosts(channelId) {
    const cId = parseInt(channelId, 10);
    return store.channelPosts
      .filter(p => p.channel_id === cId)
      .sort((a, b) => new Date(b.postedOn) - new Date(a.postedOn));
  },

  async addChannelPost({ channel_id, user_id, user_name, chat, mediaPath }) {
    const newPost = {
      id: store.channelPosts.length + 1,
      channel_id: parseInt(channel_id, 10),
      user_id: parseInt(user_id || 1, 10),
      user_name: user_name || 'Contributor',
      chat,
      mediaPath: mediaPath || null,
      likes: 0,
      postedOn: new Date().toISOString().slice(0, 19).replace('T', ' ')
    };
    store.channelPosts.unshift(newPost);
    return newPost;
  },

  // District Newspapers
  async getNewspapers(district = null) {
    let list = [...store.newspapers];
    if (district && district !== 'all') {
      const d = district.toLowerCase();
      list = list.filter(n => n.district.toLowerCase() === d);
    }
    return list;
  },

  async addNewspaper(newspaperData) {
    const newId = store.newspapers.length ? Math.max(...store.newspapers.map(n => n.id)) + 1 : 1;
    const newPaper = {
      id: newId,
      title: newspaperData.title,
      district: newspaperData.district,
      publisher: newspaperData.publisher || 'Regional Desk',
      edition_date: newspaperData.edition_date || new Date().toISOString().slice(0, 10),
      pages: parseInt(newspaperData.pages || 8, 10),
      thumbnail: newspaperData.thumbnail || 'images/belagavi.png',
      pdf_url: newspaperData.pdf_url || '#',
      uploaded_by: parseInt(newspaperData.uploaded_by || 1, 10)
    };
    store.newspapers.unshift(newPaper);
    return newPaper;
  },

  // Magazine & Publishers
  async getPublishers() {
    return store.publishers;
  },

  async generateMagazine({ publishers = [], categories = [], limit = 12 }) {
    let list = [...store.articles];
    if (publishers.length > 0) {
      list = list.filter(a => publishers.some(p => a.publisher && a.publisher.toLowerCase().includes(p.toLowerCase())));
    }
    if (categories.length > 0) {
      list = list.filter(a => categories.includes(a.category_id) || categories.includes(a.category_name));
    }
    return list.slice(0, limit);
  },

  // Collections tagging & eSamudaay
  async tagCollection(userId, articleId, tag) {
    const uId = parseInt(userId, 10);
    const aId = parseInt(articleId, 10);
    const item = store.userCollections.find(c => c.user_id === uId && c.article_id === aId);
    if (item) {
      item.tag = tag;
      return { success: true, item };
    }
    return { success: false, error: 'Item not in collections' };
  },

  async shareToEsamudaay(userId, articleId) {
    const uId = parseInt(userId, 10);
    const aId = parseInt(articleId, 10);
    const item = store.userCollections.find(c => c.user_id === uId && c.article_id === aId);
    if (item) {
      item.esamudaay = 1;
      item.shared_at = new Date().toISOString();
      return { success: true, shared: true };
    }
    return { success: false, error: 'Article must be saved before sharing' };
  },

  // Analytics & Leads
  async getAnalyticsOverview() {
    const totalArticles = store.articles.length;
    const totalViews = store.articles.reduce((acc, a) => acc + (a.views || 0), 0);
    const totalLikes = store.articles.reduce((acc, a) => acc + (a.likes || 0), 0);
    const totalComplaints = store.complaints.length;
    const resolvedComplaints = store.complaints.filter(c => c.status === 'Resolved').length;
    const totalLeads = store.leads.length;
    const totalUsers = store.users.length;
    const totalChannels = store.channels.length;

    return {
      totalArticles,
      totalViews,
      totalLikes,
      totalComplaints,
      resolvedComplaints,
      totalLeads,
      totalUsers,
      totalChannels,
      recentActivity: [
        { type: 'article_view', text: '54 new readers viewed Bengaluru Rail project article', time: '10 mins ago' },
        { type: 'lead_captured', text: 'New lead generated from Clean Energy dossier', time: '45 mins ago' },
        { type: 'complaint_filed', text: 'Citizen registered water leakage grievance in Koramangala', time: '2 hours ago' },
        { type: 'newsletter_blast', text: 'Morning digest opened by 84% of subscribers', time: '4 hours ago' }
      ]
    };
  },

  async getTopArticles(limit = 5) {
    return [...store.articles].sort((a, b) => (b.views || 0) - (a.views || 0)).slice(0, limit);
  },

  async getLeads() {
    return store.leads;
  },

  async recordLead(leadData) {
    const newId = store.leads.length ? Math.max(...store.leads.map(l => l.id)) + 1 : 1;
    const newLead = {
      id: newId,
      article_id: leadData.article_id,
      article_title: leadData.article_title || 'Featured Article',
      lead_name: leadData.lead_name || leadData.name,
      lead_email: leadData.lead_email || leadData.email,
      lead_company: leadData.lead_company || leadData.company || 'N/A',
      lead_mobile: leadData.lead_mobile || leadData.mobile || 'N/A',
      date_captured: new Date().toISOString().slice(0, 19).replace('T', ' ')
    };
    store.leads.unshift(newLead);
    return newLead;
  },

  // Templates
  async getTemplates() {
    return store.templates;
  },

  async getTemplateById(id) {
    return store.templates.find(t => t.id === parseInt(id, 10)) || null;
  },

  async saveTemplate(tplData) {
    const newId = store.templates.length ? Math.max(...store.templates.map(t => t.id)) + 1 : 1;
    const newTemplate = {
      id: newId,
      name: tplData.name || 'Untitled Landing Page',
      heroTitle: tplData.heroTitle,
      heroSubtitle: tplData.heroSubtitle,
      ctaText: tplData.ctaText || 'Submit',
      primaryColor: tplData.primaryColor || '#b00000',
      theme: tplData.theme || 'light',
      fields: tplData.fields || ['name', 'email', 'mobile'],
      date_created: new Date().toISOString().slice(0, 10)
    };
    store.templates.push(newTemplate);
    return newTemplate;
  },

  // Admin Ads & Users
  async getAllAds() {
    return store.ads;
  },

  async createOrUpdateAd(adData) {
    if (adData.id) {
      const numId = parseInt(adData.id, 10);
      const index = store.ads.findIndex(a => a.id === numId);
      if (index > -1) {
        store.ads[index] = { ...store.ads[index], ...adData, id: numId };
        return store.ads[index];
      }
    }
    const newId = store.ads.length ? Math.max(...store.ads.map(a => a.id)) + 1 : 1;
    const newAd = {
      id: newId,
      title: adData.title,
      description: adData.description || '',
      image: adData.image || 'images/ethical.png',
      ad_link: adData.ad_link || '/',
      position: adData.position || 'feed',
      is_active: adData.is_active !== undefined ? (adData.is_active ? 1 : 0) : 1,
      clicks: 0
    };
    store.ads.push(newAd);
    return newAd;
  },

  async deleteAd(id) {
    const numId = parseInt(id, 10);
    const index = store.ads.findIndex(a => a.id === numId);
    if (index > -1) {
      return store.ads.splice(index, 1)[0];
    }
    return null;
  },

  async getAllUsers() {
    return store.users.map(({ password, ...rest }) => rest);
  }
};

initDb();

module.exports = db;
