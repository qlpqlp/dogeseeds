-- DogeSeeds.org — Additional NGOs & aid organisations (food, clothing, toys, essentials)
-- Run AFTER install wizard (uses user_id = 1 — usually your admin account)
-- Safe to re-run: skips organisations that already exist by name
--
-- Import in phpMyAdmin: select your database → Import → this file
-- Upload uploads/seed/ folder to your server (includes logo SVGs for each org)
-- Complements database/seed-global-orgs.sql (different organisations)
-- Logos are stylised map badges (not official trademarks) — same approach as seed-global-orgs

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ── Save the Children ──
INSERT INTO organizations (user_id, name, type, description, offers_categories, needs_categories, contact_email, contact_phone, website, show_contact_public, verified)
SELECT 1, 'Save the Children', 'ngo',
  'International charity for children. Emergency food, clothing, hygiene kits and learning supplies for families in crisis.',
  '["food","clothing","toys","essentials"]', '["food","essentials"]', 'supportercare@savethechildren.org.uk', '+44 20 7012 6400', 'https://www.savethechildren.net', 1, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE name = 'Save the Children');

INSERT INTO locations (organization_id, name, latitude, longitude, address, city, country, instructions, image_path, active)
SELECT o.id, 'Save the Children UK', 51.51750000, -0.12080000,
  '1 St John''s Lane', 'London', 'United Kingdom',
  'Contact your national Save the Children office for local family support programmes and donation centres.',
  'uploads/seed/save-the-children.svg',
  1
FROM organizations o WHERE o.name = 'Save the Children'
  AND NOT EXISTS (SELECT 1 FROM locations l JOIN organizations o2 ON l.organization_id = o2.id WHERE o2.name = 'Save the Children');

-- ── Action Against Hunger ──
INSERT INTO organizations (user_id, name, type, description, offers_categories, needs_categories, contact_email, contact_phone, website, show_contact_public, verified)
SELECT 1, 'Action Against Hunger', 'ngo',
  'Global hunger-relief NGO. Nutrition programmes, emergency food and water for communities facing malnutrition.',
  '["food","essentials"]', '["food"]', 'info@actionagainsthunger.org', '+33 1 70 84 70 84', 'https://www.actionagainsthunger.org', 1, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE name = 'Action Against Hunger');

INSERT INTO locations (organization_id, name, latitude, longitude, address, city, country, instructions, image_path, active)
SELECT o.id, 'Action Against Hunger HQ', 48.87380000, 2.29500000,
  '9 Rue Notre-Dame de Lorette', 'Paris', 'France',
  'Field programmes worldwide. Visit the website to find food and nutrition support in your region.',
  'uploads/seed/action-against-hunger.svg',
  1
FROM organizations o WHERE o.name = 'Action Against Hunger'
  AND NOT EXISTS (SELECT 1 FROM locations l JOIN organizations o2 ON l.organization_id = o2.id WHERE o2.name = 'Action Against Hunger');

-- ── The Trussell Trust (UK food banks) ──
INSERT INTO organizations (user_id, name, type, description, offers_categories, needs_categories, contact_email, contact_phone, website, show_contact_public, verified)
SELECT 1, 'The Trussell Trust', 'ngo',
  'UK network of food banks. Free emergency food parcels for people referred by local agencies.',
  '["food","essentials"]', '["food"]', 'info@trusselltrust.org', '+44 1722 580180', 'https://www.trusselltrust.org', 1, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE name = 'The Trussell Trust');

INSERT INTO locations (organization_id, name, latitude, longitude, address, city, country, instructions, image_path, active)
SELECT o.id, 'The Trussell Trust', 51.07080000, -1.79740000,
  'Canal Reach, Salisbury', 'Salisbury', 'United Kingdom',
  'Use the food bank finder on trusselltrust.org — bring a referral voucher from a local agency.',
  'uploads/seed/trussell-trust.svg',
  1
FROM organizations o WHERE o.name = 'The Trussell Trust'
  AND NOT EXISTS (SELECT 1 FROM locations l JOIN organizations o2 ON l.organization_id = o2.id WHERE o2.name = 'The Trussell Trust');

-- ── Banco Alimentar contra a Fome (Portugal) ──
INSERT INTO organizations (user_id, name, type, description, offers_categories, needs_categories, contact_email, contact_phone, website, show_contact_public, verified)
SELECT 1, 'Banco Alimentar contra a Fome', 'ngo',
  'Portuguese food bank federation. Collects surplus food and distributes it to people in need through partner institutions.',
  '["food"]', '["food"]', 'geral@bancoalimentar.pt', '+351 21 397 7232', 'https://www.bancoalimentar.pt', 1, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE name = 'Banco Alimentar contra a Fome');

INSERT INTO locations (organization_id, name, latitude, longitude, address, city, country, instructions, image_path, active)
SELECT o.id, 'Banco Alimentar Lisboa', 38.73690000, -9.14260000,
  'Rua da Bela Vista à Graça 5', 'Lisbon', 'Portugal',
  'Contact your local Banco Alimentar or partner institution for food assistance.',
  'uploads/seed/banco-alimentar-pt.svg',
  1
FROM organizations o WHERE o.name = 'Banco Alimentar contra a Fome'
  AND NOT EXISTS (SELECT 1 FROM locations l JOIN organizations o2 ON l.organization_id = o2.id WHERE o2.name = 'Banco Alimentar contra a Fome');

INSERT INTO locations (organization_id, name, latitude, longitude, address, city, country, instructions, image_path, active)
SELECT o.id, 'Banco Alimentar Porto', 41.15790000, -8.62910000,
  'Rua de Dom Manuel II', 'Porto', 'Portugal',
  'Regional food bank hub. Partner pantries distribute free groceries across northern Portugal.',
  'uploads/seed/banco-alimentar-pt.svg',
  1
FROM organizations o WHERE o.name = 'Banco Alimentar contra a Fome'
  AND NOT EXISTS (SELECT 1 FROM locations l WHERE l.name = 'Banco Alimentar Porto' AND l.organization_id = o.id);

-- ── Cruz Vermelha Portuguesa ──
INSERT INTO organizations (user_id, name, type, description, offers_categories, needs_categories, contact_email, contact_phone, website, show_contact_public, verified)
SELECT 1, 'Cruz Vermelha Portuguesa', 'ngo',
  'Portuguese Red Cross. Emergency aid, food parcels, clothing and social support for vulnerable people.',
  '["food","clothing","essentials"]', '["food","essentials"]', 'geral@cruzvermelha.pt', '+351 21 391 5200', 'https://www.cruzvermelha.pt', 1, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE name = 'Cruz Vermelha Portuguesa');

INSERT INTO locations (organization_id, name, latitude, longitude, address, city, country, instructions, image_path, active)
SELECT o.id, 'Cruz Vermelha Portuguesa', 38.73650000, -9.14580000,
  'Jardim 9 de Abril 9', 'Lisbon', 'Portugal',
  'Visit your local delegação for food, clothing and emergency social assistance.',
  'uploads/seed/cruz-vermelha-pt.svg',
  1
FROM organizations o WHERE o.name = 'Cruz Vermelha Portuguesa'
  AND NOT EXISTS (SELECT 1 FROM locations l JOIN organizations o2 ON l.organization_id = o2.id WHERE o2.name = 'Cruz Vermelha Portuguesa');

-- ── Tafel Deutschland (German food banks) ──
INSERT INTO organizations (user_id, name, type, description, offers_categories, needs_categories, contact_email, contact_phone, website, show_contact_public, verified)
SELECT 1, 'Tafel Deutschland', 'ngo',
  'German food bank network (Tafeln). Free groceries for people on low incomes through local distribution points.',
  '["food"]', '["food"]', 'info@tafel.de', '+49 30 240 308 10', 'https://www.tafel.de', 1, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE name = 'Tafel Deutschland');

INSERT INTO locations (organization_id, name, latitude, longitude, address, city, country, instructions, image_path, active)
SELECT o.id, 'Berliner Tafel', 52.52000000, 13.40500000,
  'Siemensdamm 62', 'Berlin', 'Germany',
  'Register at your nearest Tafel distribution point. Bring required documents as listed on tafel.de.',
  'uploads/seed/tafel-deutschland.svg',
  1
FROM organizations o WHERE o.name = 'Tafel Deutschland'
  AND NOT EXISTS (SELECT 1 FROM locations l JOIN organizations o2 ON l.organization_id = o2.id WHERE o2.name = 'Tafel Deutschland');

-- ── FareShare UK ──
INSERT INTO organizations (user_id, name, type, description, offers_categories, needs_categories, contact_email, contact_phone, website, show_contact_public, verified)
SELECT 1, 'FareShare UK', 'ngo',
  'UK surplus food redistribution charity. Partners with charities and community groups to provide free meals and groceries.',
  '["food"]', '["food"]', 'info@fareshare.org.uk', '+44 20 7394 2466', 'https://fareshare.org.uk', 1, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE name = 'FareShare UK');

INSERT INTO locations (organization_id, name, latitude, longitude, address, city, country, instructions, image_path, active)
SELECT o.id, 'FareShare UK', 51.50740000, -0.12780000,
  'Unit 7, Deptford Trading Estate', 'London', 'United Kingdom',
  'Food is distributed through partner charities. Search fareshare.org.uk for a community group near you.',
  'uploads/seed/fareshare-uk.svg',
  1
FROM organizations o WHERE o.name = 'FareShare UK'
  AND NOT EXISTS (SELECT 1 FROM locations l JOIN organizations o2 ON l.organization_id = o2.id WHERE o2.name = 'FareShare UK');

-- ── Goodwill Industries ──
INSERT INTO organizations (user_id, name, type, description, offers_categories, needs_categories, contact_email, contact_phone, website, show_contact_public, verified)
SELECT 1, 'Goodwill Industries', 'ngo',
  'Nonprofit thrift stores and job training. Affordable clothing, household essentials and community programmes.',
  '["clothing","essentials","toys"]', '["clothing","essentials"]', 'info@goodwill.org', '+1 800 466 3945', 'https://www.goodwill.org', 1, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE name = 'Goodwill Industries');

INSERT INTO locations (organization_id, name, latitude, longitude, address, city, country, instructions, image_path, active)
SELECT o.id, 'Goodwill of Greater Washington', 38.90720000, -77.03690000,
  '2200 South Dakota Avenue NE', 'Washington', 'United States',
  'Thrift stores sell donated clothing and goods at low prices. Some locations run voucher programmes.',
  'uploads/seed/goodwill.svg',
  1
FROM organizations o WHERE o.name = 'Goodwill Industries'
  AND NOT EXISTS (SELECT 1 FROM locations l JOIN organizations o2 ON l.organization_id = o2.id WHERE o2.name = 'Goodwill Industries');

-- ── Toys for Tots ──
INSERT INTO organizations (user_id, name, type, description, offers_categories, needs_categories, contact_email, contact_phone, website, show_contact_public, verified)
SELECT 1, 'Toys for Tots', 'ngo',
  'US Marine Corps Reserve programme. Collects and distributes new toys to children in need during the holidays and year-round drives.',
  '["toys"]', '["toys"]', 'support@toysfortots.org', '+1 703 640 9433', 'https://www.toysfortots.org', 1, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE name = 'Toys for Tots');

INSERT INTO locations (organization_id, name, latitude, longitude, address, city, country, instructions, image_path, active)
SELECT o.id, 'Toys for Tots Foundation', 38.80140000, -77.08950000,
  '18251 Quantico Gateway Drive', 'Triangle', 'United States',
  'Find a local toy drive or distribution campaign on toysfortots.org.',
  'uploads/seed/toys-for-tots.svg',
  1
FROM organizations o WHERE o.name = 'Toys for Tots'
  AND NOT EXISTS (SELECT 1 FROM locations l JOIN organizations o2 ON l.organization_id = o2.id WHERE o2.name = 'Toys for Tots');

-- ── St. Vincent de Paul ──
INSERT INTO organizations (user_id, name, type, description, offers_categories, needs_categories, contact_email, contact_phone, website, show_contact_public, verified)
SELECT 1, 'Society of St. Vincent de Paul', 'ngo',
  'Catholic volunteer network. Food pantries, clothing banks, home visits and emergency aid for people in poverty.',
  '["food","clothing","essentials"]', '["food","clothing"]', 'info@svdpusa.org', '+1 314 881 1600', 'https://www.svdpusa.org', 1, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE name = 'Society of St. Vincent de Paul');

INSERT INTO locations (organization_id, name, latitude, longitude, address, city, country, instructions, image_path, active)
SELECT o.id, 'St. Vincent de Paul National Council', 38.62700000, -90.19940000,
  '58 Progress Parkway', 'St. Louis', 'United States',
  'Contact your local SVdP conference for food, clothing and home visit support.',
  'uploads/seed/svdp.svg',
  1
FROM organizations o WHERE o.name = 'Society of St. Vincent de Paul'
  AND NOT EXISTS (SELECT 1 FROM locations l JOIN organizations o2 ON l.organization_id = o2.id WHERE o2.name = 'Society of St. Vincent de Paul');

-- ── Los Angeles Regional Food Bank ──
INSERT INTO organizations (user_id, name, type, description, offers_categories, needs_categories, contact_email, contact_phone, website, show_contact_public, verified)
SELECT 1, 'Los Angeles Regional Food Bank', 'ngo',
  'Major California food bank. Free groceries and meals through hundreds of partner agencies across LA County.',
  '["food"]', '["food"]', 'info@lafoodbank.org', '+1 323 234 3030', 'https://www.lafoodbank.org', 1, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE name = 'Los Angeles Regional Food Bank');

INSERT INTO locations (organization_id, name, latitude, longitude, address, city, country, instructions, image_path, active)
SELECT o.id, 'Los Angeles Regional Food Bank', 34.01950000, -118.20120000,
  '1734 East 41st Street', 'Los Angeles', 'United States',
  'Use the pantry locator on lafoodbank.org for free food near you.',
  'uploads/seed/la-food-bank.svg',
  1
FROM organizations o WHERE o.name = 'Los Angeles Regional Food Bank'
  AND NOT EXISTS (SELECT 1 FROM locations l JOIN organizations o2 ON l.organization_id = o2.id WHERE o2.name = 'Los Angeles Regional Food Bank');

-- ── Houston Food Bank ──
INSERT INTO organizations (user_id, name, type, description, offers_categories, needs_categories, contact_email, contact_phone, website, show_contact_public, verified)
SELECT 1, 'Houston Food Bank', 'ngo',
  'One of the largest US food banks. Distributes food and essentials to families through partner pantries and programmes.',
  '["food","essentials"]', '["food"]', 'info@houstonfoodbank.org', '+1 713 223 3700', 'https://www.houstonfoodbank.org', 1, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE name = 'Houston Food Bank');

INSERT INTO locations (organization_id, name, latitude, longitude, address, city, country, instructions, image_path, active)
SELECT o.id, 'Houston Food Bank', 29.76040000, -95.36980000,
  '535 Portwall Street', 'Houston', 'United States',
  'Partner pantries listed on houstonfoodbank.org. Bring ID if required by your local agency.',
  'uploads/seed/houston-food-bank.svg',
  1
FROM organizations o WHERE o.name = 'Houston Food Bank'
  AND NOT EXISTS (SELECT 1 FROM locations l JOIN organizations o2 ON l.organization_id = o2.id WHERE o2.name = 'Houston Food Bank');

-- ── Daily Bread Food Bank (Toronto) ──
INSERT INTO organizations (user_id, name, type, description, offers_categories, needs_categories, contact_email, contact_phone, website, show_contact_public, verified)
SELECT 1, 'Daily Bread Food Bank', 'ngo',
  'Toronto hunger-relief charity. Food hampers and meal programmes through agency partners across the GTA.',
  '["food"]', '["food"]', 'info@dailybread.ca', '+1 416 203 0050', 'https://www.dailybread.ca', 1, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE name = 'Daily Bread Food Bank');

INSERT INTO locations (organization_id, name, latitude, longitude, address, city, country, instructions, image_path, active)
SELECT o.id, 'Daily Bread Food Bank', 43.65320000, -79.38320000,
  '191 New Toronto Street', 'Toronto', 'Canada',
  'Agency locator on dailybread.ca. Most partner sites require referral or appointment.',
  'uploads/seed/daily-bread.svg',
  1
FROM organizations o WHERE o.name = 'Daily Bread Food Bank'
  AND NOT EXISTS (SELECT 1 FROM locations l JOIN organizations o2 ON l.organization_id = o2.id WHERE o2.name = 'Daily Bread Food Bank');

-- ── OzHarvest (Australia) ──
INSERT INTO organizations (user_id, name, type, description, offers_categories, needs_categories, contact_email, contact_phone, website, show_contact_public, verified)
SELECT 1, 'OzHarvest', 'ngo',
  'Australian food rescue charity. Surplus food redistributed free to people in need through community organisations.',
  '["food"]', '["food"]', 'info@ozharvest.org', '+61 2 9516 3877', 'https://www.ozharvest.org', 1, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE name = 'OzHarvest');

INSERT INTO locations (organization_id, name, latitude, longitude, address, city, country, instructions, image_path, active)
SELECT o.id, 'OzHarvest Sydney', -33.86880000, 151.20930000,
  '46-62 Maddox Street', 'Sydney', 'Australia',
  'Community meals and food support via partner charities. Check ozharvest.org for programmes near you.',
  'uploads/seed/ozharvest.svg',
  1
FROM organizations o WHERE o.name = 'OzHarvest'
  AND NOT EXISTS (SELECT 1 FROM locations l JOIN organizations o2 ON l.organization_id = o2.id WHERE o2.name = 'OzHarvest');

-- ── Emmaüs International ──
INSERT INTO organizations (user_id, name, type, description, offers_categories, needs_categories, contact_email, contact_phone, website, show_contact_public, verified)
SELECT 1, 'Emmaüs International', 'ngo',
  'Solidarity movement. Second-hand shops, housing and food support — proceeds fund aid for people excluded from society.',
  '["clothing","food","essentials"]', '["clothing","food"]', 'contact@emmaus-international.org', '+33 1 40 21 70 20', 'https://www.emmaus-international.org', 1, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE name = 'Emmaüs International');

INSERT INTO locations (organization_id, name, latitude, longitude, address, city, country, instructions, image_path, active)
SELECT o.id, 'Emmaüs France', 48.85660000, 2.35220000,
  '47 rue de la Montagne Sainte-Geneviève', 'Paris', 'France',
  'Emmaüs communities sell donated goods at low prices and run solidarity programmes. Find a community near you.',
  'uploads/seed/emmaus.svg',
  1
FROM organizations o WHERE o.name = 'Emmaüs International'
  AND NOT EXISTS (SELECT 1 FROM locations l JOIN organizations o2 ON l.organization_id = o2.id WHERE o2.name = 'Emmaüs International');

-- ── Dress for Success ──
INSERT INTO organizations (user_id, name, type, description, offers_categories, needs_categories, contact_email, contact_phone, website, show_contact_public, verified)
SELECT 1, 'Dress for Success', 'ngo',
  'Global nonprofit providing professional clothing and career support to women entering the workforce.',
  '["clothing","essentials"]', '["clothing"]', 'info@dressforsuccess.org', '+1 212 532 1922', 'https://dressforsuccess.org', 1, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE name = 'Dress for Success');

INSERT INTO locations (organization_id, name, latitude, longitude, address, city, country, instructions, image_path, active)
SELECT o.id, 'Dress for Success Worldwide', 40.71280000, -74.00600000,
  '32 East 31st Street', 'New York', 'United States',
  'Free interview-appropriate clothing by appointment. Search dressforsuccess.org for your local affiliate.',
  'uploads/seed/dress-for-success.svg',
  1
FROM organizations o WHERE o.name = 'Dress for Success'
  AND NOT EXISTS (SELECT 1 FROM locations l JOIN organizations o2 ON l.organization_id = o2.id WHERE o2.name = 'Dress for Success');

-- ── Baby2Baby ──
INSERT INTO organizations (user_id, name, type, description, offers_categories, needs_categories, contact_email, contact_phone, website, show_contact_public, verified)
SELECT 1, 'Baby2Baby', 'ngo',
  'US nonprofit distributing diapers, clothing and gear to children living in poverty through partner agencies.',
  '["clothing","essentials","toys"]', '["clothing","essentials"]', 'info@baby2baby.org', '+1 310 442 0000', 'https://baby2baby.org', 1, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE name = 'Baby2Baby');

INSERT INTO locations (organization_id, name, latitude, longitude, address, city, country, instructions, image_path, active)
SELECT o.id, 'Baby2Baby National', 34.05220000, -118.24370000,
  '5830 West Jefferson Boulevard', 'Los Angeles', 'United States',
  'Items distributed through partner shelters and schools. Families should contact a partner agency listed on baby2baby.org.',
  'uploads/seed/baby2baby.svg',
  1
FROM organizations o WHERE o.name = 'Baby2Baby'
  AND NOT EXISTS (SELECT 1 FROM locations l JOIN organizations o2 ON l.organization_id = o2.id WHERE o2.name = 'Baby2Baby');

-- ── Voedselbanken Nederland ──
INSERT INTO organizations (user_id, name, type, description, offers_categories, needs_categories, contact_email, contact_phone, website, show_contact_public, verified)
SELECT 1, 'Voedselbanken Nederland', 'ngo',
  'Dutch food bank federation. Weekly food packages for households referred by local social services.',
  '["food"]', '["food"]', 'info@voedselbankennederland.nl', '+31 30 737 0630', 'https://www.voedselbankennederland.nl', 1, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE name = 'Voedselbanken Nederland');

INSERT INTO locations (organization_id, name, latitude, longitude, address, city, country, instructions, image_path, active)
SELECT o.id, 'Voedselbank Amsterdam', 52.36760000, 4.90410000,
  'Transformatorweg 102', 'Amsterdam', 'Netherlands',
  'Referral required via municipal social services. Find your local voedselbank on voedselbankennederland.nl.',
  'uploads/seed/voedselbank-nl.svg',
  1
FROM organizations o WHERE o.name = 'Voedselbanken Nederland'
  AND NOT EXISTS (SELECT 1 FROM locations l JOIN organizations o2 ON l.organization_id = o2.id WHERE o2.name = 'Voedselbanken Nederland');

-- ── Banco Alimentare (Italy) ──
INSERT INTO organizations (user_id, name, type, description, offers_categories, needs_categories, contact_email, contact_phone, website, show_contact_public, verified)
SELECT 1, 'Banco Alimentare', 'ngo',
  'Italian food bank network. Surplus food collected from industry and distributed to charities feeding people in need.',
  '["food"]', '["food"]', 'info@bancoalimentare.it', '+39 02 580 021 91', 'https://www.bancoalimentare.it', 1, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE name = 'Banco Alimentare');

INSERT INTO locations (organization_id, name, latitude, longitude, address, city, country, instructions, image_path, active)
SELECT o.id, 'Banco Alimentare della Lombardia', 45.46420000, 9.19000000,
  'Via A. Grandi 40', 'Milan', 'Italy',
  'Food support via partner charities. Contact your local Banco Alimentare regional office.',
  'uploads/seed/banco-alimentare-it.svg',
  1
FROM organizations o WHERE o.name = 'Banco Alimentare'
  AND NOT EXISTS (SELECT 1 FROM locations l JOIN organizations o2 ON l.organization_id = o2.id WHERE o2.name = 'Banco Alimentare');

-- ── Second Harvest Japan ──
INSERT INTO organizations (user_id, name, type, description, offers_categories, needs_categories, contact_email, contact_phone, website, show_contact_public, verified)
SELECT 1, 'Second Harvest Japan', 'ngo',
  'Japan''s first food bank. Rescues surplus food and delivers it free to orphanages, shelters and people in need.',
  '["food","essentials"]', '["food"]', 'info@2hj.org', '+81 3 5822 5371', 'https://www.2hj.org', 1, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE name = 'Second Harvest Japan');

INSERT INTO locations (organization_id, name, latitude, longitude, address, city, country, instructions, image_path, active)
SELECT o.id, 'Second Harvest Japan', 35.67620000, 139.65030000,
  '4-5-1 Asakusabashi', 'Tokyo', 'Japan',
  'Food assistance through partner organisations. Visit 2hj.org for donation drives and recipient programmes.',
  'uploads/seed/second-harvest-jp.svg',
  1
FROM organizations o WHERE o.name = 'Second Harvest Japan'
  AND NOT EXISTS (SELECT 1 FROM locations l JOIN organizations o2 ON l.organization_id = o2.id WHERE o2.name = 'Second Harvest Japan');

-- ── Refood (Portugal) ──
INSERT INTO organizations (user_id, name, type, description, offers_categories, needs_categories, contact_email, contact_phone, website, show_contact_public, verified)
SELECT 1, 'Refood', 'volunteer',
  'Volunteer movement fighting food waste. Surplus meals and groceries redistributed daily to people in need.',
  '["food"]', '["food"]', 'info@re-food.org', '+351 21 886 0390', 'https://www.re-food.org', 1, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE name = 'Refood');

INSERT INTO locations (organization_id, name, latitude, longitude, address, city, country, instructions, image_path, active)
SELECT o.id, 'Refood Lisboa', 38.72230000, -9.13930000,
  'Various volunteer hubs', 'Lisbon', 'Portugal',
  'Volunteer-led evening food distribution. Contact re-food.org to find the hub nearest you or to volunteer.',
  'uploads/seed/refood.svg',
  1
FROM organizations o WHERE o.name = 'Refood'
  AND NOT EXISTS (SELECT 1 FROM locations l JOIN organizations o2 ON l.organization_id = o2.id WHERE o2.name = 'Refood');

-- ── Room to Grow ──
INSERT INTO organizations (user_id, name, type, description, offers_categories, needs_categories, contact_email, contact_phone, website, show_contact_public, verified)
SELECT 1, 'Room to Grow', 'ngo',
  'Supports babies born into poverty. Free clothing, toys, books and essential gear from birth through age three.',
  '["clothing","toys","essentials"]', '["clothing","toys"]', 'info@roomtogrow.org', '+1 617 859 4545', 'https://www.roomtogrow.org', 1, 1
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM organizations WHERE name = 'Room to Grow');

INSERT INTO locations (organization_id, name, latitude, longitude, address, city, country, instructions, image_path, active)
SELECT o.id, 'Room to Grow Boston', 42.36010000, -71.05890000,
  'PO Box 120039', 'Boston', 'United States',
  'Enrolled families receive seasonal supplies by appointment. Referrals through partner hospitals and agencies.',
  'uploads/seed/room-to-grow.svg',
  1
FROM organizations o WHERE o.name = 'Room to Grow'
  AND NOT EXISTS (SELECT 1 FROM locations l JOIN organizations o2 ON l.organization_id = o2.id WHERE o2.name = 'Room to Grow');


-- ══════════════════════════════════════════════════════════════
-- Donations — one or more per location so items appear on the map
-- ══════════════════════════════════════════════════════════════

-- Save the Children
INSERT INTO donations (location_id, title, description, category, quantity, pickup_start, pickup_end, status)
SELECT l.id, 'Food to share', 'Emergency food support for families with children.', 'food', 'Programme-based', NOW(), DATE_ADD(NOW(), INTERVAL 120 DAY), 'available'
FROM locations l JOIN organizations o ON l.organization_id = o.id WHERE o.name = 'Save the Children'
  AND NOT EXISTS (SELECT 1 FROM donations d WHERE d.location_id = l.id AND d.category = 'food');

INSERT INTO donations (location_id, title, description, category, quantity, pickup_start, pickup_end, status)
SELECT l.id, 'Clothing to donate', 'Children''s clothing and winter wear for families in need.', 'clothing', 'Ongoing', NOW(), DATE_ADD(NOW(), INTERVAL 120 DAY), 'available'
FROM locations l JOIN organizations o ON l.organization_id = o.id WHERE o.name = 'Save the Children'
  AND NOT EXISTS (SELECT 1 FROM donations d WHERE d.location_id = l.id AND d.category = 'clothing');

INSERT INTO donations (location_id, title, description, category, quantity, pickup_start, pickup_end, status)
SELECT l.id, 'Toys to donate', 'Books, games and toys for children in crisis.', 'toys', 'Seasonal drives', NOW(), DATE_ADD(NOW(), INTERVAL 120 DAY), 'available'
FROM locations l JOIN organizations o ON l.organization_id = o.id WHERE o.name = 'Save the Children'
  AND NOT EXISTS (SELECT 1 FROM donations d WHERE d.location_id = l.id AND d.category = 'toys');

-- Action Against Hunger
INSERT INTO donations (location_id, title, description, category, quantity, pickup_start, pickup_end, status)
SELECT l.id, 'Food to share', 'Nutrition programmes and emergency food rations.', 'food', 'Field programmes', NOW(), DATE_ADD(NOW(), INTERVAL 120 DAY), 'available'
FROM locations l JOIN organizations o ON l.organization_id = o.id WHERE o.name = 'Action Against Hunger'
  AND NOT EXISTS (SELECT 1 FROM donations d WHERE d.location_id = l.id AND d.category = 'food');

INSERT INTO donations (location_id, title, description, category, quantity, pickup_start, pickup_end, status)
SELECT l.id, 'Essentials to share', 'Hygiene kits and basic supplies in emergency response.', 'essentials', 'Varies', NOW(), DATE_ADD(NOW(), INTERVAL 120 DAY), 'available'
FROM locations l JOIN organizations o ON l.organization_id = o.id WHERE o.name = 'Action Against Hunger'
  AND NOT EXISTS (SELECT 1 FROM donations d WHERE d.location_id = l.id AND d.category = 'essentials');

-- The Trussell Trust
INSERT INTO donations (location_id, title, description, category, quantity, pickup_start, pickup_end, status)
SELECT l.id, 'Food to share', 'Free emergency food parcels with referral voucher.', 'food', '3-day parcel', NOW(), DATE_ADD(NOW(), INTERVAL 120 DAY), 'available'
FROM locations l JOIN organizations o ON l.organization_id = o.id WHERE o.name = 'The Trussell Trust'
  AND NOT EXISTS (SELECT 1 FROM donations d WHERE d.location_id = l.id AND d.category = 'food');

-- Banco Alimentar (both locations)
INSERT INTO donations (location_id, title, description, category, quantity, pickup_start, pickup_end, status)
SELECT l.id, 'Food to share', 'Free groceries through partner institutions.', 'food', 'Weekly parcels', NOW(), DATE_ADD(NOW(), INTERVAL 120 DAY), 'available'
FROM locations l JOIN organizations o ON l.organization_id = o.id WHERE o.name = 'Banco Alimentar contra a Fome'
  AND NOT EXISTS (SELECT 1 FROM donations d WHERE d.location_id = l.id AND d.category = 'food');

-- Cruz Vermelha Portuguesa
INSERT INTO donations (location_id, title, description, category, quantity, pickup_start, pickup_end, status)
SELECT l.id, 'Food to share', 'Food parcels and emergency social aid.', 'food', 'By appointment', NOW(), DATE_ADD(NOW(), INTERVAL 120 DAY), 'available'
FROM locations l JOIN organizations o ON l.organization_id = o.id WHERE o.name = 'Cruz Vermelha Portuguesa'
  AND NOT EXISTS (SELECT 1 FROM donations d WHERE d.location_id = l.id AND d.category = 'food');

INSERT INTO donations (location_id, title, description, category, quantity, pickup_start, pickup_end, status)
SELECT l.id, 'Clothing to donate', 'Donated clothing for families in need.', 'clothing', 'In stock', NOW(), DATE_ADD(NOW(), INTERVAL 120 DAY), 'available'
FROM locations l JOIN organizations o ON l.organization_id = o.id WHERE o.name = 'Cruz Vermelha Portuguesa'
  AND NOT EXISTS (SELECT 1 FROM donations d WHERE d.location_id = l.id AND d.category = 'clothing');

-- Tafel Deutschland
INSERT INTO donations (location_id, title, description, category, quantity, pickup_start, pickup_end, status)
SELECT l.id, 'Food to share', 'Weekly food packages for registered clients.', 'food', 'Weekly', NOW(), DATE_ADD(NOW(), INTERVAL 120 DAY), 'available'
FROM locations l JOIN organizations o ON l.organization_id = o.id WHERE o.name = 'Tafel Deutschland'
  AND NOT EXISTS (SELECT 1 FROM donations d WHERE d.location_id = l.id AND d.category = 'food');

-- FareShare UK
INSERT INTO donations (location_id, title, description, category, quantity, pickup_start, pickup_end, status)
SELECT l.id, 'Food to share', 'Surplus food redistributed to community groups.', 'food', 'Ongoing', NOW(), DATE_ADD(NOW(), INTERVAL 120 DAY), 'available'
FROM locations l JOIN organizations o ON l.organization_id = o.id WHERE o.name = 'FareShare UK'
  AND NOT EXISTS (SELECT 1 FROM donations d WHERE d.location_id = l.id AND d.category = 'food');

-- Goodwill Industries
INSERT INTO donations (location_id, title, description, category, quantity, pickup_start, pickup_end, status)
SELECT l.id, 'Clothing to donate', 'Affordable thrift-store clothing and shoes.', 'clothing', 'In store', NOW(), DATE_ADD(NOW(), INTERVAL 120 DAY), 'available'
FROM locations l JOIN organizations o ON l.organization_id = o.id WHERE o.name = 'Goodwill Industries'
  AND NOT EXISTS (SELECT 1 FROM donations d WHERE d.location_id = l.id AND d.category = 'clothing');

INSERT INTO donations (location_id, title, description, category, quantity, pickup_start, pickup_end, status)
SELECT l.id, 'Toys to donate', 'Gently used toys and games at low cost.', 'toys', 'In store', NOW(), DATE_ADD(NOW(), INTERVAL 120 DAY), 'available'
FROM locations l JOIN organizations o ON l.organization_id = o.id WHERE o.name = 'Goodwill Industries'
  AND NOT EXISTS (SELECT 1 FROM donations d WHERE d.location_id = l.id AND d.category = 'toys');

-- Toys for Tots
INSERT INTO donations (location_id, title, description, category, quantity, pickup_start, pickup_end, status)
SELECT l.id, 'Toys to donate', 'New toys for children during holiday drives.', 'toys', 'Seasonal', NOW(), DATE_ADD(NOW(), INTERVAL 120 DAY), 'available'
FROM locations l JOIN organizations o ON l.organization_id = o.id WHERE o.name = 'Toys for Tots'
  AND NOT EXISTS (SELECT 1 FROM donations d WHERE d.location_id = l.id AND d.category = 'toys');

-- St. Vincent de Paul
INSERT INTO donations (location_id, title, description, category, quantity, pickup_start, pickup_end, status)
SELECT l.id, 'Food to share', 'Pantry food and home-delivered meals.', 'food', 'Varies', NOW(), DATE_ADD(NOW(), INTERVAL 120 DAY), 'available'
FROM locations l JOIN organizations o ON l.organization_id = o.id WHERE o.name = 'Society of St. Vincent de Paul'
  AND NOT EXISTS (SELECT 1 FROM donations d WHERE d.location_id = l.id AND d.category = 'food');

INSERT INTO donations (location_id, title, description, category, quantity, pickup_start, pickup_end, status)
SELECT l.id, 'Clothing to donate', 'Free clothing vouchers and thrift assistance.', 'clothing', 'By appointment', NOW(), DATE_ADD(NOW(), INTERVAL 120 DAY), 'available'
FROM locations l JOIN organizations o ON l.organization_id = o.id WHERE o.name = 'Society of St. Vincent de Paul'
  AND NOT EXISTS (SELECT 1 FROM donations d WHERE d.location_id = l.id AND d.category = 'clothing');

-- Los Angeles Regional Food Bank
INSERT INTO donations (location_id, title, description, category, quantity, pickup_start, pickup_end, status)
SELECT l.id, 'Food to share', 'Free groceries via partner pantries across LA County.', 'food', 'Ongoing', NOW(), DATE_ADD(NOW(), INTERVAL 120 DAY), 'available'
FROM locations l JOIN organizations o ON l.organization_id = o.id WHERE o.name = 'Los Angeles Regional Food Bank'
  AND NOT EXISTS (SELECT 1 FROM donations d WHERE d.location_id = l.id AND d.category = 'food');

-- Houston Food Bank
INSERT INTO donations (location_id, title, description, category, quantity, pickup_start, pickup_end, status)
SELECT l.id, 'Food to share', 'Partner agency food distribution.', 'food', 'Ongoing', NOW(), DATE_ADD(NOW(), INTERVAL 120 DAY), 'available'
FROM locations l JOIN organizations o ON l.organization_id = o.id WHERE o.name = 'Houston Food Bank'
  AND NOT EXISTS (SELECT 1 FROM donations d WHERE d.location_id = l.id AND d.category = 'food');

-- Daily Bread Food Bank
INSERT INTO donations (location_id, title, description, category, quantity, pickup_start, pickup_end, status)
SELECT l.id, 'Food to share', 'Food hampers through GTA agency partners.', 'food', 'Ongoing', NOW(), DATE_ADD(NOW(), INTERVAL 120 DAY), 'available'
FROM locations l JOIN organizations o ON l.organization_id = o.id WHERE o.name = 'Daily Bread Food Bank'
  AND NOT EXISTS (SELECT 1 FROM donations d WHERE d.location_id = l.id AND d.category = 'food');

-- OzHarvest
INSERT INTO donations (location_id, title, description, category, quantity, pickup_start, pickup_end, status)
SELECT l.id, 'Food to share', 'Rescued surplus food via community partners.', 'food', 'Daily', NOW(), DATE_ADD(NOW(), INTERVAL 120 DAY), 'available'
FROM locations l JOIN organizations o ON l.organization_id = o.id WHERE o.name = 'OzHarvest'
  AND NOT EXISTS (SELECT 1 FROM donations d WHERE d.location_id = l.id AND d.category = 'food');

-- Emmaüs International
INSERT INTO donations (location_id, title, description, category, quantity, pickup_start, pickup_end, status)
SELECT l.id, 'Clothing to donate', 'Low-cost second-hand clothing in Emmaüs shops.', 'clothing', 'In store', NOW(), DATE_ADD(NOW(), INTERVAL 120 DAY), 'available'
FROM locations l JOIN organizations o ON l.organization_id = o.id WHERE o.name = 'Emmaüs International'
  AND NOT EXISTS (SELECT 1 FROM donations d WHERE d.location_id = l.id AND d.category = 'clothing');

INSERT INTO donations (location_id, title, description, category, quantity, pickup_start, pickup_end, status)
SELECT l.id, 'Food to share', 'Solidarity meals and food support in communities.', 'food', 'Community-based', NOW(), DATE_ADD(NOW(), INTERVAL 120 DAY), 'available'
FROM locations l JOIN organizations o ON l.organization_id = o.id WHERE o.name = 'Emmaüs International'
  AND NOT EXISTS (SELECT 1 FROM donations d WHERE d.location_id = l.id AND d.category = 'food' AND d.title = 'Food to share');

-- Dress for Success
INSERT INTO donations (location_id, title, description, category, quantity, pickup_start, pickup_end, status)
SELECT l.id, 'Clothing to donate', 'Free professional attire for job interviews.', 'clothing', 'By appointment', NOW(), DATE_ADD(NOW(), INTERVAL 120 DAY), 'available'
FROM locations l JOIN organizations o ON l.organization_id = o.id WHERE o.name = 'Dress for Success'
  AND NOT EXISTS (SELECT 1 FROM donations d WHERE d.location_id = l.id AND d.category = 'clothing');

-- Baby2Baby
INSERT INTO donations (location_id, title, description, category, quantity, pickup_start, pickup_end, status)
SELECT l.id, 'Clothing to donate', 'Diapers, baby clothing and gear for low-income families.', 'clothing', 'Partner agencies', NOW(), DATE_ADD(NOW(), INTERVAL 120 DAY), 'available'
FROM locations l JOIN organizations o ON l.organization_id = o.id WHERE o.name = 'Baby2Baby'
  AND NOT EXISTS (SELECT 1 FROM donations d WHERE d.location_id = l.id AND d.category = 'clothing');

INSERT INTO donations (location_id, title, description, category, quantity, pickup_start, pickup_end, status)
SELECT l.id, 'Toys to donate', 'Toys and books for children living in poverty.', 'toys', 'Ongoing', NOW(), DATE_ADD(NOW(), INTERVAL 120 DAY), 'available'
FROM locations l JOIN organizations o ON l.organization_id = o.id WHERE o.name = 'Baby2Baby'
  AND NOT EXISTS (SELECT 1 FROM donations d WHERE d.location_id = l.id AND d.category = 'toys');

-- Voedselbanken Nederland
INSERT INTO donations (location_id, title, description, category, quantity, pickup_start, pickup_end, status)
SELECT l.id, 'Food to share', 'Weekly food packages with social services referral.', 'food', 'Weekly', NOW(), DATE_ADD(NOW(), INTERVAL 120 DAY), 'available'
FROM locations l JOIN organizations o ON l.organization_id = o.id WHERE o.name = 'Voedselbanken Nederland'
  AND NOT EXISTS (SELECT 1 FROM donations d WHERE d.location_id = l.id AND d.category = 'food');

-- Banco Alimentare
INSERT INTO donations (location_id, title, description, category, quantity, pickup_start, pickup_end, status)
SELECT l.id, 'Food to share', 'Surplus food via Italian charity partners.', 'food', 'Ongoing', NOW(), DATE_ADD(NOW(), INTERVAL 120 DAY), 'available'
FROM locations l JOIN organizations o ON l.organization_id = o.id WHERE o.name = 'Banco Alimentare'
  AND NOT EXISTS (SELECT 1 FROM donations d WHERE d.location_id = l.id AND d.category = 'food');

-- Second Harvest Japan
INSERT INTO donations (location_id, title, description, category, quantity, pickup_start, pickup_end, status)
SELECT l.id, 'Food to share', 'Rescued food delivered to shelters and families.', 'food', 'Daily', NOW(), DATE_ADD(NOW(), INTERVAL 120 DAY), 'available'
FROM locations l JOIN organizations o ON l.organization_id = o.id WHERE o.name = 'Second Harvest Japan'
  AND NOT EXISTS (SELECT 1 FROM donations d WHERE d.location_id = l.id AND d.category = 'food');

-- Refood
INSERT INTO donations (location_id, title, description, category, quantity, pickup_start, pickup_end, status)
SELECT l.id, 'Food to share', 'Surplus meals redistributed by volunteers every evening.', 'food', 'Daily evening', NOW(), DATE_ADD(NOW(), INTERVAL 120 DAY), 'available'
FROM locations l JOIN organizations o ON l.organization_id = o.id WHERE o.name = 'Refood'
  AND NOT EXISTS (SELECT 1 FROM donations d WHERE d.location_id = l.id AND d.category = 'food');

-- Room to Grow
INSERT INTO donations (location_id, title, description, category, quantity, pickup_start, pickup_end, status)
SELECT l.id, 'Clothing to donate', 'Baby and toddler clothing for enrolled families.', 'clothing', 'Seasonal', NOW(), DATE_ADD(NOW(), INTERVAL 120 DAY), 'available'
FROM locations l JOIN organizations o ON l.organization_id = o.id WHERE o.name = 'Room to Grow'
  AND NOT EXISTS (SELECT 1 FROM donations d WHERE d.location_id = l.id AND d.category = 'clothing');

INSERT INTO donations (location_id, title, description, category, quantity, pickup_start, pickup_end, status)
SELECT l.id, 'Toys to donate', 'Books, toys and developmental gear for babies.', 'toys', 'Seasonal', NOW(), DATE_ADD(NOW(), INTERVAL 120 DAY), 'available'
FROM locations l JOIN organizations o ON l.organization_id = o.id WHERE o.name = 'Room to Grow'
  AND NOT EXISTS (SELECT 1 FROM donations d WHERE d.location_id = l.id AND d.category = 'toys');

-- ── Backfill logos on re-run (if locations exist but image_path is empty) ──
UPDATE locations l
JOIN organizations o ON l.organization_id = o.id
SET l.image_path = CASE o.name
    WHEN 'Save the Children' THEN 'uploads/seed/save-the-children.svg'
    WHEN 'Action Against Hunger' THEN 'uploads/seed/action-against-hunger.svg'
    WHEN 'The Trussell Trust' THEN 'uploads/seed/trussell-trust.svg'
    WHEN 'Banco Alimentar contra a Fome' THEN 'uploads/seed/banco-alimentar-pt.svg'
    WHEN 'Cruz Vermelha Portuguesa' THEN 'uploads/seed/cruz-vermelha-pt.svg'
    WHEN 'Tafel Deutschland' THEN 'uploads/seed/tafel-deutschland.svg'
    WHEN 'FareShare UK' THEN 'uploads/seed/fareshare-uk.svg'
    WHEN 'Goodwill Industries' THEN 'uploads/seed/goodwill.svg'
    WHEN 'Toys for Tots' THEN 'uploads/seed/toys-for-tots.svg'
    WHEN 'Society of St. Vincent de Paul' THEN 'uploads/seed/svdp.svg'
    WHEN 'Los Angeles Regional Food Bank' THEN 'uploads/seed/la-food-bank.svg'
    WHEN 'Houston Food Bank' THEN 'uploads/seed/houston-food-bank.svg'
    WHEN 'Daily Bread Food Bank' THEN 'uploads/seed/daily-bread.svg'
    WHEN 'OzHarvest' THEN 'uploads/seed/ozharvest.svg'
    WHEN 'Emmaüs International' THEN 'uploads/seed/emmaus.svg'
    WHEN 'Dress for Success' THEN 'uploads/seed/dress-for-success.svg'
    WHEN 'Baby2Baby' THEN 'uploads/seed/baby2baby.svg'
    WHEN 'Voedselbanken Nederland' THEN 'uploads/seed/voedselbank-nl.svg'
    WHEN 'Banco Alimentare' THEN 'uploads/seed/banco-alimentare-it.svg'
    WHEN 'Second Harvest Japan' THEN 'uploads/seed/second-harvest-jp.svg'
    WHEN 'Refood' THEN 'uploads/seed/refood.svg'
    WHEN 'Room to Grow' THEN 'uploads/seed/room-to-grow.svg'
    ELSE l.image_path
END
WHERE o.name IN (
    'Save the Children', 'Action Against Hunger', 'The Trussell Trust',
    'Banco Alimentar contra a Fome', 'Cruz Vermelha Portuguesa', 'Tafel Deutschland',
    'FareShare UK', 'Goodwill Industries', 'Toys for Tots', 'Society of St. Vincent de Paul',
    'Los Angeles Regional Food Bank', 'Houston Food Bank', 'Daily Bread Food Bank',
    'OzHarvest', 'Emmaüs International', 'Dress for Success', 'Baby2Baby',
    'Voedselbanken Nederland', 'Banco Alimentare', 'Second Harvest Japan', 'Refood', 'Room to Grow'
)
AND (l.image_path IS NULL OR l.image_path = '');

SET FOREIGN_KEY_CHECKS = 1;
