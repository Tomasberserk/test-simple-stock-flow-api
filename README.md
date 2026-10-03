# Simple Stock Flow — Backend API (Laravel 10 + Onion Architecture)

Backend de Simple Stock Flow implementado en PHP 8.1+ y Laravel 10 bajo los principios estrictos de **Arquitectura Onion (Cebolla)** y desarrollo guiado por especificación (SDD).

---

## 🏛️ Estructura de la Arquitectura Onion

```text
app/
├── Domain/                    # Anillo 1: Núcleo Puro (Entidades, Value Objects, Excepciones)
│   ├── Exception/             # BusinessRuleViolation y excepciones de dominio
│   ├── Model/                 # Product, Sale, SaleItem, Category, User
│   └── ValueObject/           # Money (BigDecimal scale 2), Quantity, IDs, Role, Username
│
├── Application/               # Anillo 2: Casos de Uso y Puertos
│   ├── Ports/
│   │   ├── Inbound/           # PlaceSale, ManageProducts, GetSales, GetSalesReport, Authenticate
│   │   └── Outbound/          # ProductRepository, SaleRepository, CategoryRepository, etc.
│   └── UseCase/               # Servicios de orquestación de aplicación
│
├── Infrastructure/            # Anillo 3: Adaptadores de Salida y Persistencia
│   ├── Persistence/           # Repositorios Eloquent, Mappers, Modelos físicos
│   ├── Security/              # BcryptPasswordHasher, JwtTokenGenerator
│   ├── Storage/               # LocalFileStorage
│   └── Time/                  # SystemClock
│
├── Presentation/              # Anillo 4: Adaptadores de Entrada HTTP
│   └── Http/
│       ├── Controllers/       # Auth, Product, Category, Sale, Report, Health
│       └── Middleware/        # AuthenticateJwt, RequireRole
│
└── Bootstrap/                 # Composición Raíz (Artículo III de la Constitución)
    └── PortBindingsServiceProvider.php
```

---

## 🚀 Requisitos y Configuración

- **PHP**: 8.1+ (extensiones: `bcmath`, `pdo_mysql`, `mbstring`, `openssl`).
- **Base de Datos**: MySQL 8.4 LTS (ejecutada mediante Docker Compose en `infra/`).
- **Composer**: 2.x.

### Variables de Entorno (.env)
Copie el archivo de ejemplo y configure la conexión MySQL:
```bash
cp .env.example .env
php artisan key:generate
```

Parámetros clave de base de datos (`infra/docker-compose.yml`):
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=stock_flow
DB_USERNAME=stock_user
DB_PASSWORD=stock_secret
```

---

## 🛠️ Ejecución de Migraciones y Semillas

```bash
# Ejecutar migraciones (5 tablas en singular con 9 CHECKs exactos)
php artisan migrate

# Ejecutar sembrador inicial (5 categorías y usuario administrador)
php artisan db:seed
```

---

## 🧪 Pruebas Automatizadas

```bash
# Ejecutar suite completa de tests de Dominio y Presentación HTTP
php artisan test
```

---

## 📡 Endpoints de la API

| Método | Endpoint | Rol Requerido | Descripción |
|---|---|---|---|
| `GET` | `/api/health` | Público | Verificación de estado del servicio y DB |
| `POST` | `/api/auth/login` | Público | Autenticación y obtención de JWT |
| `POST` | `/api/auth/register-seller` | `admin` | Alta de usuarios vendedores |
| `GET` | `/api/categories` | Público | Listado de categorías fijas |
| `GET` | `/api/products` | Público | Búsqueda y paginación de productos |
| `GET` | `/api/products/{id}` | Público | Detalle de un producto |
| `POST` | `/api/products` | `admin` | Creación de producto |
| `PUT` | `/api/products/{id}` | `admin` | Edición de producto |
| `DELETE` | `/api/products/{id}` | `admin` | Baja lógica de producto (`is_active = 0`) |
| `POST` | `/api/sales` | `admin`, `seller` | Registro atómico de venta con concurrencia optimista |
| `GET` | `/api/sales` | `admin`, `seller` | Listado paginado de ventas por rango |
| `GET` | `/api/sales/{id}` | `admin`, `seller` | Detalle de una venta con cálculo dinámico de total |
| `GET` | `/api/reports/sales` | `admin` | Reporte consolidado de ventas por rango de fechas |
