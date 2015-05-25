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

