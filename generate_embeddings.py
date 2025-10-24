import os
import json
import mysql.connector
import numpy as np
from tensorflow.keras.applications.mobilenet_v2 import MobileNetV2, preprocess_input
from tensorflow.keras.preprocessing import image

# Load MobileNetV2
model = MobileNetV2(weights="imagenet", include_top=False, pooling="avg")

DB_CONFIG = {
    "host": "localhost",
    "user": "root",
    "password": "",
    "database": "fishencyclopedia"
}

def get_db_connection():
    return mysql.connector.connect(**DB_CONFIG)

def get_embedding(img_path):
    img = image.load_img(img_path, target_size=(224, 224))
    x = image.img_to_array(img)
    x = np.expand_dims(x, axis=0)
    x = preprocess_input(x)
    return model.predict(x)[0].tolist()  # convert to list for JSON

def precompute_embeddings():
    conn = get_db_connection()
    cursor = conn.cursor(dictionary=True)

    cursor.execute("SELECT id, image_url, image_male_url, embedding, embedding_male FROM fishes")
    fishes = cursor.fetchall()

    for fish in fishes:
        fish_id = fish["id"]

        # Female embedding
        if not fish["embedding"] and fish["image_url"]:
            if os.path.exists(fish["image_url"]):
                emb = get_embedding(fish["image_url"])
                cursor.execute(
                    "UPDATE fishes SET embedding=%s WHERE id=%s",
                    (json.dumps(emb), fish_id)
                )
                print(f"Processed female embedding for {fish_id}")

        # Male embedding
        if not fish["embedding_male"] and fish["image_male_url"]:
            if os.path.exists(fish["image_male_url"]):
                emb_male = get_embedding(fish["image_male_url"])
                cursor.execute(
                    "UPDATE fishes SET embedding_male=%s WHERE id=%s",
                    (json.dumps(emb_male), fish_id)
                )
                print(f"Processed male embedding for {fish_id}")

    conn.commit()
    cursor.close()
    conn.close()
    print("✅ Finished saving embeddings to database.")

if __name__ == "__main__":
    precompute_embeddings()
