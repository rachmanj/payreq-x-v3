**Purpose**: Record technical decisions and rationale for future reference
**Last Updated**: 2025-01-15

# Technical Decision Records

## Decision Template

Decision: [Title] - [YYYY-MM-DD]

**Context**: [What situation led to this decision?]

**Options Considered**:

1. **Option A**: [Description]
    - ✅ Pros: [Benefits]
    - ❌ Cons: [Drawbacks]
2. **Option B**: [Description]
    - ✅ Pros: [Benefits]
    - ❌ Cons: [Drawbacks]

**Decision**: [What we chose]

**Rationale**: [Why we chose this option]

**Implementation**: [How this affects the codebase]

**Review Date**: [When to revisit this decision]

---

## Recent Decisions

### Decision: Laravel 10+ with Modern Architecture - 2024-05-01

**Context**: Need for a robust, scalable accounting system with modern PHP practices

**Options Considered**:

1. **Laravel 10+ with new skeleton structure**

    - ✅ Pros: Latest features, improved performance, modern middleware approach, auto-discovery
    - ❌ Cons: Learning curve for new structure, some packages may not be compatible yet

2. **Laravel 9 with traditional structure**
    - ✅ Pros: Stable, well-documented, extensive package support
    - ❌ Cons: Older PHP version, missing modern features, traditional service provider approach

**Decision**: Laravel 10+ with new skeleton structure

**Rationale**: Future-proof architecture, better performance, modern PHP 8.1+ features, and improved developer experience

**Implementation**:

-   Service providers registered in `bootstrap/providers.php`
-   Middleware by class name in routes
-   Event listeners auto-registered with type hints
-   New view creation with `php artisan make:view`

**Review Date**: 2025-06-01

### Decision: Spatie Laravel Permission for RBAC - 2024-06-01

**Context**: Need for comprehensive role-based access control with granular permissions

**Options Considered**:

1. **Spatie Laravel Permission**

    - ✅ Pros: Mature, well-maintained, granular permissions, middleware support, extensive documentation
    - ❌ Cons: Additional package dependency, learning curve

2. **Custom RBAC implementation**

    - ✅ Pros: Full control, no external dependencies
    - ❌ Cons: Development time, security risks, maintenance burden

3. **Laravel Gates and Policies**
    - ✅ Pros: Built-in, lightweight
    - ❌ Cons: Limited granularity, more complex for complex permission structures

**Decision**: Spatie Laravel Permission

**Rationale**: Production-ready solution with proven security, comprehensive permission system, and excellent Laravel integration

**Implementation**:

-   Granular permissions for exchange rates (create, edit, delete, import, export)
-   Middleware protection on all routes
-   Exception handling with SweetAlert2 integration
-   Permission-based UI rendering

**Review Date**: 2025-06-01

### Decision: Yajra DataTables for Financial Reports - 2024-07-01

**Context**: Need for efficient handling of large financial datasets with search, pagination, and export capabilities

**Options Considered**:

1. **Yajra DataTables**

    - ✅ Pros: Laravel integration, server-side processing, built-in export features, extensive customization
    - ❌ Cons: Additional package dependency, learning curve

2. **Custom AJAX implementation**

    - ✅ Pros: Full control, no external dependencies
    - ❌ Cons: Development time, maintenance burden, potential performance issues

3. **Vue.js with custom components**
    - ✅ Pros: Modern frontend, reactive updates
    - ❌ Cons: Additional complexity, learning curve for team

**Decision**: Yajra DataTables

**Rationale**: Best balance of functionality, performance, and Laravel integration for financial reporting needs

**Implementation**:

-   Server-side processing for large datasets
-   Excel export integration
-   Search and filtering capabilities
-   Performance optimization with database indexes

**Review Date**: 2025-07-01

### Decision: Laravel Excel for Import/Export - 2024-08-01

**Context**: Need for bulk data import/export capabilities for financial data management

**Options Considered**:

1. **Laravel Excel (Maatwebsite)**

    - ✅ Pros: Laravel integration, extensive format support, chunk processing, validation
    - ❌ Cons: Additional package dependency, memory considerations for large files

2. **Custom CSV/Excel handling**

    - ✅ Pros: Full control, no external dependencies
    - ❌ Cons: Development time, format limitations, maintenance burden

3. **Direct database operations**
    - ✅ Pros: Fastest performance, direct control
    - ❌ Cons: Security risks, validation complexity, error handling challenges

**Decision**: Laravel Excel

**Rationale**: Production-ready solution with excellent Laravel integration, comprehensive format support, and built-in validation

**Implementation**:

-   Exchange rate bulk updates
-   Financial data exports
-   Template downloads
-   Chunk processing for large files

**Review Date**: 2025-08-01

### Decision: Performance Optimization Strategy - 2024-12-01

**Context**: Significant performance difference between localhost (1.51s) and server (15.29s) for DataTables

**Options Considered**:

1. **Database index optimization + query improvements**

    - ✅ Pros: Addresses root cause, significant performance gains, maintains UI consistency
    - ❌ Cons: Requires database migration, testing needed

2. **View rendering optimization (inline HTML)**

    - ✅ Pros: Faster rendering, reduced file I/O
    - ❌ Cons: UI changes, harder maintenance, potential security risks

3. **Caching implementation**
    - ✅ Pros: Immediate performance gains, reduced database load
    - ❌ Cons: Cache invalidation complexity, memory usage, data staleness

**Decision**: Database index optimization + query improvements

**Rationale**: Addresses the fundamental performance bottleneck while maintaining user experience and system reliability

**Implementation**:

-   Added critical database indexes for WHERE clauses and JOINs
-   Optimized date processing at database level
-   Improved search query efficiency
-   Maintained original view rendering for UI consistency

**Review Date**: 2025-03-01

# Architecture Decision Records

## ADR-005: Per-User DDS Department Code Implementation (2025-09-09)

### Status

Accepted

### Context

Initially, the Invoice Payment feature used a single department code from the `.env` file (`DDS_DEPARTMENT_CODE`) for all users. This approach limited flexibility as different users might need to work with different department codes when communicating with the DDS API.

### Decision

We implemented a user-specific DDS department code with the following architecture:

1. **User-level Configuration**: Added `dds_department_code` field to the `users` table
2. **Fallback Mechanism**: Maintained `.env` variable as fallback for backward compatibility
3. **Flexible Resolution**: Controller resolves department code from user first, then falls back to environment
4. **Enhanced Logging**: Added source tracking in logs (user vs. env)
5. **UI Integration**: Added field to user create/edit forms with validation

### Consequences

#### Positive

-   **Enhanced Flexibility**: Different users can work with different departments
-   **Backward Compatibility**: System continues to work with existing configuration
-   **Improved Debugging**: Logs now show source of department code
-   **Better User Experience**: Warning alerts when department code is missing
-   **Seamless Integration**: No changes needed to DDS API communication logic

#### Negative

-   **Additional Configuration**: Requires setting up department codes per user
-   **Data Migration**: Existing users need department codes assigned
-   **UI Complexity**: Additional field in user management forms
-   **Potential Confusion**: Two possible sources for department code

#### Risks

-   **Missing Configuration**: Users might not have department codes set
-   **Incorrect Codes**: Users might enter invalid department codes
-   **Permission Issues**: Users might need specific permissions for certain departments

### Implementation Details

#### Files Created/Modified

-   `database/migrations/2025_09_09_063420_add_dds_department_code_to_users_table.php` - Added field to users table
-   `app/Models/User.php` - Updated fillable attributes
-   `app/Http/Controllers/InvoicePaymentController.php` - Updated department code resolution
-   `app/Http/Controllers/UserController.php` - Added validation and handling for new field
-   `resources/views/users/index.blade.php` - Added field to create form
-   `resources/views/users/edit.blade.php` - Added field to edit form
-   `resources/views/invoice-payment/index.blade.php` - Added warning alert for missing department code

#### Key Features Implemented

-   User-specific department code storage
-   Environment variable fallback mechanism
-   Form field validation (nullable, string, max:20)
-   Warning alerts for missing configuration
-   Enhanced logging with source tracking

### Review Date

2025-12-09 (3 months from implementation)

## ADR-004: Invoice Payment Feature Implementation (2025-09-04)

### Status

Accepted

### Context

The project needed to integrate with a DDS (Document Distribution System) application to manage invoice payments. This required creating a new feature that could communicate with external APIs, display invoice data, and allow users to update payment statuses.

### Decision

We implemented a comprehensive Invoice Payment feature with the following architecture:

1. **Controller-based API Integration**: Used Laravel HTTP Client to communicate with DDS API endpoints
2. **DataTables Frontend**: Implemented DataTables for enhanced user experience with search, sorting, and pagination
3. **Modal-based Payment Updates**: Created a payment modal for updating invoice statuses
4. **Real-time Dashboard**: Built a dashboard with real-time statistics
5. **Permission-based Access**: Used Spatie Laravel Permission for access control

### Consequences

#### Positive

-   **Enhanced User Experience**: DataTables provide excellent search, sorting, and pagination capabilities
-   **Real-time Updates**: Tables refresh automatically after payment updates
-   **Secure Integration**: Proper API key authentication and CSRF protection
-   **Scalable Architecture**: Modular design allows for easy extension
-   **Comprehensive Logging**: Extensive logging for debugging and monitoring

#### Negative

-   **External Dependency**: System now depends on DDS API availability
-   **Complex Error Handling**: API integration requires robust error handling
-   **Environment Configuration**: Requires proper environment variable setup
-   **Performance Considerations**: API calls may introduce latency

#### Risks

-   **API Changes**: DDS API changes could break functionality
-   **Network Issues**: Network problems could affect invoice management
-   **Data Synchronization**: Potential for data inconsistencies between systems

### Implementation Details

#### Files Created/Modified

-   `app/Http/Controllers/InvoicePaymentController.php` - Main controller
-   `resources/views/invoice-payment/index.blade.php` - Frontend interface
-   `routes/cashier.php` - Route definitions

#### Environment Variables Required

-   `DDS_API_URL` - Base URL for DDS API
-   `DDS_API_KEY` - Authentication key for API access
-   `DDS_DEPARTMENT_CODE` - Department identifier

#### Key Features Implemented

-   Dashboard with invoice statistics
-   Waiting payment invoices table
-   Paid invoices table
-   Payment update functionality
-   Automatic table refresh
-   Manual refresh buttons
-   Error handling and logging

### Review Date

2025-12-04 (3 months from implementation)

---

## ADR-003: DataTables Integration (2024-12-31)

### Status

Accepted

### Context

The application needed to display large datasets with search, sorting, and pagination capabilities. Users required efficient ways to navigate through financial data.

### Decision

We integrated Yajra DataTables package for enhanced table functionality across the application.

### Consequences

#### Positive

-   **Improved User Experience**: Users can search, sort, and paginate data efficiently
-   **Reduced Server Load**: Client-side processing reduces server requests
-   **Consistent Interface**: Standardized table behavior across the application
-   **Responsive Design**: Tables adapt to different screen sizes

#### Negative

-   **JavaScript Dependency**: Requires JavaScript to be enabled
-   **Initial Load Time**: DataTables library increases initial page load time
-   **Complexity**: More complex than simple HTML tables

### Implementation Details

-   Used Yajra DataTables package
-   Implemented server-side processing for large datasets
-   Added custom styling for consistency
-   Integrated with existing AdminLTE theme

### Review Date

2025-06-30

---

## ADR-002: Role-Based Access Control (2024-12-31)

### Status

Accepted

### Context

The application needed fine-grained access control for different user roles and permissions. Security was a primary concern for financial data management.

### Decision

We implemented Spatie Laravel Permission package for role-based access control (RBAC).

### Consequences

#### Positive

-   **Flexible Permissions**: Granular control over user access
-   **Easy Management**: Simple permission assignment and revocation
-   **Audit Trail**: Clear tracking of user permissions
-   **Scalable**: Easy to add new roles and permissions

#### Negative

-   **Complexity**: More complex than simple role-based systems
-   **Performance**: Permission checks add overhead
-   **Maintenance**: Requires careful permission management

### Implementation Details

-   Used Spatie Laravel Permission package
-   Created middleware for permission checks
-   Implemented permission-based UI elements
-   Added permission seeding for initial setup

### Review Date

2025-06-30

---

## ADR-001: Laravel Framework Choice (2024-12-31)

### Status

Accepted

### Context

We needed to choose a PHP framework for building a comprehensive accounting system with complex business logic, database operations, and user management.

### Decision

We chose Laravel framework for its robust features, active community, and excellent documentation.

### Consequences

#### Positive

-   **Rapid Development**: Laravel's features accelerate development
-   **Security**: Built-in security features and best practices
-   **Ecosystem**: Rich ecosystem of packages and tools
-   **Documentation**: Excellent documentation and community support

#### Negative

-   **Learning Curve**: Steep learning curve for complex features
-   **Performance**: Framework overhead compared to plain PHP
-   **Dependency**: Locked into Laravel ecosystem

### Implementation Details

-   Laravel 10.x with PHP 8.1+
-   Used Laravel's built-in features (Eloquent ORM, Blade templating, etc.)
-   Integrated with AdminLTE for frontend
-   Implemented custom middleware and services

### Review Date

2025-06-30
