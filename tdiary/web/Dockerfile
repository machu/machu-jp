FROM nginx

RUN rm /etc/nginx/conf.d/default.conf
COPY nginx.conf /etc/nginx/
COPY tdiary.conf /etc/nginx/conf.d/
COPY html /usr/share/nginx/html
