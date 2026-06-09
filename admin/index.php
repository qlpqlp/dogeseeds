<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (!isInstalled()) {
    header('Location: ../install/');
    exit;
}

$user = Auth::user();
if (!$user || $user['role'] !== 'admin') {
    http_response_code(403);
    echo 'Admin access required. <a href="../">Back to site</a>';
    exit;
}

I18n::init($_GET['lang'] ?? null);
$t = fn(string $key) => I18n::t($key);
$siteName = getSetting('site_name', 'DogeSeeds.org') ?? 'DogeSeeds.org';
$languages = I18n::getLanguageMeta();
$countries = require dirname(__DIR__) . '/includes/countries.php';
$mapLat = getSetting('map_default_lat', '38.7223') ?? '38.7223';
$mapLng = getSetting('map_default_lng', '-9.1393') ?? '-9.1393';
$mapZoom = (int) (getSetting('map_default_zoom', '6') ?? '6');

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
$categoryOptions = [
    'food' => ['icon' => 'restaurant', 'label' => $t('filter_food')],
    'clothing' => ['icon' => 'checkroom', 'label' => $t('filter_clothing')],
    'toys' => ['icon' => 'toys', 'label' => $t('filter_toys')],
    'essentials' => ['icon' => 'medical_services', 'label' => $t('filter_essentials')],
];
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(I18n::getLang()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($t('admin_title')) ?> | <?= htmlspecialchars($siteName) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="<?= htmlspecialchars(webPath('assets/css/style.css')) ?>">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css">
</head>
<body class="admin-page">
<div class="admin-wrap">
    <header class="admin-header">
        <a href="../" class="admin-back"><span class="material-icons">arrow_back</span><?= htmlspecialchars($t('admin_back')) ?></a>
        <h1><span class="material-icons">admin_panel_settings</span><?= htmlspecialchars($t('admin_title')) ?></h1>
        <p><?= htmlspecialchars($t('admin_subtitle')) ?></p>
    </header>

    <nav class="admin-tabs" aria-label="<?= htmlspecialchars($t('admin_title')) ?>">
        <button type="button" class="admin-tab active" data-admin-tab="settings">
            <span class="material-icons">settings</span><?= htmlspecialchars($t('admin_tab_settings')) ?>
        </button>
        <button type="button" class="admin-tab" data-admin-tab="listings">
            <span class="material-icons">inventory</span><?= htmlspecialchars($t('admin_tab_listings')) ?>
        </button>
        <button type="button" class="admin-tab" data-admin-tab="users">
            <span class="material-icons">group</span><?= htmlspecialchars($t('admin_tab_users')) ?>
        </button>
    </nav>

    <p class="admin-status" id="adminStatus" hidden></p>

    <form id="adminForm" class="admin-form admin-section" data-admin-section="settings">
        <section class="admin-card">
            <h2><span class="material-icons">public</span><?= htmlspecialchars($t('admin_general_title')) ?></h2>
            <p class="hint"><?= htmlspecialchars($t('admin_general_hint')) ?></p>

            <label><?= htmlspecialchars($t('admin_site_name')) ?></label>
            <input type="text" name="site_name" id="siteName" required>

            <label><?= htmlspecialchars($t('admin_site_url')) ?></label>
            <input type="url" name="site_url" id="siteUrl" placeholder="https://yourdomain.com">
            <p class="hint"><?= htmlspecialchars($t('admin_site_url_hint')) ?></p>

            <label><?= htmlspecialchars($t('admin_default_language')) ?></label>
            <select name="default_language" id="defaultLanguage">
                <?php foreach ($languages as $code => $meta): ?>
                <option value="<?= htmlspecialchars($code) ?>"><?= htmlspecialchars($meta['label']) ?></option>
                <?php endforeach; ?>
            </select>
            <p class="hint"><?= htmlspecialchars($t('admin_default_language_hint')) ?></p>
        </section>

        <section class="admin-card">
            <h2><span class="material-icons">map</span><?= htmlspecialchars($t('admin_map_title')) ?></h2>
            <p class="hint"><?= htmlspecialchars($t('admin_map_hint')) ?></p>

            <div class="form-row">
                <div>
                    <label><?= htmlspecialchars($t('admin_map_lat')) ?></label>
                    <input type="number" name="map_default_lat" id="mapLat" step="any" min="-90" max="90">
                </div>
                <div>
                    <label><?= htmlspecialchars($t('admin_map_lng')) ?></label>
                    <input type="number" name="map_default_lng" id="mapLng" step="any" min="-180" max="180">
                </div>
            </div>

            <label><?= htmlspecialchars($t('admin_map_zoom')) ?></label>
            <input type="number" name="map_default_zoom" id="mapZoom" min="1" max="18" step="1">
        </section>

        <section class="admin-card">
            <h2><span class="material-icons">currency_bitcoin</span><?= htmlspecialchars($t('admin_donate_title')) ?></h2>
            <p class="hint"><?= htmlspecialchars($t('admin_donate_hint')) ?></p>

            <label><?= htmlspecialchars($t('donate_wallet')) ?></label>
            <input type="text" name="doge_wallet" id="dogeWallet" placeholder="D...">

            <label><?= htmlspecialchars($t('admin_donate_note')) ?></label>
            <textarea name="doge_transparency_note" id="dogeNote" rows="3"></textarea>
        </section>

        <section class="admin-card">
            <h2><span class="material-icons">mail</span><?= htmlspecialchars($t('admin_smtp_title')) ?></h2>
            <p class="hint"><?= htmlspecialchars($t('admin_smtp_hint')) ?></p>

            <label class="switch-row switch-row-block">
                <span class="switch-row-label">
                    <span class="material-icons">power_settings_new</span>
                    <span><?= htmlspecialchars($t('admin_smtp_enable')) ?></span>
                </span>
                <span class="switch">
                    <input type="checkbox" name="smtp_enabled" id="smtpEnabled" value="1">
                    <span class="switch-slider"></span>
                </span>
            </label>

            <div class="form-row">
                <div>
                    <label><?= htmlspecialchars($t('admin_smtp_host')) ?></label>
                    <input type="text" name="smtp_host" id="smtpHost" placeholder="smtp.example.com">
                </div>
                <div>
                    <label><?= htmlspecialchars($t('admin_smtp_port')) ?></label>
                    <input type="number" name="smtp_port" id="smtpPort" value="587" min="1" max="65535">
                </div>
            </div>

            <label><?= htmlspecialchars($t('admin_smtp_encryption')) ?></label>
            <select name="smtp_encryption" id="smtpEncryption">
                <option value="tls">TLS</option>
                <option value="ssl">SSL</option>
                <option value="none"><?= htmlspecialchars($t('admin_smtp_none')) ?></option>
            </select>

            <div class="form-row">
                <div>
                    <label><?= htmlspecialchars($t('admin_smtp_username')) ?></label>
                    <input type="text" name="smtp_username" id="smtpUsername" autocomplete="username">
                </div>
                <div>
                    <label><?= htmlspecialchars($t('admin_smtp_password')) ?></label>
                    <input type="password" name="smtp_password" id="smtpPassword" autocomplete="new-password" placeholder="<?= htmlspecialchars($t('admin_smtp_password_placeholder')) ?>">
                </div>
            </div>

            <div class="form-row">
                <div>
                    <label><?= htmlspecialchars($t('admin_smtp_from_email')) ?></label>
                    <input type="email" name="smtp_from_email" id="smtpFromEmail">
                </div>
                <div>
                    <label><?= htmlspecialchars($t('admin_smtp_from_name')) ?></label>
                    <input type="text" name="smtp_from_name" id="smtpFromName">
                </div>
            </div>

            <div class="admin-actions admin-actions-inline">
                <button type="button" class="btn btn-outline" id="btnTestEmail">
                    <span class="material-icons">send</span><?= htmlspecialchars($t('admin_test_email')) ?>
                </button>
                <p class="hint"><?= htmlspecialchars($t('admin_test_email_hint')) ?></p>
            </div>
        </section>

        <div class="admin-save-bar">
            <button type="submit" class="btn btn-block">
                <span class="material-icons">save</span><?= htmlspecialchars($t('admin_save_all')) ?>
            </button>
        </div>
    </form>

    <section class="admin-section hidden" data-admin-section="listings" id="adminListingsSection">
        <div class="admin-card">
            <h2><span class="material-icons">inventory</span><?= htmlspecialchars($t('admin_listings_title')) ?></h2>
            <p class="hint"><?= htmlspecialchars($t('admin_listings_hint')) ?></p>
            <div class="admin-search-wrap">
                <span class="material-icons" aria-hidden="true">search</span>
                <input type="search" id="adminListingsSearch" placeholder="<?= htmlspecialchars($t('admin_search_placeholder')) ?>" autocomplete="off">
            </div>
            <div id="adminListingsList" class="admin-table-wrap"></div>
        </div>
        <div class="admin-card hidden" id="adminListingEditCard">
            <h2><span class="material-icons">edit</span><?= htmlspecialchars($t('admin_edit_listing')) ?></h2>
            <form id="adminListingEditForm" class="sheet-form">
                <input type="hidden" name="location_id" id="adminEditLocationId">
                <input type="hidden" name="offers_sent" value="1">
                <input type="hidden" name="needs_sent" value="1">
                <p class="hint" id="adminEditOwnerInfo"></p>

                <label><?= htmlspecialchars($t('org_name')) ?></label>
                <input type="text" name="org_name" id="adminEditOrgName" required>

                <p class="step-section-title"><span class="material-icons">badge</span><?= htmlspecialchars($t('org_type')) ?></p>
                <p class="hint"><?= htmlspecialchars($t('org_type_hint')) ?></p>

                <p class="step-section-sub"><?= htmlspecialchars($t('org_type_individuals')) ?></p>
                <div class="option-cards option-cards-org" id="adminEditOrgTypeIndividuals">
                    <?php foreach ($individualTypes as $value => $opt): ?>
                    <label class="option-card">
                        <input type="radio" name="type" value="<?= htmlspecialchars($value) ?>">
                        <span class="material-icons"><?= $opt['icon'] ?></span>
                        <span><?= htmlspecialchars($opt['label']) ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>

                <p class="step-section-sub"><?= htmlspecialchars($t('org_type_producers')) ?></p>
                <div class="option-cards option-cards-org">
                    <?php foreach ($producerTypes as $value => $opt): ?>
                    <label class="option-card">
                        <input type="radio" name="type" value="<?= htmlspecialchars($value) ?>">
                        <span class="material-icons"><?= $opt['icon'] ?></span>
                        <span><?= htmlspecialchars($opt['label']) ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>

                <p class="step-section-sub"><?= htmlspecialchars($t('org_type_groups')) ?></p>
                <div class="option-cards option-cards-org">
                    <?php foreach ($groupTypes as $value => $opt): ?>
                    <label class="option-card">
                        <input type="radio" name="type" value="<?= htmlspecialchars($value) ?>">
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
                            <input type="checkbox" name="offers[]" value="<?= htmlspecialchars($value) ?>">
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
                            <input type="checkbox" name="needs[]" value="<?= htmlspecialchars($value) ?>">
                            <span class="switch-slider"></span>
                        </span>
                    </label>
                    <?php endforeach; ?>
                </div>

                <label><?= htmlspecialchars($t('share_description')) ?></label>
                <textarea name="description" id="adminEditDescription" rows="2" placeholder="<?= htmlspecialchars($t('share_description_hint')) ?>"></textarea>

                <label><?= htmlspecialchars($t('instructions')) ?></label>
                <textarea name="instructions" id="adminEditInstructions" rows="2" placeholder="<?= htmlspecialchars($t('share_instructions_hint')) ?>"></textarea>

                <p class="step-section-title"><span class="material-icons">schedule</span><?= htmlspecialchars($t('pickup_window')) ?></p>
                <p class="hint"><?= htmlspecialchars($t('pickup_hint')) ?></p>
                <div class="form-row">
                    <div>
                        <label><?= htmlspecialchars($t('pickup_start')) ?></label>
                        <input type="datetime-local" name="pickup_start" id="adminEditPickupStart">
                    </div>
                    <div>
                        <label><?= htmlspecialchars($t('pickup_end')) ?></label>
                        <input type="datetime-local" name="pickup_end" id="adminEditPickupEnd">
                    </div>
                </div>

                <label><?= htmlspecialchars($t('address')) ?></label>
                <input type="text" name="address" id="adminEditAddress">
                <div class="form-row">
                    <div><label><?= htmlspecialchars($t('city')) ?></label><input type="text" name="city" id="adminEditCity"></div>
                    <div>
                        <label><?= htmlspecialchars($t('country')) ?></label>
                        <select name="country" id="adminEditCountry">
                            <option value=""><?= htmlspecialchars($t('country_select')) ?></option>
                            <?php foreach ($countries as $code => $label): ?>
                            <option value="<?= htmlspecialchars($code) ?>"><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <label><?= htmlspecialchars($t('email')) ?></label>
                <input type="email" name="contact_email" id="adminEditContactEmail">
                <label><?= htmlspecialchars($t('phone')) ?></label>
                <input type="tel" name="contact_phone" id="adminEditContactPhone">

                <label class="switch-row switch-row-block">
                    <span class="switch-row-label">
                        <span class="material-icons">visibility</span>
                        <span><?= htmlspecialchars($t('contact_public_label')) ?></span>
                    </span>
                    <span class="switch">
                        <input type="checkbox" name="show_contact_public" id="adminEditShowContact" value="1">
                        <span class="switch-slider"></span>
                    </span>
                </label>
                <p class="hint warning-hint"><?= htmlspecialchars($t('contact_public_warning')) ?></p>

                <p class="step-section-title"><span class="material-icons">photo_camera</span><?= htmlspecialchars($t('location_photo')) ?></p>
                <p class="hint"><?= htmlspecialchars($t('location_photo_hint')) ?></p>
                <input type="hidden" name="remove_image" id="adminEditRemoveImage" value="0">
                <div class="photo-upload">
                    <div class="photo-preview hidden" id="adminEditImagePreview">
                        <img src="" alt="" id="adminEditImagePreviewImg">
                        <button type="button" class="photo-remove" id="adminEditImageRemove" aria-label="<?= htmlspecialchars($t('location_photo_remove')) ?>">
                            <span class="material-icons">close</span>
                        </button>
                    </div>
                    <label class="photo-upload-btn" for="adminEditImage" id="adminEditImageUploadBtn">
                        <span class="material-icons">add_a_photo</span>
                        <span id="adminEditImageUploadLabel"><?= htmlspecialchars($t('location_photo_choose')) ?></span>
                    </label>
                    <input type="file" name="image" id="adminEditImage" accept="image/jpeg,image/png,image/webp,image/gif" hidden>
                </div>

                <p class="step-section-title"><span class="material-icons">place</span><?= htmlspecialchars($t('location')) ?></p>
                <p class="hint"><?= htmlspecialchars($t('map_hint')) ?></p>
                <button type="button" class="btn btn-outline btn-block" id="adminBtnPickMyLocation">
                    <span class="material-icons">my_location</span>
                    <?= htmlspecialchars($t('use_my_location')) ?>
                </button>
                <div id="adminPickMap" class="pick-map"></div>
                <input type="hidden" name="latitude" id="adminPickLat">
                <input type="hidden" name="longitude" id="adminPickLng">

                <div class="admin-actions">
                    <button type="button" class="btn btn-outline" id="adminCancelListingEdit"><?= htmlspecialchars($t('cancel')) ?></button>
                    <button type="submit" class="btn"><span class="material-icons">save</span><?= htmlspecialchars($t('admin_save')) ?></button>
                </div>
            </form>
        </div>
    </section>

    <section class="admin-section hidden" data-admin-section="users" id="adminUsersSection">
        <div class="admin-card">
            <h2><span class="material-icons">group</span><?= htmlspecialchars($t('admin_users_title')) ?></h2>
            <p class="hint"><?= htmlspecialchars($t('admin_users_hint')) ?></p>
            <div class="admin-search-wrap">
                <span class="material-icons" aria-hidden="true">search</span>
                <input type="search" id="adminUsersSearch" placeholder="<?= htmlspecialchars($t('admin_search_placeholder')) ?>" autocomplete="off">
            </div>
            <div id="adminUsersList" class="admin-table-wrap"></div>
        </div>
        <div class="admin-card hidden" id="adminUserEditCard">
            <h2><span class="material-icons">edit</span><?= htmlspecialchars($t('admin_edit_user')) ?></h2>
            <form id="adminUserEditForm">
                <input type="hidden" name="id" id="adminEditUserId">
                <label><?= htmlspecialchars($t('name')) ?></label>
                <input type="text" name="name" id="adminEditUserName" required>
                <label><?= htmlspecialchars($t('email')) ?></label>
                <input type="email" name="email" id="adminEditUserEmail" required>
                <label><?= htmlspecialchars($t('admin_user_role')) ?></label>
                <select name="role" id="adminEditUserRole">
                    <option value="user"><?= htmlspecialchars($t('role_user')) ?></option>
                    <option value="business"><?= htmlspecialchars($t('role_business')) ?></option>
                    <option value="volunteer"><?= htmlspecialchars($t('role_volunteer')) ?></option>
                    <option value="ngo"><?= htmlspecialchars($t('role_ngo')) ?></option>
                    <option value="admin"><?= htmlspecialchars($t('role_admin')) ?></option>
                </select>
                <label class="switch-row switch-row-block">
                    <span class="switch-row-label">
                        <span class="material-icons">verified</span>
                        <span><?= htmlspecialchars($t('admin_user_verified')) ?></span>
                    </span>
                    <span class="switch">
                        <input type="checkbox" name="verified" id="adminEditUserVerified" value="1">
                        <span class="switch-slider"></span>
                    </span>
                </label>
                <label class="switch-row switch-row-block">
                    <span class="switch-row-label">
                        <span class="material-icons">block</span>
                        <span><?= htmlspecialchars($t('admin_user_blocked')) ?></span>
                    </span>
                    <span class="switch">
                        <input type="checkbox" name="blocked" id="adminEditUserBlocked" value="1">
                        <span class="switch-slider"></span>
                    </span>
                </label>
                <p class="hint" id="adminEditUserMeta"></p>
                <div class="admin-actions">
                    <button type="button" class="btn btn-outline" id="adminCancelUserEdit"><?= htmlspecialchars($t('cancel')) ?></button>
                    <button type="submit" class="btn"><span class="material-icons">save</span><?= htmlspecialchars($t('admin_save')) ?></button>
                </div>
            </form>
        </div>
    </section>
</div>

<script>
window.DogeSeedsAdmin = {
    apiBase: <?= json_encode(webPath('api/'), JSON_UNESCAPED_UNICODE) ?>,
    adminId: <?= (int) $user['id'] ?>,
    mapDefaults: { lat: <?= (float) $mapLat ?>, lng: <?= (float) $mapLng ?>, zoom: <?= $mapZoom ?> },
    strings: <?= json_encode([
        'saved' => $t('admin_saved'),
        'test_sent' => $t('admin_test_sent'),
        'error' => $t('admin_error'),
        'save' => $t('admin_save'),
        'search_placeholder' => $t('admin_search_placeholder'),
        'listings_empty' => $t('admin_listings_empty'),
        'users_empty' => $t('admin_users_empty'),
        'listing_live' => $t('my_listing_live'),
        'listing_hidden' => $t('my_listing_hidden'),
        'edit' => $t('my_edit'),
        'hide' => $t('admin_hide_listing'),
        'show' => $t('admin_show_listing'),
        'remove' => $t('admin_remove_listing'),
        'remove_confirm' => $t('admin_remove_listing_confirm'),
        'hide_confirm' => $t('admin_hide_listing_confirm'),
        'delete_user' => $t('admin_delete_user'),
        'delete_user_confirm' => $t('admin_delete_user_confirm'),
        'block' => $t('admin_user_blocked'),
        'unblock' => $t('admin_user_unblock'),
        'listings_count' => $t('admin_user_listings_count'),
        'registered' => $t('admin_user_registered'),
        'owner' => $t('admin_listing_owner'),
        'cancel' => $t('cancel'),
        'photo_choose' => $t('location_photo_choose'),
        'photo_replace' => $t('location_photo_replace'),
        'photo_remove' => $t('location_photo_remove'),
        'photo_too_large' => $t('location_photo_too_large'),
        'search_address' => $t('search_address'),
    ], JSON_UNESCAPED_UNICODE) ?>,
};

(function () {
    const tabsNav = document.querySelector('.admin-tabs');
    if (!tabsNav) return;

    function switchAdminTab(tab) {
        tabsNav.querySelectorAll('.admin-tab').forEach((btn) => {
            btn.classList.toggle('active', btn.getAttribute('data-admin-tab') === tab);
        });
        document.querySelectorAll('[data-admin-section]').forEach((section) => {
            section.classList.toggle('hidden', section.getAttribute('data-admin-section') !== tab);
        });
        document.dispatchEvent(new CustomEvent('dogeseeds-admin-tab', { detail: { tab } }));
    }

    tabsNav.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-admin-tab]');
        if (!btn || !tabsNav.contains(btn)) return;
        e.preventDefault();
        switchAdminTab(btn.getAttribute('data-admin-tab'));
    });

    window.switchAdminTab = switchAdminTab;
})();
</script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
<?php $adminJs = dirname(__DIR__) . '/assets/js/admin.js'; ?>
<script src="<?= htmlspecialchars(webPath('assets/js/admin.js')) ?>?v=<?= is_file($adminJs) ? (int) filemtime($adminJs) : 1 ?>"></script>
</body>
</html>
