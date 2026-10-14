"""Загружает локальный каталог в магазин, не публикуя происхождение данных."""

from __future__ import annotations

import argparse
import html
import json
import os
import re
import sqlite3
import sys
from collections import defaultdict
from datetime import datetime, timezone
from pathlib import Path

import pymysql

ROOT = Path(__file__).resolve().parents[1]
CATALOG_DIR = Path(r"C:\Users\Administrator\Projects\petrovich-catalog\data")
BATCH_SIZE = 200
MAX_IMAGES = 6
SITE_URL = "http://127.0.0.1:8090"

TEXT_PATTERNS = [
    re.compile(r"https?://\S+", re.I),
    re.compile(r"петрович\w*", re.I),
    re.compile(r"русский\s+свет\w*", re.I),
    re.compile(r"мир\s+инструмента", re.I),
    re.compile(r"лемана\s*про", re.I),
    re.compile(r"\bлемана\b", re.I),
    re.compile(r"(?<![\w])этм(?![\w])", re.I),
    re.compile(r"монолит\s+рус", re.I),
    re.compile(r"клуб[аеу]?\s+друзей", re.I),
    re.compile(r"дилерская\s+цена[^.]{0,80}\.?", re.I),
    re.compile(r"прайс\s+ооо[^.]{0,160}\.?", re.I),
]

TRANSLIT = {
    "а": "a", "б": "b", "в": "v", "г": "g", "д": "d", "е": "e", "ё": "e",
    "ж": "zh", "з": "z", "и": "i", "й": "y", "к": "k", "л": "l", "м": "m",
    "н": "n", "о": "o", "п": "p", "р": "r", "с": "s", "т": "t", "у": "u",
    "ф": "f", "х": "h", "ц": "ts", "ч": "ch", "ш": "sh", "щ": "sch", "ъ": "",
    "ы": "y", "ь": "", "э": "e", "ю": "yu", "я": "ya",
}

INTERNAL_KEYS = {
    "etm_id", "saturn_id", "mir_id", "rs_code", "warehouse_id", "personal",
    "personal_with_vat", "dealer_price", "cli_code", "brand_code", "base_price",
    "retail_price", "price_alt_gold", "price_alt_retail", "unit", "unit_alt",
    "article", "photo", "stock", "residue", "status",
}


def clean_text(value) -> str:
    if value is None:
        return ""
    text = str(value)
    for pattern in TEXT_PATTERNS:
        text = pattern.sub(" ", text)
    text = re.sub(r"\s+", " ", text)
    text = re.sub(r"\s+([,.;:])", r"\1", text)
    return text.strip(" ,.;:-")


def slugify(value: str) -> str:
    chars = []
    for char in value.casefold():
        if char in TRANSLIT:
            chars.append(TRANSLIT[char])
        elif "a" <= char <= "z" or char.isdigit():
            chars.append(char)
        else:
            chars.append("-")
    slug = re.sub(r"-{2,}", "-", "".join(chars)).strip("-")
    return slug[:80]


def load_env(path: Path) -> dict[str, str]:
    values = {}
    for line in path.read_text(encoding="utf-8").splitlines():
        line = line.strip()
        if not line or line.startswith("#") or "=" not in line:
            continue
        key, value = line.split("=", 1)
        values[key.strip()] = value.strip()
    return values


def catalog_path() -> Path:
    matches = sorted(CATALOG_DIR.glob("*.sqlite"))
    if not matches:
        raise SystemExit("Файл каталога не найден")
    return matches[0]


def hidden_category(code: str, original: str) -> bool:
    folded = original.casefold()
    if code == "catalog":
        return True
    if "клуб" in folded and "друз" in folded:
        return True
    return clean_text(original) == ""


def visible_parent(code: str, nodes: dict, hidden: set[str]) -> str | None:
    parent = nodes[code]["parent"]
    while parent:
        if parent not in hidden and parent in nodes:
            return parent
        parent = nodes.get(parent, {}).get("parent")
    return None


def specs_from_json(raw: str | None) -> list[tuple[str, str]]:
    if not raw:
        return []
    try:
        data = json.loads(raw)
    except json.JSONDecodeError:
        return []
    if not isinstance(data, dict):
        return []

    rows: list[tuple[str, str]] = []
    properties = data.get("properties")
    if isinstance(properties, list):
        for prop in properties:
            if not isinstance(prop, dict):
                continue
            title = clean_text(prop.get("title"))
            values = []
            for item in prop.get("value") or []:
                if isinstance(item, dict):
                    values.append(clean_text(item.get("title")))
                else:
                    values.append(clean_text(item))
            text = ", ".join(item for item in values if item)
            unit = clean_text(prop.get("unit"))
            if unit and text and unit.casefold() not in text.casefold():
                text = f"{text} {unit}"
            if title and text:
                rows.append((title, text))
        return rows[:40]

    labels = {"brand": "Бренд", "class": "Класс", "category": "Раздел"}
    for key, label in labels.items():
        value = data.get(key)
        if isinstance(value, str):
            text = clean_text(value)
            if text:
                rows.append((label, text))
    return rows


def photo_from_json(raw: str | None) -> list[str]:
    if not raw:
        return []
    try:
        data = json.loads(raw)
    except json.JSONDecodeError:
        return []
    if not isinstance(data, dict):
        return []
    photo = data.get("photo")
    path = photo.split("?", 1)[0].lower() if isinstance(photo, str) else ""
    if path.startswith("https://") and path.endswith((".jpg", ".jpeg", ".png", ".webp", ".gif")):
        return [photo]
    return []


def stock_status(value: str | None) -> str:
    text = (value or "").strip().casefold()
    if not text:
        return "instock"
    if text in {"not_in_stock", "out_of_stock", "нет в наличии"}:
        return "outofstock"
    if "нет в наличии" in text:
        return "outofstock"
    return "instock"


def price_text(retail, gold) -> str:
    for value in (retail, gold):
        if value is None:
            continue
        try:
            number = float(value)
        except (TypeError, ValueError):
            continue
        if number > 0:
            return f"{number:.2f}"
    return ""


def build_content(description: str, specs: list[tuple[str, str]]) -> str:
    parts = []
    for paragraph in re.split(r"\n+", description):
        paragraph = paragraph.strip()
        if paragraph:
            parts.append(f"<p>{html.escape(paragraph)}</p>")
    if specs:
        body = "".join(
            f"<tr><th>{html.escape(title)}</th><td>{html.escape(value)}</td></tr>"
            for title, value in specs
        )
        parts.append(f'<table class="product-specs"><tbody>{body}</tbody></table>')
    return "".join(parts)


def insert_many(cursor, table: str, columns: list[str], rows: list[tuple]) -> None:
    if not rows:
        return
    placeholder = "(" + ",".join(["%s"] * len(columns)) + ")"
    sql = (
        f"INSERT INTO {table} ({','.join(columns)}) VALUES "
        + ",".join([placeholder] * len(rows))
    )
    flat = [value for row in rows for value in row]
    cursor.execute(sql, flat)


def self_check() -> None:
    sample = clean_text("Купить в Петрович. Прайс ООО «МОНОЛИТ РУС», категория «Оснастка».")
    assert "петрович" not in sample.casefold()
    assert "монолит рус" not in sample.casefold()
    kept = clean_text("при монолитно-каркасном строительстве")
    assert "монолитно" in kept.casefold()
    print("check ok")


def main() -> int:
    sys.stdout.reconfigure(encoding="utf-8", errors="replace")
    parser = argparse.ArgumentParser()
    parser.add_argument("--check", action="store_true")
    parser.add_argument("--limit", type=int, default=0)
    args = parser.parse_args()
    if args.check:
        self_check()
        return 0

    env = load_env(ROOT / ".env")
    database = pymysql.connect(
        host="127.0.0.1",
        port=int(env["MYSQL_PORT"]),
        user=env["MYSQL_USER"],
        password=env["MYSQL_PASSWORD"],
        database=env["MYSQL_DATABASE"],
        charset="utf8mb4",
        autocommit=False,
    )
    catalog = sqlite3.connect(catalog_path())
    catalog.row_factory = sqlite3.Row
    catalog.execute("PRAGMA query_only=ON")

    try:
        import_catalog(database, catalog, args.limit)
    except Exception:
        database.rollback()
        raise
    finally:
        catalog.close()
        database.close()
    return 0


def import_catalog(database, catalog, limit: int) -> None:
    cursor = database.cursor()
    cursor.execute("SHOW TABLES LIKE 'wp_wc_product_meta_lookup'")
    if cursor.fetchone() is None:
        raise SystemExit("WooCommerce ещё не установлен")

    simple_type = scalar(
        cursor,
        """
        SELECT tt.term_taxonomy_id
        FROM wp_terms t
        JOIN wp_term_taxonomy tt ON tt.term_id = t.term_id
        WHERE tt.taxonomy = 'product_type' AND t.slug = 'simple'
        """,
    )
    if simple_type is None:
        raise SystemExit("Не найден тип товара simple")
    outofstock_term = scalar(
        cursor,
        """
        SELECT tt.term_taxonomy_id
        FROM wp_terms t
        JOIN wp_term_taxonomy tt ON tt.term_id = t.term_id
        WHERE tt.taxonomy = 'product_visibility' AND t.slug = 'outofstock'
        """,
    )
    lookup_columns = table_columns(cursor, "wp_wc_product_meta_lookup")

    print("очистка предыдущей витрины", flush=True)
    cursor.execute(
        """
        DELETE tr FROM wp_term_relationships tr
        INNER JOIN wp_posts p ON p.ID = tr.object_id
        WHERE p.post_type = 'product'
        """
    )
    cursor.execute(
        """
        DELETE pm FROM wp_postmeta pm
        INNER JOIN wp_posts p ON p.ID = pm.post_id
        WHERE p.post_type = 'product'
        """
    )
    cursor.execute("DELETE FROM wp_wc_product_meta_lookup")
    cursor.execute("DELETE FROM wp_posts WHERE post_type = 'product'")
    cursor.execute(
        """
        DELETE t, tt FROM wp_terms t
        INNER JOIN wp_term_taxonomy tt ON tt.term_id = t.term_id
        WHERE tt.taxonomy = 'product_cat'
        """
    )
    database.commit()

    categories = load_categories(catalog, cursor)
    database.commit()
    print(f"разделов {len(categories)}", flush=True)

    used_skus: set[str] = set()
    used_slugs: set[str] = set()
    now_local = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
    now_gmt = datetime.now(timezone.utc).strftime("%Y-%m-%d %H:%M:%S")
    imported = 0
    query = """
        SELECT nomenclature, title, price_retail, price_gold, description,
               unit_title, availability, properties_json, article
        FROM products
        ORDER BY nomenclature
    """
    if limit:
        query += f" LIMIT {int(limit)}"

    batch = []
    for row in catalog.execute(query):
        batch.append(row)
        if len(batch) >= BATCH_SIZE:
            imported += write_batch(
                cursor, catalog, batch, categories, simple_type, outofstock_term,
                lookup_columns, used_skus, used_slugs, now_local, now_gmt,
            )
            database.commit()
            batch = []
            if imported % 2000 == 0:
                print(f"товаров {imported}", flush=True)
    if batch:
        imported += write_batch(
            cursor, catalog, batch, categories, simple_type, outofstock_term,
            lookup_columns, used_skus, used_slugs, now_local, now_gmt,
        )
        database.commit()

    print("пересчёт разделов", flush=True)
    cursor.execute(
        """
        UPDATE wp_term_taxonomy tt
        LEFT JOIN (
            SELECT tr.term_taxonomy_id AS term_taxonomy_id, COUNT(*) AS cnt
            FROM wp_term_relationships tr
            INNER JOIN wp_posts p ON p.ID = tr.object_id AND p.post_status = 'publish'
            GROUP BY tr.term_taxonomy_id
        ) counted ON counted.term_taxonomy_id = tt.term_taxonomy_id
        SET tt.count = COALESCE(counted.cnt, 0)
        WHERE tt.taxonomy = 'product_cat'
        """
    )
    rollup_category_counts(cursor)
    database.commit()
    leaked = scalar(
        cursor,
        """
        SELECT COUNT(*) FROM wp_posts
        WHERE post_type = 'product' AND (
            post_title LIKE '%Петрович%' OR post_title LIKE '%петрович%'
            OR post_title LIKE '%Русский Свет%' OR post_content LIKE '%petrovich%'
            OR post_content LIKE '%etm.ru%' OR post_content LIKE '%rs24.ru%'
            OR post_title LIKE '%ЭТМ%' OR post_content LIKE '%Лемана%'
        )
        """,
    )
    print(f"готово {imported}, совпадений с именами поставщиков в текстах: {leaked}", flush=True)


def rollup_category_counts(cursor) -> None:
    cursor.execute("SELECT term_taxonomy_id, term_id, parent, count FROM wp_term_taxonomy WHERE taxonomy = 'product_cat'")
    rows = cursor.fetchall()
    by_term = {
        int(term_id): {"tt": int(term_taxonomy_id), "parent": int(parent), "count": int(count)}
        for term_taxonomy_id, term_id, parent, count in rows
    }
    children: dict[int, list[int]] = defaultdict(list)
    for term_id, info in by_term.items():
        children[info["parent"]].append(term_id)
    memo: dict[int, int] = {}

    def total(term_id: int) -> int:
        if term_id in memo:
            return memo[term_id]
        amount = by_term[term_id]["count"]
        for child_id in children.get(term_id, []):
            amount += total(child_id)
        memo[term_id] = amount
        return amount

    for term_id in by_term:
        total(term_id)
    cursor.executemany(
        "UPDATE wp_term_taxonomy SET count = %s WHERE term_taxonomy_id = %s",
        [(memo[term_id], by_term[term_id]["tt"]) for term_id in by_term],
    )


def scalar(cursor, sql: str):
    cursor.execute(sql)
    row = cursor.fetchone()
    return None if row is None else row[0]


def table_columns(cursor, table: str) -> list[str]:
    cursor.execute(f"SHOW COLUMNS FROM {table}")
    return [row[0] for row in cursor.fetchall()]


def load_categories(catalog, cursor) -> dict[str, int]:
    nodes = {}
    for row in catalog.execute("SELECT code, title, parent_code FROM groups"):
        nodes[row["code"]] = {"title": row["title"] or "", "parent": row["parent_code"]}
    hidden = {code for code, node in nodes.items() if hidden_category(code, node["title"])}
    children: dict[str | None, list[str]] = defaultdict(list)
    for code in nodes:
        if code in hidden:
            continue
        children[visible_parent(code, nodes, hidden)].append(code)

    mapping: dict[str, int] = {}
    term_ids: dict[str, int] = {}
    slugs: set[str] = set()

    def visit(parent_code: str | None) -> None:
        ordered = sorted(children.get(parent_code, []), key=lambda item: clean_text(nodes[item]["title"]).casefold())
        for code in ordered:
            name = clean_text(nodes[code]["title"])[:190]
            base = slugify(name) or f"razdel-{code}"
            slug = base
            suffix = 2
            while slug in slugs:
                slug = f"{base[:70]}-{suffix}"
                suffix += 1
            slugs.add(slug)
            parent_term = 0 if parent_code is None else term_ids[parent_code]
            cursor.execute(
                "INSERT INTO wp_terms (name, slug, term_group) VALUES (%s, %s, 0)",
                (name, slug),
            )
            term_id = cursor.lastrowid
            cursor.execute(
                """
                INSERT INTO wp_term_taxonomy (term_id, taxonomy, description, parent, count)
                VALUES (%s, 'product_cat', '', %s, 0)
                """,
                (term_id, parent_term),
            )
            mapping[code] = cursor.lastrowid
            term_ids[code] = term_id
            visit(code)

    visit(None)
    return mapping


def write_batch(
    cursor, catalog, rows, categories, simple_type, outofstock_term,
    lookup_columns, used_skus, used_slugs, now_local, now_gmt,
) -> int:
    codes = [row["nomenclature"] for row in rows]
    marks = ",".join("?" * len(codes))
    groups = defaultdict(list)
    for item in catalog.execute(
        f"SELECT nomenclature, group_code FROM product_groups WHERE nomenclature IN ({marks})",
        codes,
    ):
        groups[item["nomenclature"]].append(item["group_code"])
    photos = defaultdict(list)
    for item in catalog.execute(
        f"""
        SELECT nomenclature, url FROM assets
        WHERE kind = 'photo' AND nomenclature IN ({marks})
        ORDER BY id
        """,
        codes,
    ):
        bucket = photos[item["nomenclature"]]
        url = str(item["url"] or "")
        path = url.split("?", 1)[0].lower()
        if len(bucket) < MAX_IMAGES and path.startswith("https://") and path.endswith((".jpg", ".jpeg", ".png", ".webp", ".gif")):
            bucket.append(url)

    posts = []
    prepared = []
    for index, row in enumerate(rows):
        title = clean_text(row["title"]) or "Товар"
        description = clean_text(row["description"])
        specs = specs_from_json(row["properties_json"])
        content = build_content(description, specs)
        excerpt = re.sub(r"\s+", " ", description)[:240]
        sku = unique_sku(clean_text(row["article"]), used_skus, len(used_skus) + index + 1)
        slug = unique_slug(title, sku, used_slugs)
        price = price_text(row["price_retail"], row["price_gold"])
        status = stock_status(row["availability"])
        unit = clean_text(row["unit_title"])[:40]
        images = photos[row["nomenclature"]] or photo_from_json(row["properties_json"])
        images = images[:MAX_IMAGES]
        posts.append((
            1, now_local, now_gmt, content, title, excerpt, "publish", "closed", "closed",
            "", slug, "", "", now_local, now_gmt, "", 0, "", 0, "product", "", 0,
        ))
        prepared.append({
            "sku": sku,
            "price": price,
            "status": status,
            "unit": unit,
            "images": images,
            "groups": groups[row["nomenclature"]],
        })

    insert_many(
        cursor,
        "wp_posts",
        [
            "post_author", "post_date", "post_date_gmt", "post_content", "post_title",
            "post_excerpt", "post_status", "comment_status", "ping_status", "post_password",
            "post_name", "to_ping", "pinged", "post_modified", "post_modified_gmt",
            "post_content_filtered", "post_parent", "guid", "menu_order", "post_type",
            "post_mime_type", "comment_count",
        ],
        posts,
    )
    first_id = cursor.lastrowid
    ids = list(range(first_id, first_id + len(posts)))
    cursor.executemany(
        "UPDATE wp_posts SET guid=%s WHERE ID=%s",
        [(f"{SITE_URL}/?post_type=product&p={product_id}", product_id) for product_id in ids],
    )

    meta_rows = []
    lookup_rows = []
    relations = []
    for product_id, item in zip(ids, prepared):
        meta = {
            "_sku": item["sku"],
            "_stock_status": item["status"],
            "_manage_stock": "no",
            "_virtual": "no",
            "_downloadable": "no",
            "_tax_status": "taxable",
            "_tax_class": "",
        }
        if item["price"]:
            meta["_regular_price"] = item["price"]
            meta["_price"] = item["price"]
        if item["unit"]:
            meta["_unit"] = item["unit"]
        if item["images"]:
            meta["_catalog_images"] = json.dumps(item["images"], ensure_ascii=False)
        for key, value in meta.items():
            meta_rows.append((product_id, key, value))
        lookup = lookup_record(product_id, item, lookup_columns)
        lookup_rows.append(tuple(lookup[column] for column in lookup_columns))
        relations.append((product_id, simple_type, 0))
        if item["status"] == "outofstock" and outofstock_term:
            relations.append((product_id, outofstock_term, 0))
        seen_terms = set()
        for code in item["groups"]:
            term_id = categories.get(code)
            if term_id and term_id not in seen_terms:
                relations.append((product_id, term_id, 0))
                seen_terms.add(term_id)

    for offset in range(0, len(meta_rows), 1000):
        insert_many(cursor, "wp_postmeta", ["post_id", "meta_key", "meta_value"], meta_rows[offset:offset + 1000])
    insert_many(cursor, "wp_wc_product_meta_lookup", lookup_columns, lookup_rows)
    insert_many(
        cursor,
        "wp_term_relationships",
        ["object_id", "term_taxonomy_id", "term_order"],
        relations,
    )
    return len(posts)


def lookup_record(product_id: int, item: dict, columns: list[str]) -> dict:
    price = item["price"] or None
    record = {
        "product_id": product_id,
        "sku": item["sku"][:100],
        "virtual": 0,
        "downloadable": 0,
        "min_price": price,
        "max_price": price,
        "onsale": 0,
        "stock_quantity": None,
        "stock_status": item["status"],
        "rating_count": 0,
        "average_rating": 0,
        "total_sales": 0,
        "tax_status": "taxable",
        "tax_class": "",
        "global_unique_id": "",
    }
    unknown = [column for column in columns if column not in record]
    if unknown:
        raise SystemExit("В таблице цен есть необработанные поля: " + ", ".join(unknown))
    return {column: record[column] for column in columns}


def unique_sku(article: str, used: set[str], sequence: int) -> str:
    base = slugify(article).upper().replace("-", "")[:40] or f"SM{sequence}"
    if not base[0].isalnum():
        base = f"SM{sequence}"
    sku = base
    suffix = 2
    while sku.casefold() in used:
        sku = f"{base[:36]}{suffix}"
        suffix += 1
    used.add(sku.casefold())
    return sku


def unique_slug(title: str, sku: str, used: set[str]) -> str:
    base = f"{slugify(title) or 'tovar'}-{slugify(sku) or 'item'}"[:170].strip("-")
    slug = base or "tovar"
    suffix = 2
    while slug in used:
        slug = f"{base[:160]}-{suffix}"
        suffix += 1
    used.add(slug)
    return slug


if __name__ == "__main__":
    raise SystemExit(main())
