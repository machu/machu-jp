# machu/tdiary docker image

This image is published on Docker Hub: https://registry.hub.docker.com/u/machu/tdiary/

# How to use this image

Run docker command

```
docker run -v "$(pwd)/data":/usr/src/app/data --rm -p 8080:9292 machu/tdiary
``` 

or you can also use docker-compose

```
docker-compose up
```

Then, access it via `http://localhost:8080` in a browser.

 * user: `user`, password: `user`

To change password, run the following command and recreate a image.

```
htpasswd -d -c dot.htpasswd
```

## via docker-compose

to be written...

## how to create this image

```
docker build -t machu/tdiary .
```

# Supported Docker versions

This image is supported on Docker version 1.6.0.
