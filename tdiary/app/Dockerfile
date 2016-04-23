FROM tdiary/tdiary:latest

COPY . /usr/src/app/
RUN bundle && bundle exec rake assets:copy
