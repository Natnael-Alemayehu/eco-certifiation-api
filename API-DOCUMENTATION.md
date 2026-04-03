# Verdant Collective -- Eco-Certification API Documentation

Base URL: `http://eco-certifiation.local`

all endpoints are public and read-only. No authentication required for GET requests

---------------

## Endpoints 

### 1. Get all certifications (Wordpress native endpoints)

Returns all certifications with full custom field data.

```
GET /wp-json/verdant/v1/
```

**Example Response:**
```json
[
    {
        "id": 28,
        "date": "2026-04-03T09:59:28",
        "date_gmt": "2026-04-03T09:59:28",
        "guid": {
            "rendered": "http://eco-certifiation.local/?post_type=certification&#038;p=28"
        },
        "modified": "2026-04-03T09:59:28",
        "modified_gmt": "2026-04-03T09:59:28",
        "slug": "new-certification",
        "status": "publish",
        "type": "certification",
        "link": "http://eco-certifiation.local/certification/new-certification/",
        "title": {
            "rendered": "New Certification"
        },
        "content": {
            "rendered": "<p><img loading=\"lazy\" decoding=\"async\" clas ... /p>\n",
            "protected": false
        },
        "featured_media": 0,
        "template": "",
        "cert_logo_url": "https://example.com/logo.url",
        "cert_authority": "",
        "cert_impact_category": "waste",
        "cert_renewal_months": 36,
        "_links": {
            "self": [
                {
                    "href": "http://eco-certifiation.local/wp-json/wp/v2/certifications/28",
                    "targetHints": {
                        "allow": [
                            "GET"
                        ]
                    }
                }
            ],
            "collection": [
                {
                    "href": "http://eco-certifiation.local/wp-json/wp/v2/certifications"
                }
            ],
            "about": [
                {
                    "href": "http://eco-certifiation.local/wp-json/wp/v2/types/certification"
                }
            ],
            "wp:attachment": [
                {
                    "href": "http://eco-certifiation.local/wp-json/wp/v2/media?parent=28"
                }
            ],
            "curies": [
                {
                    "name": "wp",
                    "href": "https://api.w.org/{rel}",
                    "templated": true
                }
            ]
        }
    },

]
```

---

### 2. Get all certifications (custom endpoint)
Returns a cleander, flatter JSON structure optimized for external consumers.

```
GET /wp-json/verdant/v1/certifications
```

**Example Response:**
```json
[
    {
        "id": 28,
        "name": "New Certification",
        "logo_url": "https://example.com/logo.url",
        "authority": "",
        "impact_category": "waste",
        "renewal_months": 36
    },
    {
        "id": 25,
        "name": "Cradle to Cradle Certified",
        "logo_url": "https://example.com/logos/c2c.png",
        "authority": "Cradle to Cradle Products Innovation Institute",
        "impact_category": "materials",
        "renewal_months": 36
    },
    {
        "id": 24,
        "name": "Carbon Neutral Certification",
        "logo_url": "https://example.com/logos/carbon-neutral.png",
        "authority": "Carbon Trust",
        "impact_category": "energy",
        "renewal_months": 12
    }
]
```

### 3. Filter certifications by impact category

Returns only certifications matching the specified impact category.

```
GET /wp-json/verdant/v1/certifications?category?{value}
```

**Allowed Category Values:**
| Value     | Description |
|---|---|
| `energy`  | Energy-realated certifications| 
| `waste`  | Waste reduction certifications| 
| `fair-labor`  | Fair labor and trade certifications| 
| `materials`  | Sustainable materials certifications| 

**Example - Get all energy certifications:**
```
GET /wp-json/verdant/v1/certifications?category=energy
```

**Example Response**
```json
[
    {
        "id": 24,
        "name": "Carbon Neutral Certification",
        "logo_url": "https://example.com/logos/carbon-neutral.png",
        "authority": "Carbon Trust",
        "impact_category": "energy",
        "renewal_months": 12
    },
    {
        "id": 19,
        "name": "Carbon Neutral Certification",
        "logo_url": "https://example.com/logos/carbon-neutral.png",
        "authority": "Carbon Trust",
        "impact_category": "energy",
        "renewal_months": 12
    }
]
```

**Example -- invalid category value:**
```
GET wp-json/verdant/v1/certifications?category=invalid
```

**Response (400 Bad Request):**
```json
{
    "code": "rest_invalid_param",
    "message": "Invalid parameter(s): category",
    "data": {
        "status": 400,
        "params": {
            "category": "Invalid parameter."
        },
        "details": []
    }
}
```