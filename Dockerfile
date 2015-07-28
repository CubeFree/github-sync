FROM heroku/cedar:14
MAINTAINER Roberto Andrade <roberto.andrade@gmail.com>


# Heroku settings

RUN useradd -d /app -m app
USER app
WORKDIR /app

ENV HOME /app
ENV PORT 3000
ENV PATH /app/.heroku/php/lib/php:/app/.heroku/php/bin:/app/.heroku/php/sbin:/tmp/php-pack/bin:$PATH
ENV STACK cedar-14
ENV DOCKER_BUILD 1

RUN mkdir -p /app/.heroku
RUN mkdir -p /app/src
RUN mkdir -p /app/.profile.d

RUN mkdir -p /tmp/cache
RUN mkdir -p /tmp/php-pack
RUN mkdir -p /tmp/environment

WORKDIR /app/src

# App files

COPY src /app/src/src
COPY composer.* github-sync.php run.sh /app/src/

# Installing dependencies

RUN git clone https://github.com/heroku/heroku-buildpack-php.git /tmp/php-pack --depth 1
RUN bash -l /tmp/php-pack/bin/compile /app /tmp/cache /app/.env

EXPOSE 3000

RUN cd /app/src/src
RUN curl -sS https://getcomposer.org/installer | php
RUN composer install

CMD /app/src/run.sh