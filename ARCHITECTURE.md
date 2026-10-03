# Arquitectura del Sistema (API) — Simple Stock Flow
## Especificación de Arquitectura Onion en Laravel (PHP 8.1+)

> **Fase:** Commit Cero (Definición Arquitectónica Previa a la Implementación)  
> **Cumplimiento:** Principios de la Constitución del Proyecto (Artículos I, II, V, VII, IX, XI) y ADR-001 a ADR-004.

---

## 1. La Regla Sagrada de Dependencias

En la Arquitectura Onion (Cebolla), las dependencias de código apuntan estrictamente hacia el centro. Las implementaciones tecnológicas concretas quedan afuera y el núcleo de negocio permanece agnóstico a cualquier base de datos, librería externa o framework HTTP.

```text
    ┌────────────────────────────────────────────────────────┐
    │                      Presentation                      │
    │           Controllers / Requests / Resources           │
    └───────────────────────────┬────────────────────────────┘
                                │ (invoca)
                                ▼
    ┌────────────────────────────────────────────────────────┐
    │                      Application                       │
    │                   Use Cases / DTOs                     │
    │         TransactionManagerInterface (puerto)           │
    └───────────────────────────┬────────────────────────────┘
                                │ (opera sobre)
                                ▼
    ┌────────────────────────────────────────────────────────┐
    │                         Domain                         │
    │               Entities / ValueObjects                  │
    │          Exceptions / Repository Interfaces            │
    └───────────────────────────▲────────────────────────────┘
                                │ (implementa interfaces)
    ┌───────────────────────────┴────────────────────────────┐
    │                     Infrastructure                     │
    │             Eloquent Models / Repositories             │
    │                 Mappers / DB Transactions              │
    │                Bcrypt / JWT / FileStorage              │
    └────────────────────────────────────────────────────────┘
```

**Dirección de Dependencias:**
- `Domain` no conoce a Laravel, Eloquent ni MySQL.
- `Application` solo conoce a `Domain`.
- `Infrastructure` depende de `Domain` y `Application` para implementar sus contratos.
- `Presentation` recibe peticiones HTTP y delega a `Application`.

---

## 2. Límites y Responsabilidades por Capa

### Capa 1: Domain (`app/Domain/`) — El Núcleo Puro
- **Garantía:** Cero librerías de terceros, cero clases de `Illuminate\*`. Código PHP 8.1+ nativo estricto.
- **`Entities/`**:
  - `Product`: Agregado catálogo. Métodos de negocio: `withdrawStock(Quantity $qty)`, `restock(Quantity $qty)`, `changePrice(Money $price)`. **No tiene atributo `version`** (ADR-002: el testigo de concurrencia pertenece a la persistencia).
  - `Sale`: Agregado venta. Inmutable una vez confirmada. Garantiza al menos 1 línea y prohíbe productos duplicados. **No almacena total ni subtotal**: el método `getTotal(): Money` se calcula en tiempo real sumando los subtotales (Artículo VII).
  - `SaleItem`: Línea con valores congelados (`unitPrice`, `productName`, `categoryName`). Subtotal dinámico (`quantity * unitPrice`).
  - `Category`: Entidad de referencia fija (5 categorías de solo lectura).
  - `User`: Identidad (`username` normalizado en minúsculas y sin espacios, `role` en `['admin', 'seller']`, `passwordHash`).
- **`ValueObjects/`**: Inmutables y autocontenidos:
  - `Money`: Precisión decimal con redondeo `ROUND_HALF_UP` a 2 decimales.
  - `Quantity`: Entero estrictamente mayor a 0.
  - `DateRange`: Rango de fechas para reportes (`startDate <= endDate`).
- **`Exceptions/`**: Clases PHP puras que extienden de `\DomainException` con mensajes en español legibles para el usuario (`InsufficientStockException`, `InvalidPriceException`). No manejan estados HTTP ni respuestas JSON.
- **`Repositories/`**: Interfaces puras (`ProductRepositoryInterface`, `SaleRepositoryInterface`, `CategoryRepositoryInterface`, `UserRepositoryInterface`, `SalesReportQueryInterface`).

### Capa 2: Application (`app/Application/`) — Casos de Uso
- **Garantía:** Orquesta el negocio sin conocer controladores HTTP ni modelos Eloquent.
- **`Ports/`**:
  - `TransactionManagerInterface`: Puerto para ejecutar operaciones atómicas desacopladas (`execute(callable $operation): mixed`).
- **`UseCases/`**: Una clase por acción con método `execute()` o `__invoke()`:
  - `RegisterSaleUseCase`: Inyecta `TransactionManagerInterface` para garantizar atomicidad en la consulta de stock, retiro y guardado de la venta.
  - `CreateProductUseCase`, `UpdateProductUseCase`, `SoftDeleteProductUseCase`, `ListProductsUseCase`.
  - `GenerateSalesReportUseCase`: Consulta la agregación en BD sin traer ventas a memoria (Artículo VI y CA-06.5).
- **`DTOs/`**: Objetos planos para transporte de datos desacoplados de Laravel (`RegisterSaleDTO`, `ProductDTO`).

### Capa 3: Infrastructure (`app/Infrastructure/`) — Adaptadores Técnicos
- **Garantía:** Todo el acoplamiento a Laravel queda encapsulado aquí.
- **`Persistence/Models/`**: Modelos Eloquent (`ProductModel`, `SaleModel`, `SaleItemModel`, `UserModel`, `CategoryModel`).
  - `ProductModel` contiene la columna `version INT` para concurrencia optimista y `deleted_at` para baja lógica.
- **`Persistence/Mappers/`**: Mapeo estricto bidireccional (`ProductModel` ↔ `Product`).
- **`Persistence/Repositories/`**: Implementaciones Eloquent de las interfaces del Dominio. Si un `UPDATE product WHERE id = ? AND version = ?` afecta 0 filas, traduce a `ProductConcurrencyException`.
- **`Persistence/LaravelTransactionManager`**: Implementa `TransactionManagerInterface` llamando internamente a `DB::transaction($operation)`.
- **`Services/`**: `BcryptPasswordHasher`, `JwtTokenService`, `LocalStorageService`.

### Capa 4: Presentation (`app/Presentation/`) — Entrada y Salida HTTP
- **`Controllers/`**: Controladores limpios de Laravel. Reciben el `Request`, instancian el DTO y llaman al UseCase.
- **`Requests/`**: `FormRequest` para validación inicial de esquema HTTP.
- **`Resources/`**: `JsonResource` para cumplir estrictamente con los contratos JSON de `api-contract.md` (ej. serializar `sold_by_username` como `soldBy`).
- **`Exceptions/Handler`**: Traduce excepciones de Dominio a respuestas HTTP apropiadas (ej. `InsufficientStockException` → HTTP 422 Unprocessable Entity, `ProductConcurrencyException` → HTTP 409 Conflict).

---

## 3. Decisiones sobre Infraestructura y Base de Datos

1. **Propiedad del Esquema (Artículo V):** Las migraciones de base de datos pertenecen a `test-simple-stock-flow-api/database/migrations/`. El contenedor de `infra` solo levanta MySQL 8.4 LTS vacío (`stockflow`, usuario `stockflow`, `utf8mb4_0900_ai_ci`).
2. **Sembrado Idempotente (Artículo IX y D-10):**
   - 5 categorías fijas sembradas por migración con UUIDs versión 4 literales predeterminados.
   - Admin inicial creado desde variables de entorno (`ADMIN_USERNAME`, `ADMIN_PASSWORD`), nunca hardcodeado en el código.
3. **Herramienta Demo (`tool`):** Consume la API vía HTTP (`POST /api/...`), nunca conectándose directamente a la base de datos MySQL.

---

## 4. Verificación de Arquitectura (La Prueba del Grep)

Para validar que la arquitectura no esté viciada antes de entregar:
- `grep -rn "Illuminate\\\\" app/Domain` debe retornar **0 resultados**.
- `grep -rn "DB::" app/Application` debe retornar **0 resultados**.
- `grep -rn "total" database/migrations` debe retornar **0 columnas** en tablas `sale` o `sale_item`.
