<?php

declare(strict_types=1);

namespace App\Support\Pdf;

use RuntimeException;

final class SimplePdfDocument
{
    public const PAGE_WIDTH = 595.28;
    public const PAGE_HEIGHT = 841.89;

    /** @var array<int, array<int, string>> */
    private array $pages = [];

    private int $currentPage = -1;

    public function addPage(): int
    {
        $this->pages[] = [];
        $this->currentPage = count($this->pages) - 1;

        return $this->currentPage;
    }

    public function setPage(int $page): void
    {
        if (! array_key_exists($page, $this->pages)) {
            throw new RuntimeException(
                'Halaman PDF tidak ditemukan.'
            );
        }

        $this->currentPage = $page;
    }

    public function pageCount(): int
    {
        return count($this->pages);
    }

    public function text(
        float $x,
        float $top,
        string $text,
        float $size = 9,
        bool $bold = false
    ): void {
        $font = $bold ? 'F2' : 'F1';

        $baseline =
            self::PAGE_HEIGHT
            - $top
            - $size;

        $this->command(
            'BT /'
            .$font
            .' '
            .$this->number($size)
            .' Tf '
            .$this->number($x)
            .' '
            .$this->number($baseline)
            .' Td ('
            .$this->escape($text)
            .') Tj ET'
        );
    }

    public function centeredText(
        float $centerX,
        float $top,
        string $text,
        float $size = 9,
        bool $bold = false
    ): void {
        $this->text(
            $centerX
                - (
                    $this->measureText(
                        $text,
                        $size,
                        $bold
                    ) / 2
                ),
            $top,
            $text,
            $size,
            $bold
        );
    }

    public function rightText(
        float $right,
        float $top,
        string $text,
        float $size = 9,
        bool $bold = false
    ): void {
        $this->text(
            $right
                - $this->measureText(
                    $text,
                    $size,
                    $bold
                ),
            $top,
            $text,
            $size,
            $bold
        );
    }

    public function line(
        float $x1,
        float $top1,
        float $x2,
        float $top2,
        float $width = 0.5
    ): void {
        $this->command(
            '[] 0 d '
            .$this->number($width)
            .' w '
            .$this->number($x1)
            .' '
            .$this->number(
                self::PAGE_HEIGHT - $top1
            )
            .' m '
            .$this->number($x2)
            .' '
            .$this->number(
                self::PAGE_HEIGHT - $top2
            )
            .' l S'
        );
    }

    public function dottedLine(
        float $x1,
        float $top1,
        float $x2,
        float $top2,
        float $width = 0.35,
        float $dot = 1.2,
        float $gap = 2.2
    ): void {
        $this->command(
            '['
            .$this->number($dot)
            .' '
            .$this->number($gap)
            .'] 0 d '
            .$this->number($width)
            .' w '
            .$this->number($x1)
            .' '
            .$this->number(
                self::PAGE_HEIGHT - $top1
            )
            .' m '
            .$this->number($x2)
            .' '
            .$this->number(
                self::PAGE_HEIGHT - $top2
            )
            .' l S [] 0 d'
        );
    }

    public function rectangle(
        float $x,
        float $top,
        float $width,
        float $height,
        float $lineWidth = 0.5
    ): void {
        $this->command(
            '[] 0 d '
            .$this->number($lineWidth)
            .' w '
            .$this->number($x)
            .' '
            .$this->number(
                self::PAGE_HEIGHT
                - $top
                - $height
            )
            .' '
            .$this->number($width)
            .' '
            .$this->number($height)
            .' re S'
        );
    }

    public function wrappedText(
        float $x,
        float $top,
        string $text,
        float $maxWidth,
        float $size = 9,
        float $lineHeight = 12,
        bool $bold = false,
        ?int $maxLines = null,
        string $align = 'left'
    ): float {
        $lines =
            $this->wrapLines(
                $text,
                $maxWidth,
                $size,
                $bold
            );

        if (
            $maxLines !== null
            &&
            count($lines) > $maxLines
        ) {
            $lines =
                array_slice(
                    $lines,
                    0,
                    $maxLines
                );

            $lines[$maxLines - 1] =
                $this->truncateToWidth(
                    $lines[$maxLines - 1]
                        .'...',
                    $maxWidth,
                    $size,
                    $bold
                );
        }

        foreach (
            $lines
            as $index => $line
        ) {
            $lineWidth =
                $this->measureText(
                    $line,
                    $size,
                    $bold
                );

            $lineX = match ($align) {
                'center' =>
                    $x
                    + (
                        (
                            $maxWidth
                            - $lineWidth
                        ) / 2
                    ),

                'right' =>
                    $x
                    + $maxWidth
                    - $lineWidth,

                default =>
                    $x,
            };

            $this->text(
                $lineX,
                $top
                    + (
                        $index
                        * $lineHeight
                    ),
                $line,
                $size,
                $bold
            );
        }

        return $top
            + (
                count($lines)
                * $lineHeight
            );
    }

    public function cellText(
        float $x,
        float $top,
        float $width,
        float $height,
        string $text,
        float $size = 7,
        bool $bold = false,
        string $align = 'left',
        float $padding = 3,
        float $lineHeight = 8
    ): void {
        $availableWidth =
            max(
                1,
                $width
                    - (
                        2
                        * $padding
                    )
            );

        $maxLines =
            max(
                1,
                (int) floor(
                    (
                        $height
                        - (
                            2
                            * $padding
                        )
                    )
                    / $lineHeight
                )
            );

        $allLines =
            $this->wrapLines(
                $text,
                $availableWidth,
                $size,
                $bold
            );

        $wasTruncated =
            count($allLines)
            > $maxLines;

        $lines =
            array_slice(
                $allLines,
                0,
                $maxLines
            );

        if ($wasTruncated) {
            $last =
                $maxLines - 1;

            $lines[$last] =
                $this->truncateToWidth(
                    $lines[$last]
                        .'...',
                    $availableWidth,
                    $size,
                    $bold
                );
        }

        $contentHeight =
            count($lines)
            * $lineHeight;

        $lineTop =
            $top
            + max(
                $padding,
                (
                    $height
                    - $contentHeight
                ) / 2
            );

        foreach (
            $lines
            as $index => $line
        ) {
            $lineWidth =
                $this->measureText(
                    $line,
                    $size,
                    $bold
                );

            $lineX = match ($align) {
                'center' =>
                    $x
                    + (
                        (
                            $width
                            - $lineWidth
                        ) / 2
                    ),

                'right' =>
                    $x
                    + $width
                    - $padding
                    - $lineWidth,

                default =>
                    $x
                    + $padding,
            };

            $this->text(
                $lineX,
                $lineTop
                    + (
                        $index
                        * $lineHeight
                    ),
                $line,
                $size,
                $bold
            );
        }
    }

    /**
     * @return array<int, string>
     */
    public function wrapLines(
        string $text,
        float $maxWidth,
        float $size,
        bool $bold = false
    ): array {
        $paragraphs =
            preg_split(
                '/\R/u',
                trim($text)
            )
            ?: [''];

        $lines = [];

        foreach (
            $paragraphs
            as $paragraph
        ) {
            $paragraph =
                trim($paragraph);

            if ($paragraph === '') {
                $lines[] = '';

                continue;
            }

            $words =
                preg_split(
                    '/\s+/u',
                    $paragraph
                )
                ?: [];

            $line = '';

            foreach (
                $words
                as $word
            ) {
                $parts =
                    $this->breakLongWord(
                        $word,
                        $maxWidth,
                        $size,
                        $bold
                    );

                foreach (
                    $parts
                    as $partIndex => $part
                ) {
                    $isBrokenPart =
                        count($parts) > 1;

                    $candidate =
                        $line === ''
                            ? $part
                            : $line
                                .' '
                                .$part;

                    if (
                        $line === ''
                        ||
                        $this->measureText(
                            $candidate,
                            $size,
                            $bold
                        ) <= $maxWidth
                    ) {
                        $line =
                            $candidate;
                    } else {
                        $lines[] =
                            $line;

                        $line =
                            $part;
                    }

                    if (
                        $isBrokenPart
                        &&
                        $partIndex
                            < count($parts) - 1
                    ) {
                        $lines[] =
                            $line;

                        $line = '';
                    }
                }
            }

            if ($line !== '') {
                $lines[] =
                    $line;
            }
        }

        return $lines === []
            ? ['']
            : $lines;
    }

    public function measureText(
        string $text,
        float $size,
        bool $bold = false
    ): float {
        $units = 0;

        foreach (
            mb_str_split($text)
            as $character
        ) {
            $units +=
                $this->characterWidth(
                    $character
                );
        }

        $width =
            (
                $units / 1000
            )
            * $size;

        return $bold
            ? $width * 1.025
            : $width;
    }

    public function output(): string
    {
        if ($this->pages === []) {
            $this->addPage();
        }

        $objects = [];
        $pageReferences = [];

        foreach (
            $this->pages
            as $index => $commands
        ) {
            $pageObject =
                5 + ($index * 2);

            $contentObject =
                $pageObject + 1;

            $pageReferences[] =
                $pageObject.' 0 R';

            $stream =
                implode(
                    "\n",
                    $commands
                );

            $objects[$pageObject] =
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 '
                .$this->number(
                    self::PAGE_WIDTH
                )
                .' '
                .$this->number(
                    self::PAGE_HEIGHT
                )
                .'] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> '
                .'/Contents '
                .$contentObject
                .' 0 R >>';

            $objects[$contentObject] =
                '<< /Length '
                .strlen($stream)
                ." >>\nstream\n"
                .$stream
                ."\nendstream";
        }

        $objects[1] =
            '<< /Type /Catalog /Pages 2 0 R >>';

        $objects[2] =
            '<< /Type /Pages /Count '
            .count($this->pages)
            .' /Kids ['
            .implode(
                ' ',
                $pageReferences
            )
            .'] >>';

        $objects[3] =
            '<< /Type /Font /Subtype /Type1 '
            .'/BaseFont /Helvetica '
            .'/Encoding /WinAnsiEncoding >>';

        $objects[4] =
            '<< /Type /Font /Subtype /Type1 '
            .'/BaseFont /Helvetica-Bold '
            .'/Encoding /WinAnsiEncoding >>';

        ksort($objects);

        $pdf =
            "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";

        $offsets = [0];

        $objectCount =
            count($objects);

        for (
            $object = 1;
            $object <= $objectCount;
            $object++
        ) {
            $offsets[$object] =
                strlen($pdf);

            $pdf .=
                $object
                ." 0 obj\n"
                .$objects[$object]
                ."\nendobj\n";
        }

        $xref =
            strlen($pdf);

        $pdf .=
            "xref\n0 "
            .($objectCount + 1)
            ."\n";

        $pdf .=
            "0000000000 65535 f \n";

        for (
            $object = 1;
            $object <= $objectCount;
            $object++
        ) {
            $pdf .=
                sprintf(
                    "%010d 00000 n \n",
                    $offsets[$object]
                );
        }

        $pdf .=
            'trailer << /Size '
            .($objectCount + 1)
            .' /Root 1 0 R >>'
            ."\nstartxref\n"
            .$xref
            ."\n%%EOF";

        return $pdf;
    }

    /**
     * @return array<int, string>
     */
    private function breakLongWord(
        string $word,
        float $maxWidth,
        float $size,
        bool $bold
    ): array {
        if (
            $this->measureText(
                $word,
                $size,
                $bold
            ) <= $maxWidth
        ) {
            return [$word];
        }

        $parts = [];
        $part = '';

        foreach (
            mb_str_split($word)
            as $character
        ) {
            $candidate =
                $part.$character;

            if (
                $part !== ''
                &&
                $this->measureText(
                    $candidate,
                    $size,
                    $bold
                ) > $maxWidth
            ) {
                $parts[] =
                    $part;

                $part =
                    $character;
            } else {
                $part =
                    $candidate;
            }
        }

        if ($part !== '') {
            $parts[] =
                $part;
        }

        return $parts === []
            ? ['']
            : $parts;
    }

    private function truncateToWidth(
        string $text,
        float $maxWidth,
        float $size,
        bool $bold
    ): string {
        if (
            $this->measureText(
                $text,
                $size,
                $bold
            ) <= $maxWidth
        ) {
            return $text;
        }

        $suffix = '...';
        $result = '';

        foreach (
            mb_str_split($text)
            as $character
        ) {
            if (
                $this->measureText(
                    $result
                        .$character
                        .$suffix,
                    $size,
                    $bold
                ) > $maxWidth
            ) {
                break;
            }

            $result .=
                $character;
        }

        return rtrim($result)
            .$suffix;
    }

    private function characterWidth(
        string $character
    ): int {
        $upper = [
            'A' => 667,
            'B' => 667,
            'C' => 722,
            'D' => 722,
            'E' => 667,
            'F' => 611,
            'G' => 778,
            'H' => 722,
            'I' => 278,
            'J' => 500,
            'K' => 667,
            'L' => 556,
            'M' => 833,
            'N' => 722,
            'O' => 778,
            'P' => 667,
            'Q' => 778,
            'R' => 722,
            'S' => 667,
            'T' => 611,
            'U' => 722,
            'V' => 667,
            'W' => 944,
            'X' => 667,
            'Y' => 667,
            'Z' => 611,
        ];

        $lower = [
            'a' => 556,
            'b' => 556,
            'c' => 500,
            'd' => 556,
            'e' => 556,
            'f' => 278,
            'g' => 556,
            'h' => 556,
            'i' => 222,
            'j' => 222,
            'k' => 500,
            'l' => 222,
            'm' => 833,
            'n' => 556,
            'o' => 556,
            'p' => 556,
            'q' => 556,
            'r' => 333,
            's' => 500,
            't' => 278,
            'u' => 556,
            'v' => 500,
            'w' => 722,
            'x' => 500,
            'y' => 500,
            'z' => 500,
        ];

        $special = [
            ' ' => 278,
            '.' => 278,
            ',' => 278,
            ':' => 278,
            ';' => 278,
            '!' => 278,
            '|' => 260,
            '-' => 333,
            '_' => 556,
            '/' => 278,
            '\\' => 278,
            '(' => 333,
            ')' => 333,
            '[' => 278,
            ']' => 278,
            '&' => 667,
            '%' => 889,
            '*' => 389,
            '+' => 584,
            '=' => 584,
            '?' => 556,
            '"' => 355,
            "'" => 191,
        ];

        if (
            isset(
                $special[$character]
            )
        ) {
            return $special[$character];
        }

        if (
            isset(
                $upper[$character]
            )
        ) {
            return $upper[$character];
        }

        if (
            isset(
                $lower[$character]
            )
        ) {
            return $lower[$character];
        }

        if (
            preg_match(
                '/^[0-9]$/',
                $character
            ) === 1
        ) {
            return 556;
        }

        return 556;
    }

    private function escape(
        string $text
    ): string {
        $encoded =
            iconv(
                'UTF-8',
                'Windows-1252//TRANSLIT//IGNORE',
                $text
            );

        if ($encoded === false) {
            $encoded =
                preg_replace(
                    '/[^\x20-\x7E]/',
                    '?',
                    $text
                )
                ?? '';
        }

        return str_replace(
            [
                '\\',
                '(',
                ')',
                "\r",
                "\n",
            ],
            [
                '\\\\',
                '\\(',
                '\\)',
                ' ',
                ' ',
            ],
            $encoded
        );
    }

    private function number(
        float $number
    ): string {
        return rtrim(
            rtrim(
                number_format(
                    $number,
                    2,
                    '.',
                    ''
                ),
                '0'
            ),
            '.'
        );
    }

    private function command(
        string $command
    ): void {
        if (
            $this->currentPage < 0
        ) {
            $this->addPage();
        }

        $this->pages[
            $this->currentPage
        ][] = $command;
    }
}