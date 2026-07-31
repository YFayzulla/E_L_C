<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="utf-8">
    <title>{{ $certificate->serial }}</title>
    {{--
        Print-only document rendered by dompdf. Colours are hardcoded on purpose:
        dompdf does not evaluate CSS custom properties, so the theme tokens used
        everywhere else in the app would come out blank here.
        Landscape A4 is set by the controller.
    --}}
    <style>
        @page { margin: 0; }

        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            color: #1b2233;
        }

        .sheet {
            position: relative;
            width: 100%;
            height: 540pt;
            padding: 34pt 46pt;
            box-sizing: border-box;
        }

        .frame {
            position: absolute;
            top: 18pt; right: 18pt; bottom: 18pt; left: 18pt;
            border: 3px solid #4f46e5;
        }

        .frame-inner {
            position: absolute;
            top: 26pt; right: 26pt; bottom: 26pt; left: 26pt;
            border: 1px solid #c7c9f5;
        }

        .content { position: relative; text-align: center; padding-top: 26pt; }

        .brand      { font-size: 13pt; letter-spacing: 3pt; color: #4f46e5; text-transform: uppercase; }
        .brand-sub  { font-size: 9pt; color: #667085; margin-top: 3pt; }

        h1 { font-size: 30pt; margin: 22pt 0 4pt; letter-spacing: 1pt; }

        .lead   { font-size: 10.5pt; color: #667085; margin-bottom: 14pt; }
        .name   { font-size: 24pt; font-weight: bold; margin: 6pt 0 4pt; }
        .rule   { width: 300pt; margin: 6pt auto 14pt; border-bottom: 1px solid #d3d7e4; }
        .course { font-size: 13pt; margin-bottom: 4pt; }
        .level  { font-size: 11pt; color: #4f46e5; font-weight: bold; }
        .note   { font-size: 9.5pt; color: #667085; margin-top: 12pt; }

        .meta { position: absolute; left: 46pt; right: 46pt; bottom: 54pt; font-size: 9pt; color: #667085; }
        .meta td { padding-top: 4pt; }
        .sig  { border-top: 1px solid #98a1b3; padding-top: 4pt; width: 150pt; }

        .serial {
            position: absolute; bottom: 30pt; left: 0; right: 0;
            text-align: center; font-size: 8.5pt; color: #98a1b3; letter-spacing: 1pt;
        }
    </style>
</head>
<body>
<div class="sheet">
    <div class="frame"></div>
    <div class="frame-inner"></div>

    <div class="content">
        <div class="brand">ALPHA</div>
        <div class="brand-sub">o‘quv markazi</div>

        <h1>SERTIFIKAT</h1>
        <div class="lead">Ushbu sertifikat quyidagi shaxsga berildi</div>

        <div class="name">{{ $certificate->student->name ?? '—' }}</div>
        <div class="rule"></div>

        <div class="course">{{ $certificate->title }}</div>

        @if($certificate->group)
            <div class="note">{{ $certificate->group->name }} guruhi</div>
        @endif

        @if($certificate->level)
            <div class="level">Daraja: {{ $certificate->level }}</div>
        @endif

        @if($certificate->final_score !== null)
            <div class="note">Yakuniy natija: {{ $certificate->final_score }} ball</div>
        @endif

        @if($certificate->note)
            <div class="note">{{ $certificate->note }}</div>
        @endif
    </div>

    <table class="meta" width="100%">
        <tr>
            <td align="left">Berilgan sana: {{ optional($certificate->issued_at)->format('d.m.Y') }}</td>
            <td align="right">{{ $certificate->issuer->name ?? '' }}</td>
        </tr>
        <tr>
            <td align="left"></td>
            <td align="right"><div class="sig" style="margin-left:auto;">Imzo va muhr</div></td>
        </tr>
    </table>

    <div class="serial">{{ $certificate->serial }}</div>
</div>
</body>
</html>
