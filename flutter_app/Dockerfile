FROM nginx:alpine

# Copy the pre-built Flutter web output
COPY build/web /usr/share/nginx/html

# Copy custom nginx config
COPY nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80
