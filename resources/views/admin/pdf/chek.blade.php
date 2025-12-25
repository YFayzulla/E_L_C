<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Monthly Payment Receipt</title>

    <style>
        /* --- RESET & GLOBAL --- */
        * {
            box-sizing: border-box;
        }

        html, body {
            margin: 0;
            padding: 0;
            width: 100%;
            background: #fff;
            color: #000;
            font-family: "Times New Roman", Times, serif;
            /* Reduced slightly to 10px to prevent price wrapping on 58mm paper */
            font-size: 10px;
            line-height: 1.2;
        }

        /* --- RECEIPT CONTAINER --- */
        .receipt {
            /*
               58mm Paper Width.
               We set width to 100% of the @page size defined below.
            */
            width: 100%;
            max-width: 58mm;
            margin: 0 auto;
            /*
               Right/Left padding adjusted to ensure text isn't cut off
               by the printer's hardware rails.
            */
            padding: 2mm 3mm;
            background: #fff;
        }

        /* --- UTILITIES --- */
        .bold { font-weight: bold; }
        .center { text-align: center; }
        .uppercase { text-transform: uppercase; }

        /* --- HEADER & LOGO --- */
        .logo-container {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2px;
        }

        .logo-img {
            height: 22px; /* Slightly smaller to fit safely */
            width: auto;
            margin-right: 3px;
        }

        .logo-text {
            font-family: sans-serif;
            font-weight: 800;
            font-size: 18px; /* Adjusted for 58mm scale */
            letter-spacing: -0.5px;
            color: #000;
        }

        .sub-header {
            text-align: center;
            font-size: 9px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }

        /* --- TITLE BOX --- */
        .receipt-title-box {
            border: 1.5px solid #4a934a;
            border-radius: 4px;
            padding: 3px 0;
            text-align: center;
            margin: 0 0 10px 0;
            width: 100%;
        }

        .receipt-title-text {
            font-weight: 800;
            font-size: 10px;
            text-transform: uppercase;
        }

        /* --- DATA ROWS --- */
        .row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start; /* Better alignment for wrapped text */
            margin-bottom: 4px;
        }

        .label {
            text-align: left;
            flex-shrink: 0;
            padding-right: 5px;
        }

        .value {
            text-align: right;
            font-weight: bold;
            /* Ensures long names wrap instead of pushing layout wide */
            white-space: normal;
            max-width: 65%;
        }

        /* --- INDENTED ROWS --- */
        .indent-row {
            display: flex;
            justify-content: space-between;
            align-items: baseline;
            margin-bottom: 4px;
        }

        .indent-label {
            padding-left: 20px; /* Reduced indent to save space on 58mm */
            font-weight: bold;
            font-size: 9px;
        }

        /* --- SEPARATORS --- */
        .line {
            border-top: 1px dashed #000; /* Dashed lines often look better on thermal */
            margin: 6px 0;
            width: 100%;
        }

        /* --- FOOTER --- */
        .footer {
            text-align: center;
            margin-top: 10px;
            margin-bottom: 10px;
        }

        /* --- PRINT CONFIGURATION (CRITICAL) --- */
        @media print {
            @page {
                /*
                   58mm width.
                   'auto' height allows the roll to cut only after content ends.
                */
                size: 58mm auto;
                margin: 0; /* Important: removes browser default header/footer space */
            }

            body {
                margin: 0;
                padding: 0;
            }

            .receipt {
                width: 58mm; /* Force exact width */
                /* Remove screen shadows/margins */
                box-shadow: none;
                margin: 0;
                page-break-inside: avoid;
            }

            .no-print {
                display: none !important;
            }
        }
    </style>

    @if(!request()->boolean('embed'))
        <script>
            window.addEventListener('load', function () {
                setTimeout(function () {
                    window.print();
                }, 500); // Increased delay slightly to ensure styles load
            });
        </script>
    @endif

    <?php
    // --- DATA PREP (Updated for Many-to-Many) ---
    $studentName = $student->name ?? ($payment->name ?? 'Unknown Student');
    
    // Get room from the first group, or fallback
    $roomName = $student->groups->first()?->room?->room ?? 'Unknown Room';
    
    // Use payment group name if available, otherwise list student's groups
    $courseName = $payment->group ?? ($student->groups->pluck('name')->implode(', ') ?: 'IELTS/CEFR/GEN.ENG');

    $methodRaw = strtolower($payment->type_of_money ?? 'cash');
    $method = $methodRaw === 'electronic' ? 'CARD' : 'CASH';

    $amount = (float)($payment->payment ?? 0);
    $monthlyBase = (float)($dept ?? ($student->deptStudent->dept ?? $student->should_pay ?? 0));
    $partialPaid = (float) data_get($student, 'deptStudent.payed', 0);
    $remainingToPay = max(0, $monthlyBase - $partialPaid);

    $createdAt = $payment->created_at ?? now();
    $dateDisplay = ($payment->date ?? optional($createdAt)->format('Y-m-d'));
    $dateObj = \Carbon\Carbon::parse($dateDisplay);

    $printDate = $dateObj->format('d.m.Y');
    $printTime = optional($createdAt)->format('H.i');

    $periodFrom = $dateObj->format('d/m/Y');
    $periodTo = $dateObj->copy()->addMonth()->format('d/m/Y');

    $debtMonths = isset($student->status) && $student->status < 0 ? abs((int)$student->status) : 0;
    ?>
</head>

<body>

<div class="receipt">
    <!-- Header -->
    <div class="logo-container">
        <!-- Ensure image path is correct -->
        <img src="/logos/SymbolRed.svg" class="logo-img" alt="S">
        <span class="logo-text">SpeakUp</span>
    </div>
    <div class="sub-header">LEARNING CENTER</div>

    <!-- Title -->
    <div class="receipt-title-box">
        <span class="receipt-title-text">MONTHLY PAYMENT RECEIPT</span>
    </div>

    <!-- Meta Info -->
    <div class="row">
        <div>Date: <span class="bold">{{ $printDate }}</span></div>
        <div>Time: <span class="bold">{{ $printTime }}</span></div>
    </div>

    <div class="line"></div>

    <!-- Student Info -->
    <div class="row">
        <div class="label">STUDENT:</div>
        <div class="value uppercase">{{ $studentName }}</div>
    </div>
    <div class="row">
        <div class="label">ROOM:</div>
        <div class="value uppercase">{{ $roomName }}</div>
    </div>
    <div class="row">
        <div class="label">COURSE:</div>
        <div class="value uppercase">{{ $courseName }}</div>
    </div>

    <div class="line"></div>

    <div class="row">
        <div class="label">MONTHLY FEE:</div>
        <div class="value">{{ number_format($monthlyBase, 0, '.', ' ') }}</div>
    </div>

    <!-- Payment Range Section -->
    <div class="row" style="margin-top: 5px; margin-bottom: 2px;">
        <div class="label" style="font-weight:bold; font-size: 9px;">PERIOD COVERED:</div>
    </div>

    <!-- Dynamic FROM / TO dates -->
    <div class="indent-row">
        <div class="indent-label">FROM:</div>
        <div class="value">{{ $periodFrom }}</div>
    </div>
    <div class="indent-row">
        <div class="indent-label">TO:</div>
        <div class="value">{{ $periodTo }}</div>
    </div>

    <div class="line"></div>

    <div class="row">
        <div class="label">DEBT:</div>
        <div class="value">{{ $debtMonths }} MONTH(S)</div>
    </div>
    <div class="row">
        <div class="label">METHOD:</div>
        <div class="value uppercase">{{ $method }}</div>
    </div>
    <div class="row">
        <div class="label">REST TO PAY:</div>
        <div class="value">{{ number_format($remainingToPay, 0, '.', ' ') }}</div>
    </div>

    <!-- Separator -->
    <div class="line" style="border-top: 2px solid #000;"></div>

    <!-- TOTAL PAID -->
    <div class="row">
        <div class="label bold" style="font-size: 14px;">PAID:</div>
        <div class="value bold" style="font-size: 14px;">{{ number_format($amount, 0, '.', ' ') }}</div>
    </div>

    <!-- Footer Line -->
    <div class="line" style="border-top: 1px solid #000; margin-top: 10px;"></div>

    <!-- Footer Content -->
    <div class="footer">
        <div class="bold" style="margin-bottom: 4px;">THANK YOU!</div>
        <div class="row" style="justify-content: center;">
            <div class="center">
                <div class="bold">SPEAKUP ADMIN</div>
                <div class="bold">99 968 11 77</div>
            </div>
        </div>
    </div>

    <!-- Extra padding at bottom for printer tear-off -->
    <div style="height: 10px;"></div>

    <!-- Print Button (Visible only on screen) -->
    <div class="center no-print" style="margin-top: 20px; padding-top: 10px; border-top: 1px dashed #ccc;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer;">Print Receipt</button>
    </div>
</div>

</body>
</html>