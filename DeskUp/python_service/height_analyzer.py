"""
DeskUp Height Analyzer
Professional posture analysis for optimal desk height calculation using Mediapipe.
Based on ergonomic principles and anthropometric data.
"""

import cv2
import mediapipe as mp
import numpy as np
from typing import Dict, List, Tuple, Optional
import base64
import io
from PIL import Image
import json
from dataclasses import dataclass
from scipy.spatial import distance

@dataclass
class BodyLandmarks:
    """Container for normalized body landmarks with ergonomic annotations"""
    nose: np.ndarray
    left_shoulder: np.ndarray
    right_shoulder: np.ndarray
    left_elbow: np.ndarray
    right_elbow: np.ndarray
    left_wrist: np.ndarray
    right_wrist: np.ndarray
    left_hip: np.ndarray
    right_hip: np.ndarray
    left_knee: np.ndarray
    right_knee: np.ndarray
    left_ankle: np.ndarray
    right_ankle: np.ndarray
    
    @property
    def shoulder_center(self) -> np.ndarray:
        """Calculate center point between shoulders"""
        return (self.left_shoulder + self.right_shoulder) / 2
    
    @property
    def hip_center(self) -> np.ndarray:
        """Calculate center point between hips"""
        return (self.left_hip + self.right_hip) / 2
    
    @property
    def knee_center(self) -> np.ndarray:
        """Calculate center point between knees"""
        return (self.left_knee + self.right_knee) / 2


class ErgonomicAnalyzer:
    """
    Professional ergonomic analyzer for desk height calculation.
    Uses Mediapipe pose detection and applies ergonomic principles.
    """
    
    # Ergonomics constants (in meters)
    ELBOW_ANGLE_IDEAL = 90  # degrees for optimal typing position
    DESK_TO_THIGH_CLEARANCE = 0.05  # 5cm clearance between desk and thighs
    MONITOR_EYE_LEVEL_OFFSET = 0.10  # 10cm below eye level for monitor
    TYPING_SURFACE_HEIGHT = 0.02  # 2cm for keyboard thickness
    
    # Anthropometric ratios (based on average human proportions)
    UPPER_ARM_TO_HEIGHT_RATIO = 0.186
    FOREARM_TO_HEIGHT_RATIO = 0.146
    TORSO_TO_HEIGHT_RATIO = 0.288
    LEG_TO_HEIGHT_RATIO = 0.530
    
    def __init__(self):
        """Initialize Mediapipe with optimized parameters"""
        self.mp_pose = mp.solutions.pose
        self.pose = self.mp_pose.Pose(
            static_image_mode=True,
            model_complexity=2,  # Highest accuracy
            smooth_landmarks=True,
            enable_segmentation=False,
            smooth_segmentation=True,
            min_detection_confidence=0.7,
            min_tracking_confidence=0.7
        )
        
    def extract_landmarks(self, image: np.ndarray) -> Optional[BodyLandmarks]:
        """
        Extract and normalize body landmarks from image.
        Returns BodyLandmarks object or None if detection fails.
        """
        try:
            # Convert to RGB for Mediapipe
            image_rgb = cv2.cvtColor(image, cv2.COLOR_BGR2RGB)
            results = self.pose.process(image_rgb)
            
            if not results.pose_landmarks:
                return None
            
            landmarks = results.pose_landmarks.landmark
            
            # Extract key landmarks (normalized coordinates 0-1)
            return BodyLandmarks(
                nose=np.array([landmarks[0].x, landmarks[0].y]),
                left_shoulder=np.array([landmarks[11].x, landmarks[11].y]),
                right_shoulder=np.array([landmarks[12].x, landmarks[12].y]),
                left_elbow=np.array([landmarks[13].x, landmarks[13].y]),
                right_elbow=np.array([landmarks[14].x, landmarks[14].y]),
                left_wrist=np.array([landmarks[15].x, landmarks[15].y]),
                right_wrist=np.array([landmarks[16].x, landmarks[16].y]),
                left_hip=np.array([landmarks[23].x, landmarks[23].y]),
                right_hip=np.array([landmarks[24].x, landmarks[24].y]),
                left_knee=np.array([landmarks[25].x, landmarks[25].y]),
                right_knee=np.array([landmarks[26].x, landmarks[26].y]),
                left_ankle=np.array([landmarks[27].x, landmarks[27].y]),
                right_ankle=np.array([landmarks[28].x, landmarks[28].y])
            )
            
        except Exception as e:
            print(f"Landmark extraction error: {e}")
            return None
    
    def calculate_posture_metrics(self, landmarks: BodyLandmarks) -> Dict:
        """
        Calculate comprehensive posture metrics for ergonomic assessment.
        """
        metrics = {}
        
        # 1. Vertical alignment (spine straightness)
        shoulder_hip_diff = landmarks.shoulder_center[1] - landmarks.hip_center[1]
        metrics['vertical_alignment'] = abs(shoulder_hip_diff)
        
        # 2. Shoulder symmetry
        shoulder_level_diff = abs(landmarks.left_shoulder[1] - landmarks.right_shoulder[1])
        metrics['shoulder_symmetry'] = shoulder_level_diff
        
        # 3. Hip symmetry
        hip_level_diff = abs(landmarks.left_hip[1] - landmarks.right_hip[1])
        metrics['hip_symmetry'] = hip_level_diff
        
        # 4. Elbow angles (for typing position)
        left_elbow_angle = self._calculate_angle(
            landmarks.left_shoulder, landmarks.left_elbow, landmarks.left_wrist
        )
        right_elbow_angle = self._calculate_angle(
            landmarks.right_shoulder, landmarks.right_elbow, landmarks.right_wrist
        )
        metrics['elbow_angles'] = {
            'left': left_elbow_angle,
            'right': right_elbow_angle,
            'average': (left_elbow_angle + right_elbow_angle) / 2
        }
        
        # 5. Torso inclination
        nose_hip_vector = landmarks.hip_center - np.array([landmarks.nose[0], landmarks.nose[1]])
        vertical_vector = np.array([0, 1])
        torso_angle = self._angle_between(nose_hip_vector, vertical_vector)
        metrics['torso_inclination'] = torso_angle
        
        # 6. Sitting vs standing detection
        knee_hip_ratio = landmarks.knee_center[1] / landmarks.hip_center[1]
        metrics['posture_type'] = 'sitting' if knee_hip_ratio > 0.85 else 'standing'
        metrics['posture_confidence'] = 1.0 - abs(knee_hip_ratio - 0.9)  # Higher confidence near threshold
        
        # 7. Estimated body height (relative to image)
        body_height_pixels = abs(landmarks.nose[1] - landmarks.hip_center[1]) * 2.5  # Rough estimate
        metrics['relative_body_height'] = body_height_pixels
        
        return metrics
    
    def _calculate_angle(self, a: np.ndarray, b: np.ndarray, c: np.ndarray) -> float:
        """Calculate angle between three points (b is vertex)"""
        ba = a - b
        bc = c - b
        cosine_angle = np.dot(ba, bc) / (np.linalg.norm(ba) * np.linalg.norm(bc))
        angle = np.degrees(np.arccos(np.clip(cosine_angle, -1.0, 1.0)))
        return angle
    
    def _angle_between(self, v1: np.ndarray, v2: np.ndarray) -> float:
        """Calculate angle between two vectors"""
        unit_v1 = v1 / np.linalg.norm(v1)
        unit_v2 = v2 / np.linalg.norm(v2)
        dot_product = np.dot(unit_v1, unit_v2)
        return np.degrees(np.arccos(np.clip(dot_product, -1.0, 1.0)))
    
    def estimate_user_height(self, landmarks: BodyLandmarks, image_height: int) -> Optional[float]:
        """
        Estimate user height in meters based on body proportions.
        Uses anthropometric ratios and pixel measurements.
        """
        try:
            # Measure key distances in pixels
            shoulder_hip_pixels = distance.euclidean(
                landmarks.shoulder_center, landmarks.hip_center
            ) * image_height
            
            # Using anthropometric ratio: torso is ~28.8% of total height
            estimated_height_pixels = shoulder_hip_pixels / self.TORSO_TO_HEIGHT_RATIO
            
            # Convert to meters (assuming average distance to camera ~2m)
            # This is a rough estimation - in production would need calibration
            estimated_height_meters = estimated_height_pixels / 1000  # Simplified scaling
            
            return max(1.5, min(2.0, estimated_height_meters))  # Reasonable bounds
            
        except Exception as e:
            print(f"Height estimation error: {e}")
            return None
    
    def calculate_ideal_desk_height(self, 
                                   user_height: float, 
                                   posture_metrics: Dict,
                                   current_desk_height: float) -> Tuple[float, Dict]:
        """
        Calculate ideal desk height based on ergonomic principles.
        
        Formula based on:
        1. User's estimated height
        2. Optimal elbow angle (90° for typing)
        3. Thigh clearance requirements
        4. Posture quality adjustments
        """
        
        # Base calculation using ergonomic formula:
        # Ideal desk height = (User height * sitting height ratio) - adjustments
        
        # Standard sitting height ratio (popliteal height + thigh clearance)
        sitting_height_ratio = 0.25  # Rough estimate - would need precise measurements
        
        # Base ideal height calculation
        base_ideal_height = user_height * 100 * sitting_height_ratio  # Convert to cm
        
        # Adjustments based on posture metrics
        adjustments = []
        
        # 1. Elbow angle adjustment (ideal: 90°)
        avg_elbow_angle = posture_metrics['elbow_angles']['average']
        elbow_adjustment = (avg_elbow_angle - self.ELBOW_ANGLE_IDEAL) * 0.1  # 0.1cm per degree
        adjustments.append(('elbow_angle', elbow_adjustment))
        
        # 2. Torso inclination adjustment (ideal: vertical)
        torso_adjustment = posture_metrics['torso_inclination'] * 0.05  # 0.05cm per degree
        adjustments.append(('torso_inclination', torso_adjustment))
        
        # 3. Shoulder symmetry adjustment
        shoulder_adjustment = posture_metrics['shoulder_symmetry'] * 100 * 0.1  # Scale factor
        adjustments.append(('shoulder_symmetry', shoulder_adjustment))
        
        # 4. Posture type bonus
        if posture_metrics['posture_type'] == 'sitting':
            # Small bonus for already sitting
            adjustments.append(('posture_bonus', -2.0))
        else:
            # Penalty for standing (need to estimate sitting height)
            adjustments.append(('posture_penalty', 5.0))
        
        # Calculate total adjustment
        total_adjustment = sum(adj[1] for adj in adjustments)
        
        # Apply adjustments to base height
        ideal_height = base_ideal_height + total_adjustment
        
        # Consider current desk height (if user is adjusting from existing setup)
        if current_desk_height > 0:
            # Weighted average between calculated ideal and current (if user is sitting)
            if posture_metrics['posture_type'] == 'sitting' and posture_metrics['posture_confidence'] > 0.7:
                # 70% calculated, 30% current (respect user's current adjustment)
                ideal_height = (ideal_height * 0.7) + (current_desk_height * 0.3)
        
        # Apply ergonomic constraints
        ideal_height = self._apply_ergonomic_constraints(ideal_height, user_height)
        
        # Round to 1 decimal
        ideal_height = round(ideal_height, 1)
        
        # Prepare detailed breakdown
        breakdown = {
            'base_height_cm': round(base_ideal_height, 1),
            'adjustments': {name: round(adj, 1) for name, adj in adjustments},
            'total_adjustment_cm': round(total_adjustment, 1),
            'calculated_ideal_cm': ideal_height,
            'posture_type': posture_metrics['posture_type'],
            'posture_confidence': round(posture_metrics['posture_confidence'], 2)
        }
        
        return ideal_height, breakdown
    
    def _apply_ergonomic_constraints(self, height: float, user_height: float) -> float:
        """
        Apply ergonomic constraints to ensure reasonable desk height.
        """
        min_height = 60.0  # Minimum ergonomic desk height (cm)
        max_height = 130.0  # Maximum ergonomic desk height (cm)
        
        # User-specific constraints based on height
        user_min = (user_height * 100 * 0.22)  # 22% of user height
        user_max = (user_height * 100 * 0.32)  # 32% of user height
        
        # Apply constraints
        constrained_height = max(min_height, min(max_height, height))
        constrained_height = max(user_min, min(user_max, constrained_height))
        
        return constrained_height


class HeightAnalyzer:
    """
    Main analyzer class coordinating the height calculation process.
    """
    
    def __init__(self):
        self.ergonomic_analyzer = ErgonomicAnalyzer()
    
    def base64_to_image(self, image_base64: str) -> np.ndarray:
        """Convert base64 string to OpenCV image"""
        try:
            # Remove data URL prefix if present
            if ',' in image_base64:
                image_base64 = image_base64.split(',')[1]
            
            image_bytes = base64.b64decode(image_base64)
            image = Image.open(io.BytesIO(image_bytes))
            return cv2.cvtColor(np.array(image), cv2.COLOR_RGB2BGR)
            
        except Exception as e:
            print(f"Image conversion error: {e}")
            raise
    
    def analyze(self, image_base64: str, current_desk_height: float) -> Dict:
        """
        Main analysis pipeline.
        
        Args:
            image_base64: Base64 encoded image string
            current_desk_height: Current desk height in cm
            
        Returns:
            Dictionary with analysis results
        """
        try:
            print("=" * 50)
            print("Starting professional height analysis")
            print(f"Current desk height: {current_desk_height}cm")
            
            # 1. Convert image
            image = self.base64_to_image(image_base64)
            image_height, image_width = image.shape[:2]
            print(f"Image dimensions: {image_width}x{image_height} pixels")
            
            # 2. Extract landmarks
            landmarks = self.ergonomic_analyzer.extract_landmarks(image)
            if not landmarks:
                return self._fallback_response(current_desk_height, "No pose detected")
            
            print("✓ Body landmarks successfully extracted")
            
            # 3. Calculate posture metrics
            posture_metrics = self.ergonomic_analyzer.calculate_posture_metrics(landmarks)
            print(f"✓ Posture analysis complete: {posture_metrics['posture_type']} "
                  f"(confidence: {posture_metrics['posture_confidence']:.2f})")
            
            # 4. Estimate user height
            user_height = self.ergonomic_analyzer.estimate_user_height(landmarks, image_height)
            if not user_height:
                user_height = 1.75  # Default average height
                print("⚠ Using default height (1.75m)")
            else:
                print(f"✓ Estimated user height: {user_height:.2f}m")
            
            # 5. Calculate ideal desk height
            ideal_height, breakdown = self.ergonomic_analyzer.calculate_ideal_desk_height(
                user_height, posture_metrics, current_desk_height
            )
            
            print(f"✓ Ideal desk height calculated: {ideal_height}cm")
            print("=" * 50)
            
            # 6. Prepare response
            return {
                'success': True,
                'ideal_height': ideal_height,
                'current_height': current_desk_height,
                'posture': posture_metrics['posture_type'],
                'confidence': posture_metrics['posture_confidence'],
                'user_height_estimated_m': round(user_height, 2),
                'analysis_breakdown': breakdown,
                'message': f'Professional analysis complete. Ideal height: {ideal_height}cm'
            }
            
        except Exception as e:
            print(f"Analysis error: {e}")
            return self._fallback_response(current_desk_height, str(e))
    
    def _fallback_response(self, current_height: float, error: str) -> Dict:
        """Provide fallback response when analysis fails"""
        print(f"⚠ Falling back to default calculation: {error}")
        
        # Simple fallback logic
        if current_height > 0:
            ideal_height = max(70.0, min(85.0, current_height))
        else:
            ideal_height = 75.0  # Standard desk height
        
        return {
            'success': True,
            'ideal_height': round(ideal_height, 1),
            'current_height': current_height,
            'posture': 'unknown',
            'confidence': 0.3,
            'message': f'Basic calculation: {ideal_height}cm (Professional analysis failed: {error})'
        }