<?php

// CSS
if (preg_match("/mailbox/i", $_SERVER['REQUEST_URI'])) {
  $css_minifier->add('/web/css/site/mailbox.css');
}
if (preg_match("/admin/i", $_SERVER['REQUEST_URI'])) {
  $css_minifier->add('/web/css/site/admin.css');
}
if (preg_match("/user/i", $_SERVER['REQUEST_URI'])) {
  $css_minifier->add('/web/css/site/user.css');
}
if (preg_match("/edit/i", $_SERVER['REQUEST_URI'])) {
  $css_minifier->add('/web/css/site/edit.css');
}
if (preg_match("/(quarantine|qhandler)/i", $_SERVER['REQUEST_URI'])) {
  $css_minifier->add('/web/css/site/quarantine.css');
}
if (preg_match("/debug/i", $_SERVER['REQUEST_URI'])) {
  $css_minifier->add('/web/css/site/debug.css');
}
if ($_SERVER['REQUEST_URI'] == '/') {
  $css_minifier->add('/web/css/site/index.css');
}

$hash = $css_minifier->getDataHash();
$CSSPath = '/tmp/' . $hash . '.css';
if(!file_exists($CSSPath)) {
  $css_minifier->minify($CSSPath);
  cleanupCSS($hash);
}

$mailcow_apps_processed = $MAILCOW_APPS;
$app_links = customize('get', 'app_links');
$app_links_processed = $app_links;
$hide_mailcow_apps = true;
for ($i = 0; $i < count($mailcow_apps_processed); $i++) {
  if ($hide_mailcow_apps && !$mailcow_apps_processed[$i]['hide']){
    $hide_mailcow_apps = false;
  }
  if (!empty($_SESSION['mailcow_cc_username'])){
    if ($app_links_processed[$i]['user_link']) {
      $mailcow_apps_processed[$i]['user_link'] = str_replace('%u', $_SESSION['mailcow_cc_username'], $mailcow_apps_processed[$i]['user_link']);
    } else {
      $mailcow_apps_processed[$i]['user_link'] = $mailcow_apps_processed[$i]['link'];
    }
  }
}
if ($app_links_processed){
  for ($i = 0; $i < count($app_links_processed); $i++) {
    $key = array_key_first($app_links_processed[$i]);
    if ($hide_mailcow_apps && !$app_links_processed[$i][$key]['hide']){
      $hide_mailcow_apps = false;
    }
    if (!empty($_SESSION['mailcow_cc_username'])){
      if ($app_links_processed[$i][$key]['user_link']) {
        $app_links_processed[$i][$key]['user_link'] = str_replace('%u', $_SESSION['mailcow_cc_username'], $app_links_processed[$i][$key]['user_link']);
      } else {
        $app_links_processed[$i][$key]['user_link'] = $app_links_processed[$i][$key]['link'];
      }
    }
  }
}

// Workaround to get text with <br> straight to twig.
// Using "nl2br" doesn't work with Twig as it would escape everything by default.
if (isset($UI_TEXTS["ui_footer"])) {
  $UI_TEXTS["ui_footer"] = nl2br($UI_TEXTS["ui_footer"]);
}

// === Per-domain branding logo (DRK custom) ==========================
// Serve a domain-specific logo on the login/UI depending on the vhost the
// user connects to (e.g. mail.drk-rmt.org). Falls back to the regular
// customize() logo (Redis, admin UI) for every other host.
// Add a domain by dropping SVG/PNG files into data/web/img/branding/ and
// adding one line to $per_domain_logos below.
$per_domain_logos = [
  'drk-rmt.org' => [
    'light' => 'img/branding/logo-drk-rmt.svg',
    'dark'  => 'img/branding/logo-drk-rmt-dark.svg',
    'texts' => [
      'title_name' => 'RMT Mail UI',
      'main_name'  => 'RMT Mail UI',
      'apps_name'  => 'RMT Mail Apps',
    ],
  ],
];
$branding_logo = null;
$branding_logo_dark = null;
if (!empty($_SERVER['HTTP_HOST'])) {
  $brand_host = strtolower(explode(':', $_SERVER['HTTP_HOST'])[0]);
  $brand_match = null;
  if (isset($per_domain_logos[$brand_host])) {
    $brand_match = $per_domain_logos[$brand_host];
  } else {
    $bp = explode('.', $brand_host);
    while (count($bp) > 1) {
      array_shift($bp);
      $cand = implode('.', $bp);
      if (isset($per_domain_logos[$cand])) { $brand_match = $per_domain_logos[$cand]; break; }
    }
  }
  if ($brand_match !== null) {
    $doc = $_SERVER['DOCUMENT_ROOT'];
    $mime_for = function($f) {
      $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
      return $ext === 'svg' ? 'image/svg+xml' : ($ext === 'png' ? 'image/png' : ($ext === 'jpg' || $ext === 'jpeg' ? 'image/jpeg' : 'application/octet-stream'));
    };
    if (!empty($brand_match['light']) && is_file($doc.'/'.$brand_match['light'])) {
      $branding_logo = 'data:'.$mime_for($brand_match['light']).';base64,'.base64_encode(file_get_contents($doc.'/'.$brand_match['light']));
    }
    if (!empty($brand_match['dark']) && is_file($doc.'/'.$brand_match['dark'])) {
      $branding_logo_dark = 'data:'.$mime_for($brand_match['dark']).';base64,'.base64_encode(file_get_contents($doc.'/'.$brand_match['dark']));
    }
    // Override UI texts (login heading, apps section, browser title) per domain
    if (!empty($brand_match['texts']) && is_array($UI_TEXTS)) {
      foreach ($brand_match['texts'] as $tk => $tv) {
        $UI_TEXTS[$tk] = $tv;
      }
    }
  }
}

$globalVariables = [
  'mailcow_hostname' => getenv('MAILCOW_HOSTNAME'),
  'mailcow_locale' => @$_SESSION['mailcow_locale'],
  'mailcow_cc_role' => @$_SESSION['mailcow_cc_role'],
  'mailcow_cc_username' => @$_SESSION['mailcow_cc_username'],
  'is_master' => preg_match('/y|yes/i', getenv('MASTER')),
  'dual_login' => @$_SESSION['dual-login'],
  'ui_texts' => $UI_TEXTS,
  'css_path' => '/cache/'.basename($CSSPath),
  'logo' => $branding_logo ?: customize('get', 'main_logo'),
  'logo_dark' => $branding_logo_dark ?: customize('get', 'main_logo_dark'),
  'available_languages' => $AVAILABLE_LANGUAGES,
  'lang' => $lang,
  'skip_sogo' => (getenv('SKIP_SOGO') == 'y'),
  'allow_admin_email_login' => (getenv('ALLOW_ADMIN_EMAIL_LOGIN') == 'n'),
  'hide_mailcow_apps' => $hide_mailcow_apps,
  'mailcow_apps' => $MAILCOW_APPS,
  'mailcow_apps_processed' => $mailcow_apps_processed,
  'app_links' => $app_links,
  'app_links_processed' => $app_links_processed,
  'is_root_uri' => (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) == '/'),
  'uri' => parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/',
];

foreach ($globalVariables as $globalVariableName => $globalVariableValue) {
  $twig->addGlobal($globalVariableName, $globalVariableValue);
}
