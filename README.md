# PHP CSV Import Script

This is a standalone PHP script to import a large CSV of employee records into a MySQL database, handling new records, updates, duplicates, and conflicts.

---

## Features

- Stream based CSV processing : memory stays under 4MB even for 80,000+ rows  
- Detects new employees -> inserts them  
- Detects updated employees -> logs conflict and updates record  
- Skips exact duplicates silently  
- Logs conflicts to `conflicts.log`  
- Prints a summary after import

---

## Requirements

- PHP 8.0+ (tested with 8.3)  
- MySQL 
- CLI access 

---

## Installation & Setup

1. **Clone the repository** (or download the folder):

```bash
git clone https://github.com/sudeeptharu232/csv.git