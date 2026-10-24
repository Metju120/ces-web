"""Render anniversary article from the editorial Markdown source.

The Markdown file is shared with the print editorial workflow; no external
Markdown package is needed by the static build.
"""
import html
import re
from pathlib import Path

def inline(text):
    text = html.escape(text.strip(), quote=True).replace("&lt;br&gt;", "<br>")
    text = re.sub(r"\*\*(.+?)\*\*", r"<strong>\1</strong>", text)
    text = re.sub(r"(?<!\*)\*(?!\*)(.+?)\*(?!\*)", r"<em>\1</em>", text)
    text = re.sub(r"\[([^]]+)\]\((https?://[^)]+)\)", r'<a href="\2">\1</a>', text)
    return text

def render_history_article(source):
    out = ['<div class="history-article">']
    for block in re.split(r"\n\s*\n", source.strip()):
        lines = [x.strip() for x in block.strip().splitlines() if x.strip()]
        if not lines:
            continue
        first = lines[0]
        if first.startswith("# "):
            continue
        if first.startswith("### "):
            out.append("<h3>" + inline(first[4:]) + "</h3>")
        elif first.startswith("## "):
            out.append("<h2>" + inline(first[3:]) + "</h2>")
        elif first.startswith("[[FOTO:") and first.endswith("]]"):
            parts = first[7:-2].split("|", 1)
            label = parts[0].strip()
            caption = parts[1].strip() if len(parts) > 1 else label
            out.append('<figure class="history-photo-placeholder">'
                       '<div class="history-photo-box" aria-hidden="true">'
                       '<svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M5 14h10l4-5h12l4 5h8v25H5z"/><circle cx="24" cy="26" r="8"/></svg>'
                       '<span>Fotografie k doplnění</span></div><figcaption><strong>'
                       + html.escape(label) + '</strong> – ' + html.escape(caption)
                       + '</figcaption></figure>')
        elif first.startswith("[[OBRAZEK:") and first.endswith("]]"):
            parts = [part.strip() for part in first[10:-2].split("|", 2)]
            if len(parts) == 3 and parts[0].startswith("assets/") and ".." not in parts[0]:
                path, alt, caption = parts
                if (Path(__file__).resolve().parent / path).is_file():
                    out.append('<figure class="history-photo' + (' history-photo-congress-2026' if path.endswith('18-kongres-2026-clenove-ces.jpg') else '') + '"><img src="' + html.escape(path, quote=True)
                               + '" alt="' + html.escape(alt, quote=True)
                               + '" loading="lazy" decoding="async"><figcaption>'
                               + html.escape(caption) + '</figcaption></figure>')
                else:
                    out.append('<figure class="history-photo-placeholder">'
                               '<div class="history-photo-box" aria-hidden="true">'
                               '<svg viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M5 14h10l4-5h12l4 5h8v25H5z"/><circle cx="24" cy="26" r="8"/></svg>'
                               '<span>Archivní fotografie připravena k doplnění</span></div>'
                               '<figcaption>' + html.escape(caption) + '</figcaption></figure>')
        elif first.startswith("|"):
            rows = []
            for line in lines:
                if line.startswith("|"):
                    cells = [c.strip() for c in line.strip("|").split("|")]
                    if not all(re.fullmatch(r":?-{3,}:?", c) for c in cells):
                        rows.append(cells)
            if rows:
                head, *body = rows
                out.append('<div class="history-table-scroll"><table class="history-table"><thead><tr>'
                           + "".join("<th scope=\"col\">" + inline(c) + "</th>" for c in head)
                           + "</tr></thead><tbody>")
                for cells in body:
                    out.append("<tr>" + "".join("<td>" + inline(c) + "</td>" for c in cells) + "</tr>")
                out.append("</tbody></table></div>")
        else:
            out.append("<p>" + inline(" ".join(lines)) + "</p>")
    out.append("</div>")
    return "\n".join(out)
