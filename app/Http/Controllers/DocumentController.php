<?php
namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Module;
use App\Models\LearningOutcome;
use App\Models\SemesterDistribution;
use App\Models\RupDetail;
use App\Models\AcademicYearDetail;
use App\Models\SemesterDetail;
use App\Models\YearTotal;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    private $headerStructure = [
        '1. Индекс Модуля',
        '2. Наименование дисциплины',
        '3. ФИО преподавателя',
        [
            'title' => '4. Распределение по семестрам',
            'children' => [
                '4.1 экзамены',
                '4.2 зачеты',
                '4.3 курсовые работы',
                '4.4 контрольные работы'
            ]
        ],
        [
            'title' => '5. По РУП',
            'children' => [
                '5.1 Кредиты',
                '5.2 Всего часов',
                '5.3 Теоретические занятия',
                '5.4 Лабораторно-практические занятия',
                '5.5 Курсовые работы',
                '5.6 Произв-ое обуч-ие Проф-ая практика'
            ]
        ],
        [
            'title' => '6. На текущий учебный год',
            'children' => [
                '6.1 Всего часов',
                '6.2 Из них теоретических',
                '6.3 Из них ЛПР',
                '6.4 Из них курсовые работы',
                '6.5 Производственное обучение'
            ]
        ],
        [
            'title' => '7. 3 семестр',
            'children' => [
                '7.1 кол-во недель',
                '7.2 часов в неделю',
                '7.3 всего часов',
                '7.4 из них теоретических',
                '7.5 из них ЛПР',
                '7.6 из них КР/КП',
                '7.7 проверка КП/КР',
                '7.8 Производственное обучение',
                [
                    'title' => '7.9 Подвоение',
                    'children' => [
                        '7.9.1 ЛПР',
                        '7.9.2 КП/КП',
                        '7.9.3 проверка КР/КП'
                    ]
                ],
                '7.10 консультации',
                '7.11 экзамены',
                '7.12 итого за 1 семестр'
            ]
        ],
        [
            'title' => '8. 4 семестр',
            'children' => [
                '8.1 кол-во недель',
                '8.2 часов в неделю',
                '8.3 всего часов',
                '8.4 из них теоретических',
                '8.5 из них ЛПР',
                '8.6 из них КР/КП',
                '8.7 проверка КП/КР',
                '8.8 Производственное обучение',
                [
                    'title' => '8.9 Подвоение',
                    'children' => [
                        '8.9.1 ЛПР',
                        '8.9.2 КП/КП',
                        '8.9.3 проверка КР/КП'
                    ]
                ],
                '8.10 консультации',
                '8.11 экзамены',
                '8.12 итого за 2 семестр'
            ]
        ],
        '9. Итого за год'
    ];

    public function index(Request $request)
    {
        $file = $request->file('file');
        $csvData = file_get_contents($file->getPathName());
        $rows = array_map(function ($row) {
            return str_getcsv($row, ';');
        }, explode("\n", $csvData));

        $groupInfo = $this->extractGroupInfo($rows);
        $dataStarted = false;
        $filteredRows = [];
        $currentModule = null;
        $flattenedHeaders = $this->flattenHeaders($this->headerStructure);
        $headerCount = count($flattenedHeaders);

        foreach ($rows as $row) {
            $trimmedRow = array_map('trim', $row);
            if (empty($trimmedRow[0])) continue;

            if (!$dataStarted && preg_match('/^(БМ|ПМ)\s*\d+/', $trimmedRow[0])) {
                $dataStarted = true;
            }

            if ($dataStarted) {
                if (preg_match('/^(БМ|ПМ)\s*\d+/', $trimmedRow[0])) {
                    $currentModule = [
                        'index' => $trimmedRow[0],
                        'name' => $trimmedRow[1] ?? 'Без названия'
                    ];
                } elseif (preg_match('/^РО\s*\d+\.\d+/', $trimmedRow[0]) && $currentModule) {
                    $trimmedRow = array_pad($trimmedRow, $headerCount, '-');
                    $trimmedRow = array_slice($trimmedRow, 0, $headerCount);
                    $dataRow = array_combine($flattenedHeaders, $trimmedRow);
                    $dataRow['module_index'] = $currentModule['index'];
                    $dataRow['module_name'] = $currentModule['name'];
                    $filteredRows[] = $dataRow;
                }
            }
        }

        session([
            'csv_data' => $filteredRows,
            'group_info' => $groupInfo
        ]);

        if (empty($filteredRows)) {
            return redirect()->back()->withErrors(['msg' => 'Не удалось найти данные модулей или результатов обучения в файле.']);
        }

        return redirect()->route('edit_data');
    }

    private function extractGroupInfo($rows)
    {
        $groupInfo = [
            'name' => 'Неизвестная группа',
            'specialty_code' => null,
            'specialty_name' => null,
            'students_count' => null
        ];

        foreach ($rows as $row) {
            $trimmedRow = array_map('trim', $row);
            if (preg_match('/по специальности (\d+) ["“](.+)["”]/', $trimmedRow[0], $matches)) {
                $groupInfo['specialty_code'] = $matches[1];
                $groupInfo['specialty_name'] = $matches[2];
            } elseif (preg_match('/группа (\S+), количество обучающихся - (\d+)/', $trimmedRow[0], $matches)) {
                $groupInfo['name'] = $matches[1];
                $groupInfo['students_count'] = (int)$matches[2];
            }
        }

        return $groupInfo;
    }

    public function editData()
    {
        $csvData = session('csv_data', []);
        $flattenedHeaders = $this->flattenHeaders($this->headerStructure);

        if (empty($csvData)) {
            return redirect()->route('upload')->withErrors(['msg' => 'Данные для редактирования отсутствуют. Пожалуйста, загрузите файл заново.']);
        }

        return view('document.edit', [
            'data' => $csvData,
            'headers' => $flattenedHeaders,
        ]);
    }

    public function saveData(Request $request)
    {
        $editedData = $request->input('data', []);
        $enabledRows = $request->input('enable_row', []);
        $groupInfo = session('group_info', [
            'name' => 'Неизвестная группа',
            'specialty_code' => null,
            'specialty_name' => null,
            'students_count' => null
        ]);

        if (empty($editedData)) {
            return redirect()->route('edit_data')->withErrors(['msg' => 'Нет данных для сохранения.']);
        }

        $group = Group::firstOrCreate(
            ['name' => $groupInfo['name']],
            [
                'specialty_code' => $groupInfo['specialty_code'],
                'specialty_name' => $groupInfo['specialty_name'],
                'students_count' => $groupInfo['students_count'],
            ]
        );

        $modulesData = [];
        foreach ($editedData as $index => $row) {
            if (!isset($enabledRows[$index])) continue;

            if (!isset($row['module_index']) || !isset($row['module_name'])) {
                $moduleIndex = 'UNKNOWN_' . $index;
                $moduleName = 'Неизвестный модуль';
            } else {
                $moduleIndex = $row['module_index'];
                $moduleName = $row['module_name'];
            }

            if (!isset($modulesData[$moduleIndex])) {
                $modulesData[$moduleIndex] = [
                    'name' => $moduleName,
                    'learning_outcomes' => []
                ];
            }
            $modulesData[$moduleIndex]['learning_outcomes'][] = $row;
        }

        foreach ($modulesData as $moduleIndex => $moduleData) {
            $module = Module::firstOrCreate(
                ['group_id' => $group->id, 'index' => $moduleIndex],
                ['name' => $moduleData['name']]
            );

            foreach ($moduleData['learning_outcomes'] as $loData) {
                $teacherName = $loData['3. ФИО преподавателя'] === 'вакансия' ? null : $loData['3. ФИО преподавателя'];
                $learningOutcome = LearningOutcome::firstOrCreate(
                    [
                        'module_id' => $module->id,
                        'index' => $loData['1. Индекс Модуля'],
                        'discipline_name' => $loData['2. Наименование дисциплины'],
                    ],
                    ['teacher_name' => $teacherName]
                );

                SemesterDistribution::updateOrCreate(
                    ['learning_outcome_id' => $learningOutcome->id],
                    [
                        'exams' => $this->parseSemesterRange($loData['4.1 экзамены']),
                        'credits' => $this->parseSemesterRange($loData['4.2 зачеты']),
                        'course_works' => $this->parseSemesterRange($loData['4.3 курсовые работы']),
                        'control_works' => $this->parseSemesterRange($loData['4.4 контрольные работы']),
                    ]
                );

                RupDetail::updateOrCreate(
                    ['learning_outcome_id' => $learningOutcome->id],
                    [
                        'credits' => $loData['5.1 Кредиты'] !== '-' ? (float)$loData['5.1 Кредиты'] : null,
                        'total_hours' => $loData['5.2 Всего часов'] !== '-' ? (int)$loData['5.2 Всего часов'] : null,
                        'theoretical_hours' => $loData['5.3 Теоретические занятия'] !== '-' ? (int)$loData['5.3 Теоретические занятия'] : null,
                        'lab_practical_hours' => $loData['5.4 Лабораторно-практические занятия'] !== '-' ? (int)$loData['5.4 Лабораторно-практические занятия'] : null,
                        'course_works' => $loData['5.5 Курсовые работы'] !== '-' ? (int)$loData['5.5 Курсовые работы'] : null,
                        'professional_practice' => $loData['5.6 Произв-ое обуч-ие Проф-ая практика'] !== '-' ? (int)$loData['5.6 Произв-ое обуч-ие Проф-ая практика'] : null,
                    ]
                );

                AcademicYearDetail::updateOrCreate(
                    ['learning_outcome_id' => $learningOutcome->id],
                    [
                        'total_hours' => $loData['6.1 Всего часов'] !== '-' ? (int)$loData['6.1 Всего часов'] : null,
                        'theoretical_hours' => $loData['6.2 Из них теоретических'] !== '-' ? (int)$loData['6.2 Из них теоретических'] : null,
                        'lab_practical_hours' => $loData['6.3 Из них ЛПР'] !== '-' ? (int)$loData['6.3 Из них ЛПР'] : null,
                        'course_works' => $loData['6.4 Из них курсовые работы'] !== '-' ? (int)$loData['6.4 Из них курсовые работы'] : null,
                        'professional_training' => $loData['6.5 Производственное обучение'] !== '-' ? (int)$loData['6.5 Производственное обучение'] : null,
                    ]
                );

                SemesterDetail::updateOrCreate(
                    ['learning_outcome_id' => $learningOutcome->id, 'semester_number' => 3],
                    [
                        'weeks_count' => $loData['7.1 кол-во недель'] !== '-' ? (int)$loData['7.1 кол-во недель'] : null,
                        'hours_per_week' => $loData['7.2 часов в неделю'] !== '-' ? (int)$loData['7.2 часов в неделю'] : null,
                        'total_hours' => $loData['7.3 всего часов'] !== '-' ? (int)$loData['7.3 всего часов'] : null,
                        'theoretical_hours' => $loData['7.4 из них теоретических'] !== '-' ? (int)$loData['7.4 из них теоретических'] : null,
                        'lab_practical_hours' => $loData['7.5 из них ЛПР'] !== '-' ? (int)$loData['7.5 из них ЛПР'] : null,
                        'course_projects' => $loData['7.6 из них КР/КП'] !== '-' ? (int)$loData['7.6 из них КР/КП'] : null,
                        'project_verification' => $loData['7.7 проверка КП/КР'] !== '-' ? (int)$loData['7.7 проверка КП/КР'] : null,
                        'professional_training' => $loData['7.8 Производственное обучение'] !== '-' ? (int)$loData['7.8 Производственное обучение'] : null,
                        'lab_practical_duplication' => $loData['7.9.1 ЛПР'] !== '-' ? (int)$loData['7.9.1 ЛПР'] : null,
                        'project_duplication' => $loData['7.9.2 КП/КП'] !== '-' ? (int)$loData['7.9.2 КП/КП'] : null,
                        'verification_duplication' => $loData['7.9.3 проверка КР/КП'] !== '-' ? (int)$loData['7.9.3 проверка КР/КП'] : null,
                        'consultations' => $loData['7.10 консультации'] !== '-' ? (int)$loData['7.10 консультации'] : null,
                        'exams' => $loData['7.11 экзамены'] !== '-' ? (int)$loData['7.11 экзамены'] : null,
                        'semester_total' => $loData['7.12 итого за 1 семестр'] !== '-' ? (int)$loData['7.12 итого за 1 семестр'] : null,
                    ]
                );

                SemesterDetail::updateOrCreate(
                    ['learning_outcome_id' => $learningOutcome->id, 'semester_number' => 4],
                    [
                        'weeks_count' => $loData['8.1 кол-во недель'] !== '-' ? (int)$loData['8.1 кол-во недель'] : null,
                        'hours_per_week' => $loData['8.2 часов в неделю'] !== '-' ? (int)$loData['8.2 часов в неделю'] : null,
                        'total_hours' => $loData['8.3 всего часов'] !== '-' ? (int)$loData['8.3 всего часов'] : null,
                        'theoretical_hours' => $loData['8.4 из них теоретических'] !== '-' ? (int)$loData['8.4 из них теоретических'] : null,
                        'lab_practical_hours' => $loData['8.5 из них ЛПР'] !== '-' ? (int)$loData['8.5 из них ЛПР'] : null,
                        'course_projects' => $loData['8.6 из них КР/КП'] !== '-' ? (int)$loData['8.6 из них КР/КП'] : null,
                        'project_verification' => $loData['8.7 проверка КП/КР'] !== '-' ? (int)$loData['8.7 проверка КП/КР'] : null,
                        'professional_training' => $loData['8.8 Производственное обучение'] !== '-' ? (int)$loData['8.8 Производственное обучение'] : null,
                        'lab_practical_duplication' => $loData['8.9.1 ЛПР'] !== '-' ? (int)$loData['8.9.1 ЛПР'] : null,
                        'project_duplication' => $loData['8.9.2 КП/КП'] !== '-' ? (int)$loData['8.9.2 КП/КП'] : null,
                        'verification_duplication' => $loData['8.9.3 проверка КР/КП'] !== '-' ? (int)$loData['8.9.3 проверка КР/КП'] : null,
                        'consultations' => $loData['8.10 консультации'] !== '-' ? (int)$loData['8.10 консультации'] : null,
                        'exams' => $loData['8.11 экзамены'] !== '-' ? (int)$loData['8.11 экзамены'] : null,
                        'semester_total' => $loData['8.12 итого за 2 семестр'] !== '-' ? (int)$loData['8.12 итого за 2 семестр'] : null,
                    ]
                );

                YearTotal::updateOrCreate(
                    ['learning_outcome_id' => $learningOutcome->id],
                    [
                        'total_hours' => $loData['9. Итого за год'] !== '-' ? (int)$loData['9. Итого за год'] : null,
                    ]
                );
            }
        }

        session()->forget(['csv_data', 'group_info']);
        return redirect()->route('document')->with('success', 'Данные успешно сохранены.');
    }

    public function showDocument()
    {
        $groups = Group::with([
            'modules.learningOutcomes.semesterDistribution',
            'modules.learningOutcomes.rupDetail',
            'modules.learningOutcomes.academicYearDetail',
            'modules.learningOutcomes.semesterDetails',
            'modules.learningOutcomes.yearTotal'
        ])->get();
        $flattenedHeaders = $this->flattenHeaders($this->headerStructure);

        $headerCounts = [];
        foreach ($this->headerStructure as $header) {
            if (is_array($header)) {
                $headerCounts[$header['title']] = count($this->flattenHeaders($header['children']));
            }
        }

        return view('document.index', [
            'groups' => $groups,
            'headers' => $this->headerStructure,
            'flattenedHeaders' => $flattenedHeaders,
            'headerCounts' => $headerCounts
        ]);
    }

    private function flattenHeaders($structure)
    {
        $flattened = [];
        foreach ($structure as $header) {
            if (is_array($header)) {
                $flattened = array_merge($flattened, $this->flattenHeaders($header['children']));
            } else {
                $flattened[] = $header;
            }
        }
        return $flattened;
    }
    private function parseSemesterRange($value)
    {
        if ($value === '-' || is_null($value)) {
            return null;
        }

        if (preg_match('/^(\d+)-(\d+)$/', $value, $matches)) {
            $start = (int)$matches[1];
            $end = (int)$matches[2];
            return $start <= $end ? range($start, $end) : null;
        }

        if (is_numeric($value)) {
            return (int)$value;
        }

        return null;
    }
}
