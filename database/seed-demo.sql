-- Optional demo data for DogeSeeds.org (run after install)
-- Replace user_id = 1 with your admin user ID if different

INSERT INTO organizations (user_id, name, type, description, contact_email, verified) VALUES
(1, 'Mercado Central Lisboa', 'supermarket', 'Surplus food donations every evening', 'demo@dogeseeds.org', 1),
(1, 'Restaurante Solidário', 'restaurant', 'Prepared meals for community', 'demo@dogeseeds.org', 1),
(1, 'Escuteiros de Portugal', 'scout', 'Youth volunteer distribution hub', 'demo@dogeseeds.org', 1);

INSERT INTO locations (organization_id, name, latitude, longitude, address, city, country, instructions) VALUES
(1, 'Mercado Central - Entrada B', 38.72230000, -9.13930000, 'Rua Augusta', 'Lisboa', 'Portugal', 'Ask for surplus at customer service desk'),
(2, 'Restaurante Solidário', 38.71000000, -9.14000000, 'Bairro Alto', 'Lisboa', 'Portugal', 'Ring bell at side door between 18:00-20:00'),
(3, 'Grupo Escuteiro 123', 38.75000000, -9.15000000, 'Parque Eduardo VII', 'Lisboa', 'Portugal', 'Weekend collection point');

INSERT INTO donations (location_id, title, description, category, quantity, pickup_start, pickup_end, status) VALUES
(1, 'Pão do dia', 'Fresh bakery surplus from today', 'food', '~20 loaves', NOW(), DATE_ADD(NOW(), INTERVAL 4 HOUR), 'available'),
(1, 'Frutas e legumes', 'Near-expiry produce, still good', 'food', '2 crates', NOW(), DATE_ADD(NOW(), INTERVAL 6 HOUR), 'available'),
(2, 'Sopas e refeições', 'Prepared meals in containers', 'food', '15 portions', NOW(), DATE_ADD(NOW(), INTERVAL 3 HOUR), 'available'),
(3, 'Roupa infantil', 'Gently used children clothing', 'clothing', '1 box', NOW(), DATE_ADD(NOW(), INTERVAL 48 HOUR), 'available'),
(3, 'Brinquedos', 'Donated toys in good condition', 'toys', 'Mixed bag', NOW(), DATE_ADD(NOW(), INTERVAL 48 HOUR), 'available');
