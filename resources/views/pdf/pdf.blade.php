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
            width: 50%;
            margin-right: auto;
            margin-left: auto;
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
            @if ($salary->employee->institute === 'NIOH' || $salary->employee->institute === 'BOTH')
                <img src="{{ public_path('images/nioh-header.png') }}" alt="Header" style="width:100%;">
            @else
                <img src="{{ public_path('images/rohc-header.png') }}" alt="Header" style="width:100%;">
            @endif
        </div>


        <!-- Title -->
        <div class="title">वेतन पर्ची / SALARY SLIP for
            {{ $monthName }}
            {{ $year }}</div>

        <!-- Employee Info -->
        <table style="margin-bottom: 5px">
            <tr>
                <td class="fontBold">कर्मचारी कोड / Emp. Code</td>
                <td>{{ $salary->employee->employee_code }}</td>
                <td class="fontBold">नाम / Name</td>
                <td>{{ $salary->employee->name }}</td>
            </tr>
            <tr>
                <td class="fontBold">पद / Designation</td>
                <td>{{ $salary->employee->latest_employee_designation->designation }}</td>
                <td class="fontBold">वर्ग-कैडर / Group-Cadre</td>
                <td>{{ $salary->employee->latest_employee_designation->job_group }}/{{ $salary->employee->latest_employee_designation->cadre }}
                </td>
            </tr>
            <tr>
                <td class="fontBold">मैट्रिक्स लैवल / Pay Level</td>
                <td>{{ $salary->employee->employee_pay_structure->pay_matrix_cell->pay_matrix_level->name }}</td>
                <td class="fontBold">पे इंडेक्स / Pay Index</td>
                <td>{{ $salary->employee->employee_pay_structure->pay_matrix_cell->index }} /
                    {{ $salary->employee->employee_pay_structure->pay_matrix_cell->amount }}</td>
            </tr>
            <tr>
                <td class="fontBold">ईमेल / Email</td>
                <td>{{ $salary->employee->email }}</td>
                <td class="fontBold">लिंग / Gender</td>
                <td>{{ $salary->employee->gender }}</td>
            </tr>
            <tr>
                <td class="fontBold">संस्थान / Institute</td>
                <td>{{ $salary->employee->institute }}</td>
                <td class="fontBold">वृद्धि महीना / Inc. Month</td>
                <td>
                    {{ \Carbon\Carbon::create()->month($salary->employee->increment_month)->format('F') }}
                </td>
            </tr>
            <tr>
                <td class="fontBold">पेंशन योजना / Pension Scheme</td>
                <td>{{ $salary->employee->pension_scheme }}</td>
                <td class="fontBold">पेंशन नंबर / Pension No</td>
                <td>{{ $salary->employee->pension_number }}</td>
            </tr>
            <tr>
                <td class="fontBold">स्थिति / Status</td>
                <td>{{ $salary->employee->employee_status[0]->status }}</td>
                <td class="fontBold">टिप्पणी / Remarks</td>
                <td>{{ $salary->remarks }}</td>
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
                            <th class="section-title">आय विवरण /
                                EARNINGS</th>
                            <th class="section-title">राशि / AMOUNT (₹)</th>
                        </tr>
                        @if (!empty($salary->paySlip->basic_pay))
                            <tr>
                                <td>मूल वेतन / Basic Pay</td>
                                <td class="amount">{{ number_format($salary->paySlip->basic_pay) }}</td>
                            </tr>
                        @endif

                        @if (!empty($salary->paySlip->npa_amount))
                            <tr>
                                <td>एन पी ए / NPA</td>
                                <td class="amount">{{ number_format($salary->paySlip->npa_amount) }}</td>
                            </tr>
                        @endif

                        @if (!empty($salary->paySlip->npa_amount) || !empty($salary->paySlip->basic_pay))
                            <tr>
                                <td>पे + एन पी ए / PAY + NPA</td>
                                <td class="amount">
                                    {{ number_format(($salary->paySlip->npa_amount ?? 0) + ($salary->paySlip->basic_pay ?? 0)) }}
                                </td>
                            </tr>
                        @endif

                        @if (!empty($salary->paySlip->da_amount))
                            <tr>
                                <td>महँगाई भत्ता / DA</td>
                                <td class="amount">{{ number_format($salary->paySlip->da_amount) }}</td>
                            </tr>
                        @endif

                        @if (!empty($salary->paySlip->hra_amount))
                            <tr>
                                <td>मकान किराया भत्ता / HRA</td>
                                <td class="amount">{{ number_format($salary->paySlip->hra_amount) }}</td>
                            </tr>
                        @endif

                        @if (!empty($salary->paySlip->transport_amount))
                            <tr>
                                <td>यात्रा भत्ता / Transport Allowance</td>
                                <td class="amount">{{ number_format($salary->paySlip->transport_amount) }}</td>
                            </tr>
                        @endif

                        @if (!empty($salary->paySlip->da_on_ta))
                            <tr>
                                <td>DA on T.A.</td>
                                <td class="amount">{{ number_format($salary->paySlip->da_on_ta) }}</td>
                            </tr>
                        @endif

                        @if (!empty($salary->paySlip->uniform_rate_amount))
                            <tr>
                                <td>वर्दी भत्ता / Uniform Allowance</td>
                                <td class="amount">{{ number_format($salary->paySlip->uniform_rate_amount) }}</td>
                            </tr>
                        @endif

                        @if (!empty($salary->paySlip->govt_contribution))
                            <tr>
                                <td>सरकारी योगदान / Government Contribution</td>
                                <td class="amount">{{ number_format($salary->paySlip->govt_contribution) }}</td>
                            </tr>
                        @endif

                        @if (!empty($salary->paySlip->spacial_pay))
                            <tr>
                                <td>विशेष वेतन / Special Pay </td>
                                <td class="amount">{{ number_format($salary->paySlip->spacial_pay) }}</td>
                            </tr>
                        @endif

                        @if (!empty($salary->paySlip->da_1))
                            <tr>
                                <td>महंगाई भत्ता 1 / D.A.1</td>
                                <td class="amount">{{ number_format($salary->paySlip->da_1) }}</td>
                            </tr>
                        @endif

                        @if (!empty($salary->paySlip->da_2))
                            <tr>
                                <td>महंगाई भत्ता / D.A.2</td>
                                <td class="amount">{{ number_format($salary->paySlip->da_2) }}</td>
                            </tr>
                        @endif

                        @if (!empty($salary->paySlip->itc_leave_salary))
                            <tr>
                                <td>एलटीसी छुट्टी वेतन / LTC Leave Salary</td>
                                <td class="amount">{{ number_format($salary->paySlip->itc_leave_salary) }}</td>
                            </tr>
                        @endif

                        {{-- Dynamic Arrears --}}
                        @if (!empty($salary->paySlip->salaryArrears) && count($salary->paySlip->salaryArrears) > 0)
                            @foreach ($salary->paySlip->salaryArrears as $arrear)
                                @if (!empty($arrear->amount))
                                    <tr>
                                        <td>{{ $arrear->type }}</td>
                                        <td class="amount">{{ number_format($arrear->amount) }}</td>
                                    </tr>
                                @endif
                            @endforeach
                        @endif

                        <tr>
                            <td><b>कुल आय / TOTAL EARNINGS</b></td>
                            <td class="amount"><b>{{ number_format($salary->paySlip->total_pay) }}</b></td>
                        </tr>
                    </table>
                </td>


                <!-- Deductions Table -->
                <td width="50%" valign="top" style="border: none;padding:0;margin:0">
                    <table width="100%" cellspacing="0" cellpadding="5">
                        <tr>
                            <th class="section-title">कटौती विवरण / DEDUCTIONS</th>
                            <th class="section-title">राशि / AMOUNT (₹)</th>
                        </tr>

                        @if (!empty($salary->deduction->income_tax))
                            <tr>
                                <td>आयकर / Income Tax</td>
                                <td class="amount">{{ number_format($salary->deduction->income_tax) }}</td>
                            </tr>
                        @endif

                        @if (!empty($salary->deduction->professional_tax))
                            <tr>
                                <td>व्यवसाय कर / Professional Tax</td>
                                <td class="amount">{{ number_format($salary->deduction->professional_tax) }}</td>
                            </tr>
                        @endif

                        @if (!empty($salary->deduction->gpf))
                            <tr>
                                <td>सा.भ.नि./ GPF</td>
                                <td class="amount">{{ number_format($salary->deduction->gpf) }}</td>
                            </tr>
                        @endif

                        @if (!empty($salary->deduction->license_fee))
                            <tr>
                                <td>लाइसेंस फीस / License Fee</td>
                                <td class="amount">{{ number_format($salary->deduction->license_fee) }}</td>
                            </tr>
                        @endif

                        @if (!empty($salary->deduction->nfch_donation))
                            <tr>
                                <td>दान / NFCH Donation</td>
                                <td class="amount">{{ number_format($salary->deduction->nfch_donation) }}</td>
                            </tr>
                        @endif

                        @if (!empty($salary->deduction->employee_contribution_10))
                            <tr>
                                <td>NPS Employee Contribution</td>
                                <td class="amount">{{ number_format($salary->deduction->employee_contribution_10) }}
                                </td>
                            </tr>
                        @endif

                        @if (!empty($salary->deduction->govt_contribution_14_recovery))
                            <tr>
                                <td>सरकारी योगदान रिकवरी / Govt. Contribution Rec.</td>
                                <td class="amount">
                                    {{ number_format($salary->deduction->govt_contribution_14_recovery) }}</td>
                            </tr>
                        @endif

                        @if (!empty($salary->deduction->gis))
                            <tr>
                                <td>समूह बीमा योजना / GIS</td>
                                <td class="amount">{{ number_format($salary->deduction->gis) }}</td>
                            </tr>
                        @endif

                        @if (!empty($salary->deduction->computer_advance_installment))
                            <tr>
                                <td>Computer Advance Installment</td>
                                <td class="amount">
                                    {{ number_format($salary->deduction->computer_advance_installment) }}</td>
                            </tr>
                        @endif

                        {{-- Dynamic Recoveries --}}
                        @if (!empty($salary->deduction->deductionRecoveries) && count($salary->deduction->deductionRecoveries) > 0)
                            @foreach ($salary->deduction->deductionRecoveries as $recoveries)
                                @if (!empty($recoveries->amount))
                                    <tr>
                                        <td>{{ $recoveries->type }}</td>
                                        <td class="amount">{{ number_format($recoveries->amount) }}</td>
                                    </tr>
                                @endif
                            @endforeach
                        @endif

                        <tr>
                            <td><b>कुल कटौती / TOTAL DEDUCTIONS</b></td>
                            <td class="amount">
                                <b>{{ number_format($salary->deduction->total_deductions - ($salary->deduction->lic + $salary->deduction->credit_society)) }}</b>
                            </td>
                        </tr>
                    </table>
                </td>

            </tr>
        </table>

        <div class="net-pay">
            शुद्ध वेतन / NET PAY (BEFORE ADDL. DEDUCTIONS): ₹
            {{ number_format($salary->paySlip->total_pay - $salary->deduction->total_deductions + ($salary->deduction->lic + $salary->deduction->credit_society)) }}
        </div>

        <div class="additional-deductions">
            <h3>अतिरिक्त कटौती / ADDITIONAL DEDUCTIONS</h3>
            <div class="table-responsive">
                <table class="deduction-table">
                    <tbody>
                        <tr>
                            <td>जीवन बीमा निगम / LIC</td>
                            <td class="amount">₹ {{ number_format(optional($salary->deduction)->lic ?? 0) }}</td>
                        </tr>
                        <tr>
                            <td>क्रेडिट सोसायटी / Credit Society</td>
                            <td class="amount">₹ {{ number_format(optional($salary->deduction)->credit_society ?? 0) }}
                            </td>
                        </tr>
                        <tr class="deduction-total">
                            <td><strong>कुल अतिरिक्त कटौती / Total Additional Deductions</strong></td>
                            <td class="amount"><strong>₹
                                    {{ number_format($salary->deduction->lic + $salary->deduction->credit_society) }}</strong>
                            </td>
                        </tr>
                        <tr class="deduction-final">
                            <td><strong>अंतिम शुद्ध वेतन / FINAL NET PAY</strong></td>
                            <td class="amount"><strong>₹ {{ number_format($salary->net_amount ?? 0) }}</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="slip-footer">
            <p>This is a computer-generated document and does not require a signature.</p>
        </div>


        <div>
            @if ($salary->employee->institute === 'NIOH' || $salary->employee->institute === 'BOTH')
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
            @else
                <div
                    style="display: flex; justify-content: space-between; align-items: flex-start; font-size: 12px; line-height: 1;padding:10px">

                    <!-- Left Side Address -->
                    <div>
                        <table style="border: none">
                            <tr style="border: none">
                                <td style="border: none">आई. सी. एम. आर. - क्षेत्रीय व्यावसायिक स्वास्थ्य केन्द्र
                                    (दक्षिण) (एन. आई. ओ. एच.)
                                </td>
                                <td style="border: none" class="amount"><strong>Mob :</strong> 9483507101</td>
                            </tr>
                            <tr style="border: none">
                                <td style="border: none">पूजनहल्ली मार्ग, कनमंगल पोस्ट, देवन्हल्ली तालुक, बैंगलोर-562110
                                </td>
                                <td style="border: none" class="amount">Phone :</strong> 080-22172500/501</td>
                            </tr>
                            <tr style="border: none">
                                <td style="border: none">ICMR - Regional Occupational Health Centre (Southern), (NIOH)
                                </td>
                                <td style="border: none" class="amount"><strong>FAX :</strong> 080-22172502</td>
                            </tr>
                            <tr style="border: none">
                                <td style="border: none">Poojanahalli Road, Kannamangala Post, Devanahalli Taluk,
                                    Bangalore-562110</td>
                                <td style="border: none" class="amount"><strong>Email :</strong> <a
                                        href="mailto:rohcs-admin@icmr.gov.in"
                                        style="color:#0d6efd; text-decoration: none;">rohcs-admin@icmr.gov.in</a></td>
                            </tr>
                            <tr style="border: none">
                                <td style="border: none">Karnataka, INDIA</td>
                            </tr>
                        </table>
                        {{-- <div style="text-align: right;float:right; font-size: 13px;">
                            <div><strong>Mob :</strong> 9483507101</div>
                            <div><strong>Phone :</strong> 080-22172500/501</div>
                            <div><strong>FAX :</strong> 080-22172502</div>
                            <div><strong>Email :</strong> <a href="mailto:rohcs-admin@icmr.gov.in"
                                    style="color:#0d6efd; text-decoration: none;">rohcs-admin@icmr.gov.in</a></div>
                        </div>
                        <div>आई. सी. एम. आर. - क्षेत्रीय व्यावसायिक स्वास्थ्य केन्द्र (दक्षिण) (एन. आई. ओ. एच.)</div>
                        <div>पूजनहल्ली मार्ग, कनमंगल पोस्ट, देवन्हल्ली तालुक, बैंगलोर-562110</div>
                        <div>ICMR - Regional Occupational Health Centre (Southern), (NIOH)</div>
                        <div>Poojanahalli Road, Kannamangala Post, Devanahalli Taluk, Bangalore-562110</div>
                        <div>Karnataka, INDIA</div> --}}
                    </div>

                    <!-- Right Side Contact -->

                </div>
            @endif
        </div>
    </div>
</body>

</html>
