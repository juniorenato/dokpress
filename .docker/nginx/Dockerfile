FROM nginx:1.29-alpine

# Install OpenSSL and bash for SSL certificates and entrypoint
RUN apk add --no-cache openssl bash wget

# Get user/group IDs from build args
ARG PUID=1000
ARG PGID=1000

# Create nginx group and user with correct permissions
RUN addgroup -g ${PGID} nginx 2>/dev/null || true \
    && adduser -D -u ${PUID} -G nginx nginx 2>/dev/null || true

# Declare volumes
VOLUME ["/var/log/nginx"]

# Copy custom entrypoint script to docker-entrypoint.d (nginx will execute it automatically)
COPY docker-entrypoint.sh /docker-entrypoint.d/40-substitute-env-vars.sh
RUN chmod +x /docker-entrypoint.d/40-substitute-env-vars.sh

# Use default nginx CMD
CMD ["nginx", "-g", "daemon off;"]
