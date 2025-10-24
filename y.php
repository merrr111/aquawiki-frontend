import sys
import json
import mysql.connector
import numpy as np
from tensorflow.keras.applications.mobilenet_v2 import MobileNetV2, preprocess_input
from tensorflow.keras.preprocessing import image

# Load MobileNetV2
model = MobileNetV2(weights="imagenet", include_top=False, pooling="avg")

def get_db_connection():
    return mysql.connector.connect(
        host="localhost",
        user="root",
        password="",
        database="fishencyclopedia"
    )

def get_embedding(img_path):
    img = image.load_img(img_path, target_size=(224, 224))
    x = image.img_to_array(img)
    x = np.expand_dims(x, axis=0)
    x = preprocess_input(x)
    return model.predict(x)[0]

def cosine_similarity(vec1, vec2):
    dot = np.dot(vec1, vec2)
    norm1 = np.linalg.norm(vec1)
    norm2 = np.linalg.norm(vec2)
    return dot / (norm1 * norm2 + 1e-10)

def find_similar_fish(image_path, threshold=0.5):
    query_emb = get_embedding(image_path)

    conn = get_db_connection()
    cursor = conn.cursor()
    cursor.execute("SELECT id, name, image_url, image_male_url, embedding, embedding_male FROM fishes")
    fishes = cursor.fetchall()
    conn.close()

    best_match = None
    best_score = -1
    match_type = None

    for fish in fishes:
        fish_id, fish_name, image_url, image_male_url, emb_str, emb_male_str = fish

        # Female embedding
        if emb_str:
            try:
                fish_emb = np.array(json.loads(emb_str))
                score = cosine_similarity(query_emb, fish_emb)
                if score > best_score:
                    best_score = score
                    best_match = {
                        "id": fish_id,
                        "name": fish_name,
                        "matched_image_url": image_url,
                        "match_type": "female"
                    }
            except:
                pass

        # Male embedding
        if emb_male_str:
            try:
                fish_emb_male = np.array(json.loads(emb_male_str))
                score = cosine_similarity(query_emb, fish_emb_male)
                if score > best_score:
                    best_score = score
                    best_match = {
                        "id": fish_id,
                        "name": fish_name,
                        "matched_image_url": image_male_url or image_url,
                        "match_type": "male"
                    }
            except:
                pass

    return best_match if best_score > threshold else None

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({"error": "Usage: python identify_fish.py <image_path>"}))
        sys.exit(1)

    image_path = sys.argv[1]
    result = find_similar_fish(image_path)
    print(json.dumps({"matched_fish": result}, ensure_ascii=False))
