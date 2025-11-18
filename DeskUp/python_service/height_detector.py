import cv2
import numpy as np
import mediapipe as mp
import math
import base64  
import json
from typing import Dict, List, Optional, Tuple

class HeightDetector:
    def __init__(self):
        # MediaPipe
        self.mp_pose = mp.solutions.pose
        self.mp_face_mesh = mp.solutions.face_mesh
        self.mp_drawing = mp.solutions.drawing_utils
        self.mp_drawing_styles = mp.solutions.drawing_styles
        
        # Models
        self.pose = self.mp_pose.Pose(
            static_image_mode=False,
            model_complexity=1,
            smooth_landmarks=True,
            min_detection_confidence=0.5,
            min_tracking_confidence=0.5
        )
        
        self.face_mesh = self.mp_face_mesh.FaceMesh(
            static_image_mode=True,
            max_num_faces=1,
            refine_landmarks=True,
            min_detection_confidence=0.5,
            min_tracking_confidence=0.5
        )
        
        # Ctts
        self.calibration_data = {}
        self.REFERENCE_HEIGHT_CM = 170  # reference height
        
    def decode_base64_image(self, image_data: str) -> np.ndarray:
        """Decodificar imagen base64 desde Laravel"""
        try:
            # no header
            if ',' in image_data:
                image_data = image_data.split(',')[1]
            
            # Base64
            image_bytes = base64.b64decode(image_data)
            image_array = np.frombuffer(image_bytes, dtype=np.uint8)
            image = cv2.imdecode(image_array, cv2.IMREAD_COLOR)
            
            return image
        except Exception as e:
            raise ValueError(f"Error decoding image: {str(e)}")
    
    def detect_face_landmarks(self, image: np.ndarray) -> Optional[Dict]:
        """Detectar landmarks faciales para referencia de altura"""
        try:
            # Convert BGR to RGB
            rgb_image = cv2.cvtColor(image, cv2.COLOR_BGR2RGB)
            results = self.face_mesh.process(rgb_image)
            
            if not results.multi_face_landmarks:
                return None
            
            landmarks = results.multi_face_landmarks[0]
            h, w = image.shape[:2]
            
            # Key points (face)
            key_points = {
                'left_eye': landmarks.landmark[33],    
                'right_eye': landmarks.landmark[263],  
                'nose_tip': landmarks.landmark[1],     
                'chin': landmarks.landmark[152],       
            }
            
            # Convert a coords to pixels
            pixel_points = {}
            for name, landmark in key_points.items():
                pixel_points[name] = {
                    'x': int(landmark.x * w),
                    'y': int(landmark.y * h),
                    'z': landmark.z
                }
            
            return pixel_points
            
        except Exception as e:
            print(f"Error in face detection: {str(e)}")
            return None
    
    def detect_body_pose(self, image: np.ndarray) -> Optional[Dict]:
        """Detectar postura corporal completa"""
        try:
            rgb_image = cv2.cvtColor(image, cv2.COLOR_BGR2RGB)
            results = self.pose.process(rgb_image)
            
            if not results.pose_landmarks:
                return None
            
            landmarks = results.pose_landmarks.landmark
            h, w = image.shape[:2]
            
            # Keypoints (body)
            key_points = {
                'nose': (landmarks[self.mp_pose.PoseLandmark.NOSE].x * w,
                        landmarks[self.mp_pose.PoseLandmark.NOSE].y * h),
                
                'left_shoulder': (landmarks[self.mp_pose.PoseLandmark.LEFT_SHOULDER].x * w,
                                landmarks[self.mp_pose.PoseLandmark.LEFT_SHOULDER].y * h),
                
                'right_shoulder': (landmarks[self.mp_pose.PoseLandmark.RIGHT_SHOULDER].x * w,
                                 landmarks[self.mp_pose.PoseLandmark.RIGHT_SHOULDER].y * h),
                
                'left_elbow': (landmarks[self.mp_pose.PoseLandmark.LEFT_ELBOW].x * w,
                              landmarks[self.mp_pose.PoseLandmark.LEFT_ELBOW].y * h),
                
                'right_elbow': (landmarks[self.mp_pose.PoseLandmark.RIGHT_ELBOW].x * w,
                               landmarks[self.mp_pose.PoseLandmark.RIGHT_ELBOW].y * h),
                
                'left_hip': (landmarks[self.mp_pose.PoseLandmark.LEFT_HIP].x * w,
                            landmarks[self.mp_pose.PoseLandmark.LEFT_HIP].y * h),
                
                'right_hip': (landmarks[self.mp_pose.PoseLandmark.RIGHT_HIP].x * w,
                             landmarks[self.mp_pose.PoseLandmark.RIGHT_HIP].y * h),
            }
            
            return key_points
            
        except Exception as e:
            print(f"Error in pose detection: {str(e)}")
            return None
    
    def calculate_pixel_distance(self, point1: Tuple[float, float], point2: Tuple[float, float]) -> float:
        """Calcular distancia euclidiana entre dos puntos"""
        return math.sqrt((point2[0] - point1[0])**2 + (point2[1] - point1[1])**2)
    
    def estimate_height_from_face(self, face_landmarks: Dict, image_height: int) -> float:
        """Estimar altura basada en proporciones faciales"""
        try:
            # Eye distance (scale proxy)
            eye_distance = self.calculate_pixel_distance(
                (face_landmarks['left_eye']['x'], face_landmarks['left_eye']['y']),
                (face_landmarks['right_eye']['x'], face_landmarks['right_eye']['y'])
            )
            
            # Eye-chin distance
            eye_chin_distance = self.calculate_pixel_distance(
                (face_landmarks['left_eye']['x'], face_landmarks['left_eye']['y']),
                (face_landmarks['chin']['x'], face_landmarks['chin']['y'])
            )
            
            # Ratio head-body
            head_to_body_ratio = 7.5
            
            # Height estimation
            estimated_head_height = eye_chin_distance * 2.5 
            estimated_height = estimated_head_height * head_to_body_ratio
            
            # Convert to cm
            if 'pixels_per_cm' in self.calibration_data:
                height_cm = estimated_height / self.calibration_data['pixels_per_cm']
            else:
                # Estimation before calibration
                height_cm = (estimated_height / image_height) * self.REFERENCE_HEIGHT_CM
            
            return max(150, min(200, height_cm))  # Limits
            
        except Exception as e:
            print(f"Error estimating height from face: {str(e)}")
            return 170.0  # Default height
    
    def analyze_posture(self, pose_points: Dict) -> Dict:
        """Analizar calidad de postura ergonómica"""
        analysis = {
            'shoulder_tension': 0,
            'neck_angle': 0,
            'posture_score': 100,
            'issues': []
        }
        
        try:
            # shoulder inclination
            left_shoulder = pose_points['left_shoulder']
            right_shoulder = pose_points['right_shoulder']
            
            shoulder_slope = abs(left_shoulder[1] - right_shoulder[1])
            analysis['shoulder_tension'] = min(100, shoulder_slope * 10)
            
            # shoulder elevation
            nose_y = pose_points['nose'][1]
            shoulder_avg_y = (left_shoulder[1] + right_shoulder[1]) / 2
            
            if shoulder_avg_y < nose_y + 50: 
                analysis['issues'].append('shoulders_raised')
                analysis['posture_score'] -= 20
            
            # Symmetry verification
            if shoulder_slope > 30:
                analysis['issues'].append('uneven_shoulders')
                analysis['posture_score'] -= 15
                
            analysis['posture_score'] = max(0, analysis['posture_score'])
            
        except Exception as e:
            print(f"Error in posture analysis: {str(e)}")
        
        return analysis
    
    def calculate_recommended_height(self, user_height: float, posture_data: Dict) -> float:
        """Calcular altura recomendada del escritorio"""
        # Ergonomic formula
        elbow_height = user_height * 0.63
        
        # Posture ajustments
        adjustments = 0
        
        if 'shoulders_raised' in posture_data['issues']:
            adjustments -= 3  
        
        if 'uneven_shoulders' in posture_data['issues']:
            adjustments -= 2  
            
        recommended_height = elbow_height + adjustments
        
        # Security limits
        return max(70, min(120, recommended_height))
    
    def process_image(self, image_data: str, user_height: float = None) -> Dict:
        """Procesar imagen completa y retornar resultados"""
        try:
            # Image decodification
            image = self.decode_base64_image(image_data)
            if image is None:
                return {'error': 'Invalid image data'}
            
            h, w = image.shape[:2]
            
            # Landmark detection
            face_landmarks = self.detect_face_landmarks(image)
            pose_points = self.detect_body_pose(image)
            
            if not face_landmarks and not pose_points:
                return {'error': 'No person detected'}
            
            # Height estimation
            if user_height:
                estimated_height = user_height
            elif face_landmarks:
                estimated_height = self.estimate_height_from_face(face_landmarks, h)
            else:
                estimated_height = 170.0  # Default
            
            # Posture analysis
            posture_analysis = self.analyze_posture(pose_points) if pose_points else {
                'posture_score': 0, 'issues': ['no_pose_detected']
            }
            
            # Recommended height
            recommended_height = self.calculate_recommended_height(
                estimated_height, 
                posture_analysis
            )
            
            return {
                'success': True,
                'user_height': round(estimated_height, 1),
                'recommended_desk_height': round(recommended_height, 1),
                'posture_analysis': posture_analysis,
                'detection_quality': 'good' if face_landmarks else 'limited',
                'should_adjust': True 
            }
            
        except Exception as e:
            return {
                'success': False,
                'error': f'Processing error: {str(e)}'
            }