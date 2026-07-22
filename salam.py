import os
import requests
from bs4 import BeautifulSoup
from urllib.parse import urljoin, urlparse

BASE_URL = "https://tools.negareno.com/"
SAVE_DIR = r"C:\xampp\htdocs\tools"

visited = set()

def save_file(url, content):
    path = urlparse(url).path

    if path == "/" or path == "":
        path = "/index.html"

    local_path = os.path.join(SAVE_DIR, path.lstrip("/"))

    os.makedirs(os.path.dirname(local_path), exist_ok=True)

    with open(local_path, "wb") as f:
        f.write(content)

    print("Saved:", local_path)

def crawl(url):
    if url in visited:
        return

    visited.add(url)

    try:
        r = requests.get(url, timeout=10)
    except Exception as e:
        print("Error:", e)
        return

    save_file(url, r.content)

    if "text/html" not in r.headers.get("Content-Type", ""):
        return

    soup = BeautifulSoup(r.text, "html.parser")

    for tag in soup.find_all(["a", "link", "script", "img"]):
        attr = "href" if tag.name != "img" else "src"
        link = tag.get(attr)

        if not link:
            continue

        full_url = urljoin(url, link)

        if not full_url.startswith(BASE_URL):
            continue

        crawl(full_url)

crawl(BASE_URL)

print("DONE")