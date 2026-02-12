# DeskUp

**Distributed Desk Management System with Embedded Elements**

DeskUp is a comprehensive desk management platform developed as a semester project for Distributed Software Systems. It combines web technologies, embedded systems, and intelligent scheduling to create a complete smart office solution.

## 📋 Overview

DeskUp manages standing desks with smart height adjustment capabilities, user session tracking, automated cleaning schedules, and ergonomic optimization through computer vision. The system integrates multiple components:

- **Web Application** (Laravel + Tailwind CSS)
- **Desk Simulator** (Python REST API)
- **Embedded Controller** (Raspberry Pi Pico W)
- **AI Height Analyzer** (Python + MediaPipe)

## 🏗️ Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                         DeskUp System                        │
├──────────────┬──────────────┬──────────────┬────────────────┤
│  Laravel Web │ Python AI    │ Wifi2Ble     │ Raspberry Pi   │
│  Application │ Service      │ Simulator    │ Pico W         │
│  (Port 80)   │ (Port 5000)  │ (Port 8443)  │ (Embedded)     │
└──────────────┴──────────────┴──────────────┴────────────────┘
```

## ✨ Features

### Web Application
- **User Management**: Authentication, profiles, and session tracking
- **Desk Control**: Real-time desk height adjustment and monitoring
- **Smart Scheduling**: Automated recurring cleaning schedules
- **3D Layout View**: Interactive office layout visualization using Three.js
- **Health Statistics**: User activity tracking and ergonomic analytics
- **Notifications**: Real-time alerts for events and cleanings
- **PDF Export**: Generate reports and statistics
- **Admin Dashboard**: Comprehensive system management

### AI Height Analyzer
- Computer vision-based pose detection using MediaPipe
- Ergonomic assessment and posture analysis
- Height estimation from images
- Optimal desk height calculation
- Detailed anthropometric analysis

### Wifi2Ble Desk Simulator
- REST API for desk control and monitoring
- Simulates multiple desks with configurable properties
- Real-time position, speed, and status tracking
- Error logging and usage counters
- HTTPS support with SSL certificates
- Data persistence across restarts

### Pico4DeskUp (Embedded)
- OLED display for user information
- UART communication with desk hardware
- Real-time height monitoring
- User login/logout detection
- Desk assignment tracking

## 🚀 Getting Started

### Prerequisites

- Docker & Docker Compose
- PHP 8.2+ (for local development)
- Python 3.8+ (for local development)
- Node.js 18+ (for local development)
- Raspberry Pi Pico W SDK (for embedded development)

### Quick Start with Docker

1. **Clone the repository**
   ```bash
   git clone https://github.com/dricondop/DeskUp.git
   cd DeskUp
   ```

2. **Configure environment**
   ```bash
   cd DeskUp
   cp .env.example .env
   # Edit .env with your settings
   ```

3. **Start all services**
   ```bash
   docker-compose up -d
   ```

4. **Access the application**
   - Web App: http://localhost
   - Wifi2Ble Simulator: https://localhost:8443
   - Python AI Service: http://localhost:5000

### Manual Setup

#### Laravel Web Application

```bash
cd DeskUp

# Install dependencies
composer install
npm install

# Configure environment
cp .env.example .env
php artisan key:generate

# Setup database
touch database/database.sqlite
php artisan migrate
php artisan db:seed

# Start development server
composer dev
# Or separately:
# php artisan serve
# php artisan queue:listen
# npm run dev
```

#### Python AI Service

```bash
cd DeskUp/python_service

# Create virtual environment
python -m venv venv
source venv/bin/activate  # On Windows: venv\Scripts\activate

# Install dependencies
pip install -r requirements.txt

# Start service
python main.py
```

#### Wifi2Ble Simulator

```bash
cd Wifi2Ble

# Start HTTP server
python simulator/main.py --port 8000

# Or HTTPS server
python simulator/main.py --port 8443 --https \
  --certfile config/cert.pem --keyfile config/key.pem

# With custom desk count and simulation speed
python simulator/main.py --desks 100 --speed 60
```

#### Raspberry Pi Pico W

```bash
cd Pico4DeskUp

# Build the project
mkdir build && cd build
cmake ..
make

# Flash to Pico (connect via USB)
# Copy Pico4DeskUp.uf2 to the Pico drive
```

## 📚 Documentation

### Component Documentation

- [Laravel Application](./DeskUp/README.md) - Web application details
- [Python AI Service](./DeskUp/python_service/README.md) - Height analyzer documentation
- [Wifi2Ble Simulator](./Wifi2Ble/README.md) - REST API documentation

### Key Commands

#### Laravel Artisan Commands

```bash
# Run cleaning scheduler (manual)
php artisan cleaning:run

# Complete expired events
php artisan events:complete-expired

# Run scheduler continuously
php artisan schedule:work

# Run tests
composer test
# or
php artisan test
```

#### Development

```bash
# Run linter
vendor/bin/pint

# Clear cache
php artisan config:clear
php artisan cache:clear
```

## 🛠️ Technology Stack

### Backend
- **Laravel 12** - PHP web framework
- **SQLite** - Database
- **Python 3** - AI service & simulator
- **MediaPipe** - Pose detection
- **C/C++** - Embedded firmware

### Frontend
- **Tailwind CSS 4** - Styling
- **Three.js** - 3D visualization
- **Vite** - Build tool
- **Axios** - HTTP client

### Infrastructure
- **Docker** - Containerization
- **Nginx** - Web server
- **UART** - Hardware communication
- **I2C** - OLED display interface

## 📦 Project Structure

```
DeskUp/
├── DeskUp/              # Laravel web application
│   ├── app/             # Application logic
│   ├── resources/       # Views and assets
│   ├── routes/          # Route definitions
│   ├── database/        # Migrations and seeders
│   └── python_service/  # AI height analyzer
├── Wifi2Ble/           # Desk simulator API
│   ├── simulator/       # Python REST server
│   ├── config/          # SSL certificates & API keys
│   └── data/            # Persistent desk state
├── Pico4DeskUp/        # Raspberry Pi Pico firmware
│   ├── *.cpp/*.h        # Source files
│   └── build/           # Build artifacts
└── docker-compose.yml   # Docker orchestration
```

## 🔒 Security

- API key authentication for desk simulator
- HTTPS support with SSL/TLS
- Laravel authentication and authorization
- Admin role-based access control
- Rate limiting on sensitive endpoints

## 🧪 Testing

```bash
# Laravel tests
cd DeskUp
php artisan test

# Python service tests
cd DeskUp/python_service
python test_service.py

# Wifi2Ble API tests
cd Wifi2Ble
python tests/simple_api_test.py
```

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](./Wifi2Ble/LICENSE) file for details.

Copyright (c) 2024 Krzysztof Sierszecki, SDU

## 👥 Contributors

Developed as part of the SPE25 - Distributed Software Systems with Embedded Elements course.

## 🐛 Troubleshooting

### Common Issues

1. **Docker containers won't start**
   - Ensure ports 80, 5000, and 8443 are available
   - Check Docker daemon is running
   - Review logs: `docker-compose logs`

2. **Laravel migration fails**
   - Ensure database file exists: `touch database/database.sqlite`
   - Clear config cache: `php artisan config:clear`

3. **Pico won't connect**
   - Verify UART connections
   - Check I2C wiring for OLED display
   - Review serial output for errors

4. **AI service errors**
   - Install all Python dependencies
   - Ensure camera/image permissions
   - Check MediaPipe compatibility

## 📧 Support

For questions or issues, please open an issue on the [GitHub repository](https://github.com/dricondop/DeskUp).

## 🔄 Version

Current Version: 1.0.0

---

**DeskUp** - Smart Desk Management for Modern Offices
