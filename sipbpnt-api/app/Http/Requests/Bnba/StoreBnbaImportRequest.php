<?php

declare(strict_types=1);

namespace App\Http\Requests\Bnba;

use App\Support\Bnba\BnbaWorkbookGuard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;

class StoreBnbaImportRequest
    extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'period_id' => [
                'required',
                'integer',
                'exists:bpnt_periods,id',
            ],

            'file' => [
                'required',
                'file',
                'max:'
                    .$this
                        ->maxFileKb(),

                'mimes:xlsx,xls',
                'extensions:xlsx,xls',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.max'
                => 'Ukuran file BNBA maksimal '
                    .round(
                        $this->maxFileKb()
                        / 1024,
                        2
                    )
                    .' MB.',

            'file.mimes'
                => 'Isi file BNBA harus berupa workbook Excel .xlsx atau .xls.',

            'file.extensions'
                => 'Ekstensi file BNBA harus .xlsx atau .xls.',
        ];
    }

    public function withValidator(
        Validator $validator
    ): void {
        $validator->after(
            function (
                Validator $validator
            ): void {
                if (
                    $validator
                        ->errors()
                        ->has('file')
                ) {
                    return;
                }

                $file =
                    $this->file(
                        'file'
                    );

                if (
                    ! $file
                    instanceof UploadedFile
                ) {
                    return;
                }

                $realPath =
                    $file->getRealPath();

                if (
                    $realPath === false
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'file',
                            'File BNBA tidak dapat dibaca.'
                        );

                    return;
                }

                try {
                    app(
                        BnbaWorkbookGuard::class
                    )->assertImportable(
                        $realPath
                    );
                } catch (
                    ValidationException
                    $exception
                ) {
                    foreach (
                        $exception
                            ->errors()[
                                'file'
                            ]
                            ?? [
                                'File BNBA tidak valid.',
                            ]
                        as $message
                    ) {
                        $validator
                            ->errors()
                            ->add(
                                'file',
                                $message
                            );
                    }
                }
            }
        );
    }

    private function maxFileKb(): int
    {
        return max(
            1,
            (int)
            config(
                'sipbpnt.bnba_import.max_file_kb',
                10240
            )
        );
    }
}