# HTTP request conventions

API paths include the `.php` suffix, for example `/api/boreholes/list.php`.

| Endpoint | Accepted methods |
| --- | --- |
| All `list.php` endpoints and `auth/me.php` | GET |
| All `create.php` endpoints, `auth/login.php`, and `auth/register.php` | POST |
| `deliveries/update-status.php`, `outages/update-status.php`, `users/update-scope.php`, and `vendors/status.php` | POST or PATCH |

Every endpoint accepts OPTIONS for CORS preflight without requiring database
configuration or authentication. Unsupported methods return HTTP 405 with a JSON
error and an `Allow` header. POST remains supported for existing update clients.
Authentication and role requirements still apply to accepted application requests.

Write requests must contain a JSON object. Empty bodies, malformed JSON, arrays,
and scalar values return HTTP 400. An empty object is valid JSON input but may
still fail an endpoint's required-field validation with HTTP 422.

## Regression tests

Install PHP 8.1+ and Node.js 18+ on your PATH, then run:

```sh
npm test
```

Set `PHP_BINARY` if PHP is installed at a different path. Tests start a temporary
local PHP server and check every endpoint's rejected methods and preflight
responses, plus JSON input validation. No database, credentials, or npm dependency
installation is required. Database-backed business operations are outside this suite.
