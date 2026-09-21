# Running the Container

> **Note for Podman users:** Replace `docker` with `podman` in any of the commands below.

### Build the Image
```bash
docker build -t lokapren .

```

### Run the Container

#### Linux / macOS

```bash
docker run --rm -p 8080:80 -v "$(pwd)":/var/www/html -v /var/www/html/writable -v /var/www/html/vendor lokapren

```

#### Windows

```cmd
docker run --rm -p 8080:80 -v "%cd%":/var/www/html -v /var/www/html/writable -v /var/www/html/vendor lokapren

```

### Enter the Container Shell

If you need to debug or run commands inside the container:

**Linux / macOS:**

```bash
docker run -it --rm -p 8080:80 -v "$(pwd)":/var/www/html -v /var/www/html/writable -v /var/www/html/vendor lokapren bash

```

**Windows:**

```cmd
docker run -it --rm -p 8080:80 -v "%cd%":/var/www/html -v /var/www/html/writable -v /var/www/html/vendor lokapren bash

```

### Clean Up

To remove any stopped containers built from this image and delete the local image itself:

```bash
podman rm -f $(podman ps -a -q --filter ancestor=lokapren) 2>/dev/null || true
podman rmi lokapren
```

