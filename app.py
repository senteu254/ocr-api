from flask import Flask, request, jsonify
import pytesseract
from pdf2image import convert_from_bytes

app = Flask(__name__)

@app.route('/ocr', methods=['POST'])
def ocr_pdf():
    if 'file' not in request.files:
        return jsonify({'error': 'No file uploaded'}), 400

    file = request.files['file']
    try:
        images = convert_from_bytes(file.read())
        text = ''
        for image in images:
            text += pytesseract.image_to_string(image) + '\n'
        return jsonify({'text': text.strip()})
    except Exception as e:
        return jsonify({'error': str(e)}), 500
