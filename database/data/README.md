# Kenya administrative data

`import_npm_locations.php` imports the maintained `kenya-locations` npm package. It currently provides 47 counties, 307 sub-counties, 290 constituencies, 1,448 wards, 916 localities, and 1,829 areas.

Regenerate and import the current npm dataset:

```powershell
node database/export_npm_locations.mjs
php database/import_npm_locations.php
```

The older `import_admin_data.php` imports:

- 47 counties, constituencies, and wards from `mbithuka/Counties` (`restructured_data.json`)
- Available location and sub-location records from `kelvinsnir/Kenya-Counties-json` (`sublocation.json`)

The importer downloads these public JSON sources when run. Village boundaries are not included because no complete, authoritative village registry was available in the source datasets. Add verified village rows using `villages.csv.example`, then import them into `administrative_units` as `unit_type = village`.

Run after `database/schema.sql` and `config/config.php` are ready:

```powershell
php database/import_admin_data.php
```
