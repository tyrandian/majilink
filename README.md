# MajiLink

MajiLink is an API that connects communities in Kenya with reliable water. It supports water delivery requests, borehole discovery, and outage reporting in one system.

## Problem

Access to clean water is unpredictable in many parts of Kenya. People often do not know where the nearest working borehole is, how to request a water delivery, or how to report an outage so it gets fixed quickly. MajiLink brings these three needs into a single, simple API.

## Features

- Water delivery requests: customers can request water delivery and track status
- Borehole discovery: find nearby boreholes and check their status
- Outage reporting: report an outage and follow up on repair progress

## Tech stack

- PHP
- MySQL
- REST API architecture

## Getting started

1. Clone the repository
   ```
   git clone https://github.com/tyrandian/majilink.git
   ```
2. Install dependencies (add your specific steps here, e.g. composer install)
3. Set up your environment file with database credentials
4. Run database migrations
5. Start the local server

## API overview

See [HTTP request conventions](docs/http-api.md) for supported methods, input
validation, and instructions for running the regression tests with `npm test`.

Add a short table here once your endpoints are stable, for example:

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | /api/deliveries | Create a water delivery request |
| GET | /api/boreholes | List nearby boreholes |
| POST | /api/outages | Report an outage |

## Roadmap

- Add user authentication
- Add SMS notifications for delivery and outage updates
- Add a public map view of borehole status

## Author

Built by Emmanuel Kibitok, IT professional and founder of Havana Technologies, Kenya.
