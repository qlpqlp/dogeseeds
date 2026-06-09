<?php

declare(strict_types=1);

if (isset($_GET['lang'])) {
    I18n::setLang($_GET['lang']);
}

jsonResponse([
    'lang'     => I18n::getLang(),
    'strings'  => I18n::all(),
]);
