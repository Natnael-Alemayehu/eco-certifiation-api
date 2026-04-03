# Verdant Collective -- Eco-Certification API

A headless Wordpress plugin that registers a "Certifications" Custom Post Type
and exposes all certification data via the wordpress REST SPO. Frontend access
is fully disabled = this installation acts strictly as a data provider.

---------------

## Installation

1. Copy the `verdant-certifications` folder into your WORDPRESS plugin directory:
```
    /wp-content/plugins/verdant-certifications/
```
2. Log in to your wordpress admin at `/wp-admin`
3. Go to **Plugins -> Installed Plugins**.
4. Find **Verdant Collective - Eco-Certification API** and click **Activate**.
5. Three sample certifications are automatically created on activation. 
    Find them under **Certifications** in the admin sidebar. 
6. Go to **settings -> Permalinks** and click **Save Changes** to flush 
    rewrite rules so the REST API routes register correctly.

---------------

### Managing Certifications

1. In the WP admin sidebar click **Certifications -> Add New**
2. Enter the certification name as the title
3. Fill in the **Certification Details** meta box:
    - Organization Logo URL
    - Certification Authority
    - Impact Category (dropdown)
    - Renewal Frequency in months
4. Click **Publish**

---------------

## API Endpoints

See `API-DOCUMENTATION.md` for full endpoint reference and example responses. 

Quick regerence:
```
GET /wp-json/verdant/v1/certifications
GET /wp-json/verdant/v1/certifications?category=energy
GET /wp-json/verdant/v1/certifications?category=waste
GET /wp-json/verdant/v1/certifications?category=fair-labor
GET /wp-json/verdant/v1/certifications?category=materials
```

---------------

## File Structure
```
eco-certifiation-api
├── API-DOCUMENTATION.md
├── README.md
└── verdant-certifications.php
```