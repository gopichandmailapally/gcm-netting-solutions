#!/usr/bin/env python3
"""
Sanitize All Reviews and FAQs for GCM Netting Solutions (Chennai)
Replaces all Hyderabad localities with authentic Chennai localities across:
1. data/reviews/*.json
2. data/faqs/*.json
3. Phone numbers and template references in faqs.php, blog.php, blogs.php, videos.php, reviews.php
"""

import os
import json
import glob
import re

BASE_DIR = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
REVIEWS_DIR = os.path.join(BASE_DIR, 'data', 'reviews')
FAQS_DIR = os.path.join(BASE_DIR, 'data', 'faqs')

CHENNAI_LOCALITIES = [
    "Anna Nagar", "T Nagar", "Velachery", "Adyar", "Tambaram", "Porur", 
    "Mylapore", "Nungambakkam", "Guindy", "Besant Nagar", "Sholinganallur", 
    "Perungudi", "Thoraipakkam", "Medavakkam", "Chromepet", "Pallavaram", 
    "Ambattur", "Avadi", "Poonamallee", "Kilpauk", "Kodambakkam", "Alwarpet", 
    "Thiruvanmiyur", "Madipakkam", "Saidapet", "Royapettah", "Kotturpuram", 
    "Perambur", "Mogappair", "Navalur", "Egmore", "Triplicane", "Chetpet", 
    "Gopalapuram", "Vadapalani", "Ashok Nagar", "Koyambedu", "KK Nagar", 
    "Manapakkam", "Ramapuram", "Valasaravakkam", "Nandanam", "Teynampet", 
    "Choolaimedu", "Villivakkam", "Kolathur", "Korattur", "Puzhal", 
    "Madhavaram", "Thirumullaivoyal", "Iyyappanthangal", "Mangadu", 
    "Kattupakkam", "Kundrathur", "Ullagaram", "Puzhuthivakkam", "Nanganallur", 
    "Meenambakkam", "Chitlapakkam", "Selaiyur"
]

HYDERABAD_AREAS = [
    "Malkajgiri", "Champapet", "Boduppal", "Alwal", "Mailardevpally", "Charminar",
    "Saroornagar", "Suraram", "Bollaram", "Golconda", "Himayath Nagar", "Himayat Nagar",
    "Mozamjahi Market", "Gaddiannaram", "SR Nagar", "Hayathnagar", "Toli Chowki",
    "Tolichowki", "Mansoorabad", "Gudimalkapur", "Nanakramguda", "Ghatkesar", "RC Puram",
    "Bharat Nagar", "Peerzadiguda", "Old Alwal", "Habsiguda", "Shankarpally", "Tarnaka",
    "Sanath Nagar", "Serilingampally", "Bandlaguda Jagir", "Srinagar Colony", "Gowlidoddy",
    "Trimulgherry", "Domalguda", "Lallaguda", "Moinabad", "Kothapet", "Khajaguda", "Ecil",
    "ECIL", "Mamidipally", "Patancheru", "New Bowenpally", "Borabanda", "Upparpally",
    "Mallapur", "Hyderguda", "Moulali", "AS Rao Nagar", "Barkas", "Bahadurpura", "Abids",
    "Padma Rao Nagar", "Vanasthalipuram", "Nagaram", "Afzalgunj", "Jeedimetla",
    "Tukaram Gate", "Uppal", "Bowenpally", "Karwan", "Musheerabad", "Pragathi Nagar",
    "Lingojiguda", "Koh-e-Fiza", "Lingampally", "Kapra", "Nizamabad", "Moosapet",
    "Mahendra Hills", "Hakimpet", "Chintal", "Koti", "Moazzam Jahi Market", "Kismathpur",
    "Nacharam", "Madinaguda", "Sanjeeva Reddy Nagar", "Marredpally", "Rani Gunj",
    "Neredmet", "Kismatpur", "Dammaiguda", "Ramgopalpet", "Nagole", "Balanagar",
    "Dundigal", "Nallakunta", "Beeramguda", "BHEL", "Falaknuma", "Yousufguda",
    "Hastinapuram", "Shamirpet", "Narsingi", "Adikmet", "Ramanthapur", "Patelguda",
    "Erragadda", "Vijayanagar Colony", "Chengicherla", "Rajendranagar", "Chandrayangutta",
    "Kowkoor", "Sainikpuri", "Santosh Nagar", "Katedan", "Narayanguda", "Quthbullapur",
    "Salar Jung Museum", "Lal Bahadur Nagar", "Masab Tank", "Jawahar Nagar", "Malakpet",
    "Red Hills", "Hafeezpet", "Mettuguda", "Langar Houz", "Gandhamguda", "Kachiguda",
    "Ibrahimpatnam", "Mahankali", "Medchal", "Chikkadpally", "Bandlaguda", "Begum Bazaar",
    "Safilguda", "Kingsway", "Vidyanagar", "Paradise", "Rethibowli", "Lakdikapul",
    "Shahalibanda", "IDA Jeedimetla", "Nehru Nagar", "Almasguda", "Meerpet", "Mangalhat",
    "Vikrampuri", "Tank Bund", "Zaheerabad", "Gachibowli", "Madhapur", "Kukatpally",
    "Kondapur", "Jubilee Hills", "Banjara Hills", "Secunderabad", "Ameerpet", "Begumpet",
    "Miyapur", "Manikonda", "Kompally", "Bachupally", "Dilsukhnagar", "LB Nagar", "KPHB"
]

def sanitize_reviews():
    review_files = glob.glob(os.path.join(REVIEWS_DIR, '*.json'))
    print(f"Processing {len(review_files)} review JSON files...")
    updated_count = 0
    
    for idx, f in enumerate(sorted(review_files)):
        basename = os.path.basename(f)
        if basename in ['stats.json', 'auto-settings.json', 'index.json']:
            continue
        try:
            with open(f, 'r', encoding='utf-8') as fp:
                data = json.load(fp)
        except Exception as e:
            continue
            
        old_loc = data.get('location', '')
        
        # Check if location is already a valid Chennai locality
        is_already_chennai = any(loc.lower() in old_loc.lower() for loc in CHENNAI_LOCALITIES)
        
        if is_already_chennai:
            # Clean up formatting to standard Chennai name
            assigned_loc = next(loc for loc in CHENNAI_LOCALITIES if loc.lower() in old_loc.lower())
        else:
            # Pick a deterministic Chennai locality based on review ID / index
            assigned_loc = CHENNAI_LOCALITIES[idx % len(CHENNAI_LOCALITIES)]
        
        data['location'] = assigned_loc
        
        # Now sanitize review_text
        text = data.get('review_text', '')
        if text:
            # Replace old location references
            for hyd_area in HYDERABAD_AREAS:
                # Case-insensitive whole-word replacement
                pattern = re.compile(r'\b' + re.escape(hyd_area) + r'\b', re.IGNORECASE)
                text = pattern.sub(assigned_loc, text)
            
            # Clean up general references
            text = re.sub(r'\bHyderabad\b', 'Chennai', text, flags=re.IGNORECASE)
            text = re.sub(r'\byderabad\b', 'Chennai', text, flags=re.IGNORECASE)
            text = re.sub(r'\bTelangana\b', 'Tamil Nadu', text, flags=re.IGNORECASE)
            text = re.sub(r'\bSecunderabad\b', 'Tambaram', text, flags=re.IGNORECASE)
            
            # Clean any double replacement like "Anna Nagar, Chennai, Chennai"
            text = re.sub(r'Chennai,\s*Chennai', 'Chennai', text, flags=re.IGNORECASE)
            
            data['review_text'] = text
            
        # Write back
        with open(f, 'w', encoding='utf-8') as fp:
            json.dump(data, fp, indent=4, ensure_ascii=False)
        updated_count += 1
        
    print(f"Sanitized {updated_count} review files.")

def sanitize_faqs():
    faq_files = glob.glob(os.path.join(FAQS_DIR, '*.json'))
    print(f"Processing {len(faq_files)} FAQ JSON files...")
    updated_faqs = 0
    
    for f in faq_files:
        basename = os.path.basename(f)
        if basename in ['index.json']:
            continue
        try:
            with open(f, 'r', encoding='utf-8') as fp:
                data = json.load(fp)
        except Exception:
            continue
            
        modified = False
        
        # Check question
        q = data.get('question', '')
        if q:
            new_q = q
            for hyd_area in HYDERABAD_AREAS:
                new_q = re.sub(r'\b' + re.escape(hyd_area) + r'\b', 'Anna Nagar', new_q, flags=re.IGNORECASE)
            new_q = re.sub(r'\bHyderabad\b', 'Chennai', new_q, flags=re.IGNORECASE)
            new_q = re.sub(r'\byderabad\b', 'Chennai', new_q, flags=re.IGNORECASE)
            new_q = re.sub(r'\bTelangana\b', 'Tamil Nadu', new_q, flags=re.IGNORECASE)
            if new_q != q:
                data['question'] = new_q
                modified = True
                
        # Check answer
        ans = data.get('answer', '')
        if ans:
            new_ans = ans
            for hyd_area in HYDERABAD_AREAS:
                new_ans = re.sub(r'\b' + re.escape(hyd_area) + r'\b', 'Anna Nagar', new_ans, flags=re.IGNORECASE)
            new_ans = re.sub(r'\bHyderabad\b', 'Chennai', new_ans, flags=re.IGNORECASE)
            new_ans = re.sub(r'\byderabad\b', 'Chennai', new_ans, flags=re.IGNORECASE)
            new_ans = re.sub(r'\bTelangana\b', 'Tamil Nadu', new_ans, flags=re.IGNORECASE)
            if new_ans != ans:
                data['answer'] = new_ans
                modified = True
                
        if modified:
            with open(f, 'w', encoding='utf-8') as fp:
                json.dump(data, fp, indent=4, ensure_ascii=False)
            updated_faqs += 1
            
    print(f"Sanitized {updated_faqs} FAQ files.")

def fix_code_files():
    targets = ['reviews.php', 'faqs.php', 'blog.php', 'blogs.php', 'videos.php']
    for t in targets:
        fpath = os.path.join(BASE_DIR, t)
        if not os.path.exists(fpath):
            continue
        with open(fpath, 'r', encoding='utf-8') as fp:
            content = fp.read()
            
        new_content = content.replace('+91 91213 99234', '+91 99123 99224')
        new_content = new_content.replace('91213 99234', '99123 99224')
        new_content = new_content.replace('9121399234', '9912399224')
        
        if new_content != content:
            with open(fpath, 'w', encoding='utf-8') as fp:
                fp.write(new_content)
            print(f"Updated phone numbers in {t}")

if __name__ == '__main__':
    sanitize_reviews()
    sanitize_faqs()
    fix_code_files()
    print("All reviews, FAQs, and template files sanitized successfully!")
