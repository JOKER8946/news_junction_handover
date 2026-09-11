const express = require('express');
const router = express.Router();
const db = require('../db');

function getUserId(req) {
  if (req.session && req.session.userId) return req.session.userId;
  if (req.cookies && req.cookies.knobly_user_data) {
    try {
      const parsed = JSON.parse(req.cookies.knobly_user_data);
      return parsed.userId;
    } catch {}
  }
  return null;
}

/**
 * List all channels with optional search and featured filtering
 */
router.get('/', async (req, res) => {
  const { featured, q } = req.query;
  let channels = await db.getChannels();

  if (featured === 'Y' || featured === '1') {
    channels = channels.filter(c => c.featured_channel === 'Y');
  }

  if (q) {
    const query = q.toLowerCase();
    channels = channels.filter(c => c.name.toLowerCase().includes(query) || (c.bio && c.bio.toLowerCase().includes(query)));
  }

  const userId = getUserId(req);
  const channelsWithFollow = await Promise.all(
    channels.map(async (c) => {
      let isFollowed = false;
      if (userId) {
        isFollowed = await db.isChannelFollowed(userId, c.id);
      }
      return { ...c, isFollowed };
    })
  );

  res.json(channelsWithFollow);
});

/**
 * Get single channel by ID
 */
router.get('/:id', async (req, res) => {
  const id = parseInt(req.params.id, 10);
  const channel = await db.getChannelById(id);
  if (!channel) {
    return res.status(404).json({ error: 'Channel not found' });
  }

  const userId = getUserId(req);
  let isFollowed = false;
  if (userId) {
    isFollowed = await db.isChannelFollowed(userId, id);
  }

  const posts = await db.getChannelPosts(id);

  res.json({
    ...channel,
    isFollowed,
    posts
  });
});

/**
 * Create a new channel
 * Converts logic from app/add_channel.php & handle_channel.php
 */
router.post('/', async (req, res) => {
  const userId = getUserId(req) || 1;
  const { name, bio, profilePic, visibility, featured } = req.body;

  if (!name || !name.trim()) {
    return res.status(400).json({ error: 'Channel name is required' });
  }

  const newChannel = await db.createChannel({
    name: name.trim(),
    bio: bio || '',
    profilePic: profilePic || 'images/district.png',
    visibility: visibility || 'public',
    featured: featured || 'N',
    created_by: userId
  });

  res.status(201).json({ status: 'OK', channel: newChannel });
});

/**
 * Toggle follow/unfollow channel
 * Converts logic from app/follow_action.php
 */
router.post('/:id/follow', async (req, res) => {
  const userId = getUserId(req) || 1;
  const channelId = parseInt(req.params.id, 10);

  const result = await db.toggleFollowChannel(userId, channelId);
  res.json(result);
});

/**
 * Get channel stream posts
 */
router.get('/:id/posts', async (req, res) => {
  const channelId = parseInt(req.params.id, 10);
  const posts = await db.getChannelPosts(channelId);
  res.json(posts);
});

/**
 * Post into channel stream
 * Converts logic from app/streamPush.php & channel.php
 */
router.post('/:id/posts', async (req, res) => {
  const userId = getUserId(req) || 1;
  const channelId = parseInt(req.params.id, 10);
  const { chat, mediaPath } = req.body;

  if (!chat || !chat.trim()) {
    return res.status(400).json({ error: 'Post content cannot be empty' });
  }

  const authorName = (req.session && req.session.userName) || 'News Junction Author';

  const newPost = await db.addChannelPost({
    channel_id: channelId,
    user_id: userId,
    user_name: authorName,
    chat: chat.trim(),
    mediaPath: mediaPath || null
  });

  res.status(201).json({ status: 'OK', post: newPost });
});

module.exports = router;
