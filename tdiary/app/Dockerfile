FROM tdiary/tdiary:latest

COPY Gemfile.local /usr/src/app/
RUN bundle && bundle exec rake assets:copy
COPY . /usr/src/app/
