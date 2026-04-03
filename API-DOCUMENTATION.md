# Verdant Collective -- Eco-Certification API Documentation

Base URL: `http://eco-certifiation.local`

all endpoints are public and read-only. No authentication required for GET requests

---------------

## Endpoints 

### 1. Get all certifications (Wordpress native endpoints)

Returns all certifications with full custom field data.

```
GET 
```

**Example Response:**
```json

```

---

### 2. Get all certifications (custom endpoint)
Returns a cleander, flatter JSON structure optimized for external consumers.

```
GET /wp-json/
```

**Example Response:**
```json

```

### 3. Filter certifications by impact category

Returns only cer