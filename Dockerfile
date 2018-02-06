FROM        index.alauda.cn/rongchang/php:5.5
MAINTAINER  Arkii sunqy@edaixi.com
ENV         VERSION 0.1

WORKDIR /app
COPY . /app/

EXPOSE 80
ENTRYPOINT ["docker/entrypoint.sh"]
CMD ["supervisord", "-c", "/app/docker/supervisord.conf"]
