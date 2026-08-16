# Imagen base: PHP 8.2 en modo CLI.
# Es suficiente para desarrollo porque levantamos el backend con `php artisan serve`.
# (Para producción más adelante se usaría php-fpm + nginx, pero eso es otra fase.)
FROM php:8.2-cli

# Dependencias del sistema que necesitan Composer y las extensiones de PHP.
#   git, unzip -> Composer descarga/descomprime paquetes
#   libzip-dev -> extensión zip
#   libonig-dev -> extensión mbstring
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libonig-dev \
    && rm -rf /var/lib/apt/lists/*

# Extensiones de PHP que usa Laravel + MySQL:
#   pdo_mysql -> conexión a la base de datos MySQL (Fase 2)
#   mbstring, bcmath, zip -> requeridas por Laravel/Composer
RUN docker-php-ext-install pdo_mysql mbstring bcmath zip

# Copiamos el binario de Composer desde su imagen oficial (más limpio que instalarlo a mano).
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Carpeta de trabajo dentro del contenedor. El código se monta aquí vía volumen (docker-compose).
WORKDIR /var/www/html

# Puerto que expone el servidor de desarrollo de Laravel.
EXPOSE 8000

# Comando por defecto: servidor de desarrollo accesible desde fuera del contenedor.
# --host=0.0.0.0 es imprescindible; si no, solo escucharía dentro del contenedor y no lo verías desde Windows.
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
