<?php

namespace App\Http\Controllers;

use App\Http\Requests\ImportSessionsRequest;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\User;
use App\Support\CsvImportReader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class SessionImportController extends Controller
{
    public function create(): View
    {
        return view('pages.sessions.import');
    }

    public function store(ImportSessionsRequest $request, CsvImportReader $reader): View
    {
        $file = $request->file('file');
        assert($file instanceof UploadedFile);
        $imported = 0;
        $failures = [];

        foreach ($reader->rows($file, ['course_name', 'instructor_email', 'start_date', 'end_date']) as $row) {
            $rowNumber = $row['row'];
            if ($row['error'] !== null) {
                $failures[] = ['row' => $rowNumber, 'message' => $row['error']];

                continue;
            }

            $data = $row['data'];
            $validator = Validator::make($data, [
                'course_name' => ['required', 'string', 'max:255'],
                'instructor_email' => ['required', 'email', 'max:255'],
                'start_date' => ['required', 'date_format:Y-m-d'],
                'end_date' => [
                    'required',
                    'date_format:Y-m-d',
                    'after_or_equal:start_date',
                ],
            ]);

            if ($validator->fails()) {
                $failures[] = [
                    'row' => $rowNumber,
                    'message' => implode(' ', $validator->errors()->all()),
                ];

                continue;
            }

            $course = Course::where('name', $data['course_name'])->first();

            $instructor = User::approvedInstructors()
                ->where('email', $data['instructor_email'])
                ->first();

            if (! $course || ! $instructor) {
                $messages = [];

                if (! $course) {
                    $messages[] = 'Course not found: '.$data['course_name'].'.';
                }

                if (! $instructor) {
                    $messages[] = 'Approved instructor not found: '
                        .$data['instructor_email'].'.';
                }

                $failures[] = [
                    'row' => $rowNumber,
                    'message' => implode(' ', $messages),
                ];

                continue;
            }

            DB::transaction(function () use ($course, $instructor, $data): void {
                Course::whereKey($course->id)->lockForUpdate()->firstOrFail();

                CourseSession::create([
                    'course_id' => $course->id,
                    'instructor_id' => $instructor->id,
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'],
                ]);
            });

            $imported++;
        }

        return view('pages.sessions.import', compact('imported', 'failures'));
    }
}
