FROM nginx:alpine

ARG env

COPY ./nginx.conf /etc/nginx/nginx.conf
RUN rm /etc/nginx/conf.d/default.conf
COPY ./conf.d/${env}/ /etc/nginx/conf.d/

CMD ["nginx", "-g", "daemon off;"]