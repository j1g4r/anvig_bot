import sys
import argparse
import base64
import json
import os
from io import BytesIO

# Try to import dependencies, with helpful error messages
try:
    import pyautogui
    from PIL import Image
    import pyscreeze
except ImportError as e:
    print(json.dumps({"error": f"Missing dependency: {str(e)}", "hint": "Run: pip install pyautogui pillow pyscreeze"}))
    sys.exit(1)

def capture_screen():
    try:
        # Capture the entire screen
        screenshot = pyautogui.screenshot()
        
        # Save to buffer
        buffered = BytesIO()
        screenshot.save(buffered, format="JPEG", quality=70)
        
        # Encode as base64
        img_str = base64.b64encode(buffered.getvalue()).decode()
        
        return {"success": True, "image": img_str, "width": screenshot.width, "height": screenshot.height}
    except Exception as e:
        return {"success": False, "error": str(e)}

def mouse_action(action, x=None, y=None):
    try:
        if action == "move":
            pyautogui.moveTo(x, y)
        elif action == "click":
            pyautogui.click(x, y)
        elif action == "double_click":
            pyautogui.doubleClick(x, y)
        elif action == "right_click":
            pyautogui.rightClick(x, y)
        else:
            return {"success": False, "error": f"Unknown mouse action: {action}"}
        
        return {"success": True}
    except Exception as e:
        return {"success": False, "error": str(e)}

def keyboard_action(text=None, key=None):
    try:
        if text:
            pyautogui.write(text, interval=0.1)
        elif key:
            pyautogui.press(key)
        else:
            return {"success": False, "error": "No text or key provided for keyboard action"}
        
        return {"success": True}
    except Exception as e:
        return {"success": False, "error": str(e)}

def main():
    parser = argparse.ArgumentParser(description="Jerry Desktop Sentience Bridge")
    parser.add_argument("--action", required=True, choices=["capture", "mouse", "keyboard", "test"])
    parser.add_argument("--subaction", help="Sub-action for mouse/keyboard")
    parser.add_argument("--x", type=int, help="X coordinate")
    parser.add_argument("--y", type=int, help="Y coordinate")
    parser.add_argument("--text", help="Text to type")
    parser.add_argument("--key", help="Key to press")
    
    args = parser.parse_args()
    
    result = {"success": False, "error": "No action performed"}
    
    if args.action == "test":
        result = {"success": True, "message": "Dependencies verified", "screen_size": list(pyautogui.size())}
    elif args.action == "capture":
        result = capture_screen()
    elif args.action == "mouse":
        result = mouse_action(args.subaction, args.x, args.y)
    elif args.action == "keyboard":
        result = keyboard_action(args.text, args.key)
        
    print(json.dumps(result))

if __name__ == "__main__":
    # Ensure accessibility permissions on macOS
    # PyAutoGUI will often fail silently or throw an error if not permitted
    main()
