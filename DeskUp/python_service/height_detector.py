import cv2
import numpy as np
import mediapipe as mp
import math
import base64  
import json
from typing import Dict, List, Optional, Tuple

class HeightDetector:
    def __init__(self):
        self.mp_pose = mp.solutions.pose
        self.mp_face_mesh = mp.solutions.face_mesh
        self.mp_drawing = mp.solutions.drawing_utils
        self.mp_drawing_styles = mp.solutions.drawing_styles
        
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
        
        self.calibration_data = {}
        self.REFERENCE_HEIGHT_CM = 170

    def decode_base64_image(self, image_data: str) -> np.ndarray:
        try:
            if ',' in image_data:
                image_data = image_data.split(',')[1]
            
            image_bytes = base64.b64decode(image_data)
            image_array = np.frombuffer(image_bytes, dtype=np.uint8)
            image = cv2.imdecode(image_array, cv2.IMREAD_COLOR)
            
            return image
        except Exception as e:
            raise ValueError(f"Error decoding image: {str(e)}")
    
    def detect_face_landmarks(self, image: np.ndarray) -> Optional[Dict]:
        try:
            rgb_image = cv2.cvtColor(image, cv2.COLOR_BGR2RGB)
            results = self.face_mesh.process(rgb_image)
            
            if not results.multi_face_landmarks:
                print("DEBUG: No face landmarks detected")
                return None
            
            landmarks = results.multi_face_landmarks[0]
            h, w = image.shape[:2]
            
            key_points = {
                'left_eye': landmarks.landmark[33],    
                'right_eye': landmarks.landmark[263],  
                'nose_tip': landmarks.landmark[1],     
                'chin': landmarks.landmark[152],       
            }
            
            pixel_points = {}
            for name, landmark in key_points.items():
                pixel_points[name] = {
                    'x': int(landmark.x * w),
                    'y': int(landmark.y * h),
                    'z': landmark.z
                }
            
            print(f"DEBUG: Face landmarks found: {len(pixel_points)} points")
            return pixel_points
            
        except Exception as e:
            print(f"ERROR in face detection: {str(e)}")
            return None
    
    def detect_body_pose(self, image: np.ndarray) -> Optional[Dict]:
        try:
            rgb_image = cv2.cvtColor(image, cv2.COLOR_BGR2RGB)
            results = self.pose.process(rgb_image)
            
            if not results.pose_landmarks:
                print("DEBUG: No body pose detected")
                return None
            
            landmarks = results.pose_landmarks.landmark
            h, w = image.shape[:2]
            
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
            
            print(f"DEBUG: Body pose detected: {len(key_points)} points")
            return key_points
            
        except Exception as e:
            print(f"ERROR in pose detection: {str(e)}")
            return None
    
    def calculate_pixel_distance(self, point1: Tuple[float, float], point2: Tuple[float, float]) -> float:
        return math.sqrt((point2[0] - point1[0])**2 + (point2[1] - point1[1])**2)
    
    def quick_calibration(self, actual_height_cm: float, face_landmarks: Dict, image_height: int) -> float:
        try:
            eye_distance_pixels = self.calculate_pixel_distance(
                (face_landmarks['left_eye']['x'], face_landmarks['left_eye']['y']),
                (face_landmarks['right_eye']['x'], face_landmarks['right_eye']['y'])
            )
            
            pixels_per_cm = eye_distance_pixels / 6.5
            self.calibration_data['pixels_per_cm'] = pixels_per_cm
            
            print(f"CALIBRATION: pixels_per_cm = {pixels_per_cm:.2f}")
            return pixels_per_cm
            
        except Exception as e:
            print(f"CALIBRATION ERROR: {str(e)}")
            return 10.0
    
    def estimate_height_from_face(self, face_landmarks: Dict, image_height: int) -> float:
        try:
            eye_distance = self.calculate_pixel_distance(
                (face_landmarks['left_eye']['x'], face_landmarks['left_eye']['y']),
                (face_landmarks['right_eye']['x'], face_landmarks['right_eye']['y'])
            )
            
            eye_chin_distance = self.calculate_pixel_distance(
                (face_landmarks['left_eye']['x'], face_landmarks['left_eye']['y']),
                (face_landmarks['chin']['x'], face_landmarks['chin']['y'])
            )
            
            print(f"DEBUG: Face measurements - eye_distance: {eye_distance:.1f}px, eye_chin: {eye_chin_distance:.1f}px")
            
            head_width = eye_distance * 5
            head_height = eye_chin_distance * 2.2
            estimated_height_pixels = head_height * 7.5
            
            print(f"DEBUG: Head width: {head_width:.1f}px, head height: {head_height:.1f}px")
            print(f"DEBUG: Estimated height in pixels: {estimated_height_pixels:.1f}px")
            
            if 'pixels_per_cm' in self.calibration_data:
                height_cm = estimated_height_pixels / self.calibration_data['pixels_per_cm']
                print(f"DEBUG: Using calibration: {height_cm:.1f}cm")
            else:
                if image_height > 0:
                    height_ratio = estimated_height_pixels / image_height
                    height_cm = (height_ratio / 0.8) * 170
                else:
                    height_cm = 170.0
            
                print(f"DEBUG: Using improved estimation: {height_cm:.1f}cm")
            
            final_height = max(150, min(200, height_cm))
            print(f"DEBUG: Final estimated height: {final_height:.1f}cm")
            
            return final_height
            
        except Exception as e:
            print(f"ERROR estimating height from face: {str(e)}")
            return 170.0
    
    def analyze_posture(self, pose_points: Dict) -> Dict:
        analysis = {
            'shoulder_tension': 0,
            'neck_angle': 0,
            'posture_score': 100,
            'issues': []
        }
        
        try:
            left_shoulder = pose_points['left_shoulder']
            right_shoulder = pose_points['right_shoulder']
            
            shoulder_slope = abs(left_shoulder[1] - right_shoulder[1])
            analysis['shoulder_tension'] = min(100, shoulder_slope * 10)
            
            nose_y = pose_points['nose'][1]
            shoulder_avg_y = (left_shoulder[1] + right_shoulder[1]) / 2
            
            if shoulder_avg_y < nose_y + 50: 
                analysis['issues'].append('shoulders_raised')
                analysis['posture_score'] -= 20
            
            if shoulder_slope > 30:
                analysis['issues'].append('uneven_shoulders')
                analysis['posture_score'] -= 15
                
            analysis['posture_score'] = max(0, analysis['posture_score'])
            
            print(f"DEBUG: Posture analysis - score: {analysis['posture_score']}, issues: {analysis['issues']}")
            
        except Exception as e:
            print(f"ERROR in posture analysis: {str(e)}")
        
        return analysis
    
    def calculate_recommended_height(self, user_height: float, posture_data: Dict) -> float:
        print(f"DEBUG: Calculating recommended height for user: {user_height}cm")
        
        elbow_height = user_height * 0.63
        print(f"DEBUG: Elbow height (63%): {elbow_height:.1f}cm")
        
        adjustments = 0
        
        if 'shoulders_raised' in posture_data['issues']:
            adjustments -= 3  
            print("DEBUG: Adjustment for raised shoulders: -3cm")
        
        if 'uneven_shoulders' in posture_data['issues']:
            adjustments -= 2  
            print("DEBUG: Adjustment for uneven shoulders: -2cm")
            
        recommended_height = elbow_height + adjustments
        print(f"DEBUG: Recommended height before limits: {recommended_height:.1f}cm")
        
        min_height = 70
        max_height = user_height * 0.75
        final_height = max(min_height, min(max_height, recommended_height))
        
        print(f"DEBUG: Final recommended height: {final_height:.1f}cm (limits: {min_height}-{max_height:.1f}cm)")
        
        return final_height
    
    def process_image(self, image_data: str, user_height: float = None) -> Dict:
        try:
            print("DEBUG: Starting image processing...")
            
            image = self.decode_base64_image(image_data)
            if image is None:
                print("DEBUG: Invalid image data")
                return {'error': 'Invalid image data'}
            
            h, w = image.shape[:2]
            print(f"DEBUG: Image dimensions: {w}x{h} pixels")
            
            face_landmarks = self.detect_face_landmarks(image)
            pose_points = self.detect_body_pose(image)
            
            if not face_landmarks and not pose_points:
                print("DEBUG: No person detected in image")
                return {'error': 'No person detected'}
            
            if user_height:
                estimated_height = user_height
                print(f"DEBUG: Using provided user height: {estimated_height}cm")
            elif face_landmarks:
                if 'pixels_per_cm' not in self.calibration_data:
                    self.quick_calibration(170.0, face_landmarks, h)
                
                estimated_height = self.estimate_height_from_face(face_landmarks, h)
                print(f"DEBUG: Estimated height from face: {estimated_height}cm")
            else:
                estimated_height = 170.0
                print(f"DEBUG: Using default height: {estimated_height}cm")
            
            posture_analysis = self.analyze_posture(pose_points) if pose_points else {
                'posture_score': 0, 'issues': ['no_pose_detected'], 'shoulder_tension': 0
            }
            
            recommended_height = self.calculate_recommended_height(
                estimated_height, 
                posture_analysis
            )
            
            result = {
                'success': True,
                'user_height': round(estimated_height, 1),
                'recommended_desk_height': round(recommended_height, 1),
                'posture_analysis': posture_analysis,
                'detection_quality': 'good' if face_landmarks else 'limited',
                'should_adjust': True 
            }
            
            print(f"RESULT: Final result - user: {result['user_height']}cm, desk: {result['recommended_desk_height']}cm")
            print(f"RESULT: Posture score: {posture_analysis['posture_score']}, issues: {posture_analysis['issues']}")
            
            return result
            
        except Exception as e:
            print(f"ERROR in process_image: {str(e)}")
            return {
                'success': False,
                'error': f'Processing error: {str(e)}'
            }