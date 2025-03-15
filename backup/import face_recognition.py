import face_recognition
import cv2
import numpy as np

# Load an image to recognize faces from
image_path = "path_to_your_image.jpg"  # Replace with the path to your image
image = cv2.imread(image_path)

# Convert the image from BGR (OpenCV format) to RGB (face_recognition format)
rgb_image = cv2.cvtColor(image, cv2.COLOR_BGR2RGB)

# Find all face locations and face encodings in the image
face_locations = face_recognition.face_locations(rgb_image)
face_encodings = face_recognition.face_encodings(rgb_image, face_locations)

print(f"Found {len(face_locations)} face(s) in this image.")

# Loop over each face found in the image
for face_location, face_encoding in zip(face_locations, face_encodings):
    top, right, bottom, left = face_location
    print(f"Face location: Top: {top}, Right: {right}, Bottom: {bottom}, Left: {left}")
    
    # Draw a rectangle around each face
    cv2.rectangle(image, (left, top), (right, bottom), (0, 255, 0), 2)
    
    # Compare the face encoding with known faces (if you have any known faces saved)
    # Example: Compare with a pre-saved encoding (you can save encodings to compare later)
    # If you have a reference image's encoding, you can compare them with:
    # matches = face_recognition.compare_faces([known_face_encoding], face_encoding)

# Display the image with detected faces
cv2.imshow("Facial Recognition", image)
cv2.waitKey(0)
cv2.destroyAllWindows()
