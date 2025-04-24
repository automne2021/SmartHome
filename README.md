# SmartHome Project

## Overview
The SmartHome project is a web-based application designed to control and monitor various hardware devices within a home environment. This includes managing devices such as lamps, fans, doors, and sensors for temperature and humidity. The project integrates with Adafruit services to facilitate communication between the web application and the hardware components.

## Features
- Control hardware devices (lamp, fan, door) through a user-friendly interface.
- Monitor real-time data from temperature and humidity sensors.
- Store and retrieve device and sensor data using a database.
- Visualize historical sensor data and analytics.

## Project Structure
```
smarthome-project
├── src
│   ├── assets
│   │   ├── css
│   │   │   └── style.css
│   │   └── js
│   │       ├── main.js
│   │       └── adafruitAPI.js
│   ├── config
│   │   ├── database.php
│   │   └── adafruit_config.php
│   ├── controllers
│   │   ├── DeviceController.php
│   │   └── SensorController.php
│   ├── models
│   │   ├── Device.php
│   │   └── SensorData.php
│   ├── views
│   │   ├── dashboard.php
│   │   ├── devices.php
│   │   └── analytics.php
│   └── utils
│       └── DataProcessor.php
├── public
│   └── index.php
├── database
│   ├── migrations
│   │   └── init.sql
│   └── schema.js
├── config.json
└── README.md
```

## Installation
1. Clone the repository to your local machine.
2. Navigate to the project directory.
3. Set up the database using the SQL commands in `database/migrations/init.sql`.
4. Configure the database connection in `src/config/database.php`.
5. Set up Adafruit API credentials in `src/config/adafruit_config.php`.
6. Run a local server to access the application.

## Usage
- Access the application through `public/index.php`.
- Use the dashboard to view and control devices.
- Monitor sensor data in real-time and view analytics.

## Contributing
Contributions are welcome! Please submit a pull request or open an issue for any enhancements or bug fixes.

## License
This project is licensed under the MIT License. See the LICENSE file for more details.