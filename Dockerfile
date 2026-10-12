FROM node:24-alpine AS build
RUN apk add --no-cache openssl
WORKDIR /app
COPY package*.json ./
COPY prisma ./prisma
RUN npm ci
COPY index.html vite.config.mjs ./
COPY src ./src
COPY public ./public
RUN npm run build
RUN npm prune --omit=dev --ignore-scripts
FROM node:24-alpine
RUN apk add --no-cache openssl
WORKDIR /app
ENV NODE_ENV=production
COPY --from=build /app/node_modules ./node_modules
COPY package*.json ./
COPY server.js ./
COPY server ./server
COPY scripts ./scripts
COPY prisma ./prisma
COPY database/schema.sql ./database/schema.sql
COPY --from=build /app/dist ./dist
COPY public ./public
RUN mkdir -p public/media/uploads && chown -R node:node public/media/uploads
USER node
EXPOSE 3000
CMD ["node", "server.js"]
