<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportCoursesRequest;
use App\Models\Course;
use App\Models\QuestionnaireTemplate;
use App\Support\CsvImportReader;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class CourseImportController extends Controller
{
    public function create(): View
    {
        return view('pages.courses.import');
    }

    public function store(ImportCoursesRequest $request, CsvImportReader $reader): View
    {
        $file = $request->file('file');
        assert($file instanceof UploadedFile);
        $imported = 0;
        $failures = [];

        foreach ($reader->rows($file, ['course_name', 'questionnaire_name']) as $row) {
            $rowNumber = $row['row'];
            if ($row['error'] !== null) {
                $failures[] = ['row' => $rowNumber, 'message' => $row['error']];

                continue;
            }

            $data = $row['data'];
            $validator = Validator::make($data, [
                'course_name' => ['required', 'string', 'max:255', 'unique:courses,name'],
                'questionnaire_name' => ['required', 'string', 'max:255'],
            ]);
            if ($validator->fails()) {
                $failures[] = ['row' => $rowNumber, 'message' => implode(' ', $validator->errors()->all())];

                continue;
            }

            $templates = QuestionnaireTemplate::where('name', $data['questionnaire_name'])->get();
            if ($templates->count() !== 1) {
                $failures[] = ['row' => $rowNumber, 'message' => $templates->isEmpty()
                    ? __('Questionnaire not found: :name.', ['name' => $data['questionnaire_name']])
                    : __('Multiple questionnaires match: :name.', ['name' => $data['questionnaire_name']])];

                continue;
            }

            try {
                Course::create([
                    'name' => $data['course_name'],
                    'questionnaire_template_id' => $templates->sole()->id,
                ]);
            } catch (UniqueConstraintViolationException) {
                $failures[] = ['row' => $rowNumber, 'message' => __('The course name has already been taken.')];

                continue;
            }

            $imported++;
        }

        return view('pages.courses.import', compact('imported', 'failures'));
    }
}
