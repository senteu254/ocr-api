from flask import Flask, request, jsonify
import pytesseract
from pdf2image import convert_from_path
import os

app = Flask(__name__)

@app.route("/", methods=["POST"])
def ocr_pdf():
    if 'file' not in request.files:
        return jsonify({"error": "No file provided"}), 400

    file = request.files['file']
    path = f"/tmp/{file.filename}"
    file.save(path)

    # Convert PDF to image and extract text
    images = convert_from_path(path)
    text = ""
    for img in images:
        text += pytesseract.image_to_string(img)

    os.remove(path)
    return jsonify({"ParsedResults": [{"ParsedText": text}]})

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=8080)
