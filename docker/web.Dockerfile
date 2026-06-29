# --- Stage 1: Build ---
FROM oven/bun:1.3-alpine AS builder
WORKDIR /app

COPY apps/web/package.json apps/web/bun.lock ./
RUN bun install --frozen-lockfile

COPY apps/web .
RUN bun run build

# --- Stage 2: Serve static assets ---
FROM nginx:1.27-alpine AS production

COPY --from=builder /app/dist /usr/share/nginx/html

COPY docker/nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
