FROM python:3.11-slim

# Install system dependencies for OpenCV
RUN apt-get update && apt-get install -y libgl1 libglib2.0-0 && rm -rf /var/lib/apt/lists/*

WORKDIR /app
COPY requirements.txt .
RUN pip install --default-timeout=300 --no-cache-dir -r requirements.txt

COPY . .
EXPOSE 5000
CMD ["python", "main.py"]