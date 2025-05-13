# Boarding House Management System

A PHP-based CRUD application for managing boarding houses, apartments, tenants, and landlords.

## Features

- User authentication (login/registration)
- Different user roles (Admin, Landlord, Tenant)
- Apartment listings
- Reservation system
- User management
- Admin dashboard

## Deployment Instructions

### Local Development

1. Clone the repository
2. Import `db_schema.sql` to your MySQL database
3. Configure database connection in `includes/db_connection.php`
4. Run with a local PHP server

### Production Deployment

This application can be deployed to Render or Avien hosting services. See the comprehensive [deployment guide](deployment_guide.md) for detailed instructions.

## Environment Variables

The following environment variables need to be set in production:

- `DB_HOST`: Database hostname
- `DB_NAME`: Database name
- `DB_USER`: Database username
- `DB_PASSWORD`: Database password

## License

This project is open-source software. "# NewTest" 
