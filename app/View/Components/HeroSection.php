<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class HeroSection extends Component
{
    public $title;
    public $subtitle;
    public $buttonText;
    public $buttonLink;
    public $rightText;
    public $backgroundImage;

    public function __construct($title, $subtitle, $buttonText, $buttonLink, $rightText, $backgroundImage)
    {
        $this->title = $title;
        $this->subtitle = $subtitle;
        $this->buttonText = $buttonText;
        $this->buttonLink = $buttonLink;
        $this->rightText = $rightText;
        $this->backgroundImage = $backgroundImage;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.hero-section');
    }
}
