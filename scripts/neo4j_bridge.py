#!/usr/bin/env python3
"""
Neo4j Python Bridge for Laravel PHP
Bypasses broken laudis/neo4j-php-client with working Python driver
"""
import sys
import json
import os
from neo4j import GraphDatabase

URI = os.getenv('NEO4J_URI', 'bolt://localhost:7687')
USER = os.getenv('NEO4J_USER', 'neo4j')
PASSWORD = os.getenv('NEO4J_PASSWORD', 'password')

def run_query(cypher_query, parameters=None):
    try:
        driver = GraphDatabase.driver(URI, auth=(USER, PASSWORD))
        with driver.session() as session:
            result = session.run(cypher_query, parameters or {})
            records = []
            for record in result:
                records.append({k: v for k, v in record.items()})
            summary = result.consume()
            return {
                "success": True,
                "records": records,
                "counters": {
                    "nodes_created": summary.counters.nodes_created if hasattr(summary, 'counters') else 0,
                    "relationships_created": summary.counters.relationships_created if hasattr(summary, 'counters') else 0,
                    "properties_set": summary.counters.properties_set if hasattr(summary, 'counters') else 0
                }
            }
    except Exception as e:
        return {"success": False, "error": str(e)}
    finally:
        if 'driver' in locals():
            driver.close()

if __name__ == "__main__":
    if len(sys.argv) < 2:
        print(json.dumps({"success": False, "error": "No query provided"}))
        sys.exit(1)
    
    cypher = sys.argv[1]
    params = json.loads(sys.argv[2]) if len(sys.argv) > 2 else {}
    
    result = run_query(cypher, params)
    print(json.dumps(result))
