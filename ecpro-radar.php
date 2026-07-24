<?php
/**
 * Plugin Name: ECPRO 電商雷達
 * Description: 在你的 WordPress／WooCommerce 顯示「電商力徽章」與網站技術偵測，資料來自 ECPRO 電商博士（ecpro.tw）。提供短代碼與後台儀表板小工具。
 * Version: 1.0.0
 * Author: ECPRO 電商博士
 * Author URI: https://ecpro.tw
 * License: GPLv2 or later
 */

if (!defined('ABSPATH')) exit;

define('ECPRO_API', 'https://ecpro.pages.dev/api/v1');
define('ECPRO_BADGE', 'https://ecpro.pages.dev/api/badge');

function ecpro_host($domain = '') {
  if ($domain) return preg_replace('#^https?://(www\.)?#', '', rtrim($domain, '/'));
  return preg_replace('#^www\.#', '', parse_url(home_url(), PHP_URL_HOST));
}

function ecpro_get($path) {
  $res = wp_remote_get(ECPRO_API . $path, array('timeout' => 15, 'headers' => array('accept' => 'application/json')));
  if (is_wp_error($res)) return null;
  return json_decode(wp_remote_retrieve_body($res), true);
}

/** [ecpro_badge domain="example.com.tw"] → 電商力徽章（連回 ECPRO） */
function ecpro_badge_shortcode($atts) {
  $a = shortcode_atts(array('domain' => ''), $atts);
  $host = ecpro_host($a['domain']);
  $badge = ECPRO_BADGE . '?host=' . urlencode($host);
  $link = 'https://ecpro.pages.dev/competitor?host=' . urlencode($host);
  return '<a href="' . esc_url($link) . '" target="_blank" rel="noopener"><img src="' . esc_url($badge) . '" alt="ECPRO 電商力" height="28" loading="lazy"></a>';
}
add_shortcode('ecpro_badge', 'ecpro_badge_shortcode');

/** [ecpro_detect domain="example.com.tw"] → 技術偵測卡 */
function ecpro_detect_shortcode($atts) {
  $a = shortcode_atts(array('domain' => ''), $atts);
  $host = ecpro_host($a['domain']);
  $d = ecpro_get('/detect?url=' . urlencode($host));
  if (!$d || empty($d['ok'])) return '<div class="ecpro-card">無法偵測 ' . esc_html($host) . '</div>';
  $ads = !empty($d['ads']) ? implode('、', array_map('esc_html', $d['ads'])) : '未偵測到';
  $out  = '<div class="ecpro-card" style="border:1px solid #e9e9ee;border-radius:12px;padding:16px;max-width:420px;font-family:-apple-system,sans-serif">';
  $out .= '<div style="font-weight:800;font-size:15px">' . esc_html($d['host']) . '</div>';
  $out .= '<div style="font-size:13px;color:#666;margin:6px 0"><b>平台：</b>' . esc_html($d['platform']) . '</div>';
  $out .= '<div style="font-size:13px;color:#666;margin:6px 0"><b>投放平台：</b>' . $ads . '</div>';
  $out .= '<div style="font-size:13px;color:#666"><b>偵測技術：</b>' . intval($d['techCount']) . ' 項</div>';
  $out .= '<a href="https://ecpro.pages.dev/competitor?host=' . urlencode($d['host']) . '" target="_blank" rel="noopener" style="font-size:12px;color:#0066ff">完整解析 →</a></div>';
  return $out;
}
add_shortcode('ecpro_detect', 'ecpro_detect_shortcode');

/** 後台儀表板小工具：顯示本站偵測 */
function ecpro_dashboard_widget() {
  $host = ecpro_host();
  $d = ecpro_get('/detect?url=' . urlencode($host));
  if (!$d || empty($d['ok'])) { echo '<p>目前無法偵測本站（' . esc_html($host) . '）。</p>'; return; }
  echo '<p><b>本站：</b>' . esc_html($d['host']) . '</p>';
  echo '<p><b>偵測平台：</b>' . esc_html($d['platform']) . '</p>';
  echo '<p><b>投放平台：</b>' . (!empty($d['ads']) ? esc_html(implode('、', $d['ads'])) : '未偵測到') . '</p>';
  echo '<p><b>技術數：</b>' . intval($d['techCount']) . ' 項</p>';
  echo '<p><a href="https://ecpro.pages.dev/diagnose?host=' . urlencode($d['host']) . '" target="_blank" rel="noopener">用電商博士健診本站 →</a></p>';
  echo '<p style="color:#888;font-size:12px">資料來源：ECPRO 電商博士 ecpro.tw</p>';
}
add_action('wp_dashboard_setup', function () {
  wp_add_dashboard_widget('ecpro_radar_widget', 'ECPRO 電商雷達 — 本站偵測', 'ecpro_dashboard_widget');
});
