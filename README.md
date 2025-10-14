# Innoscripta Lararvel News Aggregator

A Laravel API service that **fetches articles** from multiple external sources — **The New York Times**, **NewsAPI**, **The Guardian** — into a single, normalized data format using an Adapter pattern.

It supports:
- Searching and filtering (by source_name, source_id, category, author, published_before and published_after)
- Periodic background fetching via artisan command booted though the NewsAggregatorServiceProvider
- Fetch user preferences (personalized feed) thorough a generated session id
- Fetch popular articles by view counts, categories and refresh articles



## Tech Stack

- **PHP**: 8.3
- **Laravel**: 12.x
- **Database**: MySQL
- **Queue**: Database or Sync


## Setup Instructions
1. **Clone the Repository**
```bash
git clone https://github.com/kingsleyudenewu/news-aggregator.git
```

2. **Run the following commands**
```bash
cd news-aggregator

cp .env.example .env

# all the necessary credentials for the providers are set up
```

3. **Install Dependency**
```bash
composer install
```

4. **Run Migration**
```bash
 php artisan migrate
```
5. **Run artisan command to fetch articles**
```bash
php artisan news:fetch

OR

php artisan news:fetch --source=newsapi

OR

php artisan news:fetch --source=guardian

OR

php artisan news:fetch --source=nyt
```

## Endpoints


### Articles

| Method | Endpoint                                 | Description                             |
| ------ | -----------------------------------------|-----------------------------------------|
| `GET`  | `/api/v1/articles/`                      | Fetch all articles                      |
| `GET`  | `/api/v1/articles/popular`               | Fetch popular articles by view count    |
| `GET`  | `/api/v1/articles/source/{source}`       | Fetch articles by their source          |
| `GET`  | `/api/v1/articles/category/{category}`   | Fetch articles by their categories      |
| `GET`  | `/api/v1/articles/{id}`                  | Fetch a single article by the ID        |
| `GET`  | `/api/v1/articles/refresh`               | Reset articles list                     |

### User Preferences

| Method   | Endpoint                          | Description                  |
| -------- | --------------------------------- | ---------------------------- |
| `GET`    | `/api/v1/preferences`             | Get current user preferences |
| `POST`   | `/api/v1/preferences/update`      | Update preferences           |
| `DELETE` | `/api/v1/preferences/reset`       | Delete preferences           |

### Article Search
| Method | Endpoint                  | Description               |
| ------ | --------------------------| --------------------------|
| `GET`  | `/api/v1/articles/search` | Fetch and filter articles |

### Article Query Parameters

| Parameter      | Example                         | Description                                |
| -------------  | -----------------------------   | ----------------------------------------   |
| `q`            | `?q=Michael`                    | Filter the 'title','description','content' |
| `sources`      | `?sources[0]=guardian`          | Filter by source                           |   
| `categories`   | `?categories[0]=Business`       | Filter by category                         |
| `authors`      | `?authors[0]=Business`          | Filter by authors and you can add more authors|
| `date_from`    | `?date_from=2025-10-10`         | Filter by publish date                     |
| `dateTo`       | `?dateTo=2025-10-10`            | Filter by publish date                     |
| `sort_by`      | `?sort_by=published_at`         | Search by published_at,title,view_count.   | 
| `sort_order`   | `?sort_order=desc`              | Search by asc,desc.                        | 
| `per_page`     | `?per_page=published_at`        | Search by published_at,title,view_count.   | 
| `live_search`  | `?live_search=published_at`     | Search by 0,1.                             |