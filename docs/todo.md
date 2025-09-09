**Purpose**: Track current work and immediate priorities
**Last Updated**: 2025-01-15

## Task Management Guidelines

### Entry Format

Each task entry must follow this format:
[status] priority: task description [context] (completed: YYYY-MM-DD)

### Context Information

Include relevant context in brackets to help with future AI-assisted coding:

-   **Files**: `[src/components/Search.tsx:45]` - specific file and line numbers
-   **Functions**: `[handleSearch(), validateInput()]` - relevant function names
-   **APIs**: `[/api/jobs/search, POST /api/profile]` - API endpoints
-   **Database**: `[job_results table, profiles.skills column]` - tables/columns
-   **Error Messages**: `["Unexpected token '<'", "404 Page Not Found"]` - exact errors
-   **Dependencies**: `[blocked by auth system, needs API key]` - blockers

### Status Options

-   `[ ]` - pending/not started
-   `[WIP]` - work in progress
-   `[blocked]` - blocked by dependency
-   `[testing]` - testing in progress
-   `[done]` - completed (add completion date)

### Priority Levels

-   `P0` - Critical (app won't work without this)
-   `P1` - Important (significantly impacts user experience)
-   `P2` - Nice to have (improvements and polish)
-   `P3` - Future (ideas for later)

---

# Project TODO

## Currently Working On

-   [ ] Performance optimization for large datasets
-   [ ] User experience improvements for mobile devices
-   [ ] Advanced reporting features

## Recently Completed

-   [x] **Per-User DDS Department Code** - Completed on 2025-09-09

    -   ✅ Added `dds_department_code` field to users table (migration)
    -   ✅ Updated User model with fillable attribute
    -   ✅ Modified InvoicePaymentController to use authenticated user's department code
    -   ✅ Added fallback to environment variable for backward compatibility
    -   ✅ Added field to user create/edit forms with validation
    -   ✅ Enhanced logging to show department code source (user vs env)
    -   ✅ Added warning alert when department code is missing
    -   ✅ Updated documentation (decisions.md, architecture.md)

-   [x] **Invoice Payment Feature** - Completed on 2025-09-04
    -   ✅ DDS API Integration for invoice management
    -   ✅ Three-tab interface: Dashboard, Waiting Payment, Paid Invoices
    -   ✅ DataTables implementation with search, sorting, and pagination
    -   ✅ "Mark as Paid" functionality with payment modal
    -   ✅ Real-time dashboard statistics
    -   ✅ Automatic table refresh after payment updates
    -   ✅ Environment variable configuration (DDS_API_URL, DDS_API_KEY, DDS_DEPARTMENT_CODE)
    -   ✅ Permission-based access control (`akses_invoice_payment`)
    -   ✅ Error handling and logging for API communication
    -   ✅ Date formatting (dd-mmm-yyyy) and currency formatting
    -   ✅ Days calculation showing invoice age
    -   ✅ Project column displaying `invoice_project` values
    -   ✅ Manual refresh buttons for tables
    -   ✅ CSRF protection and AJAX handling

## Backlog

-   [ ] Advanced filtering options
-   [ ] Export functionality for reports
-   [ ] Email notifications
-   [ ] Audit trail implementation
-   [ ] Multi-language support
-   [ ] API rate limiting improvements
-   [ ] Caching implementation
-   [ ] Real-time notifications
-   [ ] Advanced search capabilities
-   [ ] Bulk operations
-   [ ] Mobile app development
-   [ ] Integration with external systems
-   [ ] Advanced analytics dashboard
-   [ ] Automated testing suite
-   [ ] Performance monitoring
-   [ ] Security audit
-   [ ] Documentation updates
-   [ ] User training materials
-   [ ] Backup and recovery procedures
-   [ ] Disaster recovery plan
