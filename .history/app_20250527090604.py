from flask import Flask, request, jsonify
import pytesseract
from pdf2image import convert_from_path
import os
from werkzeug.utils import secure_filename  # ✅ Import this

app = Flask(__name__)

@app.route("/", methods=["GET"])
def root():
    return jsonify({"message": "OCR API is running"}), 200

@app.route("/", methods=["POST"])
def ocr_pdf():
    if 'file' not in request.files:
        return jsonify({"error": "No file provided"}), 400

    file = request.files['file']

    # ✅ Secure the filename to avoid directory issues
    filename = secure_filename(file.filename)
    path = os.path.join("/tmp", filename)

    # ✅ Save file to safe path
    file.save(path)

    # Convert PDF to image and extract text
    try:
        images = convert_from_path(path)
        text = ""
        for img in images:
            text += pytesseract.image_to_string(img)
    except Exception as e:
        return jsonify({"error": str(e)}), 500
    finally:
        if os.path.exists(path):
            os.remove(path)

    return jsonify({"ParsedResults": [{"ParsedText": text}]})

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=8080)
