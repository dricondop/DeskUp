#!/bin/bash

echo "🚀 Starting Height Detection Service..."

# Check that Python is installed
if ! command -v python3 &> /dev/null; then
    echo "❌ Python3 is not installed"
    exit 1
fi

# Check that we are in the correct directory
if [ ! -f "app.py" ]; then
    echo "❌ You must run this script from the python-service/ directory"
    exit 1
fi

# Create virtual environment if it doesn't exist
if [ ! -d "venv" ]; then
    echo "📦 Creating virtual environment..."
    python3 -m venv venv
fi

# Activate virtual environment
echo "🔧 Activating virtual environment..."
source venv/bin/activate

# Install dependencies
echo "📚 Installing dependencies..."
pip install -r requirements.txt

# Create .env file if it doesn't exist
if [ ! -f ".env" ]; then
    echo "🔧 Creating .env file..."
    cat > .env << EOL
PYTHON_SERVICE_PORT=5000
FLASK_DEBUG=True
REFERENCE_HEIGHT_CM=170
EOL
fi

# Start service
echo "🌟 Starting Flask service..."
python app.py
