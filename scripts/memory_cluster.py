
import sys
import json
import numpy as np
from sklearn.decomposition import PCA
from sklearn.cluster import KMeans

def process_memories():
    try:
        # Read input from stdin
        in_data = sys.stdin.read()
        if not in_data:
            print(json.dumps([]))
            return

        memories = json.loads(in_data)
        
        if len(memories) < 3:
            # Not enough data for PCA
            print(json.dumps(memories))
            return

        # Extract embeddings
        embeddings = [m['embedding'] for m in memories]
        ids = [m['id'] for m in memories]
        
        # Convert to numpy array
        X = np.array(embeddings)
        
        # PCA to 2D
        pca = PCA(n_components=2)
        X_r = pca.fit_transform(X)
        
        # Clustering (3-5 clusters depending on size)
        n_clusters =min(5, len(memories) // 2) if len(memories) > 5 else 1
        kmeans = KMeans(n_clusters=n_clusters, random_state=42)
        labels = kmeans.fit_predict(X)
        
        # Combine results
        results = []
        for i, memory in enumerate(memories):
            results.append({
                'id': memory['id'],
                'content': memory['content'], # Simplified content
                'created_at': memory['created_at'],
                'x': float(X_r[i][0]),
                'y': float(X_r[i][1]),
                'cluster': int(labels[i])
            })
            
        print(json.dumps(results))
        
    except Exception as e:
        # Fallback empty or error
        sys.stderr.write(str(e))
        print(json.dumps([]))

if __name__ == "__main__":
    process_memories()
