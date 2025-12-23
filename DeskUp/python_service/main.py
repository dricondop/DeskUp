from flask import Flask, request, jsonify
from flask_cors import CORS
from height_analyzer import HeightAnalyzer
import logging

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

# Initialize Flask
app = Flask(__name__)
CORS(app)

# Initialize analyzer
analyzer = HeightAnalyzer()

@app.route('/health', methods=['GET'])
def health_check():
    """Service health endpoint"""
    return jsonify({
        'status': 'online',
        'service': 'DeskUp Professional Height Analyzer',
        'version': '2.0.0',
        'capabilities': ['pose_detection', 'ergonomic_analysis', 'height_calculation']
    })

@app.route('/analyze', methods=['POST'])
def analyze():
    """
    Professional height analysis endpoint.
    
    Expected JSON:
    {
        "image": "base64_string",
        "current_height": 120.5
    }
    
    Returns detailed analysis including:
    - Ideal desk height
    - Posture classification
    - Confidence score
    - Analysis breakdown
    """
    try:
        data = request.get_json()
        
        if not data:
            return jsonify({
                'success': False,
                'error': 'No JSON data received'
            }), 400
        
        image = data.get('image')
        current_height = data.get('current_height')
        
        # Validate inputs
        if not image:
            return jsonify({
                'success': False,
                'error': 'No image provided'
            }), 400
        
        if current_height is None:
            return jsonify({
                'success': False,
                'error': 'No current_height provided'
            }), 400
        
        # Convert and validate current_height
        try:
            current_height = float(current_height)
            if current_height < 0 or current_height > 200:
                return jsonify({
                    'success': False,
                    'error': 'current_height must be between 0 and 200 cm'
                }), 400
        except ValueError:
            return jsonify({
                'success': False,
                'error': 'current_height must be a number'
            }), 400
        
        logger.info(f"Analysis request received (current height: {current_height}cm)")
        
        # Perform analysis
        result = analyzer.analyze(image, current_height)
        
        logger.info(f"Analysis completed: {result.get('message', 'No message')}")
        
        return jsonify(result)
        
    except Exception as e:
        logger.error(f"Error in /analyze endpoint: {e}", exc_info=True)
        return jsonify({
            'success': False,
            'error': f'Internal server error: {str(e)}',
            'ideal_height': 75.0  # Safe fallback
        }), 500

@app.route('/analyze/debug', methods=['POST'])
def analyze_debug():
    """
    Debug endpoint with detailed logging.
    Returns full analysis breakdown including intermediate calculations.
    """
    result = analyze()
    
    # Add debug information if successful
    if result.status_code == 200:
        result_data = result.get_json()
        if result_data.get('success'):
            logger.debug(f"Full analysis breakdown: {result_data.get('analysis_breakdown', {})}")
    
    return result

if __name__ == '__main__':
    print("=" * 60)
    print("DeskUp Professional Height Analyzer Service")
    print("Version 2.0.0 - Enhanced Ergonomic Analysis")
    print("=" * 60)
    print("Available endpoints:")
    print("  GET  /health        - Service health check")
    print("  POST /analyze       - Professional height analysis")
    print("  POST /analyze/debug - Analysis with detailed logging")
    print("=" * 60)
    print("Starting service...")
    
    app.run(host='127.0.0.1', port=5001, debug=False)  # Set debug=False for production