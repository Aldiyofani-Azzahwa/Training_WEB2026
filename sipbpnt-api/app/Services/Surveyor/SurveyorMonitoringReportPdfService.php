<?php

declare(strict_types=1);

namespace App\Services\Surveyor;

use App\Support\Pdf\SimplePdfDocument;

final class SurveyorMonitoringReportPdfService
{
    private const ACCOUNT_NUMBER =
        '5.1.06.01.01.0001';

    /**
     * Margin 3 cm dalam satuan point PDF.
     */
    private const MARGIN =
        85.04;

    /**
     * Lebar A4 dikurangi margin kiri
     * dan kanan masing-masing 3 cm.
     */
    private const CONTENT_WIDTH =
        425.20;

    private const PAGE_CENTER =
        297.64;

    private const FIRST_LIST_TABLE_TOP =
        165.0;

    private const CONTINUATION_TABLE_TOP =
        42.0;

    private const LIST_TABLE_BOTTOM =
        790.0;

    private const CLOSING_TABLE_BOTTOM =
        430.0;

    private const CLOSING_MAX_ROWS =
        8;

    private const PARTICIPANT_HEADER_HEIGHT =
        34.0;

    private const PARTICIPANT_NUMBER_HEIGHT =
        15.0;

    /**
     * Total seluruh kolom: 425,20 point.
     *
     * @var array<int, float>
     */
    private const PARTICIPANT_COLUMNS = [
        22.0,
        62.0,
        70.0,
        100.0,
        52.0,
        58.0,
        61.20,
    ];

    public function render(
        array $data
    ): string {
        $document =
            new SimplePdfDocument();

        $this->renderMonitoringPage(
            $document,
            $data
        );

        $participantPages =
            $this->participantPages(
                $document,
                $data['participants']
            );

        $participantPageCount =
            count($participantPages);

        foreach (
            $participantPages
            as $index => $participants
        ) {
            $document->addPage();

            $isFirst =
                $index === 0;

            $isClosing =
                $index
                ===
                (
                    $participantPageCount
                    - 1
                );

            if ($isFirst) {
                $this->renderParticipantHeading(
                    $document,
                    $data
                );
            }

            $tableBottom =
                $this->renderParticipantTable(
                    $document,
                    $participants,
                    $isFirst
                        ? self::FIRST_LIST_TABLE_TOP
                        : self::CONTINUATION_TABLE_TOP
                );

            if ($isClosing) {
                $this->renderClosingSection(
                    $document,
                    $data,
                    $tableBottom
                );
            }

            $document->centeredText(
                self::PAGE_CENTER,
                813,
                'Halaman '
                    .($index + 1)
                    .' dari '
                    .$participantPageCount,
                7
            );
        }

        return $document->output();
    }

    private function renderMonitoringPage(
        SimplePdfDocument $document,
        array $data
    ): void {
        $document->addPage();

        $kelurahan =
            (string) $data[
                'assignment'
            ][
                'kelurahan'
            ][
                'name'
            ];

        $allocation =
            (string) $data[
                'period'
            ][
                'allocation_label'
            ];

        $eWarungs =
            $data[
                'summary'
            ][
                'e_warungs'
            ];

        $eWarungLabel =
            $eWarungs === []
                ? '................................................'
                : implode(
                    ', ',
                    $eWarungs
                );

        $document->centeredText(
            self::PAGE_CENTER,
            45,
            'LAPORAN MONITORING DAN EVALUASI PENYALURAN PROGRAM',
            10,
            true
        );

        $document->centeredText(
            self::PAGE_CENTER,
            66,
            'BANTUAN PANGAN NON TUNAI (BPNT) APBD',
            10,
            true
        );

        $kelurahanLabel =
            'Kelurahan '
            .$kelurahan;

        $kelurahanWidth =
            $document->measureText(
                $kelurahanLabel,
                10,
                true
            );

        $document->centeredText(
            self::PAGE_CENTER,
            101,
            $kelurahanLabel,
            10,
            true
        );

        $document->line(
            self::PAGE_CENTER
                - (
                    $kelurahanWidth / 2
                ),
            115,
            self::PAGE_CENTER
                + (
                    $kelurahanWidth / 2
                ),
            115,
            0.5
        );

        $intro =
            'Sehubungan dengan telah dilaksanakannya kegiatan penyaluran '
            .'Program Bantuan Pangan Non Tunai (BPNT) yang bersumber dari dana '
            .'APBD, untuk alokasi bulan '
            .$allocation
            .', yang bertempat di E-Warung '
            .$eWarungLabel
            .', maka bersama ini disampaikan laporan hasil monitoring dan '
            .'evaluasi sebagai berikut:';

        $introBottom =
            $document->wrappedText(
                self::MARGIN,
                145,
                $intro,
                self::CONTENT_WIDTH,
                8.5,
                13.5,
                false,
                8
            );

        $tableTop =
            max(
                240,
                $introBottom + 20
            );

        $columns = [
            28.0,
            90.0,
            88.0,
            95.0,
            124.20,
        ];

        $headerHeight =
            48.0;

        $commodities =
            array_values(
                $data[
                    'editable'
                ][
                    'commodities'
                ]
            );

        $rowCount =
            max(
                2,
                count($commodities)
            );

        $reasonLabel =
            $this->reasonSummary(
                $data[
                    'summary'
                ][
                    'reason_summary'
                ]
            );

        $rowHeights = [];

        for (
            $row = 0;
            $row < $rowCount;
            $row++
        ) {
            $commodityHeight =
                $this->requiredCellHeight(
                    $document,
                    (string) (
                        $commodities[$row]
                        ?? ''
                    ),
                    $columns[1],
                    7.2,
                    8,
                    3,
                    24,
                    56
                );

            $reasonHeight =
                $row === 0
                    ? $this->requiredCellHeight(
                        $document,
                        $reasonLabel,
                        $columns[4],
                        6.3,
                        7.2,
                        3,
                        24,
                        70
                    )
                    : 24;

            $rowHeights[] =
                max(
                    24,
                    $commodityHeight,
                    $reasonHeight
                );
        }

        $this->grid(
            $document,
            self::MARGIN,
            $tableTop,
            $columns,
            $headerHeight,
            $rowHeights
        );

        $headers = [
            'NO',
            'JENIS KOMODITI',
            'JUMLAH KPM YANG DI TOP UP',
            'JUMLAH KPM YANG TIDAK MENGAMBIL',
            'KETERANGAN TIDAK MENGAMBIL',
        ];

        $x =
            self::MARGIN;

        foreach (
            $headers
            as $index => $header
        ) {
            $document->cellText(
                $x,
                $tableTop,
                $columns[$index],
                $headerHeight,
                $header,
                6.7,
                false,
                'center',
                3,
                8
            );

            $x +=
                $columns[$index];
        }

        $rowTop =
            $tableTop
            + $headerHeight;

        for (
            $row = 0;
            $row < $rowCount;
            $row++
        ) {
            $rowHeight =
                $rowHeights[$row];

            $document->cellText(
                self::MARGIN,
                $rowTop,
                $columns[0],
                $rowHeight,
                (string) ($row + 1),
                7.2,
                false,
                'center'
            );

            $document->cellText(
                self::MARGIN
                    + $columns[0],
                $rowTop,
                $columns[1],
                $rowHeight,
                (string) (
                    $commodities[$row]
                    ?? ''
                ),
                7.2,
                false,
                'left',
                4,
                8
            );

            if ($row === 0) {
                $document->cellText(
                    self::MARGIN
                        + $columns[0]
                        + $columns[1],
                    $rowTop,
                    $columns[2],
                    $rowHeight,
                    (string) $data[
                        'summary'
                    ][
                        'total_kpm'
                    ],
                    7.5,
                    false,
                    'center'
                );

                $document->cellText(
                    self::MARGIN
                        + $columns[0]
                        + $columns[1]
                        + $columns[2],
                    $rowTop,
                    $columns[3],
                    $rowHeight,
                    (string) $data[
                        'summary'
                    ][
                        'not_taking'
                    ],
                    7.5,
                    false,
                    'center'
                );

                $document->cellText(
                    self::MARGIN
                        + $columns[0]
                        + $columns[1]
                        + $columns[2]
                        + $columns[3],
                    $rowTop,
                    $columns[4],
                    $rowHeight,
                    $reasonLabel,
                    6.3,
                    false,
                    'center',
                    3,
                    7.2
                );
            }

            $rowTop +=
                $rowHeight;
        }

        $evaluationTop =
            $rowTop + 24;

        $document->text(
            self::MARGIN,
            $evaluationTop,
            'Evaluasi:',
            8.5
        );

        $evaluationBottom =
            $this->renderEvaluationArea(
                $document,
                (string) $data[
                    'summary'
                ][
                    'evaluation'
                ],
                $evaluationTop + 23
            );

        $reporterTop =
            max(
                565,
                $evaluationBottom + 42
            );

        $document->centeredText(
            self::PAGE_CENTER,
            $reporterTop - 27,
            'Demikian laporan penyaluran ini dibuat untuk dijadikan periksa.',
            8
        );

        $reporterColumns = [
            190.0,
            150.0,
            85.20,
        ];

        $reporterRows = [
            52.0,
            42.0,
        ];

        $this->grid(
            $document,
            self::MARGIN,
            $reporterTop,
            $reporterColumns,
            24,
            $reporterRows
        );

        $reporterHeaders = [
            'PELAPOR',
            'NAMA',
            'TANDA TANGAN',
        ];

        $x =
            self::MARGIN;

        foreach (
            $reporterHeaders
            as $index => $header
        ) {
            $document->cellText(
                $x,
                $reporterTop,
                $reporterColumns[$index],
                24,
                $header,
                7,
                false,
                'center'
            );

            $x +=
                $reporterColumns[$index];
        }

        $document->cellText(
            self::MARGIN,
            $reporterTop + 24,
            $reporterColumns[0],
            $reporterRows[0],
            '1. Kasi Sosial & Pemberdayaan Masyarakat selaku Anggota Tim Koordinasi BPNT',
            6.7,
            false,
            'left',
            7,
            9
        );

        $document->cellText(
            self::MARGIN
                + $reporterColumns[0],
            $reporterTop + 24,
            $reporterColumns[1],
            $reporterRows[0],
            (string) (
                $data[
                    'editable'
                ][
                    'social_officer_name'
                ]
                ?? ''
            ),
            7,
            false,
            'center'
        );

        $document->cellText(
            self::MARGIN,
            $reporterTop
                + 24
                + $reporterRows[0],
            $reporterColumns[0],
            $reporterRows[1],
            '2. Pendamping Penyaluran BPNT',
            6.7,
            false,
            'left',
            7,
            9
        );

        $document->cellText(
            self::MARGIN
                + $reporterColumns[0],
            $reporterTop
                + 24
                + $reporterRows[0],
            $reporterColumns[1],
            $reporterRows[1],
            (string) (
                $data[
                    'editable'
                ][
                    'distribution_assistant_name'
                ]
                ?? ''
            ),
            7,
            false,
            'center'
        );
    }

    private function renderEvaluationArea(
        SimplePdfDocument $document,
        string $evaluation,
        float $top
    ): float {
        $fontSize =
            7.4;

        $lineHeight =
            8.4;

        $bandHeight =
            19.0;

        $availableWidth =
            self::CONTENT_WIDTH
            - 8;

        $lines =
            $document->wrapLines(
                $evaluation,
                $availableWidth,
                $fontSize
            );

        $bandCount =
            min(
                6,
                max(
                    4,
                    count($lines)
                )
            );

        $visibleLines =
            array_slice(
                $lines,
                0,
                $bandCount
            );

        for (
            $line = 0;
            $line <= $bandCount;
            $line++
        ) {
            $document->dottedLine(
                self::MARGIN,
                $top
                    + (
                        $line
                        * $bandHeight
                    ),
                self::MARGIN
                    + self::CONTENT_WIDTH,
                $top
                    + (
                        $line
                        * $bandHeight
                    ),
                0.3
            );
        }

        foreach (
            $visibleLines
            as $index => $line
        ) {
            $document->cellText(
                self::MARGIN + 4,
                $top
                    + (
                        $index
                        * $bandHeight
                    ),
                self::CONTENT_WIDTH - 8,
                $bandHeight,
                $line,
                $fontSize,
                false,
                'left',
                0,
                $lineHeight
            );
        }

        return $top
            + (
                $bandCount
                * $bandHeight
            );
    }

    private function renderParticipantHeading(
        SimplePdfDocument $document,
        array $data
    ): void {
        $labelWidth =
            74.0;

        $valueWidth =
            self::CONTENT_WIDTH
            - $labelWidth;

        $document->cellText(
            self::MARGIN,
            37,
            270,
            22,
            'DAFTAR : PENERIMA BANTUAN PANGAN NON TUNAI (BPNT) APBD TAHUN '
                .$data[
                    'period'
                ][
                    'year'
                ],
            6.5,
            false,
            'left',
            0,
            8
        );

        $document->rightText(
            self::MARGIN
                + self::CONTENT_WIDTH,
            40,
            'Rekening : '
                .self::ACCOUNT_NUMBER,
            6.5
        );

        $metadata = [
            [
                'KEGIATAN',
                'PENGELOLAAN DATA FAKIR MISKIN CAKUPAN DAERAH KABUPATEN/KOTA',
            ],
            [
                'SUB KEGIATAN',
                'FASILITASI BANTUAN SOSIAL KESEJAHTERAAN KELUARGA',
            ],
            [
                'TANGGAL',
                strtoupper(
                    (string) $data[
                        'period'
                    ][
                        'allocation_label'
                    ]
                ),
            ],
            [
                'KELURAHAN',
                strtoupper(
                    (string) $data[
                        'assignment'
                    ][
                        'kelurahan'
                    ][
                        'name'
                    ]
                ),
            ],
        ];

        foreach (
            $metadata
            as $index => $row
        ) {
            $top =
                66
                + (
                    $index
                    * 19
                );

            $document->cellText(
                self::MARGIN,
                $top,
                $labelWidth,
                18,
                $row[0],
                6.6,
                false,
                'left',
                0,
                8
            );

            $document->cellText(
                self::MARGIN
                    + $labelWidth,
                $top,
                $valueWidth,
                18,
                ': '.$row[1],
                6.6,
                false,
                'left',
                0,
                8
            );
        }
    }

    private function renderParticipantTable(
        SimplePdfDocument $document,
        array $participants,
        float $top
    ): float {
        $headers = [
            'NO',
            'NAMA',
            'NIK',
            'ALAMAT',
            'PERIODE',
            'UANG YANG DITERIMA',
            'STATUS KPM',
        ];

        $rowHeights = [];

        foreach (
            $participants
            as $participant
        ) {
            $rowHeights[] =
                $this->participantRowHeight(
                    $document,
                    $participant
                );
        }

        $this->grid(
            $document,
            self::MARGIN,
            $top,
            self::PARTICIPANT_COLUMNS,
            self::PARTICIPANT_HEADER_HEIGHT,
            array_merge(
                [
                    self::PARTICIPANT_NUMBER_HEIGHT,
                ],
                $rowHeights
            )
        );

        $x =
            self::MARGIN;

        foreach (
            $headers
            as $index => $header
        ) {
            $document->cellText(
                $x,
                $top,
                self::PARTICIPANT_COLUMNS[
                    $index
                ],
                self::PARTICIPANT_HEADER_HEIGHT,
                $header,
                5.9,
                false,
                'center',
                2,
                7
            );

            $document->cellText(
                $x,
                $top
                    + self::PARTICIPANT_HEADER_HEIGHT,
                self::PARTICIPANT_COLUMNS[
                    $index
                ],
                self::PARTICIPANT_NUMBER_HEIGHT,
                (string) ($index + 1),
                5.8,
                false,
                'center',
                1,
                6.5
            );

            $x +=
                self::PARTICIPANT_COLUMNS[
                    $index
                ];
        }

        $rowTop =
            $top
            + self::PARTICIPANT_HEADER_HEIGHT
            + self::PARTICIPANT_NUMBER_HEIGHT;

        foreach (
            $participants
            as $index => $participant
        ) {
            $rowHeight =
                $rowHeights[$index];

            $values =
                $this->participantValues(
                    $participant
                );

            $alignments = [
                'center',
                'left',
                'center',
                'left',
                'center',
                'right',
                'center',
            ];

            $x =
                self::MARGIN;

            foreach (
                $values
                as $column => $value
            ) {
                $document->cellText(
                    $x,
                    $rowTop,
                    self::PARTICIPANT_COLUMNS[
                        $column
                    ],
                    $rowHeight,
                    $value,
                    5.6,
                    false,
                    $alignments[$column],
                    2,
                    6.5
                );

                $x +=
                    self::PARTICIPANT_COLUMNS[
                        $column
                    ];
            }

            $rowTop +=
                $rowHeight;
        }

        return $rowTop;
    }

    /**
     * @return array<int, string>
     */
    private function participantValues(
        array $participant
    ): array {
        $status =
            (string) $participant[
                'status'
            ][
                'label'
            ];

        $reason =
            trim(
                (string) (
                    $participant[
                        'status'
                    ][
                        'reason'
                    ]
                    ?? ''
                )
            );

        if ($reason !== '') {
            $status .=
                ' - '
                .$reason;
        }

        return [
            (string) $participant[
                'number'
            ],

            (string) $participant[
                'name'
            ],

            (string) $participant[
                'nik'
            ],

            (string) $participant[
                'address'
            ],

            strtoupper(
                (string) $participant[
                    'period'
                ]
            ),

            'Rp '
                .number_format(
                    (int) $participant[
                        'amount'
                    ],
                    0,
                    ',',
                    '.'
                ),

            $status,
        ];
    }

    private function participantRowHeight(
        SimplePdfDocument $document,
        array $participant
    ): float {
        $values =
            $this->participantValues(
                $participant
            );

        $lineHeight =
            6.5;

        $maxLines =
            1;

        foreach (
            $values
            as $column => $value
        ) {
            $lineCount =
                count(
                    $document->wrapLines(
                        $value,
                        self::PARTICIPANT_COLUMNS[
                            $column
                        ] - 4,
                        5.6
                    )
                );

            $maxLines =
                max(
                    $maxLines,
                    $lineCount
                );
        }

        return min(
            72,
            max(
                23,
                (
                    $maxLines
                    * $lineHeight
                ) + 6
            )
        );
    }

    /**
     * @return array<int, array<int, array<string, mixed>>>
     */
    private function participantPages(
        SimplePdfDocument $document,
        array $participants
    ): array {
        $participants =
            array_values(
                $participants
            );

        if ($participants === []) {
            return [
                [],
            ];
        }

        $firstContentTop =
            self::FIRST_LIST_TABLE_TOP
            + self::PARTICIPANT_HEADER_HEIGHT
            + self::PARTICIPANT_NUMBER_HEIGHT;

        $allRowsHeight =
            array_sum(
                array_map(
                    fn (
                        array $participant
                    ): float =>
                        $this->participantRowHeight(
                            $document,
                            $participant
                        ),
                    $participants
                )
            );

        if (
            $firstContentTop
                + $allRowsHeight
            <=
            self::CLOSING_TABLE_BOTTOM
        ) {
            return [
                $participants,
            ];
        }

        $closingCapacity =
            self::CLOSING_TABLE_BOTTOM
            - self::CONTINUATION_TABLE_TOP
            - self::PARTICIPANT_HEADER_HEIGHT
            - self::PARTICIPANT_NUMBER_HEIGHT;

        $closing = [];

        $closingHeight =
            0.0;

        while (
            $participants !== []
            &&
            count($closing)
                < self::CLOSING_MAX_ROWS
        ) {
            $candidate =
                $participants[
                    count($participants) - 1
                ];

            $candidateHeight =
                $this->participantRowHeight(
                    $document,
                    $candidate
                );

            if (
                $closing !== []
                &&
                $closingHeight
                    + $candidateHeight
                >
                $closingCapacity
            ) {
                break;
            }

            array_unshift(
                $closing,
                array_pop(
                    $participants
                )
            );

            $closingHeight +=
                $candidateHeight;
        }

        $pages = [];

        $firstCapacity =
            self::LIST_TABLE_BOTTOM
            - $firstContentTop;

        $pages[] =
            $this->takeParticipantsForHeight(
                $document,
                $participants,
                $firstCapacity
            );

        $continuationContentTop =
            self::CONTINUATION_TABLE_TOP
            + self::PARTICIPANT_HEADER_HEIGHT
            + self::PARTICIPANT_NUMBER_HEIGHT;

        $continuationCapacity =
            self::LIST_TABLE_BOTTOM
            - $continuationContentTop;

        while (
            $participants !== []
        ) {
            $pages[] =
                $this->takeParticipantsForHeight(
                    $document,
                    $participants,
                    $continuationCapacity
                );
        }

        $pages[] =
            $closing;

        return array_values(
            array_filter(
                $pages,
                fn (
                    array $page
                ): bool =>
                    $page !== []
            )
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function takeParticipantsForHeight(
        SimplePdfDocument $document,
        array &$participants,
        float $capacity
    ): array {
        $page = [];

        $used =
            0.0;

        while (
            $participants !== []
        ) {
            $height =
                $this->participantRowHeight(
                    $document,
                    $participants[0]
                );

            if (
                $page !== []
                &&
                $used + $height
                > $capacity
            ) {
                break;
            }

            $page[] =
                array_shift(
                    $participants
                );

            $used +=
                $height;

            if (
                $used >= $capacity
            ) {
                break;
            }
        }

        return $page;
    }

    private function renderClosingSection(
        SimplePdfDocument $document,
        array $data,
        float $tableBottom
    ): void {
        $totalTop =
            $tableBottom;

        $amountColumnLeft =
            self::MARGIN + 300;

        $amountColumnWidth =
            self::CONTENT_WIDTH - 300;

        $document->rectangle(
            self::MARGIN,
            $totalTop,
            self::CONTENT_WIDTH,
            24,
            0.5
        );

        $document->line(
            $amountColumnLeft,
            $totalTop,
            $amountColumnLeft,
            $totalTop + 24,
            0.5
        );

        $document->cellText(
            $amountColumnLeft,
            $totalTop,
            $amountColumnWidth,
            24,
            'Rp '
                .number_format(
                    (int) $data[
                        'summary'
                    ][
                        'total_balance'
                    ],
                    0,
                    ',',
                    '.'
                ),
            7,
            true,
            'center'
        );

        $terbilangTop =
            $totalTop + 41;

        $document->text(
            self::MARGIN + 16,
            $terbilangTop,
            'Terbilang',
            7.5
        );

        $document->wrappedText(
            self::MARGIN + 86,
            $terbilangTop,
            ': '
                .$this->terbilang(
                    (int) $data[
                        'summary'
                    ][
                        'total_balance'
                    ]
                ),
            self::CONTENT_WIDTH - 102,
            7.5,
            10,
            false,
            2
        );

        $officialTop =
            max(
                $terbilangTop + 55,
                470
            );

        $officialWidth =
            self::CONTENT_WIDTH / 3;

        $leftCenter =
            self::MARGIN
            + (
                $officialWidth / 2
            );

        $middleCenter =
            self::MARGIN
            + $officialWidth
            + (
                $officialWidth / 2
            );

        $rightCenter =
            self::MARGIN
            + (
                2
                * $officialWidth
            )
            + (
                $officialWidth / 2
            );

        $document->centeredText(
            $leftCenter,
            $officialTop,
            'Mengetahui',
            7.2
        );

        $document->centeredText(
            $leftCenter,
            $officialTop + 14,
            'Plt. KEPALA DINAS SOSIAL, PPPA',
            6.7
        );

        $document->centeredText(
            $leftCenter,
            $officialTop + 27,
            'KOTA MOJOKERTO',
            6.7
        );

        $document->centeredText(
            $leftCenter,
            $officialTop + 40,
            'Selaku Pengguna Anggaran',
            6.7
        );

        $document->centeredText(
            $middleCenter,
            $officialTop + 14,
            'Pejabat Pelaksana Teknis Kegiatan',
            6.7
        );

        $document->centeredText(
            $middleCenter,
            $officialTop + 27,
            '(PPTK)',
            6.7
        );

        $document->centeredText(
            $rightCenter,
            $officialTop,
            'Mojokerto, '
                .strtolower(
                    (string) $data[
                        'period'
                    ][
                        'allocation_label'
                    ]
                ),
            6.7
        );

        $document->centeredText(
            $rightCenter,
            $officialTop + 14,
            'Dibayar lunas tgl. ........................',
            6.7
        );

        $document->centeredText(
            $rightCenter,
            $officialTop + 27,
            'BENDAHARA PENGELUARAN',
            6.7
        );

        /*
         * Dua baris kosong setelah judul
         * pada blok tanda tangan.
         */
        $nameTop =
            $officialTop + 82;

        $this->underlinedOfficial(
            $document,
            self::MARGIN,
            $officialWidth,
            $nameTop,
            'dr. FARIDA MARIANA, M.Kes.',
            'Pembina Utama Muda',
            'NIP. 19781104 200501 2 014'
        );

        $this->underlinedOfficial(
            $document,
            self::MARGIN
                + $officialWidth,
            $officialWidth,
            $nameTop,
            'BASUKI RACHMANTO, SH., MM',
            'Pembina',
            'NIP. 19700404 198903 1 006'
        );

        $this->underlinedOfficial(
            $document,
            self::MARGIN
                + (
                    2
                    * $officialWidth
                ),
            $officialWidth,
            $nameTop,
            'NURWIDIA KUSUMA DEWI, SE',
            'Penata Muda Tk. I',
            'NIP. 19810326 200501 2 012'
        );
    }

    private function underlinedOfficial(
        SimplePdfDocument $document,
        float $left,
        float $width,
        float $top,
        string $name,
        string $rank,
        string $nip
    ): void {
        $document->cellText(
            $left + 3,
            $top,
            $width - 6,
            18,
            $name,
            6.5,
            true,
            'center',
            1,
            7.2
        );

        $document->line(
            $left + 8,
            $top + 18,
            $left
                + $width
                - 8,
            $top + 18,
            0.4
        );

        $document->centeredText(
            $left
                + (
                    $width / 2
                ),
            $top + 21,
            $rank,
            6.5
        );

        $document->centeredText(
            $left
                + (
                    $width / 2
                ),
            $top + 34,
            $nip,
            6.5
        );
    }

    private function requiredCellHeight(
        SimplePdfDocument $document,
        string $text,
        float $width,
        float $fontSize,
        float $lineHeight,
        float $padding,
        float $minimum,
        float $maximum
    ): float {
        $lines =
            count(
                $document->wrapLines(
                    $text,
                    max(
                        1,
                        $width
                            - (
                                2
                                * $padding
                            )
                    ),
                    $fontSize
                )
            );

        return min(
            $maximum,
            max(
                $minimum,
                (
                    $lines
                    * $lineHeight
                )
                + (
                    2
                    * $padding
                )
            )
        );
    }

    private function grid(
        SimplePdfDocument $document,
        float $left,
        float $top,
        array $columns,
        float $headerHeight,
        array $rowHeights
    ): void {
        $width =
            array_sum($columns);

        $height =
            $headerHeight
            + array_sum(
                $rowHeights
            );

        $document->rectangle(
            $left,
            $top,
            $width,
            $height,
            0.5
        );

        $x =
            $left;

        foreach (
            array_slice(
                $columns,
                0,
                -1
            )
            as $column
        ) {
            $x +=
                $column;

            $document->line(
                $x,
                $top,
                $x,
                $top + $height,
                0.5
            );
        }

        $rowTop =
            $top
            + $headerHeight;

        $document->line(
            $left,
            $rowTop,
            $left + $width,
            $rowTop,
            0.5
        );

        foreach (
            array_slice(
                $rowHeights,
                0,
                -1
            )
            as $rowHeight
        ) {
            $rowTop +=
                $rowHeight;

            $document->line(
                $left,
                $rowTop,
                $left + $width,
                $rowTop,
                0.5
            );
        }
    }

    private function reasonSummary(
        array $reasons
    ): string {
        if ($reasons === []) {
            return '-';
        }

        return collect($reasons)
            ->map(
                fn (
                    array $reason
                ): string =>
                    $reason['label']
                    .' ('
                    .$reason['count']
                    .')'
            )
            ->implode('; ');
    }

    private function terbilang(
        int $amount
    ): string {
        if ($amount === 0) {
            return 'Nol Rupiah';
        }

        return ucwords(
            trim(
                $this->spellNumber(
                    $amount
                )
            )
        ).' Rupiah';
    }

    private function spellNumber(
        int $number
    ): string {
        $words = [
            '',
            'satu',
            'dua',
            'tiga',
            'empat',
            'lima',
            'enam',
            'tujuh',
            'delapan',
            'sembilan',
            'sepuluh',
            'sebelas',
        ];

        if ($number < 12) {
            return $words[$number];
        }

        if ($number < 20) {
            return $this->spellNumber(
                $number - 10
            ).' belas';
        }

        if ($number < 100) {
            return $this->spellNumber(
                intdiv(
                    $number,
                    10
                )
            )
                .' puluh '
                .$this->spellNumber(
                    $number % 10
                );
        }

        if ($number < 200) {
            return 'seratus '
                .$this->spellNumber(
                    $number - 100
                );
        }

        if ($number < 1000) {
            return $this->spellNumber(
                intdiv(
                    $number,
                    100
                )
            )
                .' ratus '
                .$this->spellNumber(
                    $number % 100
                );
        }

        if ($number < 2000) {
            return 'seribu '
                .$this->spellNumber(
                    $number - 1000
                );
        }

        if (
            $number
            < 1_000_000
        ) {
            return $this->spellNumber(
                intdiv(
                    $number,
                    1000
                )
            )
                .' ribu '
                .$this->spellNumber(
                    $number % 1000
                );
        }

        if (
            $number
            < 1_000_000_000
        ) {
            return $this->spellNumber(
                intdiv(
                    $number,
                    1_000_000
                )
            )
                .' juta '
                .$this->spellNumber(
                    $number
                    % 1_000_000
                );
        }

        if (
            $number
            < 1_000_000_000_000
        ) {
            return $this->spellNumber(
                intdiv(
                    $number,
                    1_000_000_000
                )
            )
                .' miliar '
                .$this->spellNumber(
                    $number
                    % 1_000_000_000
                );
        }

        return $this->spellNumber(
            intdiv(
                $number,
                1_000_000_000_000
            )
        )
            .' triliun '
            .$this->spellNumber(
                $number
                % 1_000_000_000_000
            );
    }
}