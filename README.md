# MajiLink API prototype

PHP 8 and MySQL 8 REST backend for water delivery, borehole discovery, and public outage reporting in Kenya.

## Quick start

1. Run `database/schema.sql`, then `database/seeds.sql` in MySQL.
	For an existing installation, run `database/migrations/001_administrative_hierarchy.sql` first.
2. Copy `config/config.example.php` to `config/config.php` and set the database password and a long random `app_key`.
3. Serve the public directory:

```powershell
php -S localhost:8000 -t public public/index.php
```

4. Register at `POST /api/auth/register.php`, then send `Authorization: Bearer <token>`.

Open `http://localhost:8000/` for the MajiLink web console. The frontend includes responsive views for the overview dashboard, borehole directory, delivery requests, outage reports, vendors, and account settings. It uses the same JSON API and stores the session token in browser local storage.

For the local admin demo, run `database/seeds.sql` after creating the schema, then sign in with phone `+254700000004` and password `password`. If the account already existed before the seed was applied, rerun the seed or update that user's password before testing the demo login.

All writes use JSON bodies and all responses are JSON. Roles are `resident`, `vendor`, `operator`, and `admin`.

Visibility is scoped by role and administrative unit: admins can view every area; operators can view records in their assigned unit and descendants; vendors see delivery work in their county plus their assigned requests; residents see their own deliveries and public water/outage records in their area. Water-point creation is admin-only.

Residents can see their complete delivery and outage history. Each case shows its current status, reported date, and last-updated date. Delivery statuses are `open`, `assigned`, `en_route`, `delivered`, or `cancelled`; outage statuses are `reported`, `under_review`, `confirmed`, `resolved`, or `rejected`.

## Endpoint map

- Auth: `POST /api/auth/register.php`, `POST /api/auth/login.php`, `GET /api/auth/me.php`
- Vendors: `GET /api/vendors/list.php`, `POST /api/vendors/create.php`, `PATCH /api/vendors/status.php`
- Deliveries: `POST /api/deliveries/create.php`, `GET /api/deliveries/list.php`, `PATCH /api/deliveries/update-status.php`
- Boreholes: `GET /api/boreholes/list.php`, `POST /api/boreholes/create.php`
- Outages: `POST /api/outages/create.php`, `GET /api/outages/list.php`, `PATCH /api/outages/update-status.php`
- Administrative locations: `GET /api/locations/list.php?type=county`, then pass `parent_id` to load constituencies, wards, locations, sub-locations, or villages.
- Admin visibility management: `GET /api/users/list.php`, `PATCH /api/users/update-scope.php` with `user_id` and `administrative_unit_id`.
- Managers: `GET /api/managers/list.php`, `POST /api/managers/create.php`; manager contacts are returned as phone/email fields.
- Payments: `POST /api/payments/create.php` creates an upfront payment intent or an on-delivery record.
- Water intelligence: `GET /api/coverage/list.php`, `GET /api/bills/list.php`, `POST /api/incidents/create.php`.

## Administrative hierarchy

Run `node database/export_npm_locations.mjs` followed by `php database/import_npm_locations.php` after the schema or migration. The npm package currently imports 47 counties, sub-counties, constituencies, wards, localities, and areas into `administrative_units`. The frontend forms use dependent selectors and persist `administrative_unit_id` alongside the existing text fields. Village data should be loaded from a verified `database/data/villages.csv` using the example template; village boundaries are not fabricated from incomplete public data.

Seed users all use the password `password` for local testing. Replace seed credentials before deployment. This prototype still needs OTP verification, M-Pesa integration, geospatial indexing, audit logs, rate limiting, HTTPS, and county utility integrations.

## Demo logins

All seeded demo accounts use password `password`:

- Admin: `+254700000004`
- Country manager: `+254700000005`
- County manager: `+254700000006`
- Constituency manager: `+254700000007`
- Ward manager: `+254700000008`
- Vendor: `+254700000002`

Payment selection is available on delivery requests as `on_delivery` or `upfront`. M-Pesa/card processing still requires provider credentials and webhook configuration.

The management foundation also stores utilities, connected/total households, water sources, usage categories, bills, incidents such as dirty or poisonous water, and queued SMS/WhatsApp/email notifications. Actual delivery requires integrating an SMS provider, WhatsApp Business API, email transport, and M-Pesa/card webhooks.
