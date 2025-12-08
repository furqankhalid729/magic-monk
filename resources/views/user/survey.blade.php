@extends('layouts.app')

@section('content')
<div class="max-w-[600px] mx-auto mt-10 relative bg-white shadow-md rounded-xl p-8 border border-gray-100">
    <!-- Header Card -->
    <div class="mb-4 rounded-lg shadow-sm bg-white">
        <div class="p-6">
            <h1 class="text-3xl font-bold text-gray-900">Sugar Preference Survey</h1>
            <p class="text-gray-500 mt-2">Help us understand your relationship with sugar and sweeteners</p>
        </div>
    </div>

    <!-- Survey Form -->
    <div class="rounded-lg shadow-sm bg-white">
        <div class="p-6">
            <form id="surveyForm" method="POST" action="{{ route('survey.store') }}">
                @csrf

                <!-- Question 1: Sugar Attitude -->
                <div id="question1" class="survey-question mb-10">
                    <h5 class="text-xl font-semibold mb-3 text-gray-800">Your thoughts about Sugar</h5>
                    <p class="text-gray-500 text-sm mb-4">Choose one:</p>
                    
                    <div class="mb-4">
                        <input class="form-radio w-4 h-4 text-blue-600" type="radio" name="sugar_attitude" id="bad" value="bad" required>
                        <label class="ml-3 text-gray-700" for="bad">
                            It's Bad. Avoid it & all sweets
                        </label>
                    </div>

                    <div class="mb-4">
                        <input class="form-radio w-4 h-4 text-blue-600" type="radio" name="sugar_attitude" id="substitutes" value="substitutes">
                        <label class="ml-3 text-gray-700" for="substitutes">
                            I use Sugar Substitutes often
                        </label>
                    </div>

                    <div class="mb-4">
                        <input class="form-radio w-4 h-4 text-blue-600" type="radio" name="sugar_attitude" id="occasional" value="occasional">
                        <label class="ml-3 text-gray-700" for="occasional">
                            I have Sugar occasionally
                        </label>
                    </div>

                    <div class="mb-4">
                        <input class="form-radio w-4 h-4 text-blue-600" type="radio" name="sugar_attitude" id="cantlive" value="cantlive">
                        <label class="ml-3 text-gray-700" for="cantlive">
                            I just can't live without it
                        </label>
                    </div>

                    <button type="button" class="mt-4 px-6 py-2 text-white rounded-lg hover:opacity-90 transition" style="background-color: #964eb0;" onclick="showNextQuestion()">
                        Next <i class="fas fa-arrow-right ml-2"></i>
                    </button>
                </div>

                <!-- Question 2: Sweetener Preferences -->
                <div id="question2" class="survey-question mb-10 hidden">
                    <h5 class="text-xl font-semibold mb-3 text-gray-800">Which of these sweeteners do you think are OK?</h5>
                    <p class="text-gray-500 text-sm mb-4">Choose all that apply:</p>

                    @php
                    $sweeteners = ['Jaggery', 'Dates', 'Stevia', 'Monk Fruit', 'Sucralose/Aspartame', 'Raw Cane Sugar', 'High Fructose Corn Syrup', 'Erythritol', 'Maltitol'];
                    @endphp

                    @foreach($sweeteners as $sweetener)
                    <div class="mb-4">
                        <input class="form-checkbox w-4 h-4 text-blue-600 rounded" type="checkbox" name="sweeteners[]" id="{{ str_replace('/', '_', $sweetener) }}" value="{{ $sweetener }}">
                        <label class="ml-3 text-gray-700" for="{{ str_replace('/', '_', $sweetener) }}">
                            {{ $sweetener }}
                        </label>
                    </div>
                    @endforeach

                    <div class="mt-6 flex gap-3">
                        <button type="button" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition" onclick="showPreviousQuestion()">
                            <i class="fas fa-arrow-left mr-2"></i> Back
                        </button>
                        <button type="button" class="px-6 py-2 text-white rounded-lg hover:opacity-90 transition" style="background-color: #964eb0;" onclick="showNextQuestion()">
                            Next <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>

                <!-- Question 3: Monk Fruit Knowledge -->
                <div id="question3" class="survey-question mb-10 hidden">
                    <h5 class="text-xl font-semibold mb-3 text-gray-800">Your thoughts about Monk Fruit</h5>
                    <p class="text-gray-500 text-sm mb-4">Choose 1:</p>
                    
                    <div class="mb-4">
                        <input class="form-radio w-4 h-4 text-blue-600" type="radio" name="monk_fruit" id="know_use" value="know_use" required>
                        <label class="ml-3 text-gray-700" for="know_use">
                            I know it well & actively use
                        </label>
                    </div>

                    <div class="mb-4">
                        <input class="form-radio w-4 h-4 text-blue-600" type="radio" name="monk_fruit" id="heard_not_tried" value="heard_not_tried">
                        <label class="ml-3 text-gray-700" for="heard_not_tried">
                            Heard of it but haven't tried
                        </label>
                    </div>

                    <div class="mb-4">
                        <input class="form-radio w-4 h-4 text-blue-600" type="radio" name="monk_fruit" id="heard_not_trust" value="heard_not_trust">
                        <label class="ml-3 text-gray-700" for="heard_not_trust">
                            Heard of it but don't Trust it
                        </label>
                    </div>

                    <div class="mb-4">
                        <input class="form-radio w-4 h-4 text-blue-600" type="radio" name="monk_fruit" id="never_heard" value="never_heard">
                        <label class="ml-3 text-gray-700" for="never_heard">
                            Never even heard of it before
                        </label>
                    </div>

                    <div class="mt-6 flex gap-3">
                        <button type="button" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition" onclick="showPreviousQuestion()">
                            <i class="fas fa-arrow-left mr-2"></i> Back
                        </button>
                        <button type="button" class="px-6 py-2 text-white rounded-lg hover:opacity-90 transition" style="background-color: #964eb0;" onclick="showNextQuestion()">
                            Next <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>

                <!-- Question 4: Barriers to Sugar-Free Products -->
                <div id="question4" class="survey-question mb-10 hidden">
                    <h5 class="text-xl font-semibold mb-3 text-gray-800">What stops you from having sugar-free/alternate sweetener products more often?</h5>
                    <p class="text-gray-500 text-sm mb-4">Choose all that apply:</p>

                    <div class="mb-4">
                        <input class="form-checkbox w-4 h-4 text-blue-600 rounded" type="checkbox" name="barriers[]" id="taste" value="Taste not as good as sugar">
                        <label class="ml-3 text-gray-700" for="taste">
                            Taste not as good as sugar
                        </label>
                    </div>

                    <div class="mb-4">
                        <input class="form-checkbox w-4 h-4 text-blue-600 rounded" type="checkbox" name="barriers[]" id="expensive" value="Expensive as compared to sugar">
                        <label class="ml-3 text-gray-700" for="expensive">
                            Expensive as compared to sugar
                        </label>
                    </div>

                    <div class="mb-4">
                        <input class="form-checkbox w-4 h-4 text-blue-600 rounded" type="checkbox" name="barriers[]" id="artificial" value="I think they're all Artificial">
                        <label class="ml-3 text-gray-700" for="artificial">
                            I think they're all Artificial
                        </label>
                    </div>

                    <div class="mb-4">
                        <input class="form-checkbox w-4 h-4 text-blue-600 rounded" type="checkbox" name="barriers[]" id="not_available" value="Not easily available in Shops">
                        <label class="ml-3 text-gray-700" for="not_available">
                            Not easily available in Shops
                        </label>
                    </div>

                    <div class="mb-4">
                        <input class="form-checkbox w-4 h-4 text-blue-600 rounded" type="checkbox" name="barriers[]" id="not_aware" value="Not aware of the benefit/ need">
                        <label class="ml-3 text-gray-700" for="not_aware">
                            Not aware of the benefit/ need
                        </label>
                    </div>

                    <div class="mt-6 flex gap-3">
                        <button type="button" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition" onclick="showPreviousQuestion()">
                            <i class="fas fa-arrow-left mr-2"></i> Back
                        </button>
                        <button type="button" class="px-6 py-2 text-white rounded-lg hover:opacity-90 transition" style="background-color: #964eb0;" onclick="showNextQuestion()">
                            Next <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </div>

                <!-- Question 5: Health Issues -->
                <div id="question5" class="survey-question mb-10 hidden">
                    <h5 class="text-xl font-semibold mb-3 text-gray-800">Any health issues you're facing?</h5>
                    <p class="text-gray-500 text-sm mb-4">Choose all that apply:</p>

                    <div class="mb-4">
                        <input class="form-checkbox w-4 h-4 text-blue-600 rounded" type="checkbox" name="health_issues[]" id="overweight" value="Overweight/ Obesity">
                        <label class="ml-3 text-gray-700" for="overweight">
                            Overweight/ Obesity
                        </label>
                    </div>

                    <div class="mb-4">
                        <input class="form-checkbox w-4 h-4 text-blue-600 rounded" type="checkbox" name="health_issues[]" id="diabetes" value="Diabetes/ Pre-Diabetes">
                        <label class="ml-3 text-gray-700" for="diabetes">
                            Diabetes/ Pre-Diabetes
                        </label>
                    </div>

                    <div class="mb-4">
                        <input class="form-checkbox w-4 h-4 text-blue-600 rounded" type="checkbox" name="health_issues[]" id="bp" value="High B.P/ Hypertension">
                        <label class="ml-3 text-gray-700" for="bp">
                            High B.P/ Hypertension
                        </label>
                    </div>

                    <div class="mb-4">
                        <input class="form-checkbox w-4 h-4 text-blue-600 rounded" type="checkbox" name="health_issues[]" id="anxiety" value="Anxiety/ Depression">
                        <label class="ml-3 text-gray-700" for="anxiety">
                            Anxiety/ Depression
                        </label>
                    </div>

                    <div class="mb-4">
                        <input class="form-checkbox w-4 h-4 text-blue-600 rounded" type="checkbox" name="health_issues[]" id="skin_aging" value="Premature Skin Ageing">
                        <label class="ml-3 text-gray-700" for="skin_aging">
                            Premature Skin Ageing
                        </label>
                    </div>

                    <div class="mb-4">
                        <input class="form-checkbox w-4 h-4 text-blue-600 rounded" type="checkbox" name="health_issues[]" id="fatty_liver" value="Fatty Liver">
                        <label class="ml-3 text-gray-700" for="fatty_liver">
                            Fatty Liver
                        </label>
                    </div>

                    <div class="mb-4">
                        <input class="form-checkbox w-4 h-4 text-blue-600 rounded" type="checkbox" name="health_issues[]" id="none" value="None of the Above">
                        <label class="ml-3 text-gray-700" for="none">
                            None of the Above
                        </label>
                    </div>

                    <div class="mt-6 flex gap-3">
                        <button type="button" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition" onclick="showPreviousQuestion()">
                            <i class="fas fa-arrow-left mr-2"></i> Back
                        </button>
                        <button type="submit" class="px-6 py-2 text-white rounded-lg hover:opacity-90 transition" style="background-color: #964eb0;">
                            Submit <i class="fas fa-check ml-2"></i>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentQuestion = 1;

function showNextQuestion() {
    if (currentQuestion === 1) {
        const attitude = document.querySelector('input[name="sugar_attitude"]:checked');
        if (!attitude) {
            alert('Please select an option');
            return;
        }
        document.getElementById('question1').classList.add('hidden');
        document.getElementById('question2').classList.remove('hidden');
        currentQuestion = 2;
    } else if (currentQuestion === 2) {
        document.getElementById('question2').classList.add('hidden');
        document.getElementById('question3').classList.remove('hidden');
        currentQuestion = 3;
    } else if (currentQuestion === 3) {
        const monkFruit = document.querySelector('input[name="monk_fruit"]:checked');
        if (!monkFruit) {
            alert('Please select an option');
            return;
        }
        document.getElementById('question3').classList.add('hidden');
        document.getElementById('question4').classList.remove('hidden');
        currentQuestion = 4;
    } else if (currentQuestion === 4) {
        document.getElementById('question4').classList.add('hidden');
        document.getElementById('question5').classList.remove('hidden');
        currentQuestion = 5;
    }
    window.scrollTo(0, 0);
}

function showPreviousQuestion() {
    if (currentQuestion === 2) {
        document.getElementById('question2').classList.add('hidden');
        document.getElementById('question1').classList.remove('hidden');
        currentQuestion = 1;
    } else if (currentQuestion === 3) {
        document.getElementById('question3').classList.add('hidden');
        document.getElementById('question2').classList.remove('hidden');
        currentQuestion = 2;
    } else if (currentQuestion === 4) {
        document.getElementById('question4').classList.add('hidden');
        document.getElementById('question3').classList.remove('hidden');
        currentQuestion = 3;
    } else if (currentQuestion === 5) {
        document.getElementById('question5').classList.add('hidden');
        document.getElementById('question4').classList.remove('hidden');
        currentQuestion = 4;
    }
    window.scrollTo(0, 0);
}
</script>
@endsection
