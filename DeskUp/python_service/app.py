from flask import Flask, request, jsonify
from flask_cors import CORS
import base64
import io
from PIL import Image
import cv2
import numpy as np
import os
from dotenv import load_dotenv

from height_detector import HeightDetector

load_dotenv()

app = Flask(__name__)
CORS(app)  # Laravel CORS

# Start detector
detector = HeightDetector()

@app.route('/health', methods=['GET'])
def health_check():
    """Endpoint de verificación de salud del servicio"""
    return jsonify({
        'status': 'healthy',
        'service': 'height-detection',
        'version': '1.0.0'
    })

@app.route('/detect', methods=['POST'])
def detect_height():
    """Endpoint principal para detección de altura"""
    try:
        # Input data validation
        if not request.json or 'image' not in request.json:
            return jsonify({
                'success': False,
                'error': 'No image data provided'
            }), 400
        
        image_data = request.json['image']
        user_height = request.json.get('user_height')  # Maybe
        user_id = request.json.get('user_id', 1)
        
        # Process image
        result = detector.process_image(image_data, user_height)
        
        # Metadata
        result['user_id'] = user_id
        result['service_version'] = '1.0.0'
        
        return jsonify(result)
        
    except Exception as e:
        return jsonify({
            'success': False,
            'error': f'Server error: {str(e)}'
        }), 500

@app.route('/calibrate', methods=['POST'])
def calibrate_system():
    """Endpoint para calibración del sistema"""
    try:
        data = request.json
        actual_height = data.get('actual_height')
        measured_pixels = data.get('measured_pixels')
        
        if actual_height and measured_pixels:
            # Calibration logic
            detector.calibration_data['pixels_per_cm'] = measured_pixels / actual_height
            
            return jsonify({
                'success': True,
                'message': 'Calibration completed successfully'
            })
        else:
            return jsonify({
                'success': False,
                'error': 'Missing calibration data'
            }), 400
            
    except Exception as e:
        return jsonify({
            'success': False,
            'error': f'Calibration error: {str(e)}'
        }), 500

if __name__ == '__main__':
    port = int(os.getenv('PYTHON_SERVICE_PORT', 5001))  # PUERTO 5001
    debug = os.getenv('FLASK_DEBUG', 'True').lower() == 'true'  # Debug activado
    
    print(f"🚀 Starting Height Detection Service on port {port}")
    print(f"📧 Health check: http://localhost:{port}/health")
    print(f"🐛 Debug mode: {debug}")
    
    app.run(
        host='127.0.0.1',  # LOCALHOST for testing
        port=port, 
        debug=debug,
        threaded=True
    )