-- DogeSeeds.org — Global NGOs, scouts & volunteer hubs (sample map data)
-- Run AFTER install wizard (needs user id = 1, usually your admin account)
-- Also run migrate-v2.sql and migrate-v3.sql if your DB is from an older install
--
-- Includes logos in uploads/seed/ (upload that folder with the SQL import)
-- Safe to re-run: skips if organizations with these names already exist

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ── International Red Cross (Geneva) ──
INSERT INTO organizations (user_id, name, type, description, offers_categories, needs_categories, contact_email, contact_phone, website, show_contact_public, verified)
SELECT 1, 'International Red Cross', 'ngo',
  'Global humanitarian network. Emergency aid, food, shelter and essentials for people in crisis. Contact your national society for local help.',
  '["food","essentials","clothing"]', '["essentials"]', 'contact@icrc.org', '+41 22 734 60 01', 'https://www.icrc.org', 1, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE name = 'International Red Cross');

INSERT INTO locations (organization_id, name, latitude, longitude, address, city, country, instructions, image_path)
SELECT o.id, 'ICRC Headquarters', 46.22760000, 6.13180000,
  '19 Avenue de la Paix', 'Geneva', 'Switzerland',
  'International coordination centre. For local assistance, contact your national Red Cross or Red Crescent society.',
  'uploads/seed/icrc.svg'
FROM organizations o WHERE o.name = 'International Red Cross'
  AND NOT EXISTS (SELECT 1 FROM locations l JOIN organizations o2 ON l.organization_id = o2.id WHERE o2.name = 'International Red Cross');

-- ── Salvation Army ──
INSERT INTO organizations (user_id, name, type, description, offers_categories, needs_categories, contact_email, contact_phone, website, show_contact_public, verified)
SELECT 1, 'The Salvation Army', 'ngo',
  'Faith-based charity providing food banks, shelters, clothing and social support in 130+ countries.',
  '["food","clothing","essentials"]', '["food","clothing"]', 'info@salvationarmy.org', '+44 20 7367 4500', 'https://www.salvationarmy.org', 1, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE name = 'The Salvation Army');

INSERT INTO locations (organization_id, name, latitude, longitude, address, city, country, instructions, image_path)
SELECT o.id, 'Salvation Army International HQ', 51.49940000, -0.12700000,
  '101 Queen Victoria Street', 'London', 'United Kingdom',
  'Find your nearest corps or food programme via the national website.',
  'uploads/seed/salvation-army.svg'
FROM organizations o WHERE o.name = 'The Salvation Army'
  AND NOT EXISTS (SELECT 1 FROM locations l JOIN organizations o2 ON l.organization_id = o2.id WHERE o2.name = 'The Salvation Army');

-- ── World Food Programme ──
INSERT INTO organizations (user_id, name, type, description, offers_categories, needs_categories, contact_email, contact_phone, website, show_contact_public, verified)
SELECT 1, 'World Food Programme', 'ngo',
  'UN agency fighting hunger worldwide. Food assistance, school meals and emergency relief logistics.',
  '["food"]', '["food","essentials"]', 'wfp.info@wfp.org', '+39 06 6513 1', 'https://www.wfp.org', 1, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE name = 'World Food Programme');

INSERT INTO locations (organization_id, name, latitude, longitude, address, city, country, instructions, image_path)
SELECT o.id, 'WFP Headquarters', 41.87960000, 12.47830000,
  'Via Cesare Giulio Viola 68', 'Rome', 'Italy',
  'Global hunger response hub. Local food support is delivered through WFP country offices and partners.',
  'uploads/seed/wfp.svg'
FROM organizations o WHERE o.name = 'World Food Programme'
  AND NOT EXISTS (SELECT 1 FROM locations l JOIN organizations o2 ON l.organization_id = o2.id WHERE o2.name = 'World Food Programme');

-- ── UNICEF ──
INSERT INTO organizations (user_id, name, type, description, offers_categories, needs_categories, contact_email, contact_phone, website, show_contact_public, verified)
SELECT 1, 'UNICEF', 'ngo',
  'UN agency for children. Nutrition, hygiene kits, education supplies and emergency relief for families.',
  '["food","essentials","toys","clothing"]', '["essentials","toys"]', 'help@unicef.org', '+1 212 686 5522', 'https://www.unicef.org', 1, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE name = 'UNICEF');

INSERT INTO locations (organization_id, name, latitude, longitude, address, city, country, instructions, image_path)
SELECT o.id, 'UNICEF House', 40.74900000, -73.96800000,
  '3 United Nations Plaza', 'New York', 'United States',
  'Global programmes office. Contact UNICEF in your country for local family support services.',
  'uploads/seed/unicef.png'
FROM organizations o WHERE o.name = 'UNICEF'
  AND NOT EXISTS (SELECT 1 FROM locations l JOIN organizations o2 ON l.organization_id = o2.id WHERE o2.name = 'UNICEF');

-- ── Feeding America ──
INSERT INTO organizations (user_id, name, type, description, offers_categories, needs_categories, contact_email, contact_phone, website, show_contact_public, verified)
SELECT 1, 'Feeding America', 'ngo',
  'Largest US hunger-relief network. Connects people with food banks and meal programmes nationwide.',
  '["food"]', '["food"]', 'info@feedingamerica.org', '+1 800 771 2303', 'https://www.feedingamerica.org', 1, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE name = 'Feeding America');

INSERT INTO locations (organization_id, name, latitude, longitude, address, city, country, instructions, image_path)
SELECT o.id, 'Feeding America National Office', 41.88680000, -87.63840000,
  '161 North Clark Street', 'Chicago', 'United States',
  'Use the food bank locator on feedingamerica.org to find free groceries near you.',
  'uploads/seed/feeding-america.svg'
FROM organizations o WHERE o.name = 'Feeding America'
  AND NOT EXISTS (SELECT 1 FROM locations l JOIN organizations o2 ON l.organization_id = o2.id WHERE o2.name = 'Feeding America');

-- ── Oxfam ──
INSERT INTO organizations (user_id, name, type, description, offers_categories, needs_categories, contact_email, contact_phone, website, show_contact_public, verified)
SELECT 1, 'Oxfam International', 'ngo',
  'Anti-poverty confederation. Food, water, shelter and fair-trade charity shops in many countries.',
  '["food","clothing","essentials"]', '["food","clothing"]', 'support@oxfam.org.uk', '+44 1865 47 2626', 'https://www.oxfam.org', 1, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE name = 'Oxfam International');

INSERT INTO locations (organization_id, name, latitude, longitude, address, city, country, instructions, image_path)
SELECT o.id, 'Oxfam GB', 51.75200000, -1.25770000,
  'Oxfam House, John Smith Drive', 'Oxford', 'United Kingdom',
  'Charity shops and emergency programmes. Visit oxfam.org for your national Oxfam affiliate.',
  'uploads/seed/oxfam.svg'
FROM organizations o WHERE o.name = 'Oxfam International'
  AND NOT EXISTS (SELECT 1 FROM locations l JOIN organizations o2 ON l.organization_id = o2.id WHERE o2.name = 'Oxfam International');

-- ── World Organization of the Scout Movement ──
INSERT INTO organizations (user_id, name, type, description, offers_categories, needs_categories, contact_email, contact_phone, website, show_contact_public, verified)
SELECT 1, 'World Organization of the Scout Movement', 'scout',
  'Global scout movement. Youth volunteers run food drives, community service and disaster response worldwide.',
  '["food","clothing","toys","essentials"]', '["food","essentials"]', 'info@scout.org', '+41 22 705 10 10', 'https://www.scout.org', 1, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE name = 'World Organization of the Scout Movement');

INSERT INTO locations (organization_id, name, latitude, longitude, address, city, country, instructions, image_path)
SELECT o.id, 'World Scout Bureau', 46.21970000, 6.14370000,
  'Route de Florissant 71', 'Geneva', 'Switzerland',
  'Contact your national scout association to join local service projects and donation drives.',
  'uploads/seed/scouts-world.svg'
FROM organizations o WHERE o.name = 'World Organization of the Scout Movement'
  AND NOT EXISTS (SELECT 1 FROM locations l JOIN organizations o2 ON l.organization_id = o2.id WHERE o2.name = 'World Organization of the Scout Movement');

-- ── Scouts UK ──
INSERT INTO organizations (user_id, name, type, description, offers_categories, needs_categories, contact_email, contact_phone, website, show_contact_public, verified)
SELECT 1, 'Scouts UK', 'scout',
  'UK scout association. Young people and adult volunteers support food banks, litter picks and community aid.',
  '["food","clothing","toys"]', '["food"]', 'info@scouts.org.uk', '+44 20 8433 7100', 'https://www.scouts.org.uk', 1, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE name = 'Scouts UK');

INSERT INTO locations (organization_id, name, latitude, longitude, address, city, country, instructions, image_path)
SELECT o.id, 'Scouts UK Headquarters', 51.49250000, -0.14860000,
  'Gilwell Park, Chingford', 'London', 'United Kingdom',
  'Find a local scout group for volunteer-led community support near you.',
  'uploads/seed/scouts-uk.svg'
FROM organizations o WHERE o.name = 'Scouts UK'
  AND NOT EXISTS (SELECT 1 FROM locations l JOIN organizations o2 ON l.organization_id = o2.id WHERE o2.name = 'Scouts UK');

-- ── Escoteiros de Portugal ──
INSERT INTO organizations (user_id, name, type, description, offers_categories, needs_categories, contact_email, contact_phone, website, show_contact_public, verified)
SELECT 1, 'Escoteiros de Portugal', 'scout',
  'Portuguese scout association. Groups organise food collections, solidarity camps and help for families in need.',
  '["food","clothing","toys","essentials"]', '["food","essentials"]', 'geral@escoteiros.pt', '+351 21 781 9800', 'https://www.escoteiros.pt', 1, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE name = 'Escoteiros de Portugal');

INSERT INTO locations (organization_id, name, latitude, longitude, address, city, country, instructions, image_path)
SELECT o.id, 'Escoteiros de Portugal', 38.73690000, -9.14260000,
  'Rua António Silva 58C', 'Lisbon', 'Portugal',
  'Contact your local grupo escoteiro for volunteer activities and community collections.',
  'uploads/seed/escoteiros-pt.svg'
FROM organizations o WHERE o.name = 'Escoteiros de Portugal'
  AND NOT EXISTS (SELECT 1 FROM locations l JOIN organizations o2 ON l.organization_id = o2.id WHERE o2.name = 'Escoteiros de Portugal');

-- ── Food Bank For New York City ──
INSERT INTO organizations (user_id, name, type, description, offers_categories, needs_categories, contact_email, contact_phone, website, show_contact_public, verified)
SELECT 1, 'Food Bank For New York City', 'ngo',
  'Major urban food bank. Free groceries and meals through partner pantries and soup kitchens.',
  '["food"]', '["food"]', 'info@foodbanknyc.org', '+1 212 566 7855', 'https://www.foodbanknyc.org', 1, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE name = 'Food Bank For New York City');

INSERT INTO locations (organization_id, name, latitude, longitude, address, city, country, instructions, image_path)
SELECT o.id, 'Food Bank For New York City', 40.74890000, -73.99200000,
  '355 Food Center Drive', 'New York', 'United States',
  'Use the pantry locator on foodbanknyc.org for free food near you.',
  'uploads/seed/food-bank-nyc.svg'
FROM organizations o WHERE o.name = 'Food Bank For New York City'
  AND NOT EXISTS (SELECT 1 FROM locations l JOIN organizations o2 ON l.organization_id = o2.id WHERE o2.name = 'Food Bank For New York City');

-- ── Banques Alimentaires (France) ──
INSERT INTO organizations (user_id, name, type, description, offers_categories, needs_categories, contact_email, contact_phone, website, show_contact_public, verified)
SELECT 1, 'Banques Alimentaires', 'ngo',
  'French food bank federation. Collects and redistributes surplus food to people facing hardship.',
  '["food"]', '["food"]', 'contact@banquealimentaire.org', '+33 1 43 38 34 00', 'https://www.banquealimentaire.org', 1, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE name = 'Banques Alimentaires');

INSERT INTO locations (organization_id, name, latitude, longitude, address, city, country, instructions, image_path)
SELECT o.id, 'Fédération Française des Banques Alimentaires', 48.85660000, 2.35220000,
  '39 Rue Jean-Pierre Timbaud', 'Paris', 'France',
  'Find your département food bank on banquealimentaire.org.',
  'uploads/seed/banque-alimentaire.svg'
FROM organizations o WHERE o.name = 'Banques Alimentaires'
  AND NOT EXISTS (SELECT 1 FROM locations l JOIN organizations o2 ON l.organization_id = o2.id WHERE o2.name = 'Banques Alimentaires');

-- ── Tzu Chi Foundation ──
INSERT INTO organizations (user_id, name, type, description, offers_categories, needs_categories, contact_email, contact_phone, website, show_contact_public, verified)
SELECT 1, 'Tzu Chi Foundation', 'ngo',
  'International Buddhist charity. Disaster relief, food, medical aid and long-term community support.',
  '["food","essentials","clothing"]', '["food","essentials"]', 'info@tzuchi.org', '+886 3 856 1825', 'https://www.tzuchi.org', 1, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE name = 'Tzu Chi Foundation');

INSERT INTO locations (organization_id, name, latitude, longitude, address, city, country, instructions, image_path)
SELECT o.id, 'Tzu Chi Foundation Global HQ', 25.03300000, 121.56540000,
  'No. 757, Zhongzheng Road', 'Hualien', 'Taiwan',
  'Global humanitarian missions. Contact local Tzu Chi chapter for volunteer and aid programmes.',
  'uploads/seed/tzu-chi.svg'
FROM organizations o WHERE o.name = 'Tzu Chi Foundation'
  AND NOT EXISTS (SELECT 1 FROM locations l JOIN organizations o2 ON l.organization_id = o2.id WHERE o2.name = 'Tzu Chi Foundation');

-- ── Caritas Internationalis ──
INSERT INTO organizations (user_id, name, type, description, offers_categories, needs_categories, contact_email, contact_phone, website, show_contact_public, verified)
SELECT 1, 'Caritas Internationalis', 'ngo',
  'Catholic humanitarian network in 200+ countries. Food, shelter, migration support and social services.',
  '["food","clothing","essentials"]', '["food","essentials"]', 'communications@caritas.org', '+39 06 698 79 51', 'https://www.caritas.org', 1, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE name = 'Caritas Internationalis');

INSERT INTO locations (organization_id, name, latitude, longitude, address, city, country, instructions, image_path)
SELECT o.id, 'Caritas Internationalis', 41.90220000, 12.45390000,
  'Palazzo San Calisto, Piazza San Calisto', 'Rome', 'Italy',
  'Reach your national Caritas office for local food and social assistance.',
  'uploads/seed/caritas.svg'
FROM organizations o WHERE o.name = 'Caritas Internationalis'
  AND NOT EXISTS (SELECT 1 FROM locations l JOIN organizations o2 ON l.organization_id = o2.id WHERE o2.name = 'Caritas Internationalis');

-- ── Volunteers of America ──
INSERT INTO organizations (user_id, name, type, description, offers_categories, needs_categories, contact_email, contact_phone, website, show_contact_public, verified)
SELECT 1, 'Volunteers of America', 'volunteer',
  'US nonprofit offering housing, meals, veterans support and community volunteer programmes.',
  '["food","clothing","essentials"]', '["food","essentials"]', 'info@voa.org', '+1 703 341 5000', 'https://www.voa.org', 1, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE name = 'Volunteers of America');

INSERT INTO locations (organization_id, name, latitude, longitude, address, city, country, instructions, image_path)
SELECT o.id, 'Volunteers of America National Office', 38.80480000, -77.04300000,
  '1660 Duke Street', 'Alexandria', 'United States',
  'Search voa.org for local housing, food and volunteer opportunities.',
  'uploads/seed/voa.svg'
FROM organizations o WHERE o.name = 'Volunteers of America'
  AND NOT EXISTS (SELECT 1 FROM locations l JOIN organizations o2 ON l.organization_id = o2.id WHERE o2.name = 'Volunteers of America');

-- ── Sample donations (visible on map list/detail) ──
INSERT INTO donations (location_id, title, description, category, quantity, pickup_start, pickup_end, status)
SELECT l.id, 'Emergency food parcels', 'Contact national society for food and essentials distribution.', 'food', 'Varies', NOW(), DATE_ADD(NOW(), INTERVAL 90 DAY), 'available'
FROM locations l JOIN organizations o ON l.organization_id = o.id WHERE o.name = 'International Red Cross'
  AND NOT EXISTS (SELECT 1 FROM donations d WHERE d.location_id = l.id AND d.title = 'Emergency food parcels');

INSERT INTO donations (location_id, title, description, category, quantity, pickup_start, pickup_end, status)
SELECT l.id, 'Food bank referral', 'Free groceries through partner pantries nationwide.', 'food', 'Ongoing', NOW(), DATE_ADD(NOW(), INTERVAL 90 DAY), 'available'
FROM locations l JOIN organizations o ON l.organization_id = o.id WHERE o.name = 'Feeding America'
  AND NOT EXISTS (SELECT 1 FROM donations d WHERE d.location_id = l.id AND d.title = 'Food bank referral');

INSERT INTO donations (location_id, title, description, category, quantity, pickup_start, pickup_end, status)
SELECT l.id, 'Scout service project', 'Youth volunteers collecting food and essentials for local families.', 'essentials', 'Community drive', NOW(), DATE_ADD(NOW(), INTERVAL 90 DAY), 'available'
FROM locations l JOIN organizations o ON l.organization_id = o.id WHERE o.name = 'Escoteiros de Portugal'
  AND NOT EXISTS (SELECT 1 FROM donations d WHERE d.location_id = l.id AND d.title = 'Scout service project');

INSERT INTO donations (location_id, title, description, category, quantity, pickup_start, pickup_end, status)
SELECT l.id, 'Charity shop clothing', 'Affordable and donated clothing at Oxfam shops.', 'clothing', 'In store', NOW(), DATE_ADD(NOW(), INTERVAL 90 DAY), 'available'
FROM locations l JOIN organizations o ON l.organization_id = o.id WHERE o.name = 'Oxfam International'
  AND NOT EXISTS (SELECT 1 FROM donations d WHERE d.location_id = l.id AND d.title = 'Charity shop clothing');

INSERT INTO donations (location_id, title, description, category, quantity, pickup_start, pickup_end, status)
SELECT l.id, 'Children supplies', 'Hygiene kits, nutrition support and learning materials.', 'toys', 'Programme-based', NOW(), DATE_ADD(NOW(), INTERVAL 90 DAY), 'available'
FROM locations l JOIN organizations o ON l.organization_id = o.id WHERE o.name = 'UNICEF'
  AND NOT EXISTS (SELECT 1 FROM donations d WHERE d.location_id = l.id AND d.title = 'Children supplies');

-- ── Fix contact visibility for orgs already inserted (safe re-run) ──
UPDATE organizations SET show_contact_public = 1, contact_email = 'contact@icrc.org', contact_phone = '+41 22 734 60 01' WHERE name = 'International Red Cross';
UPDATE organizations SET show_contact_public = 1, contact_email = 'info@salvationarmy.org', contact_phone = '+44 20 7367 4500' WHERE name = 'The Salvation Army';
UPDATE organizations SET show_contact_public = 1, contact_email = 'wfp.info@wfp.org', contact_phone = '+39 06 6513 1' WHERE name = 'World Food Programme';
UPDATE organizations SET show_contact_public = 1, contact_email = 'help@unicef.org', contact_phone = '+1 212 686 5522' WHERE name = 'UNICEF';
UPDATE organizations SET show_contact_public = 1, contact_email = 'info@feedingamerica.org', contact_phone = '+1 800 771 2303' WHERE name = 'Feeding America';
UPDATE organizations SET show_contact_public = 1, contact_email = 'support@oxfam.org.uk', contact_phone = '+44 1865 47 2626' WHERE name = 'Oxfam International';
UPDATE organizations SET show_contact_public = 1, contact_email = 'info@scout.org', contact_phone = '+41 22 705 10 10' WHERE name = 'World Organization of the Scout Movement';
UPDATE organizations SET show_contact_public = 1, contact_email = 'info@scouts.org.uk', contact_phone = '+44 20 8433 7100' WHERE name = 'Scouts UK';
UPDATE organizations SET show_contact_public = 1, contact_email = 'geral@escoteiros.pt', contact_phone = '+351 21 781 9800' WHERE name = 'Escoteiros de Portugal';
UPDATE organizations SET show_contact_public = 1, contact_email = 'info@foodbanknyc.org', contact_phone = '+1 212 566 7855' WHERE name = 'Food Bank For New York City';
UPDATE organizations SET show_contact_public = 1, contact_email = 'contact@banquealimentaire.org', contact_phone = '+33 1 43 38 34 00' WHERE name = 'Banques Alimentaires';
UPDATE organizations SET show_contact_public = 1, contact_email = 'info@tzuchi.org', contact_phone = '+886 3 856 1825' WHERE name = 'Tzu Chi Foundation';
UPDATE organizations SET show_contact_public = 1, contact_email = 'communications@caritas.org', contact_phone = '+39 06 698 79 51' WHERE name = 'Caritas Internationalis';
UPDATE organizations SET show_contact_public = 1, contact_email = 'info@voa.org', contact_phone = '+1 703 341 5000' WHERE name = 'Volunteers of America';

SET FOREIGN_KEY_CHECKS = 1;
