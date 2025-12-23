import requests
import json

# Test the service
print("Testing Python service...")

# Test 1: Health check
health = requests.get("http://localhost:5001/health")
print(f"Health check: {health.status_code}")
print(health.json())

# Test 2: Analyze with dummy image
dummy_image = "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg=="

payload = {
    "image": dummy_image,
    "current_height": 120
}

response = requests.post("http://localhost:5001/analyze", json=payload)
print(f"\nAnalysis response: {response.status_code}")
print(json.dumps(response.json(), indent=2))