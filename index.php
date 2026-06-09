<?php

declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (!isInstalled()) {
    header('Location: install/');
    exit;
}

I18n::init($_GET['lang'] ?? null);
$t = fn(string $key) => I18n::t($key);
$lang = I18n::getLang();
$languages = I18n::getLanguageMeta();
$currentLang = $languages[$lang] ?? $languages['en'];

$settings = [];
$rows = Database::fetchAll('SELECT `key`, `value` FROM settings');
foreach ($rows as $row) {
    $settings[$row['key']] = $row['value'];
}

$displaySiteName = trim($settings['site_name'] ?? '') ?: $t('site_name');
$mapLat = $settings['map_default_lat'] ?? '38.7223';
$mapLng = $settings['map_default_lng'] ?? '-9.1393';
$mapZoom = $settings['map_default_zoom'] ?? '6';
$dogeWallet = $settings['doge_wallet'] ?? '';
$dogeNote = trim($settings['doge_transparency_note'] ?? '') ?: $t('donate_purpose');

$user = Auth::user();
$canPost = $user !== null;
$countries = require __DIR__ . '/includes/countries.php';

$individualTypes = [
    'donor' => ['icon' => 'redeem', 'label' => $t('org_donor')],
    'person' => ['icon' => 'person', 'label' => $t('org_person')],
];

$producerTypes = [
    'farmer' => ['icon' => 'agriculture', 'label' => $t('org_farmer')],
    'fisherman' => ['icon' => 'set_meal', 'label' => $t('org_fisherman')],
];

$groupTypes = [
    'supermarket' => ['icon' => 'store', 'label' => $t('org_supermarket')],
    'grocery' => ['icon' => 'shopping_basket', 'label' => $t('org_grocery')],
    'restaurant' => ['icon' => 'restaurant', 'label' => $t('org_restaurant')],
    'cafe' => ['icon' => 'local_cafe', 'label' => $t('org_cafe')],
    'ngo' => ['icon' => 'volunteer_activism', 'label' => $t('org_ngo')],
    'scout' => ['icon' => 'hiking', 'label' => $t('org_scout')],
    'volunteer' => ['icon' => 'groups', 'label' => $t('org_volunteer')],
];

$orgTypes = $individualTypes + $producerTypes + $groupTypes;

$categoryOptions = [
    'food' => ['icon' => 'restaurant', 'label' => $t('filter_food')],
    'clothing' => ['icon' => 'checkroom', 'label' => $t('filter_clothing')],
    'toys' => ['icon' => 'toys', 'label' => $t('filter_toys')],
    'essentials' => ['icon' => 'medical_services', 'label' => $t('filter_essentials')],
];

$sloganDefs = [
    ['slogan_main', 'eco'],
    ['slogan_doge', 'favorite'],
    ['slogan_coded_love', 'code'],
    ['slogan_since_2013', 'history'],
    ['slogan_people', 'visibility'],
    ['slogan_business', 'storefront'],
    ['slogan_volunteer', 'volunteer_activism'],
    ['slogan_jamaica', 'downhill_skiing'],
    ['slogan_akc', 'pets'],
    ['slogan_doge4water', 'water_drop'],
    ['slogan_socks', 'checkroom'],
    ['slogan_nascar', 'directions_car'],
    ['slogan_scout', 'hiking'],
    ['slogan_ngo', 'handshake'],
    ['slogan_connect', 'hub'],
    ['slogan_waste', 'compost'],
    ['slogan_help', 'volunteer_activism'],
    ['slogan_community', 'groups'],
    ['slogan_together', 'diversity_3'],
    ['slogan_small_coins', 'savings'],
    ['slogan_shibes', 'sentiment_very_satisfied'],
];
$sloganLines = array_map(static fn(array $def): string => $t($def[0]), $sloganDefs);
$sloganIcons = array_column($sloganDefs, 1);

ensureLocationSlugs();

$siteBaseUrl = siteUrl();
$shareListing = null;
$shareIntent = null;
$shareCategory = null;
$shareLocationId = null;

$listingSlug = trim($_GET['listing_slug'] ?? '');
if ($listingSlug !== '') {
    $shareListing = fetchListingBySlug($listingSlug);
    if ($shareListing) {
        [$shareIntent, $shareCategory] = listingShareIntentCategory(
            $shareListing,
            is_string($_GET['listing_intent'] ?? null) ? $_GET['listing_intent'] : null,
            is_string($_GET['listing_category'] ?? null) ? $_GET['listing_category'] : null
        );
        $shareLocationId = (int) $shareListing['location_id'];
    }
}

if ($shareListing) {
    $listingTitle = trim($shareListing['location_name'] ?? '') ?: trim($shareListing['org_name'] ?? '');
    $pageTitle = $listingTitle . ' | ' . $displaySiteName;
    $metaDescription = listingShareDescription($shareListing, $shareIntent, $shareCategory, $t);
    $metaImage = absoluteUrl($shareListing['image_path'] ?? null);
    $metaUrl = buildListingShareUrl($shareListing['slug'], $shareIntent, $shareCategory);
    $metaType = 'article';
} else {
    $pageTitle = $displaySiteName . ' | ' . $t('slogan_main');
    $metaDescription = $t('slogan_main');
    $metaImage = absoluteUrl('assets/img/DogeSeeds_card.png');
    $metaUrl = $siteBaseUrl . '/';
    $metaType = 'website';
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta name="theme-color" content="#4CAF50">
    <link rel="canonical" href="<?= htmlspecialchars($metaUrl) ?>">
    <meta property="og:site_name" content="<?= htmlspecialchars($displaySiteName) ?>">
    <meta property="og:title" content="<?= htmlspecialchars($shareListing ? $listingTitle : $displaySiteName) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($metaImage) ?>">
    <meta property="og:url" content="<?= htmlspecialchars($metaUrl) ?>">
    <meta property="og:type" content="<?= htmlspecialchars($metaType) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($shareListing ? $listingTitle : $displaySiteName) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($metaDescription) ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($metaImage) ?>">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css">
    <link rel="stylesheet" href="<?= htmlspecialchars(webPath('assets/css/style.css')) ?>">
    <link rel="icon" href="<?= htmlspecialchars(webPath('assets/img/DogeSeeds_logo.png')) ?>" type="image/png">
    <link rel="preload" href="<?= htmlspecialchars(webPath('assets/img/DogeSeeds_card.png')) ?>" as="image">
</head>
<body>
<nav class="site-navbar" id="siteNavbar">
    <div class="site-navbar-inner">
        <div class="site-nav-slot site-nav-slot-left">
            <div class="lang-dropdown mobile-lang-dropdown" id="mobileLangDropdown">
                <button type="button" class="mobile-top-icon-btn mobile-lang-trigger" id="mobileLangToggle" aria-expanded="false" aria-controls="mobileLangSheet" aria-label="<?= htmlspecialchars($t('language')) ?>">
                    <span class="material-icons">language</span>
                </button>
            </div>
        </div>

        <a href="<?= htmlspecialchars(webPath()) ?>" class="site-brand" id="siteBrand" aria-label="<?= htmlspecialchars($displaySiteName) ?>">
            <span class="brand-logo-live" id="brandLogoLive">
                <span class="brand-logo-stage" id="brandLogoStage">
                    <img src="<?= htmlspecialchars(webPath('assets/img/DogeSeeds_logo.png')) ?>" alt="<?= htmlspecialchars($displaySiteName) ?>" class="brand-logo-img" id="brandLogoImg">
                    <span class="brand-logo-badge" id="brandLogoBadge" hidden>
                        <span class="material-icons brand-logo-icon" id="brandLogoIcon">favorite</span>
                    </span>
                    <span class="brand-beta-badge"><?= htmlspecialchars($t('beta_badge')) ?></span>
                </span>
            </span>
        </a>

        <div class="site-nav-slot site-nav-slot-right">
            <?php if ($user): ?>
            <button type="button" class="mobile-top-icon-btn" id="btnMyListingsMobileTop" aria-label="<?= htmlspecialchars($t('nav_my_listings')) ?>">
                <span class="material-icons">inventory</span>
            </button>
            <button type="button" class="mobile-top-icon-btn" id="btnLogoutMobileTop" aria-label="<?= htmlspecialchars($t('nav_logout')) ?>">
                <span class="material-icons">logout</span>
            </button>
            <?php else: ?>
            <button type="button" class="mobile-top-icon-btn" id="btnLoginMobileTop" aria-label="<?= htmlspecialchars($t('nav_login')) ?>">
                <span class="material-icons">person</span>
            </button>
            <?php endif; ?>
        </div>

        <div class="site-nav-center site-nav-center-desktop">
            <div class="site-nav-pills">
                <button type="button" class="nav-pill active" data-panel="">
                    <span class="material-icons" aria-hidden="true">map</span>
                    <span><?= htmlspecialchars($t('nav_map')) ?></span>
                </button>
                <button type="button" class="nav-pill" data-panel="list">
                    <span class="material-icons" aria-hidden="true">list</span>
                    <span><?= htmlspecialchars($t('nav_list')) ?></span>
                </button>
                <?php if ($user): ?>
                <button type="button" class="nav-pill" data-panel="my">
                    <span class="material-icons" aria-hidden="true">inventory</span>
                    <span><?= htmlspecialchars($t('nav_my_listings')) ?></span>
                </button>
                <?php endif; ?>
                <?php if ($canPost): ?>
                <button type="button" class="nav-pill" data-panel="post">
                    <span class="material-icons" aria-hidden="true">add_location</span>
                    <span><?= htmlspecialchars($t('post_item')) ?></span>
                </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="site-nav-actions">
            <div class="lang-dropdown" id="langDropdown">
                <button type="button" class="nav-pill nav-pill-lang" id="langToggle" aria-expanded="false">
                    <img src="https://flagcdn.com/w20/<?= htmlspecialchars($currentLang['flag']) ?>.png" class="flag" alt="">
                    <span class="lang-label"><?= htmlspecialchars($currentLang['short']) ?></span>
                    <span class="material-icons lang-chevron" aria-hidden="true">expand_more</span>
                </button>
                <ul class="lang-menu" id="langMenu" hidden>
                    <?php foreach ($languages as $code => $meta): ?>
                    <li>
                        <a href="?lang=<?= urlencode($code) ?>" class="lang-option <?= $lang === $code ? 'active' : '' ?>">
                            <img src="https://flagcdn.com/w20/<?= htmlspecialchars($meta['flag']) ?>.png" class="flag" alt="">
                            <span><?= htmlspecialchars($meta['label']) ?></span>
                            <span class="material-icons lang-check" aria-hidden="true">check</span>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <?php if ($user): ?>
                <?php if ($user['role'] === 'admin'): ?>
                <a href="<?= htmlspecialchars(webPath('admin/')) ?>" class="nav-pill nav-pill-account" title="<?= htmlspecialchars($t('admin_title')) ?>">
                    <span class="material-icons" aria-hidden="true">settings</span>
                </a>
                <?php endif; ?>
                <span class="user-chip"><?= sanitize($user['name']) ?></span>
                <button type="button" class="nav-pill nav-pill-account" id="btnLogout">
                    <span class="material-icons" aria-hidden="true">logout</span>
                    <span class="nav-pill-account-label"><?= htmlspecialchars($t('nav_logout')) ?></span>
                </button>
            <?php else: ?>
                <button type="button" class="nav-pill nav-pill-account" id="btnLogin">
                    <span class="material-icons" aria-hidden="true">person</span>
                    <span class="nav-pill-account-label"><?= htmlspecialchars($t('nav_login')) ?></span>
                </button>
            <?php endif; ?>
        </div>

        <button type="button" class="site-nav-toggler" id="menuToggle" aria-label="Menu" aria-expanded="false" aria-controls="navbarNav">
            <span class="material-icons">menu</span>
        </button>
    </div>

    <div class="navbar-collapse" id="navbarNav">
        <div class="site-nav-pills">
            <button type="button" class="nav-pill active" data-panel="">
                <span class="material-icons">map</span>
                <span><?= htmlspecialchars($t('nav_map')) ?></span>
            </button>
            <button type="button" class="nav-pill" data-panel="list">
                <span class="material-icons">list</span>
                <span><?= htmlspecialchars($t('nav_list')) ?></span>
            </button>
            <?php if ($user): ?>
            <button type="button" class="nav-pill" data-panel="my">
                <span class="material-icons">inventory</span>
                <span><?= htmlspecialchars($t('nav_my_listings')) ?></span>
            </button>
            <?php endif; ?>
            <?php if ($canPost): ?>
            <button type="button" class="nav-pill" data-panel="post">
                <span class="material-icons">add_location</span>
                <span><?= htmlspecialchars($t('post_item')) ?></span>
            </button>
            <?php endif; ?>
        </div>
        <div class="site-nav-actions site-nav-actions-mobile">
            <?php foreach ($languages as $code => $meta): ?>
            <a href="?lang=<?= urlencode($code) ?>" class="lang-option <?= $lang === $code ? 'active' : '' ?>">
                <img src="https://flagcdn.com/w20/<?= htmlspecialchars($meta['flag']) ?>.png" class="flag" alt="">
                <?= htmlspecialchars($meta['short']) ?>
            </a>
            <?php endforeach; ?>
            <?php if ($user): ?>
                <button type="button" class="nav-pill nav-pill-account" id="btnLogoutMobile">
                    <span class="material-icons">logout</span>
                    <span><?= htmlspecialchars($t('nav_logout')) ?></span>
                </button>
            <?php else: ?>
                <button type="button" class="nav-pill nav-pill-account" id="btnLoginMobile">
                    <span class="material-icons">person</span>
                    <span><?= htmlspecialchars($t('nav_login')) ?></span>
                </button>
            <?php endif; ?>
        </div>
    </div>
</nav>

<div class="mobile-lang-backdrop" id="mobileLangBackdrop" aria-hidden="true"></div>
<div class="mobile-lang-sheet" id="mobileLangSheet" aria-hidden="true">
    <div class="mobile-lang-sheet-header">
        <span class="material-icons">translate</span>
        <span><?= htmlspecialchars($t('language')) ?></span>
    </div>
    <ul class="mobile-lang-grid" id="mobileLangMenu">
        <?php foreach ($languages as $code => $meta): ?>
        <li>
            <a href="?lang=<?= urlencode($code) ?>" class="mobile-lang-chip <?= $lang === $code ? 'active' : '' ?>">
                <img src="https://flagcdn.com/w40/<?= htmlspecialchars($meta['flag']) ?>.png" class="mobile-lang-chip-flag" alt="">
                <span class="mobile-lang-chip-label"><?= htmlspecialchars($meta['label']) ?></span>
                <span class="mobile-lang-chip-code"><?= htmlspecialchars($meta['short']) ?></span>
            </a>
        </li>
        <?php endforeach; ?>
    </ul>
</div>

<div class="filter-bar" id="filters">
    <button type="button" class="filter-pill active" data-category="">
        <?= htmlspecialchars($t('filter_all')) ?>
        <span class="filter-count" data-count-for="all">0</span>
    </button>
    <button type="button" class="filter-pill" data-category="food">
        <span class="material-icons">restaurant</span>
        <?= htmlspecialchars($t('filter_food')) ?>
        <span class="filter-count" data-count-for="food">0</span>
    </button>
    <button type="button" class="filter-pill" data-category="clothing">
        <span class="material-icons">checkroom</span>
        <?= htmlspecialchars($t('filter_clothing')) ?>
        <span class="filter-count" data-count-for="clothing">0</span>
    </button>
    <button type="button" class="filter-pill" data-category="toys">
        <span class="material-icons">toys</span>
        <?= htmlspecialchars($t('filter_toys')) ?>
        <span class="filter-count" data-count-for="toys">0</span>
    </button>
    <button type="button" class="filter-pill" data-category="essentials">
        <span class="material-icons">medical_services</span>
        <?= htmlspecialchars($t('filter_essentials')) ?>
        <span class="filter-count" data-count-for="essentials">0</span>
    </button>
    <button type="button" class="filter-pill filter-pill-nearby" id="btnNearby" aria-label="<?= htmlspecialchars($t('filter_nearby')) ?>">
        <span class="material-icons">my_location</span>
        <?= htmlspecialchars($t('filter_nearby')) ?>
    </button>
</div>

<div class="map-hint" id="mapHint">
    <span class="material-icons map-hint-icon" id="mapHintIcon" aria-hidden="true">eco</span>
    <span class="map-hint-scroll" id="mapHintScroll">
        <span class="map-hint-text" id="sloganRotate"><?= htmlspecialchars($t('slogan_main')) ?></span>
    </span>
</div>

<nav class="bottom-nav" id="bottomNav" aria-label="<?= htmlspecialchars($displaySiteName) ?>">
    <button type="button" class="bottom-nav-item active" data-panel="">
        <span class="material-icons">map</span>
        <span><?= htmlspecialchars($t('nav_map')) ?></span>
    </button>
    <button type="button" class="bottom-nav-item" data-panel="list">
        <span class="material-icons">list</span>
        <span><?= htmlspecialchars($t('nav_list')) ?></span>
    </button>
    <?php if ($canPost): ?>
    <button type="button" class="bottom-nav-item" data-panel="post">
        <span class="material-icons">add_location</span>
        <span><?= htmlspecialchars($t('post_item')) ?></span>
    </button>
    <?php endif; ?>
</nav>

<div id="map"></div>
<div class="map-overlay hidden" id="mapOverlay" aria-hidden="true"></div>

<div class="splash-screen" id="splashScreen" aria-hidden="false">
    <div class="splash-backdrop" id="splashBackdrop"></div>
    <div class="splash-card-badge" id="splashCard">
        <img src="<?= htmlspecialchars(webPath('assets/img/DogeSeeds_card.png')) ?>" alt="" class="splash-card-img" id="splashCardImg" width="300" height="300">
        <span class="splash-card-icon-wrap" id="splashCardIconWrap" hidden>
            <span class="material-icons splash-card-icon" id="splashCardIcon">favorite</span>
        </span>
    </div>
</div>

<!-- List panel -->
<div class="sheet-backdrop" id="listPanel" aria-hidden="true">
    <div class="sheet-card" role="dialog">
        <header class="sheet-header">
            <div>
                <h2 class="sheet-title">
                    <span class="material-icons">list</span>
                    <?= htmlspecialchars($t('nav_list')) ?>
                </h2>
                <p class="sheet-sub"><?= htmlspecialchars($t('map_title')) ?></p>
            </div>
            <button type="button" class="btn-sheet-close" data-close-panel aria-label="<?= htmlspecialchars($t('cancel')) ?>">
                <span class="material-icons">close</span>
            </button>
        </header>
        <div class="sheet-body">
            <div class="list-search-wrap">
                <span class="material-icons list-search-icon" aria-hidden="true">search</span>
                <input type="search" id="listSearch" class="list-search-input" placeholder="<?= htmlspecialchars($t('list_search_placeholder')) ?>" autocomplete="off" aria-label="<?= htmlspecialchars($t('list_search_placeholder')) ?>">
            </div>
            <div class="list-container" id="donationList"></div>
        </div>
    </div>
</div>

<?php if ($user): ?>
<!-- My listings panel -->
<div class="sheet-backdrop" id="myPanel" aria-hidden="true">
    <div class="sheet-card sheet-card-wide" role="dialog">
        <header class="sheet-header">
            <div>
                <h2 class="sheet-title">
                    <span class="material-icons">inventory</span>
                    <?= htmlspecialchars($t('nav_my_listings')) ?>
                </h2>
                <p class="sheet-sub" id="myPanelSubtitle"><?= htmlspecialchars($t('my_listings_subtitle')) ?></p>
            </div>
            <button type="button" class="btn-sheet-close" data-close-panel aria-label="<?= htmlspecialchars($t('cancel')) ?>">
                <span class="material-icons">close</span>
            </button>
        </header>
        <div class="sheet-body">
            <div id="myListingsView">
                <div class="list-container" id="myListingsList"></div>
            </div>
            <form id="editListingForm" class="sheet-form hidden">
                <input type="hidden" name="location_id" id="editLocationId">
                <p class="step-section-title"><span class="material-icons">edit</span><span id="editListingTitle"></span></p>

                <label><?= htmlspecialchars($t('share_description')) ?></label>
                <textarea name="description" id="editDescription" rows="2"></textarea>

                <label><?= htmlspecialchars($t('instructions')) ?></label>
                <textarea name="instructions" id="editInstructions" rows="2"></textarea>

                <div class="form-row">
                    <div>
                        <label><?= htmlspecialchars($t('pickup_start')) ?></label>
                        <input type="datetime-local" name="pickup_start" id="editPickupStart" required>
                    </div>
                    <div>
                        <label><?= htmlspecialchars($t('pickup_end')) ?></label>
                        <input type="datetime-local" name="pickup_end" id="editPickupEnd" required>
                    </div>
                </div>

                <label><?= htmlspecialchars($t('address')) ?></label>
                <input type="text" name="address" id="editAddress">
                <div class="form-row">
                    <div><label><?= htmlspecialchars($t('city')) ?></label><input type="text" name="city" id="editCity"></div>
                    <div>
                        <label><?= htmlspecialchars($t('country')) ?></label>
                        <input type="text" name="country" id="editCountry">
                    </div>
                </div>

                <label><?= htmlspecialchars($t('email')) ?></label>
                <input type="email" name="contact_email" id="editContactEmail">
                <label><?= htmlspecialchars($t('phone')) ?></label>
                <input type="tel" name="contact_phone" id="editContactPhone">

                <label class="switch-row switch-row-block">
                    <span class="switch-row-label">
                        <span class="material-icons">visibility</span>
                        <span><?= htmlspecialchars($t('contact_public_label')) ?></span>
                    </span>
                    <span class="switch">
                        <input type="checkbox" name="show_contact_public" id="editShowContact" value="1">
                        <span class="switch-slider"></span>
                    </span>
                </label>

                <div class="my-edit-actions">
                    <button type="button" class="btn btn-outline" id="btnCancelEdit"><?= htmlspecialchars($t('cancel')) ?></button>
                    <button type="submit" class="btn"><span class="material-icons">save</span><?= htmlspecialchars($t('my_save_changes')) ?></button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Donate panel -->
<div class="sheet-backdrop" id="donatePanel" aria-hidden="true">
    <div class="sheet-card" role="dialog">
        <header class="sheet-header">
            <div>
                <h2 class="sheet-title">
                    <span class="material-icons">volunteer_activism</span>
                    <?= htmlspecialchars($t('nav_donate')) ?>
                </h2>
                <p class="sheet-sub"><?= htmlspecialchars($t('slogan_main')) ?></p>
            </div>
            <button type="button" class="btn-sheet-close" data-close-panel aria-label="<?= htmlspecialchars($t('cancel')) ?>">
                <span class="material-icons">close</span>
            </button>
        </header>
        <div class="sheet-body">
            <div class="stakeholders">
                <div class="stakeholder-pill pill-people">
                    <span class="material-icons">groups</span>
                    <?= htmlspecialchars($t('slogan_people')) ?>
                </div>
                <div class="stakeholder-pill pill-business">
                    <span class="material-icons">storefront</span>
                    <?= htmlspecialchars($t('slogan_business')) ?>
                </div>
                <div class="stakeholder-pill pill-volunteer">
                    <span class="material-icons">volunteer_activism</span>
                    <?= htmlspecialchars($t('slogan_volunteer')) ?>
                </div>
                <div class="stakeholder-pill pill-scout">
                    <span class="material-icons">hiking</span>
                    <?= htmlspecialchars($t('slogan_scout')) ?>
                </div>
                <div class="stakeholder-pill pill-ngo">
                    <span class="material-icons">handshake</span>
                    <?= htmlspecialchars($t('slogan_ngo')) ?>
                </div>
            </div>

            <div class="info-card">
                <div class="info-card-icon info-card-icon-doge"><span class="doge-symbol">Ð</span></div>
                <div>
                    <strong><?= htmlspecialchars($t('donate_title')) ?></strong>
                    <p class="info-card-desc"><?= htmlspecialchars($dogeNote) ?></p>
                </div>
            </div>

            <?php if ($dogeWallet): ?>
            <?php $dogeUri = 'dogecoin:' . $dogeWallet; ?>
            <div class="wallet-box">
                <label><?= htmlspecialchars($t('donate_wallet')) ?></label>
                <code id="dogeWallet"><?= htmlspecialchars($dogeWallet) ?></code>
                <div class="wallet-qr">
                    <img
                        src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&amp;bgcolor=ffffff&amp;color=000000&amp;data=<?= rawurlencode($dogeUri) ?>"
                        alt="<?= htmlspecialchars($t('donate_qr_alt')) ?>"
                        width="200"
                        height="200"
                        loading="lazy"
                    >
                    <p class="wallet-qr-hint"><?= htmlspecialchars($t('donate_qr_hint')) ?></p>
                </div>
                <a href="<?= htmlspecialchars($dogeUri) ?>" class="btn btn-doge btn-block" id="openDogeWallet">
                    <span class="doge-symbol">Ð</span>
                    <?= htmlspecialchars($t('donate_open_wallet')) ?>
                </a>
                <button type="button" class="btn btn-outline btn-block" id="copyWallet">
                    <span class="material-icons">content_copy</span>
                    <?= htmlspecialchars($t('donate_copy')) ?>
                </button>
            </div>
            <?php else: ?>
            <p class="hint"><?= htmlspecialchars($t('donate_subtitle')) ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($canPost): ?>
<!-- Post organization panel -->
<div class="sheet-backdrop" id="postPanel" aria-hidden="true">
    <div class="sheet-card sheet-card-wide" role="dialog">
        <header class="sheet-header">
            <div>
                <h2 class="sheet-title">
                    <span class="material-icons">add_location</span>
                    <?= htmlspecialchars($t('register_org_title')) ?>
                </h2>
                <p class="sheet-sub"><?= htmlspecialchars($t('post_subtitle')) ?></p>
            </div>
            <button type="button" class="btn-sheet-close" data-close-panel aria-label="<?= htmlspecialchars($t('cancel')) ?>">
                <span class="material-icons">close</span>
            </button>
        </header>
        <div class="sheet-body">
            <form id="orgForm" class="sheet-form">
                <label><?= htmlspecialchars($t('org_name')) ?></label>
                <input type="text" name="name" required>

                <p class="step-section-title"><span class="material-icons">badge</span><?= htmlspecialchars($t('org_type')) ?></p>
                <p class="hint"><?= htmlspecialchars($t('org_type_hint')) ?></p>

                <p class="step-section-sub"><?= htmlspecialchars($t('org_type_individuals')) ?></p>
                <div class="option-cards option-cards-org">
                    <?php $first = true; foreach ($individualTypes as $value => $opt): ?>
                    <label class="option-card">
                        <input type="radio" name="type" value="<?= $value ?>" <?= $first ? 'checked' : '' ?>>
                        <span class="material-icons"><?= $opt['icon'] ?></span>
                        <span><?= htmlspecialchars($opt['label']) ?></span>
                    </label>
                    <?php $first = false; endforeach; ?>
                </div>

                <p class="step-section-sub"><?= htmlspecialchars($t('org_type_producers')) ?></p>
                <div class="option-cards option-cards-org">
                    <?php foreach ($producerTypes as $value => $opt): ?>
                    <label class="option-card">
                        <input type="radio" name="type" value="<?= $value ?>">
                        <span class="material-icons"><?= $opt['icon'] ?></span>
                        <span><?= htmlspecialchars($opt['label']) ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>

                <p class="step-section-sub"><?= htmlspecialchars($t('org_type_groups')) ?></p>
                <div class="option-cards option-cards-org">
                    <?php foreach ($groupTypes as $value => $opt): ?>
                    <label class="option-card">
                        <input type="radio" name="type" value="<?= $value ?>">
                        <span class="material-icons"><?= $opt['icon'] ?></span>
                        <span><?= htmlspecialchars($opt['label']) ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>

                <p class="step-section-title"><span class="material-icons">inventory_2</span><?= htmlspecialchars($t('we_offer')) ?></p>
                <p class="hint"><?= htmlspecialchars($t('select_offers_hint')) ?></p>
                <div class="switch-list">
                    <?php foreach ($categoryOptions as $value => $opt): ?>
                    <label class="switch-row">
                        <span class="switch-row-label">
                            <span class="material-icons"><?= $opt['icon'] ?></span>
                            <span><?= htmlspecialchars($opt['label']) ?></span>
                        </span>
                        <span class="switch">
                            <input type="checkbox" name="offers[]" value="<?= $value ?>">
                            <span class="switch-slider"></span>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>

                <p class="step-section-title"><span class="material-icons">favorite</span><?= htmlspecialchars($t('we_need')) ?></p>
                <p class="hint"><?= htmlspecialchars($t('select_needs_hint')) ?></p>
                <div class="switch-list">
                    <?php foreach ($categoryOptions as $value => $opt): ?>
                    <label class="switch-row">
                        <span class="switch-row-label">
                            <span class="material-icons"><?= $opt['icon'] ?></span>
                            <span><?= htmlspecialchars($opt['label']) ?></span>
                        </span>
                        <span class="switch">
                            <input type="checkbox" name="needs[]" value="<?= $value ?>">
                            <span class="switch-slider"></span>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>

                <label><?= htmlspecialchars($t('share_description')) ?></label>
                <textarea name="description" rows="2" placeholder="<?= htmlspecialchars($t('share_description_hint')) ?>"></textarea>

                <label><?= htmlspecialchars($t('instructions')) ?></label>
                <textarea name="instructions" rows="2" placeholder="<?= htmlspecialchars($t('share_instructions_hint')) ?>"></textarea>

                <p class="step-section-title"><span class="material-icons">schedule</span><?= htmlspecialchars($t('pickup_window')) ?></p>
                <p class="hint"><?= htmlspecialchars($t('pickup_hint')) ?></p>
                <div class="form-row">
                    <div>
                        <label><?= htmlspecialchars($t('pickup_start')) ?></label>
                        <input type="datetime-local" name="pickup_start" id="pickupStart" required>
                    </div>
                    <div>
                        <label><?= htmlspecialchars($t('pickup_end')) ?></label>
                        <input type="datetime-local" name="pickup_end" id="pickupEnd" required>
                    </div>
                </div>

                <label><?= htmlspecialchars($t('address')) ?></label>
                <input type="text" name="address" id="orgAddress">
                <div class="form-row">
                    <div><label><?= htmlspecialchars($t('city')) ?></label><input type="text" name="city" id="orgCity"></div>
                    <div>
                        <label><?= htmlspecialchars($t('country')) ?></label>
                        <select name="country" id="orgCountry">
                            <option value=""><?= htmlspecialchars($t('country_select')) ?></option>
                            <?php foreach ($countries as $code => $label): ?>
                            <option value="<?= htmlspecialchars($code) ?>"><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <label><?= htmlspecialchars($t('email')) ?></label>
                <input type="email" name="contact_email" value="<?= $user ? sanitize($user['email']) : '' ?>">
                <label><?= htmlspecialchars($t('phone')) ?></label>
                <input type="tel" name="contact_phone">

                <label class="switch-row switch-row-block">
                    <span class="switch-row-label">
                        <span class="material-icons">visibility</span>
                        <span><?= htmlspecialchars($t('contact_public_label')) ?></span>
                    </span>
                    <span class="switch">
                        <input type="checkbox" name="show_contact_public" value="1">
                        <span class="switch-slider"></span>
                    </span>
                </label>
                <p class="hint warning-hint"><?= htmlspecialchars($t('contact_public_warning')) ?></p>

                <p class="step-section-title"><span class="material-icons">photo_camera</span><?= htmlspecialchars($t('location_photo')) ?></p>
                <p class="hint"><?= htmlspecialchars($t('location_photo_hint')) ?></p>
                <div class="photo-upload">
                    <label class="photo-upload-btn" for="orgImage">
                        <span class="material-icons">add_a_photo</span>
                        <span><?= htmlspecialchars($t('location_photo_choose')) ?></span>
                    </label>
                    <input type="file" name="image" id="orgImage" accept="image/jpeg,image/png,image/webp,image/gif" hidden>
                    <div class="photo-preview hidden" id="orgImagePreview">
                        <img src="" alt="" id="orgImagePreviewImg">
                        <button type="button" class="photo-remove" id="orgImageRemove" aria-label="<?= htmlspecialchars($t('location_photo_remove')) ?>">
                            <span class="material-icons">close</span>
                        </button>
                    </div>
                </div>

                <p class="step-section-title"><span class="material-icons">place</span><?= htmlspecialchars($t('location')) ?></p>
                <p class="hint"><?= htmlspecialchars($t('map_hint')) ?></p>
                <button type="button" class="btn btn-outline btn-block" id="btnPickMyLocation">
                    <span class="material-icons">my_location</span>
                    <?= htmlspecialchars($t('use_my_location')) ?>
                </button>
                <div id="pickMap" class="pick-map"></div>
                <input type="hidden" name="latitude" id="pickLat">
                <input type="hidden" name="longitude" id="pickLng">

                <div class="slide-submit" id="slideSubmit">
                    <span class="track-text"><?= htmlspecialchars($t('slide_submit')) ?></span>
                    <div class="thumb"><span class="material-icons">chevron_right</span></div>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Detail panel -->
<div class="sheet-backdrop" id="detailPanel" aria-hidden="true">
    <div class="sheet-card" role="dialog">
        <header class="sheet-header">
            <div>
                <h2 class="sheet-title" id="detailTitle">
                    <span class="material-icons">place</span>
                    <span id="detailTitleText"></span>
                </h2>
                <p class="sheet-sub" id="detailSubtitle"></p>
            </div>
            <button type="button" class="btn-sheet-close" data-close-panel aria-label="<?= htmlspecialchars($t('cancel')) ?>">
                <span class="material-icons">close</span>
            </button>
        </header>
        <div class="sheet-body" id="detailContent"></div>
    </div>
</div>

<!-- Onboarding wizard (first visit) -->
<div class="onboarding-backdrop" id="onboardingWizard" aria-hidden="true">
    <div class="onboarding-card" role="dialog" aria-labelledby="onboardingTitle">
        <div class="onboarding-header">
            <img src="<?= htmlspecialchars(webPath('assets/img/DogeSeeds_logo.png')) ?>" alt="" class="onboarding-logo">
            <h2 id="onboardingTitle" class="onboarding-title"><?= htmlspecialchars($t('onboarding_welcome')) ?></h2>
            <p class="onboarding-sub" id="onboardingSubtitle"><?= htmlspecialchars($t('onboarding_subtitle')) ?></p>
        </div>

        <div class="onboarding-body">
            <!-- Step 1: choose path -->
            <div class="onboarding-step" data-step="welcome">
                <p class="onboarding-question"><?= htmlspecialchars($t('onboarding_question')) ?></p>
                <div class="onboarding-cookie-notice">
                    <span class="material-icons">cookie</span>
                    <p><?= htmlspecialchars($t('cookie_notice')) ?></p>
                </div>
                <div class="wizard-choices">
                    <button type="button" class="wizard-choice" data-path="seeker">
                        <span class="material-icons">search</span>
                        <strong><?= htmlspecialchars($t('onboarding_seeker_title')) ?></strong>
                        <span><?= htmlspecialchars($t('onboarding_seeker_desc')) ?></span>
                    </button>
                    <button type="button" class="wizard-choice" data-path="sharer">
                        <span class="material-icons">volunteer_activism</span>
                        <strong><?= htmlspecialchars($t('onboarding_sharer_title')) ?></strong>
                        <span><?= htmlspecialchars($t('onboarding_sharer_desc')) ?></span>
                    </button>
                </div>
            </div>

            <!-- Seeker: what do you need? -->
            <div class="onboarding-step hidden" data-step="seeker-categories">
                <p class="onboarding-question"><?= htmlspecialchars($t('onboarding_seeker_cats')) ?></p>
                <p class="hint"><?= htmlspecialchars($t('onboarding_seeker_cats_hint')) ?></p>
                <div class="switch-list wizard-cat-list">
                    <?php foreach ($categoryOptions as $value => $opt): ?>
                    <label class="switch-row">
                        <span class="switch-row-label">
                            <span class="material-icons"><?= $opt['icon'] ?></span>
                            <span><?= htmlspecialchars($opt['label']) ?></span>
                        </span>
                        <span class="switch">
                            <input type="checkbox" name="wizard_seek_cat" value="<?= $value ?>">
                            <span class="switch-slider"></span>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Seeker: done -->
            <div class="onboarding-step hidden" data-step="seeker-done">
                <div class="onboarding-done-icon"><span class="material-icons">map</span></div>
                <p class="onboarding-question"><?= htmlspecialchars($t('onboarding_seeker_done')) ?></p>
                <ul class="onboarding-tips">
                    <li><span class="material-icons">my_location</span><?= htmlspecialchars($t('onboarding_tip_nearby')) ?></li>
                    <li><span class="material-icons">filter_list</span><?= htmlspecialchars($t('onboarding_tip_filters')) ?></li>
                    <li><span class="material-icons">list</span><?= htmlspecialchars($t('onboarding_tip_list')) ?></li>
                </ul>
            </div>

            <!-- Sharer: who are you? -->
            <div class="onboarding-step hidden" data-step="sharer-type">
                <p class="onboarding-question"><?= htmlspecialchars($t('onboarding_sharer_who')) ?></p>
                <div class="option-cards option-cards-org wizard-org-grid">
                    <?php foreach ($orgTypes as $value => $opt): ?>
                    <label class="option-card wizard-org-card">
                        <input type="radio" name="wizard_org_type" value="<?= $value ?>">
                        <span class="material-icons"><?= $opt['icon'] ?></span>
                        <span><?= htmlspecialchars($opt['label']) ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Sharer: offer or need support -->
            <div class="onboarding-step hidden" data-step="sharer-intent">
                <p class="onboarding-question"><?= htmlspecialchars($t('onboarding_sharer_intent')) ?></p>
                <p class="hint"><?= htmlspecialchars($t('onboarding_sharer_intent_hint')) ?></p>

                <div class="wizard-intent-list">
                    <div class="wizard-intent-group">
                        <label class="switch-row wizard-intent-toggle">
                            <span class="switch-row-label">
                                <span class="material-icons">inventory_2</span>
                                <span class="wizard-intent-copy">
                                    <span class="wizard-intent-title"><?= htmlspecialchars($t('onboarding_intent_offer')) ?></span>
                                    <span class="wizard-intent-hint"><?= htmlspecialchars($t('onboarding_intent_offer_hint')) ?></span>
                                </span>
                            </span>
                            <span class="switch">
                                <input type="checkbox" id="wizardIntentOffer">
                                <span class="switch-slider"></span>
                            </span>
                        </label>
                        <div class="switch-list wizard-intent-cats hidden" id="wizardOfferCats">
                            <?php foreach ($categoryOptions as $value => $opt): ?>
                            <label class="switch-row switch-row-nested">
                                <span class="switch-row-label">
                                    <span class="material-icons"><?= $opt['icon'] ?></span>
                                    <span><?= htmlspecialchars($opt['label']) ?></span>
                                </span>
                                <span class="switch">
                                    <input type="checkbox" name="wizard_offer_cat" value="<?= $value ?>">
                                    <span class="switch-slider"></span>
                                </span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="wizard-intent-group">
                        <label class="switch-row wizard-intent-toggle">
                            <span class="switch-row-label">
                                <span class="material-icons">favorite</span>
                                <span class="wizard-intent-copy">
                                    <span class="wizard-intent-title"><?= htmlspecialchars($t('onboarding_intent_need')) ?></span>
                                    <span class="wizard-intent-hint"><?= htmlspecialchars($t('onboarding_intent_need_hint')) ?></span>
                                </span>
                            </span>
                            <span class="switch">
                                <input type="checkbox" id="wizardIntentNeed">
                                <span class="switch-slider"></span>
                            </span>
                        </label>
                        <div class="switch-list wizard-intent-cats hidden" id="wizardNeedCats">
                            <?php foreach ($categoryOptions as $value => $opt): ?>
                            <label class="switch-row switch-row-nested">
                                <span class="switch-row-label">
                                    <span class="material-icons"><?= $opt['icon'] ?></span>
                                    <span><?= htmlspecialchars($opt['label']) ?></span>
                                </span>
                                <span class="switch">
                                    <input type="checkbox" name="wizard_need_cat" value="<?= $value ?>">
                                    <span class="switch-slider"></span>
                                </span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sharer: done -->
            <div class="onboarding-step hidden" data-step="sharer-done">
                <div class="onboarding-done-icon"><span class="material-icons">add_location</span></div>
                <p class="onboarding-question"><?= htmlspecialchars($t('onboarding_sharer_done')) ?></p>
                <p class="hint" id="onboardingSharerDoneHint"><?= htmlspecialchars($t('onboarding_sharer_done_guest')) ?></p>
                <div class="onboarding-actions-stack">
                    <button type="button" class="btn btn-block" id="onboardingBtnPost"><?= htmlspecialchars($t('onboarding_btn_post')) ?></button>
                    <button type="button" class="btn btn-outline btn-block" id="onboardingBtnRegister"><?= htmlspecialchars($t('onboarding_btn_register')) ?></button>
                    <button type="button" class="btn btn-ghost btn-block" id="onboardingBtnExplore"><?= htmlspecialchars($t('onboarding_btn_explore')) ?></button>
                </div>
            </div>
        </div>

        <footer class="onboarding-footer">
            <div class="onboarding-progress" id="onboardingProgress" aria-hidden="true"></div>
            <label class="onboarding-dismiss switch-row">
                <span class="switch-row-label">
                    <span><?= htmlspecialchars($t('onboarding_dismiss')) ?></span>
                </span>
                <span class="switch switch-compact">
                    <input type="checkbox" id="onboardingDismiss">
                    <span class="switch-slider"></span>
                </span>
            </label>
            <div class="onboarding-nav">
                <button type="button" class="btn btn-outline btn-sm hidden" id="onboardingBack"><?= htmlspecialchars($t('onboarding_back')) ?></button>
                <button type="button" class="btn btn-sm hidden" id="onboardingNext"><?= htmlspecialchars($t('onboarding_continue')) ?></button>
                <button type="button" class="btn btn-ghost btn-sm" id="onboardingSkip"><?= htmlspecialchars($t('onboarding_skip')) ?></button>
            </div>
        </footer>
    </div>
</div>

<!-- Auth panel -->
<div class="sheet-backdrop" id="authPanel" aria-hidden="true">
    <div class="sheet-card sheet-card-auth" role="dialog">
        <header class="sheet-header auth-header">
            <div>
                <div class="auth-badge" aria-hidden="true">
                    <span class="material-icons" id="authPanelIcon">login</span>
                </div>
                <h2 class="sheet-title auth-title">
                    <span id="authPanelTitle"><?= htmlspecialchars($t('nav_login')) ?></span>
                </h2>
                <p class="sheet-sub auth-sub" id="authPanelSubtitle"><?= htmlspecialchars($t('auth_login_subtitle')) ?></p>
            </div>
            <button type="button" class="btn-sheet-close" data-close-panel aria-label="<?= htmlspecialchars($t('cancel')) ?>">
                <span class="material-icons">close</span>
            </button>
        </header>
        <div class="sheet-body auth-body">
            <div class="auth-tabs" role="tablist">
                <button type="button" class="auth-tab active" data-auth="login" role="tab" aria-selected="true">
                    <span class="material-icons">login</span>
                    <span><?= htmlspecialchars($t('nav_login')) ?></span>
                </button>
                <button type="button" class="auth-tab" data-auth="register" role="tab" aria-selected="false">
                    <span class="material-icons">person_add</span>
                    <span><?= htmlspecialchars($t('nav_register')) ?></span>
                </button>
            </div>
            <form id="loginForm" class="auth-form">
                <div class="auth-field">
                    <label for="loginEmail"><?= htmlspecialchars($t('email')) ?></label>
                    <div class="auth-input-wrap">
                        <span class="material-icons auth-input-icon">mail</span>
                        <input type="email" name="email" id="loginEmail" required autocomplete="email" placeholder="<?= htmlspecialchars($t('email')) ?>">
                    </div>
                </div>
                <div class="auth-field">
                    <label for="loginPassword"><?= htmlspecialchars($t('password')) ?></label>
                    <div class="auth-input-wrap">
                        <span class="material-icons auth-input-icon">lock</span>
                        <input type="password" name="password" id="loginPassword" required autocomplete="current-password" placeholder="<?= htmlspecialchars($t('password')) ?>">
                    </div>
                </div>
                <button type="submit" class="btn btn-block auth-submit" id="loginSubmitBtn">
                    <span class="material-icons">login</span>
                    <span><?= htmlspecialchars($t('nav_login')) ?></span>
                </button>
                <p class="auth-forgot-wrap">
                    <button type="button" class="auth-link-btn" id="btnShowForgot"><?= htmlspecialchars($t('password_forgot')) ?></button>
                </p>
            </form>
            <form id="forgotForm" class="auth-form hidden">
                <p class="auth-forgot-hint"><?= htmlspecialchars($t('password_forgot_hint')) ?></p>
                <div class="auth-field">
                    <label for="forgotEmail"><?= htmlspecialchars($t('email')) ?></label>
                    <div class="auth-input-wrap">
                        <span class="material-icons auth-input-icon">mail</span>
                        <input type="email" name="email" id="forgotEmail" required autocomplete="email" placeholder="<?= htmlspecialchars($t('email')) ?>">
                    </div>
                </div>
                <button type="submit" class="btn btn-block auth-submit">
                    <span class="material-icons">mail</span>
                    <span><?= htmlspecialchars($t('password_forgot_send')) ?></span>
                </button>
                <p class="auth-forgot-wrap">
                    <button type="button" class="auth-link-btn" id="btnBackToLogin"><?= htmlspecialchars($t('password_forgot_back')) ?></button>
                </p>
            </form>
            <form id="registerForm" class="auth-form hidden">
                <div class="auth-field">
                    <label for="registerName"><?= htmlspecialchars($t('name')) ?></label>
                    <div class="auth-input-wrap">
                        <span class="material-icons auth-input-icon">badge</span>
                        <input type="text" name="name" id="registerName" required autocomplete="name" placeholder="<?= htmlspecialchars($t('name')) ?>">
                    </div>
                </div>
                <div class="auth-field">
                    <label for="registerEmail"><?= htmlspecialchars($t('email')) ?></label>
                    <div class="auth-input-wrap">
                        <span class="material-icons auth-input-icon">mail</span>
                        <input type="email" name="email" id="registerEmail" required autocomplete="email" placeholder="<?= htmlspecialchars($t('email')) ?>">
                    </div>
                </div>
                <div class="auth-field">
                    <label for="registerPassword"><?= htmlspecialchars($t('password')) ?></label>
                    <div class="auth-input-wrap">
                        <span class="material-icons auth-input-icon">lock</span>
                        <input type="password" name="password" id="registerPassword" minlength="8" required autocomplete="new-password" placeholder="<?= htmlspecialchars($t('password')) ?>">
                    </div>
                </div>
                <div class="auth-field">
                    <label for="registerPasswordConfirm"><?= htmlspecialchars($t('password_confirm')) ?></label>
                    <div class="auth-input-wrap">
                        <span class="material-icons auth-input-icon">lock</span>
                        <input type="password" name="password_confirm" id="registerPasswordConfirm" minlength="8" required autocomplete="new-password" placeholder="<?= htmlspecialchars($t('password_confirm')) ?>">
                    </div>
                </div>
                <div class="auth-field">
                    <label for="registerRole"><?= htmlspecialchars($t('org_type')) ?></label>
                    <div class="auth-input-wrap auth-input-wrap-select">
                        <span class="material-icons auth-input-icon">groups</span>
                        <select name="role" id="registerRole">
                            <option value="user"><?= htmlspecialchars($t('role_user')) ?></option>
                            <option value="business"><?= htmlspecialchars($t('role_business')) ?></option>
                            <option value="volunteer"><?= htmlspecialchars($t('role_volunteer')) ?></option>
                            <option value="ngo"><?= htmlspecialchars($t('role_ngo')) ?></option>
                        </select>
                    </div>
                </div>
                <div class="privacy-notice">
                    <span class="material-icons">shield</span>
                    <p><?= htmlspecialchars($t('privacy_notice')) ?></p>
                </div>
                <label class="switch-row switch-row-block privacy-consent">
                    <span class="switch-row-label">
                        <span class="material-icons">verified_user</span>
                        <span><?= htmlspecialchars($t('privacy_consent')) ?></span>
                    </span>
                    <span class="switch">
                        <input type="checkbox" name="privacy_consent" value="1" required>
                        <span class="switch-slider"></span>
                    </span>
                </label>
                <button type="submit" class="btn btn-block auth-submit" id="registerSubmitBtn">
                    <span class="material-icons">person_add</span>
                    <span><?= htmlspecialchars($t('nav_register')) ?></span>
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Toast + modal dialogs -->
<div class="ds-toast-stack" id="toastStack" aria-live="polite"></div>
<div class="ds-modal-backdrop" id="dsModalBackdrop" aria-hidden="true">
    <div class="ds-modal" role="alertdialog" aria-labelledby="dsModalTitle" aria-describedby="dsModalMessage">
        <div class="ds-modal-icon" id="dsModalIcon"><span class="material-icons">info</span></div>
        <h3 class="ds-modal-title" id="dsModalTitle"></h3>
        <p class="ds-modal-message" id="dsModalMessage"></p>
        <div class="ds-modal-actions">
            <button type="button" class="btn btn-outline" id="dsModalCancel"><?= htmlspecialchars($t('cancel')) ?></button>
            <button type="button" class="btn" id="dsModalConfirm"><?= htmlspecialchars($t('confirm')) ?></button>
        </div>
    </div>
</div>

<script>
    window.DogeSeeds = {
        apiBase: <?= json_encode(webPath('api/'), JSON_UNESCAPED_UNICODE) ?>,
        siteUrl: <?= json_encode($siteBaseUrl, JSON_UNESCAPED_UNICODE) ?>,
        shareLocationId: <?= $shareLocationId ? (int) $shareLocationId : 'null' ?>,
        lang: '<?= $lang ?>',
        mapDefaults: { lat: <?= $mapLat ?>, lng: <?= $mapLng ?>, zoom: <?= $mapZoom ?> },
        user: <?= $user ? json_encode(['id' => $user['id'], 'name' => $user['name'], 'role' => $user['role']]) : 'null' ?>,
        canPost: <?= $canPost ? 'true' : 'false' ?>,
        strings: <?= json_encode(I18n::all(), JSON_UNESCAPED_UNICODE) ?>,
        slogans: <?= json_encode($sloganLines, JSON_UNESCAPED_UNICODE) ?>,
        sloganIcons: <?= json_encode($sloganIcons, JSON_UNESCAPED_UNICODE) ?>,
        brandLogoCycle: [
            { icon: 'favorite', color: '#4CAF50' },
            { icon: 'restaurant', color: '#4CAF50' },
            { icon: 'checkroom', color: '#42A5F5' },
            { icon: 'toys', color: '#F5A623' },
            { icon: 'medical_services', color: '#EC407A' },
            { icon: 'volunteer_activism', color: '#FF9800' },
            { icon: 'pets', color: '#F5A623' }
        ]
    };
</script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
<script src="<?= htmlspecialchars(webPath('assets/js/app.js')) ?>"></script>
</body>
</html>
