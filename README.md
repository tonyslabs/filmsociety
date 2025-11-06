# Film Society - Frontend para Stremio Addon

Este proyecto es una aplicación web desarrollada con Laravel que sirve como una interfaz de usuario amigable y moderna para un addon de Stremio. Permite explorar, buscar y visualizar contenido multimedia, como películas y series, consumiendo los datos desde la API del addon.

## Características Principales

- **Exploración de Catálogo:** Navega por diferentes catálogos como "Populares", "Nuevos Lanzamientos" y "Destacados".
- **Búsqueda Integrada:** Busca películas y series directamente en la API del addon.
- **Visualización de Contenido:** Accede a una página de reproducción para ver el contenido seleccionado.
- **Diseño Responsivo:** Interfaz construida con Tailwind CSS, adaptable a diferentes tamaños de pantalla.
- **Cache Inteligente:** Utiliza el sistema de caché de Laravel para acelerar las respuestas de la API y mejorar la experiencia de usuario.

## Stack Tecnológico

- **Backend:** PHP 8.2, Laravel 11
- **Frontend:** Vite, Tailwind CSS, Alpine.js
- **Dependencias Clave:**
    - `laravel/framework`: El núcleo del framework de Laravel.
    - `tailwindcss`: Para el diseño de la interfaz.
    - `alpinejs`: Para la interactividad en el frontend.

## Pre-requisitos

- PHP >= 8.2
- Composer
- Node.js y npm (o un gestor de paquetes compatible)

## Instalación

1.  **Clonar el repositorio:**
    ```bash
    git clone <URL-del-repositorio>
    cd filmsociety
    ```

2.  **Instalar dependencias de PHP:**
    ```bash
    composer install
    ```
    *Este comando también creará el archivo `.env` a partir de `.env.example` y generará la clave de la aplicación.*

3.  **Crear la base de datos:**
    El proyecto está configurado para usar SQLite. El archivo de la base de datos se crea automáticamente durante la instalación de Composer.
    ```bash
    touch database/database.sqlite
    ```

4.  **Ejecutar las migraciones:**
    ```bash
    php artisan migrate
    ```

5.  **Instalar dependencias de Node.js:**
    ```bash
    npm install
    ```

## Configuración

El único paso de configuración requerido es definir la URL base del addon de Stremio en tu archivo `.env`.

Abre el archivo `.env` y añade la siguiente línea, reemplazando la URL con la de tu addon:

```env
STREMIO_ADDON_BASE="https://tu-addon-de-stremio.com"
```

## Ejecución del Proyecto

Para ejecutar la aplicación, necesitarás dos terminales:

1.  **Terminal 1: Compilar los assets del frontend con Vite:**
    ```bash
    npm run dev
    ```

2.  **Terminal 2: Iniciar el servidor de desarrollo de Laravel:**
    ```bash
    php artisan serve
    ```

Una vez que ambos comandos estén en ejecución, puedes acceder a la aplicación en tu navegador a través de la URL `http://127.0.0.1:8000`.

## Ejecución de Pruebas

Para ejecutar el conjunto de pruebas automatizadas, utiliza el siguiente comando:

```bash
php artisan test
```
