import tensorflow as tf
from tensorflow.keras.layers import TFSMLayer
from flask import Flask, request, render_template_string, jsonify
from flask_cors import CORS
from PIL import Image
import numpy as np
import base64
import io

app = Flask(__name__)
CORS(app, resources={r"/*": {"origins": "*"}})
  # ✅ allow PHP to access Flask from localhost:80

# Load model
try:
    model = TFSMLayer(
        r"C:\Users\Nilupa\OneDrive\Desktop\ai-api\saved_model",
        call_endpoint='serve'
    )
    print("Model loaded successfully")
except Exception as e:
    print(f"Error loading model: {e}")
    model = None

# Correct class order
class_names = {0: "BudRootDropping", 1: "BudRot", 2: "StemBleeding"}
THRESHOLD = 0.80

# ✅ New API route for your PHP frontend
@app.route("/predict", methods=["POST"])
def predict():
    if model is None:
        return jsonify({"error": "Model not loaded"}), 500
    if 'image' not in request.files:
        return jsonify({"error": "No image uploaded"}), 400

    file = request.files['image']
    img = Image.open(file).convert("RGB")
    img_resized = img.resize((160, 160))
    img_array = np.expand_dims(np.array(img_resized) / 255.0, axis=0)

    # Prediction
    pred_tensor = model(img_array)
    pred_array = pred_tensor.numpy()[0]
    predicted_class = int(np.argmax(pred_array))
    top_prob = float(pred_array[predicted_class])
    predicted_name = class_names.get(predicted_class, "Unknown")

    # Unknown check
    if top_prob < THRESHOLD:
        predicted_name = "Unknown or Unrecognized Disease"

    return jsonify({
        "predicted_disease": predicted_name,
        "probabilities": {name: float(pred_array[i]) for i, name in class_names.items()},
        "confidence": round(top_prob * 100, 2)
    })


# Keep your existing HTML demo (optional)
@app.route("/", methods=["GET", "POST"])
def index():
    return "✅ Flask AI API is running. Use POST /predict to send images."

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=5000)
