<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    {{-- <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Devanagari&display=swap" rel="stylesheet"> --}}
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }

        .header {
            text-align: center;
            margin-bottom: 5px;
        }

        .header img {
            max-width: 100%;
            height: auto;
        }

        .title {
            font-weight: bold;
            font-size: 14px;
            text-align: center;
            margin: 8px 0 12px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            /* margin-bottom: 12px; */
        }

        th,
        td {
            border: 1px solid #000;
            padding: 2px 4px;
            font-size: 10px;
        }

        th.section-title {
            font-weight: bold;
            background: #f2f2f2;
            text-align: left;
        }

        td.amount {
            text-align: right;
        }

        h4 {
            margin: 10px 0 6px;
            font-size: 13px;
        }

        p {
            font-size: 12px;
            margin: 2px 0;
        }

        .fontBold {
            font-weight: bold;
        }

        .net-pay {
            padding: 5px 5px 7px;
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            border: 1px solid #000;
            background: #f8f8f8;
        }

        .additional-deductions {
            /* margin-top: 20px; */
            width: 50%;
            margin-right: auto;
            margin-left: auto;
        }

        .additional-deductions h3 {
            margin-bottom: 10px;
            text-align: center;
            font-size: 15px;
            color: #32325d;
        }

        .deduction-table {
            width: 100%;
            border-collapse: collapse;
        }

        .deduction-table td {
            border: 1px solid #000;
            padding: 2px 4px;
            font-size: 10px;
        }

        .deduction-table .amount {
            text-align: right;
        }

        .deduction-total {
            background: #f0f0f0;
            font-weight: bold;
        }

        .deduction-final {
            background: #e0e0e0;
            font-weight: bold;
        }

        .slip-footer {
            margin-top: 10px;
            text-align: center;
            font-size: 12px;
        }

        @font-face {
            font-family: 'mangal';
            src: url('{{ public_path('fonts/MANGAL.TTF') }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        @font-face {
            font-family: 'mangal';
            src: url('{{ public_path('fonts/MANGAL.TTF') }}') format('truetype');
            font-weight: bold;
            font-style: normal;
        }

        /* Fallback Devanagari font */
        @font-face {
            font-family: 'Noto Sans Devanagari';
            src: url('{{ public_path('fonts/NotoSansDevanagari-Regular.ttf') }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }

        @font-face {
            font-family: 'Noto Sans Devanagari';
            src: url('{{ public_path('fonts/NotoSansDevanagari-Bold.ttf') }}') format('truetype');
            font-weight: bold;
            font-style: normal;
        }
    </style>
</head>

<body>

    <!-- Header -->
    <div style="border: 1px solid #000">
        <div class="header">
            {{-- @if ($pension->pensioner->user->institute === 'NIOH' || $pension->pensioner->user->institute === 'BOTH') --}}
            <img src="{{ public_path('images/nioh-header.png') }}" alt="Header" style="width:100%;">
            {{-- @else --}}
            {{-- <img src="{{ url('images/rohc-header.png') }}" alt="Header" style="width:100%;"> --}}
            {{-- @endif --}}
        </div>


        <!-- Title -->
        <div class="title">पेंशन पर्ची / PENSION SLIP for {{ $monthName }}
            {{ $year }}</div>

        <!-- user Info -->
        <table style="margin-bottom: 5px;border-bottom: 1px solid #000;">
            <tr>
                <td class="fontBold">पीपीओ नंबर / PPO No.</td>
                <td>{{ $pension->pensioner->ppo_no }}</td>
                <td class="fontBold">नाम / Name</td>
                <td>{{ $pension->pensioner->name }}</td>
            </tr>
            <tr>
                <td class="fontBold">संबंध / relation</td>
                <td>{{ $pension->pensioner->relation }}</td>
                <td class="fontBold">पेंशन प्रकार / Pension Type</td>
                <td>{{ $pension->pensioner->type_of_pension }}
                </td>
            </tr>
            <tr>
                <td class="fontBold">मोबाइल नंबर / MoBILE No.</td>
                <td>{{ $pension->pensioner->mobile_no }}</td>
                <td class="fontBold" style="border-bottom: 1px solid #000">टिप्पणियाँ / remarks</td>
                <td>{{ $pension->monthlyPension->remarks }}</td>
            </tr>
            <tr>
                <td class="fontBold" style="border-right: 1px solid #000">ईमेल / Email</td>
                <td>{{ $pension->pensioner->email }}</td>
            </tr>
        </table>


        <!-- Parent table to hold Earnings and Deductions side by side -->
        <table width="100%" cellspacing="0" cellpadding="5" border="0" style="border: none">
            <tr style="border: none">
                <!-- Earnings Table -->
                <!-- Earnings Table -->
                <td width="50%" valign="top" style="border: none;padding:0;margin:0">
                    <table width="100%" cellspacing="0" cellpadding="5">
                        <tr>
                            <th class="section-title">पेंशन विवरण / PENSION DETAILS</th>
                            <th class="section-title">राशि / AMOUNT (₹)</th>
                        </tr>
                        @if (!empty($pension->monthlyPension->basic_pension))
                            <tr>
                                <td>मूल पेंशन / Basic Pension</td>
                                <td class="amount">{{ number_format($pension->monthlyPension->basic_pension) }}</td>
                            </tr>
                        @endif

                        @if (!empty($pension->monthlyPension->additional_pension))
                            <tr>
                                <td>अतिरिक्त पेंशन / Additional Pension</td>
                                <td class="amount">{{ number_format($pension->monthlyPension->additional_pension) }}
                                </td>
                            </tr>
                        @endif

                        @if (!empty($pension->monthlyPension->dr_amount))
                            <tr>
                                <td>महंगाई राहत / Dearness Relief</td>
                                <td class="amount">
                                    {{ number_format($pension->monthlyPension->dr_amount ?? 0) }}
                                </td>
                            </tr>
                        @endif

                        @if (!empty($pension->monthlyPension->medical_allowance))
                            <tr>
                                <td>चिकित्सा भत्ता / Medical Allowance</td>
                                <td class="amount">{{ number_format($pension->monthlyPension->medical_allowance) }}
                                </td>
                            </tr>
                        @endif


                        {{-- Dynamic Arrears --}}
                        @if (!empty($pension->monthlyPension->arrears) && count($pension->monthlyPension->arrears) > 0)
                            @foreach ($pension->monthlyPension->arrears as $arrear)
                                @if (!empty($arrear->amount))
                                    <tr>
                                        <td>{{ $arrear->type }}</td>
                                        <td class="amount">{{ number_format($arrear->amount) }}</td>
                                    </tr>
                                @endif
                            @endforeach
                        @endif

                        <tr>
                            <td><b>कुल पेंशन / TOTAL PENSION</b></td>
                            <td class="amount"><b>{{ number_format($pension->monthlyPension->total_pension) }}</b></td>
                        </tr>
                    </table>
                </td>


                <!-- Deductions Table -->
                <td width="50%" valign="top" style="border: none;padding:0;margin:0">
                    <table width="100%" cellspacing="0" cellpadding="5">
                        <tr>
                            <th class="section-title">कटौती / DEDUCTIONS</th>
                            <th class="section-title">राशि / AMOUNT (₹)</th>
                        </tr>

                        @if (!empty($pension->pensionerDeduction->income_tax))
                            <tr>
                                <td>आयकर / Income Tax</td>
                                <td class="amount">{{ number_format($pension->pensionerDeduction->income_tax) }}</td>
                            </tr>
                        @endif

                        @if (!empty($pension->pensionerDeduction->recovery))
                            <tr>
                                <td>रिकवरी / Recovery</td>
                                <td class="amount">{{ number_format($pension->pensionerDeduction->recovery) }}</td>
                            </tr>
                        @endif

                        @if (!empty($pension->pensionerDeduction->other))
                            <tr>
                                <td>Other</td>
                                <td class="amount">{{ number_format($pension->pensionerDeduction->other) }}</td>
                            </tr>
                        @endif

                        @if (!empty($pension->pensionerDeduction->commutation_amount))
                            <tr>
                                <td>परिवर्तनीय पेंशन / Commutation Pension</td>
                                <td class="amount">
                                    {{ number_format($pension->pensionerDeduction->commutation_amount) }}</td>
                            </tr>
                        @endif

                        <tr>
                            <td><b>कुल कटौती / TOTAL DEDUCTIONS</b></td>
                            <td class="amount">
                                <b>{{ number_format($pension->pensionerDeduction->amount) }}</b>
                            </td>
                        </tr>
                    </table>
                </td>

            </tr>
        </table>

        <div class="net-pay">
            कुल देय पेंशन / NET PENSION PAYABLE: ₹
            {{ number_format($pension->net_pension) }}
        </div>

        <div class="slip-footer">
            <p>This is a computer-generated document and does not require a signature.</p>
        </div>
        <div>
            <div
                style="display: flex; justify-content: space-between; align-items: flex-start; font-size: 12px; line-height: 1;padding:10px">

                <div>
                    <table style="border: none">
                        <tr style="border: none">
                            <td style="border: none">मेघानी नगर, अहमदाबाद</td>
                            <td class="amount" style="border: none">Tel: +91-79-22688700, 22686351</td>
                        </tr>
                    </table>
                    <div>गुजरात, 380016, भारत</div>
                    <div>
                        Meghaninagar, Ahmedabad,
                    </div>
                    <div><strong>Gujarat – 380016, India</strong></div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
