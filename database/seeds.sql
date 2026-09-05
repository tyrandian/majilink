USE majilink;

INSERT INTO users (name, phone, email, password_hash, role, county) VALUES
('Amina Resident', '+254700000001', 'amina@example.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC5N5J4Vf8K5zC6dZ1a', 'resident', 'Nairobi'),
('Kijiji Water Co.', '+254700000002', 'vendor@example.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC5N5J4Vf8K5zC6dZ1a', 'vendor', 'Nairobi'),
('County Operator', '+254700000003', 'operator@example.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC5N5J4Vf8K5zC6dZ1a', 'operator', 'Nairobi'),
('MajiLink Admin', '+254700000004', 'admin@example.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC5N5J4Vf8K5zC6dZ1a', 'admin', 'Nairobi');

INSERT INTO users (name, phone, email, password_hash, role, county) VALUES
('Country Manager', '+254700000005', 'country.manager@example.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC5N5J4Vf8K5zC6dZ1a', 'country_manager', 'Nairobi'),
('Nairobi County Manager', '+254700000006', 'county.manager@example.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC5N5J4Vf8K5zC6dZ1a', 'county_manager', 'Nairobi'),
('Kasarani Constituency Manager', '+254700000007', 'constituency.manager@example.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC5N5J4Vf8K5zC6dZ1a', 'constituency_manager', 'Nairobi'),
('Mwiki Ward Manager', '+254700000008', 'ward.manager@example.test', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC5N5J4Vf8K5zC6dZ1a', 'ward_manager', 'Nairobi');

INSERT INTO vendors (user_id, business_name, phone, county, service_radius_km, verified) VALUES
(2, 'Kijiji Water Co.', '+254700000002', 'Nairobi', 20, 1);

INSERT INTO boreholes (name, county, location_text, latitude, longitude, status, operator_id) VALUES
('Kasarani Community Borehole', 'Nairobi', 'Near Mwiki market', -1.2215, 36.8962, 'working', 3),
('Ruai East Borehole', 'Nairobi', 'Ruai social hall', -1.2794, 36.9746, 'limited', 3);

INSERT INTO outage_reports (reporter_id, county, area, description, status) VALUES
(1, 'Nairobi', 'Kibera', 'No piped water since yesterday morning.', 'under_review');
