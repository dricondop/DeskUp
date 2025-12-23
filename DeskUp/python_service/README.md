# DeskUp Height Analyzer

Advanced ergonomic analysis service for calculating optimal desk height using computer vision and anthropometric data.

## Features

- **Pose Analysis**: Uses Mediapipe's full pose detection (33 landmarks)
- **Ergonomic Assessment**: Evaluates posture quality based on multiple parameters
- **Height Estimation**: Estimates user height from image using anthropometric ratios
- **Smart Calculations**: Applies ergonomic formulas for optimal desk height
- **Detailed Breakdown**: Returns comprehensive analysis data
- **Fallback Logic**: Graceful degradation when detection fails

## Installation

1. **Create virtual environment**:
```bash
python -m venv venv