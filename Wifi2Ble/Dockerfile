FROM python:3.11-slim

WORKDIR /app

# Copy simulator files
COPY simulator/ ./simulator/
COPY config/ ./config/
COPY data/ ./data/

# Modify main.py to listen on 0.0.0.0 instead of localhost
RUN sed -i 's/server_address = ("localhost", port)/server_address = ("0.0.0.0", port)/' simulator/main.py

# Expose port
EXPOSE 8443

# Run the simulator in HTTPS mode
CMD ["python", "simulator/main.py", "--port", "8443", "--https", "--certfile", "config/cert.pem", "--keyfile", "config/key.pem", "--desks", "10", "--speed", "60"]
