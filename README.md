# Laravel Docker Setup

This repository contains a containerized Laravel application using Docker Compose. The environment includes PHP-FPM, Nginx, and MySQL 8.0.

## Prerequisites

Before starting, make sure you have the following installed on your host machine:
- [Docker](https://docs.docker.com/get-docker/)
- [Docker Compose](https://docs.docker.com/compose/install/)
- Git

## Setup Instructions

Follow these steps to set up the project from scratch after cloning the repository.

### 1. Clone the Repository

Clone the project to your local machine and enter the directory:

```bash
git clone <repository-url>
cd laravel-docker
```

### 2. Set Up Environment Variables

The Laravel application is located in the `src/` directory. You need to create an environment file.

```bash
cd src
cp .env.example .env
cd ..
```

Open `src/.env` and ensure your database connection settings match the `docker-compose.yml` configuration:

```env
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=laravel
DB_PASSWORD=root
```

### 3. Start the Docker Containers

Build the Docker images and start the containers in detached mode:

```bash
docker compose up -d --build
```

### 4. Install Dependencies

Once the containers are running, install the PHP dependencies using Composer via the `laravel_app` container:

```bash
docker exec laravel_app composer install
```

### 5. Generate Application Key

Generate the Laravel application key:

```bash
docker exec laravel_app php artisan key:generate
```

### 6. Run Database Migrations

Run the database migrations to set up your tables:

```bash
docker exec laravel_app php artisan migrate
```

### 7. Set Directory Permissions

Ensure the `storage` and `bootstrap/cache` directories are writable by the container:

```bash
chmod -R 777 src/storage src/bootstrap/cache
```

*(Note: Depending on your host OS and Docker setup, you might need to run the above command with `sudo`)*

---

## Accessing the Application

Your application should now be up and running! You can access it in your browser at:
**http://localhost:8000**

## Useful Docker Commands

- **Stop containers:** `docker compose down`
- **View logs:** `docker compose logs -f`
- **Execute an artisan command:** `docker exec laravel_app php artisan <command>`
- **Open an interactive terminal inside the app container:** `docker exec -it laravel_app bash`
