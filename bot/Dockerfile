FROM node:24-alpine AS deps

WORKDIR /usr/src/app

COPY package.json package-lock.json ./
RUN npm ci --production

FROM node:18-alpine AS development

WORKDIR /usr/src/app

RUN npm install -g nodemon

COPY --from=deps /usr/src/app/node_modules ./node_modules
COPY . .

VOLUME [ "/usr/src/app" ]
EXPOSE 3001

CMD ["nodemon", "--watch", "src", "--exec", "node", "src/index.js"]


FROM node:24-alpine AS production

WORKDIR /usr/src/app

COPY --from=deps /usr/src/app/node_modules ./node_modules
COPY . .

ENV NODE_ENV=production
EXPOSE 3001

CMD ["node", "src/index.js"]