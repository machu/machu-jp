# Dockerfiles on machu.jp

This repository includes files to run docker and docker-compose on machu.jp.

## nginx (using [jwilder/nginx-proxy](https://github.com/jwilder/docker-gen))

The reverse proxy. This proxy detects containers automatically and sets up proxy configs.

Running container.

```
$ cd nginx
$ docker-compose up
```

## Pukiwiki

A web site powered by pukiwiki.

Builing the docker image.

```
$ cd pukiwiki
$ docker build -t machu/pukiwiki .
```

Running container.

```
$ docker-compose up
```

## Reverse Proxy for Product Advertising API (using [tdiary/rpaproxy-sinatra](https://github.com/tdiary/rpaproxy-sinatra))

Running container.

```
$ cd rpaproxy
$ docker-compose up
```

To tell the hostname to nginx-proxy, VIRTUAL_HOST environment is added.

### migrate database from mongohq.com

To migrate database from mongohq.com, run this commands.

```
$ docker exec rpaproxy_mongodb_1 mongodump -h linus.mongohq.com:10097 -d app20350636 --username heroku --password your_mongohq_password
$ docker exec rpaproxy_mongodb_1 mongorestore -d rpaproxy /dump/app20350636
```
