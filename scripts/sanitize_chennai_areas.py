#!/usr/bin/env python3
"""
Sanitizes and replaces all legacy Hyderabad area references with genuine Chennai localities
across all web-facing PHP and JSON content files.
"""

import os
import re

MAPPINGS = [
    # Multi-area strings
    ("Gachibowli, Jubilee Hills, Banjara Hills, Madhapur, Kondapur, HITEC City, Tambaram, and Kukatpally",
     "Anna Nagar, T Nagar, Velachery, Adyar, Tambaram, Porur, Mylapore, and Nungambakkam"),
    ("Gachibowli, Jubilee Hills, Banjara Hills, Madhapur, Kondapur, Hitech City, and Tambaram",
     "Anna Nagar, T Nagar, Velachery, Adyar, Tambaram, Porur, and Besant Nagar"),
    ("Gachibowli, Jubilee Hills, Banjara Hills, Madhapur, Kukatpally, and Tambaram",
     "Anna Nagar, T Nagar, Velachery, Adyar, Tambaram, and Porur"),
    ("Gachibowli, Madhapur, Jubilee Hills, Banjara Hills, Kukatpally, and Tambaram",
     "Anna Nagar, T Nagar, Velachery, Adyar, Tambaram, and Porur"),
    ("Gachibowli, Madhapur, Jubilee Hills, Banjara Hills, Tambaram, and Kukatpally",
     "Anna Nagar, T Nagar, Velachery, Adyar, Tambaram, and Porur"),
    ("Gachibowli, Madhapur, Jubilee Hills, Banjara Hills, Tambaram, and beyond",
     "Anna Nagar, T Nagar, Velachery, Adyar, Tambaram, and beyond"),
    ("Gachibowli, Madhapur, Banjara Hills, Jubilee Hills, Tambaram, and Kukatpally",
     "Anna Nagar, T Nagar, Velachery, Adyar, Tambaram, and Porur"),
    ("Gachibowli, Madhapur, Banjara Hills, Jubilee Hills, Tambaram, Kukatpally, and Ameerpet",
     "Anna Nagar, T Nagar, Velachery, Adyar, Tambaram, Porur, and Kodambakkam"),
    ("Gachibowli, Hitech City, Kukatpally, Ameerpet, Dilsukhnagar",
     "Anna Nagar, T Nagar, Velachery, Adyar, Tambaram"),
    ("Gachibowli, Hitech City, Banjara Hills, Jubilee Hills",
     "Anna Nagar, T Nagar, Velachery, Adyar"),
    ("from Gachibowli to Tambaram, and Banjara Hills to Kukatpally",
     "from Anna Nagar to Tambaram, and Adyar to Porur"),
    ("from Gachibowli to Tambaram and beyond",
     "from Anna Nagar to Tambaram and beyond"),
    ("Gachibowli to Tambaram", "Anna Nagar to Tambaram"),
    ("Banjara Hills to Kukatpally", "Adyar to Porur"),
    ("Banjara Hills, Jubilee Hills, Gachibowli, Madhapur, Kukatpally",
     "Anna Nagar, T Nagar, Velachery, Adyar, Porur"),
    ("Banjara Hills, Jubilee Hills, Gachibowli, Madhapur, Tambaram",
     "Anna Nagar, T Nagar, Velachery, Adyar, Tambaram"),
    ("Banjara Hills, Jubilee Hills, and Gachibowli",
     "Anna Nagar, T Nagar, and Velachery"),
    ("Banjara Hills, Jubilee Hills", "Anna Nagar, Adyar"),
    ("Jubilee Hills, Banjara Hills", "Adyar, Besant Nagar"),
    ("Jubilee Hills, Gachibowli, Tambaram, Kondapur, Madhapur, Uppal",
     "Anna Nagar, T Nagar, Velachery, Adyar, Tambaram, Porur"),
    ("Jubilee Hills, Gachibowli, Tambaram, Banjara Hills, Kondapur",
     "Anna Nagar, T Nagar, Velachery, Adyar, Tambaram"),
    ("Gachibowli, Kondapur, or Jubilee Hills",
     "Anna Nagar, Velachery, or Adyar"),
    ("Gachibowli, Jubilee Hills, Tambaram",
     "Anna Nagar, Adyar, Tambaram"),
    ("Begumpet, Gachibowli, Jubilee Hills, Banjara Hills, Tambaram",
     "Anna Nagar, T Nagar, Velachery, Adyar, Tambaram"),
    ("Tambaram, Gachibowli, Madhapur, Jubilee Hills, Banjara Hills",
     "Tambaram, Anna Nagar, T Nagar, Velachery, Adyar"),
    ("Gachibowli, Jubilee Hills", "Anna Nagar, Adyar"),
    ("Gachibowli, Madhapur, Hitech City, Kukatpally",
     "Anna Nagar, T Nagar, Velachery, Porur"),
    ("Gachibowli, Madhapur, Kukatpally", "Anna Nagar, T Nagar, Porur"),
    ("Gachibowli, Kondapur, Madhapur, Kukatpally", "Anna Nagar, Velachery, T Nagar, Porur"),
    ("Gachibowli, Madhapur", "Anna Nagar, T Nagar"),
    ("Gachibowli, Chennai", "Anna Nagar, Chennai"),
    ("Kukatpally, Chennai", "Porur, Chennai"),
    ("Madhapur, Chennai", "T Nagar, Chennai"),
    ("Kondapur, Chennai", "Velachery, Chennai"),
    ("Jubilee Hills, Chennai", "Adyar, Chennai"),
    ("Banjara Hills, Chennai", "Besant Nagar, Chennai"),
    ("in Gachibowli", "in Anna Nagar"),
    ("in Kukatpally", "in Porur"),
    ("in Madhapur", "in T Nagar"),
    ("in Kondapur", "in Velachery"),
    ("in Banjara Hills", "in Besant Nagar"),
    ("in Jubilee Hills", "in Adyar"),
    ("in Hitec City", "in Sholinganallur"),
    ("in Hitech City", "in Sholinganallur"),
    ("in Miyapur", "in Ambattur"),
    ("in Kompally", "in Madipakkam"),
    ("in Manikonda", "in Mylapore"),
    ("in Begumpet", "in Nungambakkam"),
    ("in Ameerpet", "in Kodambakkam"),
    ("in Dilsukhnagar", "in Pallavaram"),
    ("in LB Nagar", "in Avadi"),
    ("in Uppal", "in Poonamallee"),
    ("in Nizampet", "in Kilpauk"),
    ("in Attapur", "in Saidapet"),
    ("in Somajiguda", "in Alwarpet"),
    ("in KPHB Colony", "in Thiruvanmiyur"),
    ("in KPHB", "in Thiruvanmiyur"),
    ("around Gachibowli", "around Anna Nagar"),
    ("across Gachibowli", "across Anna Nagar"),
    ("for Gachibowli", "for Anna Nagar"),
    ("Gachibowli", "Anna Nagar"),
    ("Madhapur", "T Nagar"),
    ("Kondapur", "Velachery"),
    ("Jubilee Hills", "Adyar"),
    ("Banjara Hills", "Besant Nagar"),
    ("Kukatpally", "Porur"),
    ("Hitec City", "Sholinganallur"),
    ("Hitech City", "Sholinganallur"),
    ("HITEC City", "Sholinganallur"),
    ("Miyapur", "Ambattur"),
    ("Manikonda", "Mylapore"),
    ("Begumpet", "Nungambakkam"),
    ("Ameerpet", "Kodambakkam"),
    ("Chandanagar", "Chromepet"),
    ("Nallagandla", "Medavakkam"),
    ("Tellapur", "Perungudi"),
    ("Dilsukhnagar", "Pallavaram"),
    ("LB Nagar", "Avadi"),
    ("Kompally", "Madipakkam"),
    ("Nizampet", "Kilpauk"),
    ("Bachupally", "Royapettah"),
    ("Attapur", "Saidapet"),
    ("Somajiguda", "Alwarpet"),
    ("KPHB Colony", "Thiruvanmiyur"),
    ("KPHB", "Thiruvanmiyur"),
    ("Secunderabad", "Tambaram"),
    ("Tolichowki", "Kotturpuram"),
    ("Mehdipatnam", "Mogappair"),
    ("yderabad", "Chennai"),
    ("Hyderabad", "Chennai")
]

TARGET_EXTENSIONS = ('.php', '.json', '.html')
EXCLUDE_DIRS = {'.git', 'deploy', 'sql', 'cron', 'tests'}

def sanitize_file(file_path):
    try:
        with open(file_path, 'r', encoding='utf-8', errors='ignore') as f:
            content = f.read()
    except Exception as e:
        return 0

    original = content
    for old, new in MAPPINGS:
        content = content.replace(old, new)
        # Also try lowercase if applicable
        # content = re.sub(re.escape(old), new, content, flags=re.IGNORECASE)

    if content != original:
        with open(file_path, 'w', encoding='utf-8') as f:
            f.write(content)
        return 1
    return 0

def main():
    base_dir = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    count = 0
    modified = []

    for root, dirs, files in os.walk(base_dir):
        # Prune excluded dirs
        dirs[:] = [d for d in dirs if d not in EXCLUDE_DIRS]

        for file in files:
            if file.endswith(TARGET_EXTENSIONS):
                # Don't modify database dump files or config seeds
                if 'MASTER' in file or 'COMPLETE-DATABASE' in file or 'new_areas.json' in file or 'new_keywords.json' in file:
                    continue
                full_path = os.path.join(root, file)
                if sanitize_file(full_path):
                    count += 1
                    rel = os.path.relpath(full_path, base_dir)
                    modified.append(rel)

    print(f"Successfully sanitized {count} files:")
    for m in modified[:20]:
        print(f"  - {m}")
    if len(modified) > 20:
        print(f"  ... and {len(modified) - 20} more files.")

if __name__ == '__main__':
    main()
