# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**Sensorica** is an industrial production management platform built on Laravel 8.x with PHP 8.3. It integrates IoT sensors, RFID readers, SCADA/Modbus systems, and external ERPs for real-time production monitoring, Kanban-style order management, and OEE (Overall Equipment Effectiveness) calculations.

## Essential Commands

### Development & Build
```bash
# Laravel
php artisan serve                    # Local development server
php artisan migrate                  # Run database migrations
php artisan config:clear             # Clear configuration cache
php artisan cache:clear              # Clear application cache
composer install                     # Install PHP dependencies
composer dump-autoload               # Regenerate autoloader

# Frontend assets (Laravel Mix)
npm install                          # Install Node dependencies
npm run dev                          # Build for development
npm run production                   # Build for production
npm run watch                        # Watch for changes
```

### Testing
```bash
php artisan test                     # Run all tests
./vendor/bin/phpunit                 # Run PHPUnit directly
./vendor/bin/phpunit tests/Unit      # Run only unit tests
./vendor/bin/phpunit tests/Feature   # Run only feature tests
./vendor/bin/phpunit --filter=TestName  # Run specific test
```

### Background Services (Supervisor)
```bash
sudo supervisorctl status            # Check all services
sudo supervisorctl restart all       # Restart all services
sudo supervisorctl restart laravel-mqtt-subscriber  # Restart specific service
```

## Architecture Overview

### Core Stack
- **Backend**: Laravel 8.x (MVC) with Eloquent ORM
- **Database**: MySQL/Percona Server
- **Frontend**: Blade templates + JavaScript (jQuery, Bootstrap 4)
- **Real-time**: MQTT protocol for sensor/device communication
- **Background Jobs**: Supervisor-managed processes (Laravel commands + Node.js services)

### Directory Structure
```
app/
├── Console/Commands/     # Artisan commands (MQTT subscribers, OEE calculators, sync jobs)
├── Http/Controllers/     # Web & API controllers
│   ├── Api/              # REST API controllers
│   └── Auth/             # Authentication controllers
├── Models/               # Eloquent models with events (creating/saving/saved hooks)
├── Services/             # Business logic services (MqttService, ApiService)
├── Helpers/              # Global helpers (MqttHelper, MqttPersistentHelper)
└── DataTables/           # Yajra DataTables classes

node/                     # Node.js microservices
├── client-modbus.js      # Modbus/SCADA integration
├── client-mqtt-rfid.js   # RFID reader integration
├── client-mqtt-sensors.js # Sensor data processing
├── sensor-transformer.js  # Value transformation service
├── mqtt-rfid-to-api.js   # RFID gateway with WebSocket
├── sender-mqtt-server*.js # MQTT message publishers
└── connect-whatsapp.js   # WhatsApp integration

routes/
├── web.php               # Web routes (~50KB, extensive)
└── api.php               # API routes for external integrations

config/                   # Laravel configuration files
*.conf                    # Supervisor configuration templates (root directory)
```

### Key Domain Models

**Production Flow**:
- `Customer` → has many `ProductionLine` → has many `ProductionOrder`
- `OriginalOrder` (from ERP) → has many `OriginalOrderProcess` → has many `OriginalOrderArticle`
- `ProductionOrder.status`: 0=Pending, 1=In Progress, 2=Finished, 3=Incident

**Sensor/Device Integration**:
- `Sensor` → belongs to `ProductionLine`, has MQTT topic configuration
- `MonitorOee` → OEE configuration per production line
- `Modbus` → SCADA/PLC integration settings
- `RfidAnt` / `RfidList` → RFID antenna configuration

### Model Events (Critical Behavior)

Models extensively use Eloquent events for business logic:

- **ProductionOrder**:
  - `creating`: Auto-calculates `orden`, archives duplicates
  - `saving`: Sets `finished_at` when status becomes 2
  - `saved`: Updates related `OriginalOrderProcess.finished`

- **OriginalOrderProcess**:
  - `saved`: Cascades updates to parent `OriginalOrder` (finished/in_stock status)

- **Sensor/MonitorOee**:
  - `updating/deleted`: Triggers `sudo supervisorctl restart all`

### Background Services Architecture

Services communicate via MQTT broker and are managed by Supervisor:

**Laravel Commands** (PHP):
- `production:calculate-monitor-oee` - Real-time OEE calculations
- `orders:check` - Sync orders from external APIs
- `mqtt:subscribe-local` - Process barcode/sensor MQTT messages
- `shift:check` - Manage work shifts

**Node.js Services**:
- `client-modbus.js` - Modbus/TCP PLC communication
- `mqtt-rfid-to-api.js` - RFID gateway with REST + WebSocket
- `sensor-transformer.js` - Value normalization (ranges → states)
- `connect-whatsapp.js` - WhatsApp notifications via Baileys

## API Integration Points

### Incoming Webhooks (for ERPs)
```
POST /api/incoming/original-orders     # Create/update orders
DELETE /api/incoming/original-orders/{id}  # Delete order
Authorization: Bearer <customer.token>
```

### Callback System
Production order state changes trigger HTTP callbacks to configured ERP endpoints. Managed by `callbacks:process` command with retry logic.

## Environment Configuration

Key `.env` variables:
```
# MQTT Broker
MQTT_SENSORICA_SERVER=
MQTT_SENSORICA_PORT=

# AI Integration (optional)
AI_URL=
AI_TOKEN=

# Callback processing
CALLBACK_MAX_ATTEMPTS=20
```

## Development Patterns

### Controllers
- Use `AppBaseController` as base class
- DataTables for index views (Yajra DataTables)
- Return JSON for AJAX requests, Blade views for full pages

### Permissions
- Spatie Laravel Permission package
- Check with `@can('permission-name')` in Blade
- Middleware: `permission:permission-name`

### Translations
- JSON files in `resources/lang/{locale}.json`
- Use `__('key')` or `{{ __('key') }}` in Blade

### Frontend
- Bootstrap 4 + jQuery
- DataTables for tables with server-side processing
- Toast notifications via Brian2694/laravel-toastr

## Common Operations

### Adding a New Feature
1. Create migration: `php artisan make:migration create_feature_table`
2. Create model: `php artisan make:model Feature`
3. Create controller: `php artisan make:controller FeatureController`
4. Add routes to `routes/web.php` or `routes/api.php`
5. Create Blade views in `resources/views/`
6. Add permissions via seeder if needed

### Debugging MQTT Issues
1. Check Supervisor status: `sudo supervisorctl status`
2. View logs: `tail -f storage/logs/laravel.log`
3. Check Node service logs in Supervisor output
4. Verify MQTT broker connectivity

### Database
- Uses Percona Server for MySQL
- Migrations in `database/migrations/`
- Seeders for permissions/roles in `database/seeders/`
