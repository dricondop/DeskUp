import subprocess
import sys

def install_packages():
    packages = [
        "flask==2.3.3",
        "flask-cors==4.0.0", 
        "opencv-python==4.8.1.78",
        "mediapipe==0.10.8",
        "numpy==1.24.3",
        "pillow==10.0.0",
        "python-dotenv==1.0.0",
        "requests==2.31.0"
    ]
    
    for package in packages:
        print(f"Instalando {package}...")
        subprocess.check_call([sys.executable, "-m", "pip", "install", package])
    
    print("✅ Todas las dependencias instaladas correctamente")

if __name__ == "__main__":
    install_packages()