{{--
    GASQ Workforce-to-Post™ Report — page shell.

    Fixed-height pages (A4 @ 96dpi = 794 × 1122px) with a zero-margin @page, a
    report header repeated at the top of every page and a footer pinned to the
    bottom. Everything is table / absolute layout: dompdf has no flexbox or grid.

    Two dompdf rules drive the sizing conventions here:
      · an HTML width="…" attribute is read at 72dpi (1.33× too wide), so every
        width is written as a CSS pixel value instead;
      · boxes are content-box, so a cell's declared width EXCLUDES its padding
        and borders. Padding therefore lives on dedicated inner cells (.hero-pad,
        .card-pad, .panel-pad) and the column widths below already net it out.
    The body width inside the page padding is 738px.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $docTitle ?? 'GASQ Workforce-to-Post Report' }}</title>
<style>
    @page { margin: 0; }

    /* Helvetica (a PDF core font) matches the master estimate and carries the
       ™ / — / · glyphs used throughout. */
    body { margin: 0; padding: 0; font-family: Helvetica, Arial, sans-serif; color: #1b2a41; background: #fff; }
    table { border-collapse: collapse; }
    td, th { padding: 0; }
    p { margin: 0; padding: 0; }
    img { display: block; }

    .page { position: relative; width: 794px; height: 1122px; overflow: hidden; }
    .page-break { page-break-after: always; }
    .body-pad { padding: 0 28px; }
    .grid { width: 738px; }

    /* ── Report header ─────────────────────────────────────── */
    .rpt-title { font-size: 21px; font-weight: bold; color: #1e3558; letter-spacing: -.01em; }
    .rpt-sub { font-size: 7.5px; font-weight: bold; color: #7b8798; letter-spacing: .13em; text-transform: uppercase; margin-top: 4px; }
    .rpt-id { font-size: 9px; font-weight: bold; color: #1e3558; letter-spacing: .04em; }
    .rule-navy { width: 738px; height: 4px; background: #1e3558; margin: 0 28px 16px; font-size: 0; line-height: 0; }

    /* ── Contact strip ─────────────────────────────────────── */
    .contact { width: 736px; background: #f4f6fb; border: 1px solid #dbe3f0; }
    .contact td { padding: 9px 12px; border-right: 1px solid #dbe3f0; vertical-align: top; }
    .contact td.last { border-right: 0; }
    .contact .k { font-size: 7px; color: #7b8798; letter-spacing: .12em; text-transform: uppercase; font-weight: bold; }
    .contact .v { font-size: 10.5px; font-weight: bold; color: #1e3558; margin-top: 3px; }
    .contact .s { font-size: 8.5px; color: #6b7280; margin-top: 2px; }

    /* ── Section headings ──────────────────────────────────── */
    .sec-title { font-size: 14px; font-weight: bold; color: #1e3558; text-transform: uppercase; }
    .pill { width: 130px; background: #eef2f8; border: 1px solid #dbe3f0; border-radius: 11px; }
    .pill td { padding: 5px 8px; text-align: center; }
    .pill p { font-size: 8px; font-weight: bold; color: #5b6779; letter-spacing: .12em; }

    /* ── Cards ─────────────────────────────────────────────── */
    .hero { width: 280px; background: #1e3558; border-radius: 6px; }
    .hero-pad { padding: 22px 20px; }
    .hero .k { font-size: 8px; font-weight: bold; color: #a9bad4; letter-spacing: .13em; text-transform: uppercase; }
    .hero .num { font-size: 26px; font-weight: bold; color: #ffffff; margin-top: 12px; line-height: 1.05; }
    .hero .s { font-size: 8px; color: #9fb2cf; margin-top: 10px; }

    .card { width: 217px; border: 1px solid #dbe3f0; border-radius: 5px; }
    .card-pad { padding: 12px 13px; }
    .card .k { font-size: 8px; font-weight: bold; color: #1e3558; letter-spacing: .07em; text-transform: uppercase; line-height: 1.35; }
    .card .pct { font-size: 8.5px; font-weight: bold; color: #c1873a; text-align: right; }
    .card .num { font-size: 15px; font-weight: bold; color: #1e3558; margin-top: 9px; }

    /* ── Panels (bordered block with a small caps head) ────── */
    .panel { border: 1px solid #dbe3f0; border-radius: 5px; }
    .panel-pad { padding: 14px 16px; vertical-align: top; }
    .panel-k { font-size: 8.5px; font-weight: bold; color: #1e3558; letter-spacing: .12em; text-transform: uppercase; }
    .panel.tint { background: #f4f7fc; border-color: #dde5f2; }

    /* Allocation-mix bars */
    .mix-label { font-size: 9px; font-weight: bold; color: #3c4a5e; }
    .mix-pct { font-size: 9px; font-weight: bold; color: #1e3558; text-align: right; }
    .mix-track { width: 200px; background: #e7ecf5; border-radius: 4px; font-size: 0; line-height: 0; }
    .mix-fill { height: 9px; border-radius: 4px; font-size: 0; line-height: 0; }

    /* Dashboard indicators */
    .ind-num { font-size: 19px; font-weight: bold; color: #1e3558; }
    .ind-cap { font-size: 8px; color: #6b7280; line-height: 1.4; }
    .ind-rule { border-top: 1px solid #e7ecf5; font-size: 0; line-height: 0; height: 1px; }

    /* ── Callout bands ─────────────────────────────────────── */
    .flag { width: 734px; background: #fdf6e7; border-left: 4px solid #d2a03f; }
    .flag td { padding: 11px 14px; }
    .flag .k { font-size: 8px; font-weight: bold; color: #8a6412; letter-spacing: .12em; text-transform: uppercase; }
    .flag .t { font-size: 8.5px; color: #6b5524; line-height: 1.5; margin-top: 4px; }
    .flag.ok { background: #eef6f0; border-left-color: #2f7d53; }
    .flag.ok .k { color: #1f5c3c; }
    .flag.ok .t { color: #35614a; }

    .readout { width: 738px; background: #f2f5fb; border-radius: 5px; }
    .readout td { padding: 12px 14px; vertical-align: top; }
    .readout .k { font-size: 8px; font-weight: bold; color: #1e3558; letter-spacing: .12em; text-transform: uppercase; }
    .readout .t { font-size: 8.5px; color: #48566b; line-height: 1.55; }

    /* ── Line-item table (page 2) ──────────────────────────── */
    .band { width: 738px; background: #1e3558; border-radius: 4px 4px 0 0; }
    .band td { padding: 9px 16px; }
    .band p { font-size: 10px; font-weight: bold; color: #ffffff; letter-spacing: .1em; text-transform: uppercase; }

    .ltable { width: 736px; border: 1px solid #dbe3f0; border-top: 0; }
    .ltable td { padding: 5px 14px; font-size: 9.5px; color: #3c4a5e; border-bottom: 1px solid #edf1f7; }
    .ltable td.v { text-align: right; font-weight: bold; color: #1e3558; }
    .ltable tr.head td { background: #eef2f8; font-size: 7.5px; font-weight: bold; color: #6b7280; letter-spacing: .12em; text-transform: uppercase; padding: 6px 14px; }
    .ltable tr.head td.v { color: #6b7280; }
    .ltable tr.group td { background: #1e3558; color: #ffffff; font-weight: bold; font-size: 9.5px; border-bottom: 1px solid #1e3558; }
    .ltable tr.group td.v { color: #ffffff; }
    .ltable tr.group td.desc { font-weight: normal; color: #c3d1e6; }
    .ltable tr.alt td { background: #f7f9fc; }

    /* Cost-element chips */
    .chip { width: 194px; border: 1px solid #dde5f2; border-radius: 8px; background: #fbfcfe; }
    .chip td { padding: 3px 9px; }
    .chip p { font-size: 7.5px; color: #48566b; }

    .lead { font-size: 15px; font-weight: bold; color: #1e3558; }

    /* ── Statement page ────────────────────────────────────── */
    .stmt { width: 361px; border: 1px solid #dbe3f0; border-radius: 5px; }
    .stmt-k { font-size: 8.5px; font-weight: bold; color: #1e3558; letter-spacing: .1em; text-transform: uppercase; }
    .prose { font-size: 9px; color: #48566b; line-height: 1.6; }
    .closing { text-align: center; font-size: 9px; font-weight: bold; color: #1e3558; letter-spacing: .1em; line-height: 2; }

    /* ── Footer ────────────────────────────────────────────── */
    .footer { position: absolute; bottom: 0; left: 0; width: 738px; padding: 0 28px; }
    .footbar { width: 738px; border-top: 2px solid #1e3558; }
    .footbar td { padding: 9px 0 11px; }
    .footbar .org { font-size: 8.5px; font-weight: bold; color: #1e3558; }
    .footbar .meta { font-size: 7.5px; color: #8592a5; margin-top: 2px; }
</style>
</head>
<body>

{{-- Diagonal watermark — position:fixed repeats it on every page. --}}
<div style="position: fixed; top: 430px; left: 120px; width: 560px; text-align: center; transform: rotate(-30deg); color: #1e3558; opacity: 0.07; font-size: 26px; font-weight: bold; line-height: 1.6; text-transform: uppercase; letter-spacing: 0.05em;">
    GASQ Certified&trade;<br>Confidential • Proprietary<br>Methodology
</div>

@yield('pages')
</body>
</html>
