import { mkdir, readFile, rm, writeFile } from 'node:fs/promises';
import path from 'node:path';
import process from 'node:process';

const siteUrl = new URL(process.env.SITE_URL ?? 'http://127.0.0.1:8082');
const basePath = `/${(process.env.PAGES_BASE_PATH ?? '/toyota/').replace(/^\/+|\/+$/g, '')}/`;
const outputDir = path.resolve(process.env.PAGES_OUTPUT_DIR ?? '.pages-preview');
const placeholder = `${basePath}images/vehicle-placeholder.svg`;

if (outputDir === path.parse(outputDir).root || !outputDir.startsWith(`${process.cwd()}${path.sep}`)) {
    throw new Error('PAGES_OUTPUT_DIR must be a child directory of the project root.');
}

await rm(outputDir, { recursive: true, force: true });
await mkdir(outputDir, { recursive: true });

const fetchText = async (url) => {
    const response = await fetch(url);

    if (!response.ok) {
        throw new Error(`Request failed (${response.status}): ${url}`);
    }

    return response.text();
};

const sitemap = await fetchText(new URL('/sitemap.xml', siteUrl));
const pagePaths = [...sitemap.matchAll(/<loc>(.*?)<\/loc>/g)]
    .map(([, location]) => new URL(location, siteUrl))
    .filter((url) => url.origin === siteUrl.origin)
    .map((url) => url.pathname)
    .filter((pathname) => !pathname.startsWith('/admin/'));

if (pagePaths.length === 0) {
    throw new Error('The application sitemap did not contain any local pages.');
}

const assetPaths = new Set();
const staticPath = (pathname) => `${basePath}${pathname.replace(/^\/+/, '')}`;
const outputPage = (pathname) => path.join(
    outputDir,
    pathname.replace(/^\/+|\/+$/g, ''),
    'index.html',
);

const toLocalUrl = (value) => {
    try {
        const url = new URL(value, siteUrl);

        return url.origin === siteUrl.origin ? url : null;
    } catch {
        return null;
    }
};

const queueAsset = (value) => {
    const url = toLocalUrl(value);

    if (!url || url.pathname.startsWith('/storage/')) {
        return;
    }

    if (/\.(?:css|js|mjs|png|jpe?g|webp|svg|ico|woff2?|ttf|otf|map|json|mp4|webm|pdf)$/i.test(url.pathname)) {
        assetPaths.add(url.pathname);
    }
};

const sourcePages = new Map();

for (const pathname of new Set(pagePaths)) {
    const sourceUrl = new URL(pathname, siteUrl);
    const response = await fetch(sourceUrl);

    if (!response.ok) {
        throw new Error(`Page export failed (${response.status}): ${sourceUrl}`);
    }

    const html = await response.text();
    sourcePages.set(pathname, html);

    for (const [, value] of html.matchAll(/\b(?:href|src|poster|data-src|srcset)\s*=\s*["']([^"']+)["']/gi)) {
        for (const candidate of value.split(',')) {
            queueAsset(candidate.trim().split(/\s+/)[0]);
        }
    }
}

const localAssets = new Map();
const pendingAssets = [...assetPaths];

while (pendingAssets.length > 0) {
    const pathname = pendingAssets.shift();

    if (localAssets.has(pathname)) {
        continue;
    }

    const response = await fetch(new URL(pathname, siteUrl));

    if (!response.ok) {
        continue;
    }

    const contentType = response.headers.get('content-type') ?? '';
    let contents = Buffer.from(await response.arrayBuffer());

    if (contentType.includes('text/css') || /\.css$/i.test(pathname)) {
        let css = contents.toString('utf8');

        for (const [, assetUrl] of css.matchAll(/url\(\s*["']?([^"')]+)["']?\s*\)/gi)) {
            const asset = toLocalUrl(assetUrl.trim());

            if (asset && !asset.pathname.startsWith('/storage/') && !assetPaths.has(asset.pathname)) {
                assetPaths.add(asset.pathname);
                pendingAssets.push(asset.pathname);
            }
        }

        css = css.replace(/url\(\s*(["']?)(\/[^)'"\s]+)\1\s*\)/gi, (match, quote, assetUrl) => {
            if (assetUrl.startsWith('/storage/')) {
                return `url(${quote}${placeholder}${quote})`;
            }

            return `url(${quote}${staticPath(assetUrl)}${quote})`;
        });

        contents = Buffer.from(css);
    }

    localAssets.set(pathname, contents);
}

for (const [pathname, contents] of localAssets) {
    const destination = path.join(outputDir, pathname.replace(/^\/+/, ''));
    await mkdir(path.dirname(destination), { recursive: true });
    await writeFile(destination, contents);
}

const publicPlaceholder = path.resolve('public/images/vehicle-placeholder.svg');
const placeholderDestination = path.join(outputDir, 'images/vehicle-placeholder.svg');
await mkdir(path.dirname(placeholderDestination), { recursive: true });
await writeFile(placeholderDestination, await readFile(publicPlaceholder));

for (const [pathname, originalHtml] of sourcePages) {
    let html = originalHtml.replaceAll(siteUrl.origin, `https://trithai1121.github.io${basePath.slice(0, -1)}`);
    html = html.replace(/<script\b[^>]*type=["']application\/ld\+json["'][^>]*>[\s\S]*?<\/script>/gi, '');
    html = html.replace(/\b0\d(?:[\s.-]?\d){8,9}\b/g, '[HOTLINE]');
    html = html.replace(/[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/gi, '[EMAIL]');
    html = html.replace(/(?:Chỉ từ\s*)?[\d.]{5,}\s*VNĐ/gi, 'Giá chờ đại lý xác nhận');

    html = html.replace(/(?<![\w:-])(href|src|poster|data-src|action)\s*=\s*(["'])([^"']+)\2/gi, (match, attribute, quote, value) => {
        const url = toLocalUrl(value);

        if (!url) {
            return match;
        }

        if (attribute.toLowerCase() === 'action') {
            return `action="#"`;
        }

        if (url.pathname.startsWith('/storage/')) {
            return `${attribute}=${quote}${placeholder}${quote}`;
        }

        if (/\.[a-z0-9]{2,8}$/i.test(url.pathname)) {
            return `${attribute}=${quote}${staticPath(url.pathname)}${url.search}${quote}`;
        }

        const pagePath = url.pathname === '/' ? '' : `${url.pathname.replace(/^\/+|\/+$/g, '')}/`;

        return `${attribute}=${quote}${basePath}${pagePath}${url.search}${quote}`;
    });

    html = html.replace(/(?<![\w:-])srcset\s*=\s*(["'])([^"']+)\1/gi, (match, quote, value) => {
        const candidates = value.split(',').map((candidate) => {
            const [assetUrl, ...descriptors] = candidate.trim().split(/\s+/);
            const url = toLocalUrl(assetUrl);

            if (!url) {
                return candidate.trim();
            }

            const rewritten = url.pathname.startsWith('/storage/') ? placeholder : staticPath(url.pathname);

            return [rewritten, ...descriptors].join(' ');
        });

        return `srcset=${quote}${candidates.join(', ')}${quote}`;
    });

    html = html.replaceAll('/storage/', placeholder);
    html = html.replace(/<body\b([^>]*)>/i, '$&<div style="position:relative;z-index:2147483647;padding:10px 16px;background:#fff3cd;color:#664d03;text-align:center;font:14px/1.5 sans-serif">Bản xem trước tĩnh trên GitHub Pages. Form, quản trị và dữ liệu động không hoạt động.</div>');
    html = html.replace(/<form\b/gi, '<form data-static-preview-form');
    html = html.replace(/<\/body>/i, '<script>document.addEventListener("submit",function(event){if(event.target.closest("[data-static-preview-form]")){event.preventDefault();alert("Đây là bản xem trước giao diện; gửi form cần máy chủ Laravel.");}},true);</script></body>');

    const destination = outputPage(pathname);
    await mkdir(path.dirname(destination), { recursive: true });
    await writeFile(destination, html);
}

await writeFile(path.join(outputDir, '.nojekyll'), '');
console.log(`Exported ${sourcePages.size} pages and ${localAssets.size} assets to ${outputDir}`);
