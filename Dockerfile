FROM heroku/cedar:14
MAINTAINER Roberto Andrade <roberto.andrade@gmail.com>

# Installing dependencies



# Heroku settings

RUN useradd -d /app -m app
USER app
WORKDIR /app

ENV HOME /app

RUN mkdir -p /app/heroku
RUN mkdir -p /app/src
RUN mkdir -p /app/.profile.d

WORKDIR /app/src

# App files

COPY src /app/src/src
COPY composer.* github-sync.php run.sh /app/src/

CMD /app/src/run.sh