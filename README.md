# Translation Management Service (Laravel)

This is a Laravel API-driven service to manage translations across multiple locales, with tagging, searching, and JSON export endpoints.

====================================================
Requirements
====================================================

-   PHP 8.1+
-   Composer
-   # SQLite (SQLite recommended for quick setup)

1. # Clone the Repository
    git clone https://github.com/codelearner6328/translation-service.git
    cd translation-service

# ==================================================== 2. Install Dependencies

composer install

# ==================================================== 3. Environment Setup

Copy .env.example to .env:
cp .env.example .env

Set the following values inside .env:

APP_NAME=Laravel
APP_ENV=local
APP_KEY=base64:v4VaGY9tGoiFI+r2iHRu6/KGlJNIPXICbB1YjEzqxbk=
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_URL=http://localhost

DB_CONNECTION=sqlite

For SQLite, create the file manually:
touch database/database.sqlite

For MySQL, update DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME, DB_PASSWORD.

# ==================================================== 4. Generate Application Key

php artisan key:generate

# ==================================================== 5. Run Migrations and Seeders

php artisan migrate

This will create all necessary tables and seed initial data.

To populate large datasets for performance testing:
php artisan translations:seed --count=12000 --locales=en,fr,es,de

# ==================================================== 6. Run the Development Server

php artisan serve

Access at:
http://127.0.0.1:8000

# ==================================================== 7. Authentication

This API uses Bearer Token authentication.
Generate a test token:

php artisan tinker

Inside Tinker:
$user = \App\Models\User::factory()->create();
$token = $user->createToken('api-token')->plainTextToken;

or you can hit this endpoint /api//generate-token

Use this token in your API requests:
Authorization: Bearer <token>

# ==================================================== 8. API Documentation (Swagger)

Swagger documentation is available at:
http://127.0.0.1:8000/api/documentation

# ==================================================== 9. Key Endpoints

POST /api/translations -> Create translation key/values
GET /api/translations -> Search translations (filters: namespace, key, tags, locales)
GET /api/translations/{id} -> Show translation
PUT /api/translations/{id} -> Update translation
DELETE /api/translations/{id} -> Delete translation
GET /api/translations/export -> Export translations as JSON
Query params:

-   locales=en,fr
-   tags=mobile
-   namespace=app
-   flat=true

====================================================
Notes
====================================================

-   You can contact me at wahidjillani38@gmail.com if found any difficulty while setup
