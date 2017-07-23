FROM ruby:2.4
MAINTAINER MATSUOKA Kohei @machu

RUN mkdir -p /usr/src/app && \
    bundle config github.https true
WORKDIR /usr/src/app

COPY [ "Gemfile", "Gemfile.lock", "/usr/src/app/" ]
RUN bundle --without=development:test --jobs=4 --retry=3

COPY . /usr/src/app/
RUN bundle && bundle exec rake assets:copy

VOLUME [ "/usr/src/app/data", "/usr/src/app/public" ]
EXPOSE 9292
CMD [ "bundle", "exec", "rackup", "-o", "0.0.0.0", "-p", "9292" ]
