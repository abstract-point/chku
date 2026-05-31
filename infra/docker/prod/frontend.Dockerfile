FROM oven/bun:1.3.11-alpine AS build

WORKDIR /app

ARG VITE_PUBLIC_URL=http://localhost:8090
ENV VITE_PUBLIC_URL=$VITE_PUBLIC_URL

COPY apps/chku-frontend/package.json apps/chku-frontend/bun.lock ./
RUN bun install --frozen-lockfile

COPY apps/chku-frontend/ .
RUN bun run build

FROM nginx:1.29-alpine

COPY --from=build /app/dist /usr/share/nginx/html
COPY infra/docker/prod/frontend.conf /etc/nginx/conf.d/default.conf

EXPOSE 80
