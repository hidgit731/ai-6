FROM node:20-alpine

ARG PUID=1000
ARG PGID=1000

# node:20-alpine ships with user "node" at UID/GID 1000.
# Skip creation if group/user already exist (handles default PUID/PGID=1000 case).
RUN if ! getent group ${PGID} > /dev/null 2>&1; then \
      addgroup -g ${PGID} appgroup; \
    fi \
    && if ! getent passwd ${PUID} > /dev/null 2>&1; then \
      adduser -u ${PUID} -G "$(getent group ${PGID} | cut -d: -f1)" -s /bin/sh -D appuser; \
    fi

# Pre-create /app/node_modules with correct ownership so the named volume
# is initialised with the right permissions on first run (not as root).
RUN mkdir -p /app/node_modules \
    && chown -R ${PUID}:${PGID} /app

USER ${PUID}:${PGID}
WORKDIR /app

# node_modules are installed at container start into an isolated volume.
# Source code is mounted via volume — no COPY needed.
CMD sh -c "npm install && npm run dev -- --host 0.0.0.0"
