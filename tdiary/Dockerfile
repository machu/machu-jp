FROM ruby:2.2

# throw errors if Gemfile has been modified since Gemfile.lock
#RUN bundle config --global frozen 1

# create tdiary directory
RUN mkdir -p /usr/src/app
RUN gem install tdiary
RUN tdiary new --skip-bundle /usr/src/app
WORKDIR /usr/src/app

# install libraries
COPY Gemfile.local /usr/src/app/
RUN bundle --without=development:test:coffee --jobs=4 --retry=3

COPY . /usr/src/app/

VOLUME /usr/src/app/data
EXPOSE 9292
CMD [ "bundle", "exec", "rackup" ]
