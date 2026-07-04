<?php
session_start();

// ---------- Helpers ----------
function e($str) { return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8'); }

function clampPercent($v) {
    $n = intval($v);
    return max(0, min(100, $n));
}

function getInitials($name) {
    $parts = array_values(array_filter(preg_split('/\s+/', trim($name))));
    if (count($parts) === 0) return '?';
    if (count($parts) === 1) return strtoupper(substr($parts[0], 0, 2));
    return strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts) - 1], 0, 1));
}

// Generates a simple initials avatar as inline SVG (no external image service, no JS)
function generateAvatarDataUri($initials, $bg, $fg) {
    $svg = "<svg xmlns='http://www.w3.org/2000/svg' width='220' height='220'>" .
           "<rect width='220' height='220' fill='{$bg}'/>" .
           "<text x='50%' y='52%' font-family='Poppins, Arial, sans-serif' font-size='84' " .
           "font-weight='600' fill='{$fg}' text-anchor='middle' dominant-baseline='middle'>{$initials}</text>" .
           "</svg>";
    return 'data:image/svg+xml;utf8,' . rawurlencode($svg);
}

function tagsFromCsv($csv) {
    return array_values(array_filter(array_map('trim', explode(',', $csv))));
}

// ---------- Defaults ----------
$defaults = [
    'theme' => 'letterpress', 'pattern' => 'none', 'font' => 'classic',
    'name' => 'Avery Davis', 'job_title' => 'Digital Marketing Specialist', 'summary' => '',
    'edu1_year' => '2012 - 2015', 'edu1_uni' => 'Fauget University', 'edu1_deg' => 'Bachelor Degree of Marketing',
    'edu2_year' => '2015 - 2018', 'edu2_uni' => 'Borcelle University', 'edu2_deg' => 'Master Degree of Marketing',
    'exp1_year' => '2018 - 2019', 'exp1_comp' => 'Larana, Inc.', 'exp1_role' => 'Social Media Manager',
    'exp1_desc' => 'Handling social media campaigns...',
    'skill1_name' => 'SEO', 'skill1_per' => 90, 'skill2_name' => 'Content Marketing', 'skill2_per' => 80,
    'phone' => '+123-456-7890', 'email' => 'hello@site.com', 'website' => 'www.site.com', 'address' => '123 Street, City',
    'exp2_year' => '2019 - 2022', 'exp2_comp' => 'Studio Nine', 'exp2_role' => 'Marketing Lead',
    'exp2_desc' => 'Led a team of 5 across paid, organic, and lifecycle channels, growing qualified leads by 60%.',
    'proj1_name' => 'Borcelle Rebrand Campaign',
    'proj1_desc' => 'Directed a full brand refresh across web, social, and print, reaching 2M impressions in the first month.',
    'certs' => 'Google Analytics 4, HubSpot Inbound, Meta Ads Blueprint',
    'lang1_name' => 'English', 'lang1_per' => 100, 'lang2_name' => 'Spanish', 'lang2_per' => 65,
    'photo' => null,
    'page' => '1',
];

$percentFields = ['skill1_per', 'skill2_per', 'lang1_per', 'lang2_per'];
$validThemes   = ['letterpress','modern','bold','sunset','ocean','berry','forest','slate','rosegold','autumn'];
$validPatterns = ['none','dots','grid','diagonal','cross','waves'];
$validFonts    = ['classic','modern-sans','elegant-serif','bold-display','minimal-mono'];

if (!isset($_SESSION['resume']) || !is_array($_SESSION['resume'])) {
    $_SESSION['resume'] = $defaults;
}

$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0755, true); }

// ---------- Handle form submission (server-side, no JS) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    foreach ($defaults as $key => $val) {
        if ($key === 'photo' || $key === 'page') continue;
        if ($key === 'theme') {
            $_SESSION['resume']['theme'] = in_array($_POST['theme'] ?? '', $validThemes) ? $_POST['theme'] : $defaults['theme'];
        } elseif ($key === 'pattern') {
            $_SESSION['resume']['pattern'] = in_array($_POST['pattern'] ?? '', $validPatterns) ? $_POST['pattern'] : $defaults['pattern'];
        } elseif ($key === 'font') {
            $_SESSION['resume']['font'] = in_array($_POST['font'] ?? '', $validFonts) ? $_POST['font'] : $defaults['font'];
        } elseif (in_array($key, $percentFields)) {
            $_SESSION['resume'][$key] = isset($_POST[$key]) ? clampPercent($_POST[$key]) : $val;
        } else {
            $_SESSION['resume'][$key] = isset($_POST[$key]) ? trim($_POST[$key]) : '';
        }
    }

    // Photo upload
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK && $_FILES['photo']['size'] > 0) {
        $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
        $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($ext, $allowedExt)) {
            $newName = 'photo_' . uniqid() . '.' . $ext;
            if (move_uploaded_file($_FILES['photo']['tmp_name'], $uploadDir . $newName)) {
                $_SESSION['resume']['photo'] = 'uploads/' . $newName;
            }
        }
    }
    if (isset($_POST['remove_photo'])) {
        $_SESSION['resume']['photo'] = null;
    }
    if (isset($_POST['page']) && in_array($_POST['page'], ['1', '2'])) {
        $_SESSION['resume']['page'] = $_POST['page'];
    }
}

// Page switch via its own tiny GET links (no JS needed)
if (isset($_GET['page']) && in_array($_GET['page'], ['1', '2'])) {
    $_SESSION['resume']['page'] = $_GET['page'];
}

$r = $_SESSION['resume'];
$activePage = $r['page'] ?? '1';

$themeColors = [
    'letterpress' => ['gold' => '#9C7A3C', 'paper' => '#F6F1E6'],
    'modern'      => ['gold' => '#3B6FD6', 'paper' => '#FFFFFF'],
    'bold'        => ['gold' => '#FF7A3C', 'paper' => '#12213A'],
    'sunset'      => ['gold' => '#E85D3C', 'paper' => '#FFF6F2'],
    'ocean'       => ['gold' => '#0FA3A3', 'paper' => '#F0FAFA'],
    'berry'       => ['gold' => '#B8388B', 'paper' => '#FBF3FB'],
    'forest'      => ['gold' => '#7A8F3C', 'paper' => '#F3F2E8'],
    'slate'       => ['gold' => '#565B66', 'paper' => '#F5F5F6'],
    'rosegold'    => ['gold' => '#C98A73', 'paper' => '#FBF1EF'],
    'autumn'      => ['gold' => '#C87F2A', 'paper' => '#FBF3E4'],
];

$themeLabels = [
    'letterpress' => 'Letterpress', 'modern' => 'Modern', 'bold' => 'Bold',
    'sunset' => 'Sunset', 'ocean' => 'Ocean', 'berry' => 'Berry',
    'forest' => 'Forest', 'slate' => 'Slate', 'rosegold' => 'Rose Gold', 'autumn' => 'Autumn',
];
$patternLabels = [
    'none' => 'None', 'dots' => 'Dots', 'grid' => 'Grid',
    'diagonal' => 'Diagonal Lines', 'cross' => 'Cross-Hatch', 'waves' => 'Waves',
];
$fontLabels = [
    'classic' => 'Classic Serif', 'modern-sans' => 'Modern Sans',
    'elegant-serif' => 'Elegant Serif', 'bold-display' => 'Bold Display', 'minimal-mono' => 'Minimal Mono',
];

// Resolve photo source: uploaded file takes priority, else generated avatar
$photoSrc = '';
if (!empty($r['photo']) && file_exists(__DIR__ . '/' . $r['photo'])) {
    $photoSrc = e($r['photo']);
} else {
    $colors = $themeColors[$r['theme']] ?? $themeColors['letterpress'];
    $photoSrc = generateAvatarDataUri(getInitials($r['name']), $colors['gold'], $colors['paper']);
}

$skill1 = clampPercent($r['skill1_per']);
$skill2 = clampPercent($r['skill2_per']);
$lang1  = clampPercent($r['lang1_per']);
$lang2  = clampPercent($r['lang2_per']);
$certTags = tagsFromCsv($r['certs']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Resume Builder — PHP (No JavaScript)</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&family=Cormorant:ital@1&family=EB+Garamond:ital@0;1&family=Poppins:wght@400;500;600;700&family=Playfair+Display:wght@600;700&family=Lora:ital,wght@0,400;1,400&family=Montserrat:wght@700;900&family=Inter:wght@400;500;600;700&family=Space+Mono:wght@400;700&display=swap');

  :root{
    --paper:#F6F1E6;
    --ink:#1F3D2B;
    --ink-soft:#4C6656;
    --gold:#9C7A3C;
    --rule:#C9BB9A;
    --accent2:#9C7A3C;
    --header-bg:transparent;
  }
  *{box-sizing:border-box;}
  body{
    margin:0;
    background:var(--paper);
    background-image: radial-gradient(rgba(31,61,43,0.03) 1px, transparent 1px);
    background-size: 3px 3px;
    color:var(--ink);
    font-family:'EB Garamond', serif;
    padding:40px 20px;
  }

  .workspace {
    display: flex;
    max-width: 1400px;
    margin: 0 auto;
    gap: 40px;
    align-items: flex-start;
  }

  .panel {
    flex: 1;
    background:var(--paper);
    border:1px solid var(--rule);
    position:relative;
    min-height: 800px;
  }
  .panel::before{
    content:"";
    position:absolute;
    inset:14px;
    border:1px solid var(--rule);
    pointer-events:none;
  }

  .crest{
    text-align:center;
    padding:38px 20px 10px;
    background:var(--header-bg);
  }

  .crest .photo-frame {
    width: 110px;
    height: 110px;
    margin: 0 auto 14px;
    border: 1.5px solid var(--gold);
    border-radius: 50%;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--paper);
  }
  .crest .photo-frame img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  h1{
    font-family:'Cormorant Garamond', serif;
    font-weight:600;
    font-size:32px;
    text-align:center;
    letter-spacing:2px;
    text-transform:uppercase;
    margin:0 0 6px;
  }
  .subtitle{
    text-align:center;
    font-style:italic;
    color:var(--ink-soft);
    font-size:14px;
    letter-spacing:1px;
    margin-bottom:18px;
  }
  .divider{
    display:flex; align-items:center; gap:10px;
    padding:0 60px 26px;
  }
  .divider .line{ flex:1; height:1px; background:var(--rule); }
  .divider .diamond{ width:6px; height:6px; background:var(--gold); transform:rotate(45deg); }

  form { padding:0 46px 44px; }

  h3, .section-title {
    font-family:'Cormorant Garamond', serif;
    font-weight:600;
    font-size:15px;
    letter-spacing:2.5px;
    text-transform:uppercase;
    color:var(--gold);
    margin:30px 0 16px;
    text-align:center;
  }
  h3::before, h3::after, .section-title::before, .section-title::after { content:"— "; }
  h3::after, .section-title::after { content: " —"; }

  .form-group{ margin-bottom:16px; }
  .row{ display:flex; gap:18px; }
  .row .form-group{ flex:1; }

  label{
    display:block;
    font-family:'Cormorant', serif;
    font-style:italic;
    font-size:14px;
    color:var(--ink-soft);
    margin-bottom:4px;
  }

  input[type="text"], input[type="number"], input[type="file"], textarea, select{
    width:100%;
    padding:8px 4px;
    border:none;
    border-bottom:1px solid var(--rule);
    background:transparent;
    font-family:'EB Garamond', serif;
    font-size:16px;
    color:var(--ink);
  }
  select{ cursor:pointer; }
  input:focus, textarea:focus, select:focus{
    outline:none;
    border-bottom:1px solid var(--gold);
  }
  textarea{ height:70px; resize:vertical; }
  ::placeholder{ color:#A79B82; font-style:italic; }

  .style-picker{
    display:flex;
    flex-wrap:wrap;
    gap:8px;
    margin-bottom:6px;
  }
  .style-swatch{
    flex:1 1 30%;
    padding:10px 6px;
    text-align:center;
    border:1.5px solid var(--rule);
    font-family:'Cormorant Garamond', serif;
    font-size:12.5px;
    letter-spacing:1px;
    text-transform:uppercase;
    cursor:pointer;
    color:var(--ink-soft);
    background:transparent;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:6px;
  }
  .style-swatch .dot{
    width:10px; height:10px; border-radius:50%; flex-shrink:0;
  }
  .style-swatch.active{
    border-color:var(--ink);
    color:var(--ink);
    font-weight:600;
    background:rgba(156,122,60,0.1);
  }

  .font-swatch{
    flex:1 1 15%;
    padding:10px 4px 8px;
    text-align:center;
    border:1.5px solid var(--rule);
    cursor:pointer;
    color:var(--ink);
    background:transparent;
    font-size:20px;
    line-height:1.1;
  }
  .font-swatch small{
    display:block;
    font-family:'Cormorant', serif !important;
    font-style:italic;
    font-size:11px;
    letter-spacing:0.5px;
    text-transform:uppercase;
    color:var(--ink-soft);
    margin-top:3px;
  }
  .font-swatch.active{
    border-color:var(--gold);
    background:rgba(156,122,60,0.1);
  }

  .pattern-swatch{
    flex:1 1 28%;
    padding:14px 6px;
    text-align:center;
    border:1.5px solid var(--rule);
    cursor:pointer;
    color:var(--ink-soft);
    background-color:transparent;
    font-family:'Cormorant', serif;
    font-style:italic;
    font-size:12px;
    letter-spacing:0.5px;
    text-transform:uppercase;
    opacity:0.85;
  }
  .pattern-swatch.active{
    border-color:var(--gold);
    color:var(--ink);
    font-weight:600;
    opacity:1;
  }

  .submit-btn, .print-btn{
    display:block;
    width:100%;
    margin-top:32px;
    padding:14px;
    background:transparent;
    border:1.5px solid var(--ink);
    color:var(--ink);
    font-family:'Cormorant Garamond', serif;
    font-weight:600;
    font-size:15px;
    letter-spacing:3px;
    text-transform:uppercase;
    cursor:pointer;
  }
  .submit-btn:hover, .print-btn:hover{ background:var(--ink); color:var(--paper); }

  .output-content { padding: 10px 46px 44px; }
  .output-summary { text-align: center; font-style: italic; line-height: 1.6; margin-bottom: 25px; color: var(--ink-soft); }
  .item-block { margin-bottom: 20px; }
  .item-header { display: flex; justify-content: space-between; font-family: 'Cormorant Garamond', serif; font-weight: 600; font-size: 17px; }
  .item-sub { font-style: italic; color: var(--ink-soft); margin-top: 2px; }
  .item-desc { margin-top: 6px; line-height: 1.5; font-size: 15px; text-align: justify; }

  .skill-row { margin-bottom: 12px; }
  .skill-info { display: flex; justify-content: space-between; font-style: italic; margin-bottom: 4px; }
  .progress-bg { background: rgba(201,187,154,0.3); height: 4px; width: 100%; position: relative; }
  .progress-fill { background: var(--gold); height: 100%; transition: width .25s ease; }

  .contact-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; font-size: 15px; margin-top: 10px; }
  .contact-item strong { font-family: 'Cormorant', serif; font-style: italic; color: var(--gold); font-size: 16px; }

  .tag-list{ display:flex; flex-wrap:wrap; gap:8px; }
  .tag{
    padding:5px 12px;
    border:1px solid var(--gold);
    color:var(--ink);
    font-size:13px;
    border-radius:20px;
    font-family:'Cormorant', serif;
    font-style:italic;
  }

  /* ============ PAGE NAV ============ */
  .page-nav{
    display:flex;
    justify-content:center;
    gap:8px;
    padding:14px 0 0;
  }
  .page-dot{
    width:32px; height:32px;
    border:1.5px solid var(--rule);
    color:var(--ink-soft);
    background:transparent;
    font-family:'Cormorant Garamond', serif;
    font-weight:600;
    cursor:pointer;
    display:flex; align-items:center; justify-content:center;
    border-radius:50%;
  }
  .page-dot.active{
    border-color:var(--gold);
    color:var(--ink);
    background:rgba(156,122,60,0.12);
  }
  .page{ display:none; }
  .page.active{ display:block; }

  /* ============ THEME: MODERN MINIMAL (blue) ============ */
  .output-panel[data-theme="modern"]{
    --paper:#FFFFFF; --ink:#20232B; --ink-soft:#666B77;
    --gold:#3B6FD6; --rule:#E4E6EC; --header-bg:transparent;
    font-family:'Poppins', Arial, sans-serif;
  }
  .output-panel[data-theme="modern"] .crest{ text-align:left; padding:38px 46px 10px; }
  .output-panel[data-theme="modern"] .photo-frame{ margin:0 0 14px; border-radius:10px; }
  .output-panel[data-theme="modern"] h1{ text-align:left; font-family:'Poppins', sans-serif; font-weight:700; letter-spacing:0; }
  .output-panel[data-theme="modern"] .subtitle{ text-align:left; font-style:normal; }
  .output-panel[data-theme="modern"] .divider{ display:none; }
  .output-panel[data-theme="modern"] .section-title{ text-align:left; padding:0 0 8px; border-bottom:2px solid var(--gold); }
  .output-panel[data-theme="modern"] .section-title::before,
  .output-panel[data-theme="modern"] .section-title::after{ content:""; }
  .output-panel[data-theme="modern"] .progress-bg, .output-panel[data-theme="modern"] .progress-fill{ border-radius:6px; }
  .output-panel[data-theme="modern"] .item-desc{ text-align:left; }
  .output-panel[data-theme="modern"] .tag{ border-radius:6px; font-style:normal; }

  /* ============ THEME: BOLD CORPORATE (navy/orange) ============ */
  .output-panel[data-theme="bold"]{
    --paper:#12213A; --ink:#F3F5F9; --ink-soft:#AEB8CC;
    --gold:#FF7A3C; --rule:#233355; --header-bg:#0C1830;
    font-family:'Poppins', Arial, sans-serif;
  }
  .output-panel[data-theme="bold"] .crest{ padding:40px 30px 24px; margin:-1px -1px 0; }
  .output-panel[data-theme="bold"] .photo-frame{ border-radius:10px; border-width:2px; }
  .output-panel[data-theme="bold"] h1{ font-family:'Poppins', sans-serif; font-weight:700; color:#fff; }
  .output-panel[data-theme="bold"] .divider{ display:none; }
  .output-panel[data-theme="bold"] .section-title{ text-align:left; border-bottom:none; border-top:1px solid var(--rule); padding-top:16px; letter-spacing:2px; }
  .output-panel[data-theme="bold"] .section-title::before,
  .output-panel[data-theme="bold"] .section-title::after{ content:""; }
  .output-panel[data-theme="bold"] .progress-bg, .output-panel[data-theme="bold"] .progress-fill{ border-radius:6px; }
  .output-panel[data-theme="bold"] .item-desc{ text-align:left; }
  .output-panel[data-theme="bold"] .contact-item strong{ color:var(--gold); }
  .output-panel[data-theme="bold"] .tag{ border-radius:6px; font-style:normal; border-color:var(--gold); }
  .output-panel[data-theme="bold"] .page-dot{ border-color:var(--rule); color:var(--ink-soft); }
  .output-panel[data-theme="bold"] .page-dot.active{ border-color:var(--gold); color:#fff; }

  /* ============ THEME: SUNSET (coral/pink gradient) ============ */
  .output-panel[data-theme="sunset"]{
    --paper:#FFF6F2; --ink:#5A2A1E; --ink-soft:#9C5A44;
    --gold:#E85D3C; --rule:#F4D2C2;
    --header-bg:linear-gradient(135deg,#FFB199 0%,#FF6F61 55%,#E8437B 100%);
    font-family:'Poppins', Arial, sans-serif;
  }
  .output-panel[data-theme="sunset"] .crest{ padding:40px 30px 26px; margin:-1px -1px 0; }
  .output-panel[data-theme="sunset"] h1{ color:#fff; font-family:'Poppins', sans-serif; font-weight:700; text-shadow:0 1px 2px rgba(0,0,0,.15); }
  .output-panel[data-theme="sunset"] .subtitle{ color:#FFEDE7; font-style:normal; }
  .output-panel[data-theme="sunset"] .photo-frame{ border-color:#fff; border-width:2.5px; }
  .output-panel[data-theme="sunset"] .divider{ display:none; }
  .output-panel[data-theme="sunset"] .section-title{ text-align:left; border-bottom:2px solid var(--gold); padding-bottom:8px; }
  .output-panel[data-theme="sunset"] .section-title::before,
  .output-panel[data-theme="sunset"] .section-title::after{ content:""; }
  .output-panel[data-theme="sunset"] .progress-bg, .output-panel[data-theme="sunset"] .progress-fill{ border-radius:6px; }
  .output-panel[data-theme="sunset"] .item-desc{ text-align:left; }
  .output-panel[data-theme="sunset"] .tag{ border-radius:20px; background:rgba(232,93,60,0.08); }

  /* ============ THEME: OCEAN (teal/blue gradient) ============ */
  .output-panel[data-theme="ocean"]{
    --paper:#F0FAFA; --ink:#0B3B4A; --ink-soft:#3E7C8C;
    --gold:#0FA3A3; --rule:#C9EDEE;
    --header-bg:linear-gradient(135deg,#0B3B4A 0%,#0FA3A3 60%,#5FE3C4 100%);
    font-family:'Poppins', Arial, sans-serif;
  }
  .output-panel[data-theme="ocean"] .crest{ padding:40px 30px 26px; margin:-1px -1px 0; }
  .output-panel[data-theme="ocean"] h1{ color:#fff; font-family:'Poppins', sans-serif; font-weight:700; }
  .output-panel[data-theme="ocean"] .subtitle{ color:#DFFFFB; font-style:normal; }
  .output-panel[data-theme="ocean"] .photo-frame{ border-color:#fff; border-width:2.5px; }
  .output-panel[data-theme="ocean"] .divider{ display:none; }
  .output-panel[data-theme="ocean"] .section-title{ text-align:left; border-bottom:2px solid var(--gold); padding-bottom:8px; }
  .output-panel[data-theme="ocean"] .section-title::before,
  .output-panel[data-theme="ocean"] .section-title::after{ content:""; }
  .output-panel[data-theme="ocean"] .progress-bg, .output-panel[data-theme="ocean"] .progress-fill{ border-radius:6px; }
  .output-panel[data-theme="ocean"] .item-desc{ text-align:left; }
  .output-panel[data-theme="ocean"] .tag{ border-radius:20px; background:rgba(15,163,163,0.08); }

  /* ============ THEME: BERRY (purple/magenta gradient) ============ */
  .output-panel[data-theme="berry"]{
    --paper:#FBF3FB; --ink:#3B0B45; --ink-soft:#7A4C85;
    --gold:#B8388B; --rule:#EBD1EA;
    --header-bg:linear-gradient(135deg,#5B2A86 0%,#B8388B 55%,#F0678C 100%);
    font-family:'Poppins', Arial, sans-serif;
  }
  .output-panel[data-theme="berry"] .crest{ padding:40px 30px 26px; margin:-1px -1px 0; }
  .output-panel[data-theme="berry"] h1{ color:#fff; font-family:'Poppins', sans-serif; font-weight:700; }
  .output-panel[data-theme="berry"] .subtitle{ color:#FBE6F5; font-style:normal; }
  .output-panel[data-theme="berry"] .photo-frame{ border-color:#fff; border-width:2.5px; }
  .output-panel[data-theme="berry"] .divider{ display:none; }
  .output-panel[data-theme="berry"] .section-title{ text-align:left; border-bottom:2px solid var(--gold); padding-bottom:8px; }
  .output-panel[data-theme="berry"] .section-title::before,
  .output-panel[data-theme="berry"] .section-title::after{ content:""; }
  .output-panel[data-theme="berry"] .progress-bg, .output-panel[data-theme="berry"] .progress-fill{ border-radius:6px; }
  .output-panel[data-theme="berry"] .item-desc{ text-align:left; }
  .output-panel[data-theme="berry"] .tag{ border-radius:20px; background:rgba(184,56,139,0.08); }

  /* ============ THEME: FOREST (deep green/olive) ============ */
  .output-panel[data-theme="forest"]{
    --paper:#F3F2E8; --ink:#1E2C1A; --ink-soft:#5B6E4E;
    --gold:#7A8F3C; --rule:#D6D9C2; --header-bg:transparent;
    font-family:'EB Garamond', serif;
  }
  .output-panel[data-theme="forest"] .crest{ padding:38px 20px 10px; }
  .output-panel[data-theme="forest"] h1{ color:var(--ink); }
  .output-panel[data-theme="forest"] .photo-frame{ border-color:var(--gold); }
  .output-panel[data-theme="forest"] .section-title{ border-top:1px solid var(--rule); padding-top:14px; }
  .output-panel[data-theme="forest"] .tag{ border-radius:20px; background:rgba(122,143,60,0.1); }

  /* ============ THEME: SLATE (monochrome charcoal) ============ */
  .output-panel[data-theme="slate"]{
    --paper:#F5F5F6; --ink:#22252B; --ink-soft:#63666E;
    --gold:#565B66; --rule:#DCDDE1; --header-bg:transparent;
    font-family:'Poppins', Arial, sans-serif;
  }
  .output-panel[data-theme="slate"] .crest{ text-align:left; padding:38px 46px 10px; }
  .output-panel[data-theme="slate"] h1{ text-align:left; font-weight:700; letter-spacing:0; }
  .output-panel[data-theme="slate"] .subtitle{ text-align:left; font-style:normal; }
  .output-panel[data-theme="slate"] .divider{ display:none; }
  .output-panel[data-theme="slate"] .photo-frame{ border-radius:8px; }
  .output-panel[data-theme="slate"] .section-title{ text-align:left; border-bottom:2px solid var(--gold); padding-bottom:8px; }
  .output-panel[data-theme="slate"] .section-title::before,
  .output-panel[data-theme="slate"] .section-title::after{ content:""; }
  .output-panel[data-theme="slate"] .item-desc{ text-align:left; }
  .output-panel[data-theme="slate"] .tag{ border-radius:6px; font-style:normal; }

  /* ============ THEME: ROSE GOLD (blush pink/rose gold) ============ */
  .output-panel[data-theme="rosegold"]{
    --paper:#FBF1EF; --ink:#5A2E2E; --ink-soft:#9C6E68;
    --gold:#C98A73; --rule:#EAD3CC; --header-bg:transparent;
    font-family:'EB Garamond', serif;
  }
  .output-panel[data-theme="rosegold"] h1{ color:var(--ink); letter-spacing:3px; }
  .output-panel[data-theme="rosegold"] .photo-frame{ border-color:var(--gold); border-width:2px; }
  .output-panel[data-theme="rosegold"] .section-title{ border-top:1px solid var(--rule); padding-top:14px; }
  .output-panel[data-theme="rosegold"] .tag{ border-radius:20px; background:rgba(201,138,115,0.1); }

  /* ============ THEME: AUTUMN (warm mustard/brown) ============ */
  .output-panel[data-theme="autumn"]{
    --paper:#FBF3E4; --ink:#4A2E14; --ink-soft:#8A5A2E;
    --gold:#C87F2A; --rule:#E9D3AC; --header-bg:transparent;
    font-family:'EB Garamond', serif;
  }
  .output-panel[data-theme="autumn"] h1{ color:var(--ink); }
  .output-panel[data-theme="autumn"] .photo-frame{ border-color:var(--gold); border-width:2px; }
  .output-panel[data-theme="autumn"] .section-title{ border-top:1px solid var(--rule); padding-top:14px; }
  .output-panel[data-theme="autumn"] .tag{ border-radius:20px; background:rgba(200,127,42,0.1); }

  /* ============ BACKGROUND PATTERNS (layer on top of any color theme) ============ */
  .output-panel[data-pattern="dots"]{
    background-image: radial-gradient(var(--rule) 1.2px, transparent 1.2px);
    background-size: 16px 16px;
  }
  .output-panel[data-pattern="grid"]{
    background-image:
      linear-gradient(var(--rule) 1px, transparent 1px),
      linear-gradient(90deg, var(--rule) 1px, transparent 1px);
    background-size: 22px 22px;
  }
  .output-panel[data-pattern="diagonal"]{
    background-image: repeating-linear-gradient(45deg, var(--rule) 0, var(--rule) 1px, transparent 1px, transparent 13px);
  }
  .output-panel[data-pattern="cross"]{
    background-image:
      linear-gradient(45deg, var(--rule) 1px, transparent 1px),
      linear-gradient(-45deg, var(--rule) 1px, transparent 1px);
    background-size: 20px 20px;
  }
  .output-panel[data-pattern="waves"]{
    background-image: radial-gradient(circle at 10px 10px, transparent 8px, var(--rule) 9px, transparent 10px);
    background-size: 22px 22px;
  }
  /* keep gradient-header themes' crest opaque so the pattern doesn't show through the banner */
  .output-panel[data-theme="sunset"] .crest,
  .output-panel[data-theme="ocean"] .crest,
  .output-panel[data-theme="berry"] .crest,
  .output-panel[data-theme="bold"] .crest{ background:var(--header-bg); }

  /* ============ FONT PACKS (independent of color theme) ============ */

  /* --- Classic Serif (default) — Cormorant Garamond / EB Garamond, unchanged --- */

  /* --- Modern Sans — Poppins throughout --- */
  .output-panel[data-font="modern-sans"]{ font-family:'Poppins', Arial, sans-serif; }
  .output-panel[data-font="modern-sans"] h1{ font-family:'Poppins', sans-serif; font-weight:700; letter-spacing:0.5px; }
  .output-panel[data-font="modern-sans"] .subtitle{ font-family:'Poppins', sans-serif; font-style:normal; }
  .output-panel[data-font="modern-sans"] .section-title{ font-family:'Poppins', sans-serif; font-weight:600; letter-spacing:1.5px; }
  .output-panel[data-font="modern-sans"] .section-title::before,
  .output-panel[data-font="modern-sans"] .section-title::after{ content:""; }
  .output-panel[data-font="modern-sans"] .item-header{ font-family:'Poppins', sans-serif; font-weight:600; }
  .output-panel[data-font="modern-sans"] .item-sub{ font-family:'Poppins', sans-serif; font-style:normal; }
  .output-panel[data-font="modern-sans"] .output-summary{ font-family:'Poppins', sans-serif; font-style:normal; }
  .output-panel[data-font="modern-sans"] .contact-item strong{ font-family:'Poppins', sans-serif; font-style:normal; }
  .output-panel[data-font="modern-sans"] .tag{ font-family:'Poppins', sans-serif; font-style:normal; }

  /* --- Elegant Serif — Playfair Display headings / Lora body --- */
  .output-panel[data-font="elegant-serif"]{ font-family:'Lora', serif; }
  .output-panel[data-font="elegant-serif"] h1{ font-family:'Playfair Display', serif; font-weight:700; letter-spacing:1px; }
  .output-panel[data-font="elegant-serif"] .subtitle{ font-family:'Lora', serif; font-style:italic; }
  .output-panel[data-font="elegant-serif"] .section-title{ font-family:'Playfair Display', serif; font-weight:600; }
  .output-panel[data-font="elegant-serif"] .item-header{ font-family:'Playfair Display', serif; font-weight:600; }
  .output-panel[data-font="elegant-serif"] .item-sub{ font-family:'Lora', serif; font-style:italic; }
  .output-panel[data-font="elegant-serif"] .item-desc{ font-family:'Lora', serif; }
  .output-panel[data-font="elegant-serif"] .output-summary{ font-family:'Lora', serif; font-style:italic; }
  .output-panel[data-font="elegant-serif"] .contact-item strong{ font-family:'Playfair Display', serif; font-style:normal; }
  .output-panel[data-font="elegant-serif"] .tag{ font-family:'Lora', serif; }

  /* --- Bold Display — Montserrat black headings / Inter body --- */
  .output-panel[data-font="bold-display"]{ font-family:'Inter', Arial, sans-serif; }
  .output-panel[data-font="bold-display"] h1{ font-family:'Montserrat', sans-serif; font-weight:900; letter-spacing:0.5px; }
  .output-panel[data-font="bold-display"] .subtitle{ font-family:'Inter', sans-serif; font-style:normal; font-weight:500; }
  .output-panel[data-font="bold-display"] .section-title{ font-family:'Montserrat', sans-serif; font-weight:900; letter-spacing:2px; }
  .output-panel[data-font="bold-display"] .section-title::before,
  .output-panel[data-font="bold-display"] .section-title::after{ content:""; }
  .output-panel[data-font="bold-display"] .item-header{ font-family:'Montserrat', sans-serif; font-weight:700; }
  .output-panel[data-font="bold-display"] .item-sub{ font-family:'Inter', sans-serif; font-style:normal; font-weight:500; }
  .output-panel[data-font="bold-display"] .output-summary{ font-family:'Inter', sans-serif; font-style:normal; }
  .output-panel[data-font="bold-display"] .contact-item strong{ font-family:'Montserrat', sans-serif; font-style:normal; font-weight:700; }
  .output-panel[data-font="bold-display"] .tag{ font-family:'Inter', sans-serif; font-weight:600; }

  /* --- Minimal Mono — Space Mono headings / Inter body, techy feel --- */
  .output-panel[data-font="minimal-mono"]{ font-family:'Inter', Arial, sans-serif; }
  .output-panel[data-font="minimal-mono"] h1{ font-family:'Space Mono', monospace; font-weight:700; letter-spacing:1px; text-transform:lowercase; }
  .output-panel[data-font="minimal-mono"] .subtitle{ font-family:'Space Mono', monospace; font-style:normal; text-transform:lowercase; }
  .output-panel[data-font="minimal-mono"] .section-title{ font-family:'Space Mono', monospace; font-weight:700; text-transform:lowercase; letter-spacing:1px; }
  .output-panel[data-font="minimal-mono"] .section-title::before,
  .output-panel[data-font="minimal-mono"] .section-title::after{ content:"// "; }
  .output-panel[data-font="minimal-mono"] .item-header{ font-family:'Space Mono', monospace; font-weight:700; }
  .output-panel[data-font="minimal-mono"] .item-sub{ font-family:'Inter', sans-serif; font-style:normal; }
  .output-panel[data-font="minimal-mono"] .output-summary{ font-family:'Inter', sans-serif; font-style:italic; }
  .output-panel[data-font="minimal-mono"] .contact-item strong{ font-family:'Space Mono', monospace; font-style:normal; }
  .output-panel[data-font="minimal-mono"] .tag{ font-family:'Space Mono', monospace; border-radius:3px; }

  @media print {
    .no-print { display: none !important; }
    body { padding: 0; background: #fff; }
    .workspace { display: block; }
    .output-panel { border: none !important; width: 100% !important; min-height: auto; }
    .output-panel::before { display: none; }
    .page{ display:block !important; }
    .page + .page{ page-break-before: always; }
  }

  @media (max-width: 900px) {
    .workspace { flex-direction: column; }
  }
</style>
</head>
<body>

<div class="workspace">

  <div class="panel no-print">
    <div class="crest">
      <div class="photo-frame">
         <img src="<?php echo $photoSrc; ?>" alt="Preview">
      </div>
      <h1>Resume Editor</h1>
      <div class="subtitle">Server-rendered — click "Update &amp; Generate" to refresh</div>
    </div>
    <div class="divider"><span class="line"></span><span class="diamond"></span><span class="line"></span></div>

    <form method="post" action="" enctype="multipart/form-data">
      <input type="hidden" name="page" value="<?php echo e($activePage); ?>">

      <h3>Resume Style</h3>
      <div class="form-group">
        <label>Color Theme</label>
        <select name="theme">
          <?php foreach ($validThemes as $t): ?>
            <option value="<?php echo e($t); ?>" <?php echo $r['theme'] === $t ? 'selected' : ''; ?>>
              <?php echo e($themeLabels[$t]); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label>Background Pattern</label>
        <select name="pattern">
          <?php foreach ($validPatterns as $p): ?>
            <option value="<?php echo e($p); ?>" <?php echo $r['pattern'] === $p ? 'selected' : ''; ?>>
              <?php echo e($patternLabels[$p]); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label>Font Style</label>
        <select name="font">
          <?php foreach ($validFonts as $f): ?>
            <option value="<?php echo e($f); ?>" <?php echo $r['font'] === $f ? 'selected' : ''; ?>>
              <?php echo e($fontLabels[$f]); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <h3>Personal Info</h3>
      <div class="form-group">
        <label>Profile Image (leave blank to keep auto avatar)</label>
        <input type="file" name="photo" accept="image/*">
      </div>
      <?php if (!empty($r['photo'])): ?>
      <div class="form-group">
        <label><input type="checkbox" name="remove_photo" value="1" style="width:auto; display:inline; margin-right:6px;"> Remove uploaded photo (revert to auto avatar)</label>
      </div>
      <?php endif; ?>
      <div class="form-group">
        <label>Full Name</label>
        <input type="text" name="name" value="<?php echo e($r['name']); ?>" required>
      </div>
      <div class="form-group">
        <label>Job Title</label>
        <input type="text" name="job_title" value="<?php echo e($r['job_title']); ?>" required>
      </div>
      <div class="form-group">
        <label>About / Summary</label>
        <textarea name="summary" placeholder="Write a short summary about yourself..."><?php echo e($r['summary']); ?></textarea>
      </div>

      <h3>Education 1</h3>
      <div class="row">
        <div class="form-group"><label>Year</label><input type="text" name="edu1_year" value="<?php echo e($r['edu1_year']); ?>"></div>
        <div class="form-group"><label>University / School</label><input type="text" name="edu1_uni" value="<?php echo e($r['edu1_uni']); ?>"></div>
      </div>
      <div class="form-group"><label>Degree Name</label><input type="text" name="edu1_deg" value="<?php echo e($r['edu1_deg']); ?>"></div>

      <h3>Education 2</h3>
      <div class="row">
        <div class="form-group"><label>Year</label><input type="text" name="edu2_year" value="<?php echo e($r['edu2_year']); ?>"></div>
        <div class="form-group"><label>University / School</label><input type="text" name="edu2_uni" value="<?php echo e($r['edu2_uni']); ?>"></div>
      </div>
      <div class="form-group"><label>Degree Name</label><input type="text" name="edu2_deg" value="<?php echo e($r['edu2_deg']); ?>"></div>

      <h3>Experience 1</h3>
      <div class="row">
        <div class="form-group"><label>Year</label><input type="text" name="exp1_year" value="<?php echo e($r['exp1_year']); ?>"></div>
        <div class="form-group"><label>Company Name</label><input type="text" name="exp1_comp" value="<?php echo e($r['exp1_comp']); ?>"></div>
      </div>
      <div class="form-group"><label>Role / Position</label><input type="text" name="exp1_role" value="<?php echo e($r['exp1_role']); ?>"></div>
      <div class="form-group"><label>Job Description</label><textarea name="exp1_desc"><?php echo e($r['exp1_desc']); ?></textarea></div>

      <h3>Skills</h3>
      <div class="row">
        <div class="form-group"><label>Skill 1 Name</label><input type="text" name="skill1_name" value="<?php echo e($r['skill1_name']); ?>"></div>
        <div class="form-group"><label>Skill 1 Percentage (%)</label><input type="number" name="skill1_per" min="0" max="100" value="<?php echo e($skill1); ?>"></div>
      </div>
      <div class="row">
        <div class="form-group"><label>Skill 2 Name</label><input type="text" name="skill2_name" value="<?php echo e($r['skill2_name']); ?>"></div>
        <div class="form-group"><label>Skill 2 Percentage (%)</label><input type="number" name="skill2_per" min="0" max="100" value="<?php echo e($skill2); ?>"></div>
      </div>

      <h3>Contact Details</h3>
      <div class="row">
        <div class="form-group"><label>Phone Number</label><input type="text" name="phone" value="<?php echo e($r['phone']); ?>"></div>
        <div class="form-group"><label>Email Address</label><input type="text" name="email" value="<?php echo e($r['email']); ?>"></div>
      </div>
      <div class="row">
        <div class="form-group"><label>Website</label><input type="text" name="website" value="<?php echo e($r['website']); ?>"></div>
        <div class="form-group"><label>Address</label><input type="text" name="address" value="<?php echo e($r['address']); ?>"></div>
      </div>

      <h3>Page 2 — Experience 2</h3>
      <div class="row">
        <div class="form-group"><label>Year</label><input type="text" name="exp2_year" value="<?php echo e($r['exp2_year']); ?>"></div>
        <div class="form-group"><label>Company Name</label><input type="text" name="exp2_comp" value="<?php echo e($r['exp2_comp']); ?>"></div>
      </div>
      <div class="form-group"><label>Role / Position</label><input type="text" name="exp2_role" value="<?php echo e($r['exp2_role']); ?>"></div>
      <div class="form-group"><label>Job Description</label><textarea name="exp2_desc"><?php echo e($r['exp2_desc']); ?></textarea></div>

      <h3>Page 2 — Project</h3>
      <div class="form-group"><label>Project Name</label><input type="text" name="proj1_name" value="<?php echo e($r['proj1_name']); ?>"></div>
      <div class="form-group"><label>Project Description</label><textarea name="proj1_desc"><?php echo e($r['proj1_desc']); ?></textarea></div>

      <h3>Page 2 — Certifications</h3>
      <div class="form-group"><label>Comma-separated list</label><input type="text" name="certs" value="<?php echo e($r['certs']); ?>"></div>

      <h3>Page 2 — Languages</h3>
      <div class="row">
        <div class="form-group"><label>Language 1</label><input type="text" name="lang1_name" value="<?php echo e($r['lang1_name']); ?>"></div>
        <div class="form-group"><label>Fluency (%)</label><input type="number" name="lang1_per" min="0" max="100" value="<?php echo e($lang1); ?>"></div>
      </div>
      <div class="row">
        <div class="form-group"><label>Language 2</label><input type="text" name="lang2_name" value="<?php echo e($r['lang2_name']); ?>"></div>
        <div class="form-group"><label>Fluency (%)</label><input type="number" name="lang2_per" min="0" max="100" value="<?php echo e($lang2); ?>"></div>
      </div>

      <button type="submit" name="generate" value="1" class="submit-btn">Update &amp; Generate</button>
    </form>
  </div>

  <div class="panel output-panel" data-theme="<?php echo e($r['theme']); ?>" data-font="<?php echo e($r['font']); ?>" data-pattern="<?php echo e($r['pattern']); ?>">
    <div class="crest">
      <div class="photo-frame">
         <img src="<?php echo $photoSrc; ?>" alt="User Avatar">
      </div>
      <h1><?php echo e($r['name']); ?></h1>
      <div class="subtitle" style="text-transform: uppercase; letter-spacing: 2px; color: var(--gold);">
        <?php echo e($r['job_title']); ?>
      </div>
    </div>
    <div class="divider"><span class="line"></span><span class="diamond"></span><span class="line"></span></div>

    <div class="output-content">

      <?php if ($activePage === '1'): ?>
      <div class="page active">
        <p class="output-summary">
          <?php echo $r['summary'] !== '' ? '"' . e($r['summary']) . '"' : '"Write a short summary about yourself..."'; ?>
        </p>

        <div class="section-title">Experience</div>
        <div class="item-block">
          <div class="item-header">
            <span><?php echo e($r['exp1_role']); ?></span>
            <span style="color: var(--gold);"><?php echo e($r['exp1_year']); ?></span>
          </div>
          <div class="item-sub"><?php echo e($r['exp1_comp']); ?></div>
          <div class="item-desc"><?php echo e($r['exp1_desc']); ?></div>
        </div>

        <div class="section-title">Education</div>
        <div class="item-block">
          <div class="item-header">
            <span><?php echo e($r['edu1_deg']); ?></span>
            <span style="color: var(--gold);"><?php echo e($r['edu1_year']); ?></span>
          </div>
          <div class="item-sub"><?php echo e($r['edu1_uni']); ?></div>
        </div>
        <div class="item-block">
          <div class="item-header">
            <span><?php echo e($r['edu2_deg']); ?></span>
            <span style="color: var(--gold);"><?php echo e($r['edu2_year']); ?></span>
          </div>
          <div class="item-sub"><?php echo e($r['edu2_uni']); ?></div>
        </div>

        <div class="section-title">Expertise</div>
        <div class="row">
          <div class="form-group skill-row">
            <div class="skill-info"><span><?php echo e($r['skill1_name']); ?></span><span><?php echo $skill1; ?>%</span></div>
            <div class="progress-bg"><div class="progress-fill" style="width: <?php echo $skill1; ?>%;"></div></div>
          </div>
          <div class="form-group skill-row">
            <div class="skill-info"><span><?php echo e($r['skill2_name']); ?></span><span><?php echo $skill2; ?>%</span></div>
            <div class="progress-bg"><div class="progress-fill" style="width: <?php echo $skill2; ?>%;"></div></div>
          </div>
        </div>

        <div class="section-title">Communication</div>
        <div class="contact-grid">
          <div class="contact-item"><strong>Phone:</strong> <?php echo e($r['phone']); ?></div>
          <div class="contact-item"><strong>Email:</strong> <?php echo e($r['email']); ?></div>
          <div class="contact-item"><strong>Web:</strong> <?php echo e($r['website']); ?></div>
          <div class="contact-item"><strong>Loc:</strong> <?php echo e($r['address']); ?></div>
        </div>
      </div>
      <?php else: ?>
      <div class="page active">
        <div class="section-title">Further Experience</div>
        <div class="item-block">
          <div class="item-header">
            <span><?php echo e($r['exp2_role']); ?></span>
            <span style="color: var(--gold);"><?php echo e($r['exp2_year']); ?></span>
          </div>
          <div class="item-sub"><?php echo e($r['exp2_comp']); ?></div>
          <div class="item-desc"><?php echo e($r['exp2_desc']); ?></div>
        </div>

        <div class="section-title">Featured Project</div>
        <div class="item-block">
          <div class="item-header"><span><?php echo e($r['proj1_name']); ?></span></div>
          <div class="item-desc"><?php echo e($r['proj1_desc']); ?></div>
        </div>

        <div class="section-title">Certifications</div>
        <div class="tag-list">
          <?php foreach ($certTags as $tag): ?>
            <span class="tag"><?php echo e($tag); ?></span>
          <?php endforeach; ?>
        </div>

        <div class="section-title" style="margin-top:30px;">Languages</div>
        <div class="row">
          <div class="form-group skill-row">
            <div class="skill-info"><span><?php echo e($r['lang1_name']); ?></span><span><?php echo $lang1; ?>%</span></div>
            <div class="progress-bg"><div class="progress-fill" style="width: <?php echo $lang1; ?>%;"></div></div>
          </div>
          <div class="form-group skill-row">
            <div class="skill-info"><span><?php echo e($r['lang2_name']); ?></span><span><?php echo $lang2; ?>%</span></div>
            <div class="progress-bg"><div class="progress-fill" style="width: <?php echo $lang2; ?>%;"></div></div>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- Page 1 / Page 1 & 2 print together always; the tabs below just change what's shown on screen -->
      <div class="page-nav no-print">
        <a class="page-dot <?php echo $activePage === '1' ? 'active' : ''; ?>" href="?page=1" style="text-decoration:none;">1</a>
        <a class="page-dot <?php echo $activePage === '2' ? 'active' : ''; ?>" href="?page=2" style="text-decoration:none;">2</a>
      </div>

      <p class="no-print" style="text-align:center; font-style:italic; color:var(--ink-soft); margin-top:30px; font-size:14px;">
        To save this as a PDF, use your browser's print shortcut (Ctrl+P on Windows/Linux, Cmd+P on Mac) and choose "Save as PDF".
      </p>

    </div>
  </div>

</div>

</body>
</html>
