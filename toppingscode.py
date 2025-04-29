import time
import RPi.GPIO as GPIO
import numpy as np
import cv2
from picamera2 import Picamera2
from libcamera import controls
import AirtablePancake
at = AirtablePancake.at()
# Camera and GPIO setup
GPIO.setmode(GPIO.BOARD)
GPIO.setwarnings(False)
# Stepper Motor Pins
OUT1, OUT2, OUT3, OUT4 = 12, 11, 13, 15
GPIO.setup([OUT1, OUT2, OUT3, OUT4], GPIO.OUT)
GPIO.output(OUT1, GPIO.LOW)
GPIO.output(OUT2, GPIO.LOW)
GPIO.output(OUT3, GPIO.LOW)
GPIO.output(OUT4, GPIO.LOW)
# Stepper parameters
num_steps = 200
step_delay = 0.03
half_steps = num_steps // 2
step_sequence = [
   [1, 0, 1, 0],
   [0, 1, 1, 0],
   [0, 1, 0, 1],
   [1, 0, 0, 1]
]
def move_motor(sequence, steps, delay):
    for step in range(steps):
        for pin in range(4):
            GPIO.output([OUT1, OUT2, OUT3, OUT4][pin], sequence[step % 4][pin])
        time.sleep(delay)
# Calibrated HSV and Crop Defaults
hue_min, hue_max = 111, 142
sat_min, sat_max = 145, 255
val_min, val_max = 98, 255
crop_width, crop_height = 400, 240
base_speed = 35
# Trackbar callbacks
def update_hue_min(val): global hue_min; hue_min = val
def update_hue_max(val): global hue_max; hue_max = val
def update_sat_min(val): global sat_min; sat_min = val
def update_sat_max(val): global sat_max; sat_max = val
def update_val_min(val): global val_min; val_min = val
def update_val_max(val): global val_max; val_max = val
def update_crop_width(val): global crop_width; crop_width = val + 200
def update_crop_height(val): global crop_height; crop_height = val + 150
def update_speed(val): global base_speed; base_speed = val
# Calibration window
cv2.namedWindow("Calibration")
cv2.createTrackbar("Hue Min", "Calibration", hue_min, 180, update_hue_min)
cv2.createTrackbar("Hue Max", "Calibration", hue_max, 180, update_hue_max)
cv2.createTrackbar("Sat Min", "Calibration", sat_min, 255, update_sat_min)
cv2.createTrackbar("Sat Max", "Calibration", sat_max, 255, update_sat_max)
cv2.createTrackbar("Val Min", "Calibration", val_min, 255, update_val_min)
cv2.createTrackbar("Val Max", "Calibration", val_max, 255, update_val_max)
cv2.createTrackbar("Crop Width", "Calibration", crop_width - 200, 400, update_crop_width)
cv2.createTrackbar("Crop Height", "Calibration", crop_height - 150, 200, update_crop_height)
cv2.createTrackbar("Speed", "Calibration", base_speed, 50, update_speed)
# Camera setup
picam2 = Picamera2()
picam2.set_controls({"AfMode": controls.AfModeEnum.Continuous})
picam2.start()
time.sleep(1)
# Detection and cooldown timers
detection_start = None
cooldown_until = 0
motor_triggered = False
try:
    while True:
        current_time = time.time()
        if current_time < cooldown_until:
            time.sleep(0.5)
            continue
        image = picam2.capture_array("main")
        height, width, _ = image.shape
        center_x, center_y = width // 2, height // 2
        crop_img = image[
            center_y - crop_height // 2 : center_y + crop_height // 2,
            center_x - crop_width // 2 : center_x + crop_width // 2
        ]
        hsv = cv2.cvtColor(crop_img, cv2.COLOR_BGR2HSV)
        lower_color = np.array([hue_min, sat_min, val_min])
        upper_color = np.array([hue_max, sat_max, val_max])
        mask = cv2.inRange(hsv, lower_color, upper_color)
        kernel = np.ones((3, 3), np.uint8)
        mask = cv2.morphologyEx(mask, cv2.MORPH_OPEN, kernel)
        mask = cv2.morphologyEx(mask, cv2.MORPH_CLOSE, kernel)
        contours, _ = cv2.findContours(mask, cv2.RETR_TREE, cv2.CHAIN_APPROX_SIMPLE)
        if contours:
            if detection_start is None:
                detection_start = current_time
            elif current_time - detection_start >= 10 and not motor_triggered:
                print("Red detected for 10 seconds — activating motor!")
                move_motor(step_sequence, half_steps, step_delay)
                time.sleep(1)
                move_motor(step_sequence[::-1], half_steps, step_delay)
                at.changeValue("Choco Chips Status", 99)
                motor_triggered = True
                cooldown_until = current_time + 30  # 30 second delay after dispensing
        else:
            detection_start = None
            motor_triggered = False
        # Show image and mask
        cv2.imshow("Processed Image", crop_img)
        cv2.imshow("Color Mask", mask)
        cv2.waitKey(1)
except KeyboardInterrupt:
    print("Exiting...")
    GPIO.cleanup()
    cv2.destroyAllWindows()

