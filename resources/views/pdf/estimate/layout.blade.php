{{--
    GASQ Cost to Protect™ — Master Estimate Dashboard, page shell.

    Four fixed-height pages (A4 @ 96dpi = 794 × 1122px) with a zero-margin @page,
    a repeated brand bar at the top of each page and a pinned footer block at the
    bottom. Everything is table/absolute layout: dompdf has no flexbox or grid.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>{{ $docTitle ?? 'GASQ Cost to Protect Estimate' }}</title>
<style>
    @page { margin: 0; }

    /* Helvetica (a PDF core font) is closer to the report's condensed brand face
       than dompdf's bundled DejaVu, and carries the ™ / — / · glyphs used here. */
    body { margin: 0; padding: 0; font-family: Helvetica, Arial, sans-serif; color: #1b2a41; background: #fff; }
    table { border-collapse: collapse; }
    td, th { padding: 0; }
    p { margin: 0; padding: 0; }
    img { display: block; }

    .page { position: relative; width: 794px; height: 1122px; overflow: hidden; }
    .page-break { page-break-after: always; }
    .body-pad { padding: 0 20px; }

    /* ── Brand bar ─────────────────────────────────────────── */
    .brandbar { width: 794px; background: #fff; border-bottom: 3px solid #12294f; }
    .brandbar .tag { font-size: 8px; font-weight: bold; color: #12294f; letter-spacing: .06em; line-height: 1.55; }
    .brandbar .tag-right { font-size: 7.5px; font-weight: bold; color: #12294f; letter-spacing: .06em; line-height: 1.6; text-align: right; }

    /* ── Page titles ───────────────────────────────────────── */
    .h1 { font-size: 30px; font-weight: bold; color: #12294f; letter-spacing: -.01em; line-height: 1.02; }
    .h1-accent { color: #ef6c1f; }
    .h1-sub { font-size: 11.5px; font-weight: bold; color: #2f4467; margin-top: 5px; }

    /* Report ID / date meta rows next to the title */
    .meta-label { font-size: 7.5px; color: #7b8798; letter-spacing: .1em; text-transform: uppercase; }
    .meta-value { font-size: 11.5px; font-weight: bold; color: #12294f; margin-top: 1px; }

    /* ── Contact strip ─────────────────────────────────────── */
    .contact { background: #eef2f8; border: 1px solid #dbe3f0; }
    .contact td { padding: 8px 10px; border-right: 1px solid #dbe3f0; }
    .contact td.last { border-right: 0; }
    .contact .k { font-size: 7.5px; color: #7b8798; letter-spacing: .09em; text-transform: uppercase; }
    .contact .v { font-size: 10px; font-weight: bold; color: #12294f; margin-top: 2px; }

    /* ── KPI cards ─────────────────────────────────────────── */
    .kpi-head { padding: 6px 10px; height: 30px; }
    .kpi-head p { font-size: 8px; font-weight: bold; color: #fff; letter-spacing: .08em; text-transform: uppercase; line-height: 1.25; }
    .kpi-body { padding: 16px 10px 15px; text-align: center; }
    .kpi-body .num { font-size: 27px; font-weight: bold; color: #12294f; line-height: 1; }
    .kpi-body .sub { font-size: 8.5px; color: #5b6779; margin-top: 5px; }
    .kpi-body.green .num { color: #15794a; }

    .bg-navy { background: #12294f; }
    .bg-green { background: #16794a; }
    .bg-orange { background: #ef6c1f; }
    .tint-navy { background: #eef3fb; }
    .tint-green { background: #e8f5ec; }
    .tint-orange { background: #fdefe6; }

    /* ── Panels (bordered blocks with a header band) ───────── */
    .panel { border: 1px solid #dbe3f0; }
    .panel-head { background: #12294f; padding: 7px 12px; }
    .panel-head p { font-size: 9px; font-weight: bold; color: #fff; letter-spacing: .09em; text-transform: uppercase; }
    .panel-head.light { background: #e7eef8; }
    .panel-head.light p { color: #12294f; }

    /* ── Data table (page 2) ───────────────────────────────── */
    .dtable { width: 100%; border: 1px solid #dbe3f0; border-top: 0; }
    .dtable td { padding: 4px 12px; font-size: 10.5px; color: #3c4a5e; border-bottom: 1px solid #edf1f7; }
    .dtable td.v { text-align: right; font-weight: bold; color: #12294f; }
    .dtable tr.alt td { background: #f7f9fc; }
    .dtable tr.head td { background: #eaf0f9; font-size: 9px; font-weight: bold; color: #12294f; letter-spacing: .04em; }
    .dtable tr.total td { background: #e7eefb; font-weight: bold; color: #12294f; border-top: 1px solid #c9d6ea; }
    .dtable tr.recover td { background: #e8f5ec; font-weight: bold; color: #12294f; }

    /* Chart furniture */
    .axis { font-size: 7px; color: #8592a5; text-align: right; padding-right: 5px; }
    .ring-pct { font-size: 7.5px; font-weight: bold; color: #ffffff; text-align: center; }

    /* ── Prose ─────────────────────────────────────────────── */
    .prose { font-size: 10.5px; color: #3c4a5e; line-height: 1.62; }
    .prose + .prose { margin-top: 8px; }

    /* ── Footer block ──────────────────────────────────────── */
    .footer { position: absolute; bottom: 0; left: 0; width: 794px; }
    .valueprops { width: 794px; background: #12294f; }
    .valueprops td { padding: 12px 12px; border-right: 1px solid #24406c; vertical-align: top; }
    .valueprops td.last { border-right: 0; }
    .valueprops .t { font-size: 8.5px; font-weight: bold; color: #fff; letter-spacing: .06em; text-transform: uppercase; line-height: 1.35; }
    .valueprops .s { font-size: 7.5px; color: #a9bad4; margin-top: 4px; line-height: 1.4; }
    .footbar { width: 794px; border-top: 3px solid #ef6c1f; }
    .footbar td { padding: 8px 20px; }
    .footbar .org { font-size: 9px; font-weight: bold; color: #12294f; }
    .footbar .meta { font-size: 7.5px; color: #8592a5; margin-top: 2px; }
    .certstrip { width: 794px; background: #f2f5fa; }
    .certstrip td { padding: 6px 20px; text-align: center; font-size: 8px; font-weight: bold; color: #12294f; letter-spacing: .12em; }
</style>
</head>
<body>
@yield('pages')
</body>
</html>
