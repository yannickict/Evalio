<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

class QuestionnaireDraft
{
    /** @return array{text: string, type: string, options: list<string>, allows_comment: bool} */
    public static function emptyQuestion(): array
    {
        return ['text' => '', 'type' => 'single_choice', 'options' => ['', ''], 'allows_comment' => false];
    }

    /** @return array{name: string, questions: list<array{text: string, type: string, options: list<string>, allows_comment: bool}>} */
    public static function empty(): array
    {
        return ['name' => '', 'questions' => [self::emptyQuestion()]];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function apply(array $data): array
    {
        $draft = [
            'name' => $data['name'] ?? '',
            'questions' => array_map(
                fn (array $question) => [
                    'text' => $question['text'] ?? '',
                    'type' => $question['type'],
                    'allows_comment' => (bool) ($question['allows_comment'] ?? false),
                    'options' => array_values(
                        $question['options'] ?? ['', '']
                    ),
                ],
                array_values($data['questions'])
            ),
        ];

        if ($data['action'] === 'add_question') {
            if (count($draft['questions']) >= 50) {
                throw ValidationException::withMessages([
                    'questions' => __('You can add up to 50 questions.'),
                ]);
            }

            $draft['questions'][] = self::emptyQuestion();
        } elseif (str_starts_with($data['action'], 'add_option:')) {
            $index = (int) substr($data['action'], strlen('add_option:'));

            if (! isset($draft['questions'][$index])) {
                throw ValidationException::withMessages([
                    'questions' => __('This question does not exist.'),
                ]);
            }

            if ($draft['questions'][$index]['type'] !== 'single_choice') {
                throw ValidationException::withMessages([
                    'questions' => __('Only single-choice questions have answer options.'),
                ]);
            }

            if (count($draft['questions'][$index]['options']) >= 20) {
                throw ValidationException::withMessages([
                    'questions' => __('A question can have up to 20 options.'),
                ]);
            }

            $draft['questions'][$index]['options'][] = '';
        } elseif (str_starts_with($data['action'], 'remove_question:')) {
            $index = (int) substr($data['action'], strlen('remove_question:'));

            if (! isset($draft['questions'][$index]) || count($draft['questions']) <= 1) {
                throw ValidationException::withMessages([
                    'questions' => __('Keep at least one question and choose an existing question to remove.'),
                ]);
            }

            unset($draft['questions'][$index]);
            $draft['questions'] = array_values($draft['questions']);
        } elseif (str_starts_with($data['action'], 'remove_option:')) {
            [, $questionIndex, $optionIndex] = explode(':', $data['action']);
            $questionIndex = (int) $questionIndex;
            $optionIndex = (int) $optionIndex;

            if (! isset($draft['questions'][$questionIndex])
                || $draft['questions'][$questionIndex]['type'] !== 'single_choice'
                || ! array_key_exists($optionIndex, $draft['questions'][$questionIndex]['options'])
                || count($draft['questions'][$questionIndex]['options']) <= 2) {
                throw ValidationException::withMessages([
                    'questions' => __('Choose an existing single-choice option to remove and keep at least two options.'),
                ]);
            }

            unset($draft['questions'][$questionIndex]['options'][$optionIndex]);
            $draft['questions'][$questionIndex]['options'] = array_values($draft['questions'][$questionIndex]['options']);
        }

        return $draft;
    }
}
