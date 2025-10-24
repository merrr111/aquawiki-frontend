import sys
from PIL import Image
import imagehash

if len(sys.argv) < 2:
    print("")
    sys.exit(1)

image_path = sys.argv[1]

try:
    img = Image.open(image_path)
    phash = imagehash.phash(img)
    print(str(phash))
except Exception as e:
    print("")
