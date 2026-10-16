"""Packs the two plugins and the theme into dist/<slug>-<version>.zip for upload in wp-admin.

Run `npm run build` first: the plugin zip needs the built checkout scripts.
"""
import re
import sys
import zipfile
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
DIST = ROOT / "dist"
UPLOAD_LIMIT = 10 * 1024 * 1024  # InfinityFree refuses bigger uploads.

PACKAGES = [
    ("ua-commerce-kit", ROOT / "plugins/ua-commerce-kit", "ua-commerce-kit.php", ("tests/", "assets/src/")),
    ("zernyar-demo", ROOT / "plugins/zernyar-demo", "zernyar-demo.php", ()),
    ("zernyar", ROOT / "themes/zernyar", "style.css", ()),
]


def version(header_file: Path) -> str:
    match = re.search(r"^\s*\*?\s*Version:\s*(\S+)", header_file.read_text(encoding="utf-8"), re.M)
    if not match:
        sys.exit(f"No Version header in {header_file}")
    return match.group(1)


def main() -> None:
    if not (ROOT / "plugins/ua-commerce-kit/build/checkout.js").exists():
        sys.exit("plugins/ua-commerce-kit/build is missing: run npm run build first.")
    DIST.mkdir(exist_ok=True)
    for slug, source, header, skipped in PACKAGES:
        target = DIST / f"{slug}-{version(source / header)}.zip"
        with zipfile.ZipFile(target, "w", zipfile.ZIP_DEFLATED) as archive:
            for path in sorted(source.rglob("*")):
                relative = path.relative_to(source).as_posix()
                if path.is_dir() or relative.startswith(skipped) or path.name.startswith("."):
                    continue
                archive.write(path, f"{slug}/{relative}")
        size = target.stat().st_size
        print(f"{target.name}: {size / 1024:.0f} KB")
        if size > UPLOAD_LIMIT:
            sys.exit(f"{target.name} is over the 10 MB upload limit of free hosting.")


if __name__ == "__main__":
    main()
