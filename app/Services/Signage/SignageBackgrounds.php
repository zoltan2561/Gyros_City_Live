<?php

namespace App\Services\Signage;

class SignageBackgrounds
{
    public function options(): array
    {
        return [
            'graphite'=>['label'=>'Grafit','theme'=>'dark','background'=>'#11161d','accent'=>'#ffcb66','text_color'=>'#fff7e9'],
            'embers'=>['label'=>'Parázs','theme'=>'dark','background'=>'#160d0a','accent'=>'#ffc747','text_color'=>'#fff3d2'],
            'wood'=>['label'=>'Faasztal','theme'=>'dark','background'=>'#2b170f','accent'=>'#ffd08a','text_color'=>'#fff4df'],
            'wine'=>['label'=>'Bordó','theme'=>'dark','background'=>'#2d091a','accent'=>'#ffc2a1','text_color'=>'#fff1e8'],
            'cream'=>['label'=>'Világos','theme'=>'light','background'=>'#f4eddf','accent'=>'#8f174d','text_color'=>'#281c22'],
            'solid'=>['label'=>'Egyedi egyszínű','theme'=>'dark','background'=>'#152130','accent'=>'#ffcb66','text_color'=>'#fff7e9'],
        ];
    }
}
