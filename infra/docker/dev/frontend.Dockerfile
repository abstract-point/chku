FROM oven/bun:1.3.11-alpine

WORKDIR /app

ARG VITE_PUBLIC_URL=http://localhost:8090
ENV VITE_PUBLIC_URL=$VITE_PUBLIC_URL

COPY apps/chku-frontend/package.json apps/chku-frontend/bun.lock ./
RUN bun install --frozen-lockfile

COPY apps/chku-frontend/ .

EXPOSE 5180

CMD ["bun", "run", "dev", "--", "--host", "0.0.0.0", "--port", "5180"]
