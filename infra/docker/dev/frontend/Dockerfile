FROM oven/bun:1.3.11-alpine

WORKDIR /app

COPY package.json bun.lock ./
RUN bun install --frozen-lockfile

COPY . .

EXPOSE 5180

CMD ["bun", "run", "dev", "--", "--host", "0.0.0.0", "--port", "5180"]
