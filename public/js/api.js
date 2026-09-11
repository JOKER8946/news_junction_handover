/**
 * News Junction - API Client
 * Clean modern Fetch wrapper
 */

const API = {
  baseUrl: '',

  async request(endpoint, options = {}) {
    const url = `${this.baseUrl}${endpoint}`;
    const defaultHeaders = {
      'Accept': 'application/json'
    };

    if (!(options.body instanceof FormData)) {
      defaultHeaders['Content-Type'] = 'application/json';
    }

    const config = {
      ...options,
      headers: {
        ...defaultHeaders,
        ...options.headers
      }
    };

    try {
      const res = await fetch(url, config);
      const text = await res.text();
      let data;
      try {
        data = JSON.parse(text);
      } catch {
        data = text;
      }

      if (!res.ok) {
        const errorMsg = (data && data.message) || (data && data.error) || (typeof data === 'string' ? data : 'Request failed');
        throw new Error(errorMsg);
      }

      return data;
    } catch (err) {
      console.error(`API Error [${endpoint}]:`, err);
      throw err;
    }
  },

  get(endpoint, params = {}) {
    const query = new URLSearchParams(params).toString();
    const url = query ? `${endpoint}?${query}` : endpoint;
    return this.request(url, { method: 'GET' });
  },

  post(endpoint, body) {
    const isFormData = body instanceof FormData;
    return this.request(endpoint, {
      method: 'POST',
      body: isFormData ? body : JSON.stringify(body)
    });
  },

  put(endpoint, body) {
    return this.request(endpoint, {
      method: 'PUT',
      body: JSON.stringify(body)
    });
  },

  // Auth Endpoints
  auth: {
    checkSession() {
      return API.get('/api/auth/me');
    },
    login(email, password) {
      return API.post('/api/auth/login', { email, pwd: password });
    },
    register(userData) {
      return API.post('/api/auth/register', userData);
    },
    forgotPassword(email) {
      return API.post('/api/auth/forgot-password', { email });
    },
    resetPassword(token, password) {
      return API.post('/api/auth/reset-password-confirm', { token, password });
    },
    logout() {
      return API.post('/api/auth/logout', {});
    }
  },

  // Reader Endpoints
  reader: {
    getFeeds(params = {}) {
      return API.get('/api/reader/feeds', params);
    },
    getCategories() {
      return API.get('/api/reader/categories');
    },
    getChannels() {
      return API.get('/api/reader/channels');
    },
    getOpinions() {
      return API.get('/api/reader/opinions');
    },
    getSaved() {
      return API.get('/api/reader/saved');
    },
    toggleBookmark(articleId) {
      return API.post('/api/reader/bookmark', { articleId });
    },
    toggleLike(articleId) {
      return API.post('/api/reader/like', { articleId });
    }
  },

  // Articles Endpoints
  articles: {
    getById(id) {
      return API.get(`/api/articles/${id}`);
    },
    create(data) {
      return API.post('/api/articles/create', data);
    },
    update(id, data) {
      return API.put(`/api/articles/${id}`, data);
    },
    delete(id) {
      return API.request(`/api/articles/${id}`, { method: 'DELETE' });
    },
    getMy() {
      return API.get('/api/articles/author/my');
    },
    getScheduled() {
      return API.get('/api/articles/author/scheduled');
    },
    getComments(id) {
      return API.get(`/api/articles/${id}/comments`);
    },
    addComment(id, comment, userName) {
      return API.post(`/api/articles/${id}/comments`, { comment, userName });
    },
    submitLead(id, leadData) {
      return API.post(`/api/articles/${id}/lead`, leadData);
    }
  },

  // Channels
  channels: {
    list(params = {}) {
      return API.get('/api/channels', params);
    },
    getById(id) {
      return API.get(`/api/channels/${id}`);
    },
    create(data) {
      return API.post('/api/channels', data);
    },
    toggleFollow(id) {
      return API.post(`/api/channels/${id}/follow`, {});
    },
    getPosts(id) {
      return API.get(`/api/channels/${id}/posts`);
    },
    addPost(id, data) {
      return API.post(`/api/channels/${id}/posts`, data);
    }
  },

  // District Newspapers
  newspapers: {
    list(district = '') {
      return API.get('/api/newspapers', district ? { district } : {});
    },
    submit(data) {
      return API.post('/api/newspapers', data);
    }
  },

  // Digital Magazine
  magazine: {
    getPublishers() {
      return API.get('/api/magazine/publishers');
    },
    generate(options) {
      return API.post('/api/magazine/generate', options);
    },
    blast(blastData) {
      return API.post('/api/magazine/blast', blastData);
    }
  },

  // Collections & Bookmarks
  collections: {
    list() {
      return API.get('/api/collections');
    },
    tag(articleId, tag) {
      return API.post('/api/collections/tag', { articleId, tag });
    },
    shareEsamudaay(articleId) {
      return API.post('/api/collections/esamudaay', { articleId });
    },
    remove(articleId) {
      return API.request(`/api/collections/${articleId}`, { method: 'DELETE' });
    }
  },

  // Analytics
  analytics: {
    getOverview() {
      return API.get('/api/analytics/overview');
    },
    getTopArticles() {
      return API.get('/api/analytics/top-articles');
    },
    getLeads() {
      return API.get('/api/analytics/leads');
    },
    getNewsletterStats() {
      return API.get('/api/analytics/newsletter-stats');
    }
  },

  // Share Market
  sharemarket: {
    getQuotes(live = false) {
      return API.get('/api/sharemarket/quotes', live ? { live: 'true' } : {});
    }
  },

  // Influencers & Columnists
  influencers: {
    list() {
      return API.get('/api/influencers');
    },
    getById(id) {
      return API.get(`/api/influencers/${id}`);
    },
    toggleFollow(id) {
      return API.post(`/api/influencers/${id}/follow`, {});
    }
  },

  // Templates
  templates: {
    list() {
      return API.get('/api/templates');
    },
    getById(id) {
      return API.get(`/api/templates/${id}`);
    },
    save(data) {
      return API.post('/api/templates/save', data);
    },
    export(data) {
      return API.post('/api/templates/export', data);
    }
  },

  // Admin
  admin: {
    getStats() {
      return API.get('/api/admin/stats');
    },
    getAds() {
      return API.get('/api/admin/ads');
    },
    saveAd(data) {
      return API.post('/api/admin/ads', data);
    },
    deleteAd(id) {
      return API.request(`/api/admin/ads/${id}`, { method: 'DELETE' });
    },
    getComplaints(params = {}) {
      return API.get('/api/admin/complaints', params);
    },
    updateComplaint(id, data) {
      return API.put(`/api/admin/complaints/${id}`, data);
    },
    getUsers() {
      return API.get('/api/admin/users');
    }
  },

  // Ads
  ads: {
    get(position = 'feed') {
      return API.get('/api/ads', { position });
    }
  },

  // Complaints
  complaints: {
    get(pincode = null) {
      return API.get('/api/complaints', pincode ? { pincode } : {});
    },
    submit(data) {
      return API.post('/api/complaints', data);
    }
  },

  // Profile
  profile: {
    get(id = null) {
      return API.get(id ? `/api/profile/${id}` : '/api/profile');
    },
    update(data) {
      return API.put('/api/profile', data);
    }
  }
};

window.API = API;
