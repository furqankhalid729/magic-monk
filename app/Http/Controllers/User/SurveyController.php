<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Survey;
use Illuminate\Http\Request;

class SurveyController extends Controller
{
    public function index()
    {
        return view('user.survey');
    }

    public function submit(Request $request)
    {
        $responses = [
            'question_1' => [
                'question' => 'Your thoughts about Sugar',
                'value' => $request->input('sugar_attitude'),
            ],
            'question_2' => [
                'question' => 'Which of these sweeteners do you think are OK?',
                'values' => $request->input('sweeteners', []),
            ],
            'question_3' => [
                'question' => 'Your thoughts about Monk Fruit',
                'value' => $request->input('monk_fruit'),
            ],
            'question_4' => [
                'question' => 'What stops you from having sugar-free/alternate sweetener products more often?',
                'values' => $request->input('barriers', []),
            ],
            'question_5' => [
                'question' => 'Any health issues you\'re facing?',
                'values' => $request->input('health_issues', []),
            ],
        ];

        Survey::create([
            'user_id' => auth()->id(),
            'responses' => $responses,
        ]);

        return redirect()->route('dashboard')->with('success', 'Survey submitted successfully!');
    }
}
